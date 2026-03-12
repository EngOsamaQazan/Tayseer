<?php

namespace backend\modules\hr\controllers;

use Yii;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\web\Response;
use yii\filters\AccessControl;
use yii\filters\VerbFilter;
use yii\data\ActiveDataProvider;
use yii\db\Query;
use yii\helpers\ArrayHelper;
use backend\modules\hr\models\HrEvaluation;
use backend\modules\hr\models\HrEvaluationScore;
use backend\modules\hr\models\HrKpiTemplate;
use backend\modules\hr\models\HrKpiItem;
use common\models\User;
use common\helper\Permissions;

/**
 * HrEvaluationController — Performance management
 * يتطلب أحد صلاحيات الموارد البشرية.
 */
class HrEvaluationController extends Controller
{
    /**
     * @inheritdoc
     */
    public function behaviors()
    {
        return [
            'access' => [
                'class' => AccessControl::class,
                'rules' => [
                    [
                        'actions' => ['login', 'error'],
                        'allow' => true,
                    ],
                    [
                        'allow' => true,
                        'roles' => ['@'],
                        'matchCallback' => function () {
                            return Permissions::hasAnyPermission(Permissions::getHrPermissions());
                        },
                    ],
                ],
            ],
            'verbs' => [
                'class' => VerbFilter::class,
                'actions' => [
                    'delete' => ['POST'],
                ],
            ],
        ];
    }

    /**
     * List evaluations.
     *
     * @return string
     */
    public function actionIndex()
    {
        $request = Yii::$app->request;
        $filterStatus = $request->get('status', '');
        $filterEmployee = $request->get('user_id', '');

        $query = HrEvaluation::find()
            ->where(['is_deleted' => 0]);

        if (!empty($filterStatus)) {
            $query->andWhere(['status' => $filterStatus]);
        }
        if (!empty($filterEmployee)) {
            $query->andWhere(['user_id' => $filterEmployee]);
        }

        $query->orderBy(['created_at' => SORT_DESC]);

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
            'pagination' => ['pageSize' => 20],
        ]);

        $employees = ArrayHelper::map(
            User::find()->where(['IS', 'blocked_at', null])->orderBy(['username' => SORT_ASC])->asArray()->all(),
            'id',
            'username'
        );

        return $this->render('index', [
            'dataProvider' => $dataProvider,
            'filterStatus' => $filterStatus,
            'filterEmployee' => $filterEmployee,
            'employees' => $employees,
        ]);
    }

    /**
     * Create new evaluation.
     *
     * @return string|Response
     */
    public function actionCreate()
    {
        $model = new HrEvaluation();
        $request = Yii::$app->request;

        if ($model->load($request->post())) {
            $model->evaluator_id = Yii::$app->user->id;
            $model->status = 'draft';
            $model->created_at = time();
            $model->updated_at = time();
            $model->created_by = Yii::$app->user->id;
            $model->updated_by = Yii::$app->user->id;

            $transaction = Yii::$app->db->beginTransaction();
            try {
                if (!$model->save()) {
                    throw new \Exception('فشل إنشاء التقييم: ' . implode(', ', $model->getFirstErrors()));
                }

                // Create blank score entries from KPI template items
                $kpiItems = HrKpiItem::find()
                    ->where(['template_id' => $model->template_id])
                    ->orderBy(['sort_order' => SORT_ASC])
                    ->all();

                foreach ($kpiItems as $item) {
                    $score = new HrEvaluationScore();
                    $score->evaluation_id = $model->id;
                    $score->kpi_item_id = $item->id;
                    $score->score = null;
                    $score->actual_value = null;
                    $score->comment = null;
                    if (!$score->save()) {
                        throw new \Exception('فشل إنشاء عنصر التقييم: ' . implode(', ', $score->getFirstErrors()));
                    }
                }

                $transaction->commit();
                Yii::$app->session->setFlash('success', 'تم إنشاء التقييم بنجاح.');
                return $this->redirect(['update', 'id' => $model->id]);
            } catch (\Exception $e) {
                $transaction->rollBack();
                Yii::$app->session->setFlash('error', $e->getMessage());
            }
        }

        $employees = ArrayHelper::map(
            User::find()->where(['IS', 'blocked_at', null])->orderBy(['username' => SORT_ASC])->asArray()->all(),
            'id',
            'username'
        );

        $templates = ArrayHelper::map(
            HrKpiTemplate::find()->where(['status' => 'active', 'is_deleted' => 0])->asArray()->all(),
            'id',
            'name'
        );

        if ($request->isAjax) {
            Yii::$app->response->format = Response::FORMAT_JSON;
            return [
                'title' => 'إنشاء تقييم أداء جديد',
                'content' => $this->renderAjax('create', [
                    'model' => $model,
                    'employees' => $employees,
                    'templates' => $templates,
                ]),
            ];
        }

        return $this->render('create', [
            'model' => $model,
            'employees' => $employees,
            'templates' => $templates,
        ]);
    }

    /**
     * Edit evaluation scores.
     *
     * @param int $id
     * @return string|Response
     */
    public function actionUpdate($id)
    {
        $model = $this->findModel($id);
        $request = Yii::$app->request;

        // Get evaluation scores with KPI items
        $scores = HrEvaluationScore::find()
            ->alias('es')
            ->innerJoin('{{%hr_kpi_item}} ki', 'ki.id = es.kpi_item_id')
            ->where(['es.evaluation_id' => $id])
            ->orderBy(['ki.sort_order' => SORT_ASC])
            ->all();

        if ($request->isPost) {
            $model->load($request->post());
            $model->updated_at = time();
            $model->updated_by = Yii::$app->user->id;

            $transaction = Yii::$app->db->beginTransaction();
            try {
                // Update individual scores from POST data
                $scoresData = $request->post('EvaluationScore', []);
                $totalWeightedScore = 0;
                $totalWeight = 0;

                foreach ($scores as $score) {
                    if (isset($scoresData[$score->id])) {
                        $score->score = $scoresData[$score->id]['score'] ?? null;
                        $score->actual_value = $scoresData[$score->id]['actual_value'] ?? null;
                        $score->comment = $scoresData[$score->id]['comment'] ?? null;

                        if (!$score->save()) {
                            throw new \Exception('فشل حفظ درجة التقييم: ' . implode(', ', $score->getFirstErrors()));
                        }

                        // Calculate weighted score
                        $kpiItem = HrKpiItem::findOne($score->kpi_item_id);
                        if ($kpiItem && $score->score !== null) {
                            $totalWeightedScore += ($score->score * $kpiItem->weight);
                            $totalWeight += $kpiItem->weight;
                        }
                    }
                }

                // Calculate total score
                if ($totalWeight > 0) {
                    $model->total_score = round($totalWeightedScore / $totalWeight, 2);

                    // Auto-grade based on score
                    if ($model->total_score >= 90) {
                        $model->grade = 'A';
                    } elseif ($model->total_score >= 80) {
                        $model->grade = 'B';
                    } elseif ($model->total_score >= 70) {
                        $model->grade = 'C';
                    } elseif ($model->total_score >= 60) {
                        $model->grade = 'D';
                    } else {
                        $model->grade = 'F';
                    }
                }

                if (!$model->save()) {
                    throw new \Exception('فشل تحديث التقييم: ' . implode(', ', $model->getFirstErrors()));
                }

                $transaction->commit();
                Yii::$app->session->setFlash('success', 'تم تحديث التقييم بنجاح.');
                return $this->redirect(['view', 'id' => $model->id]);
            } catch (\Exception $e) {
                $transaction->rollBack();
                Yii::$app->session->setFlash('error', $e->getMessage());
            }
        }

        // Get KPI items for reference
        $kpiItems = ArrayHelper::index(
            HrKpiItem::find()->where(['template_id' => $model->template_id])->asArray()->all(),
            'id'
        );

        $employee = User::findOne($model->user_id);

        if ($request->isAjax) {
            Yii::$app->response->format = Response::FORMAT_JSON;
            return [
                'title' => 'تعديل تقييم الأداء — ' . ($employee->name ?: $employee->username),
                'content' => $this->renderAjax('update', [
                    'model' => $model,
                    'scores' => $scores,
                    'kpiItems' => $kpiItems,
                    'employee' => $employee,
                ]),
            ];
        }

        return $this->render('update', [
            'model' => $model,
            'scores' => $scores,
            'kpiItems' => $kpiItems,
            'employee' => $employee,
        ]);
    }

    /**
     * View evaluation detail.
     *
     * @param int $id
     * @return string
     */
    public function actionView($id)
    {
        $model = $this->findModel($id);

        $scores = HrEvaluationScore::find()
            ->alias('es')
            ->innerJoin('{{%hr_kpi_item}} ki', 'ki.id = es.kpi_item_id')
            ->where(['es.evaluation_id' => $id])
            ->orderBy(['ki.sort_order' => SORT_ASC])
            ->all();

        $kpiItems = ArrayHelper::index(
            HrKpiItem::find()->where(['template_id' => $model->template_id])->asArray()->all(),
            'id'
        );

        $employee = User::findOne($model->user_id);
        $evaluator = User::findOne($model->evaluator_id);
        $template = HrKpiTemplate::findOne($model->template_id);

        $request = Yii::$app->request;
        if ($request->isAjax) {
            Yii::$app->response->format = Response::FORMAT_JSON;
            return [
                'title' => 'تقييم أداء — ' . ($employee->name ?: $employee->username),
                'content' => $this->renderAjax('view', [
                    'model' => $model,
                    'scores' => $scores,
                    'kpiItems' => $kpiItems,
                    'employee' => $employee,
                    'evaluator' => $evaluator,
                    'template' => $template,
                ]),
            ];
        }

        return $this->render('view', [
            'model' => $model,
            'scores' => $scores,
            'kpiItems' => $kpiItems,
            'employee' => $employee,
            'evaluator' => $evaluator,
            'template' => $template,
        ]);
    }

    /**
     * Manage KPI templates.
     *
     * @return string
     */
    public function actionTemplates()
    {
        $dataProvider = new ActiveDataProvider([
            'query' => HrKpiTemplate::find()
                ->where(['is_deleted' => 0])
                ->orderBy(['created_at' => SORT_DESC]),
            'pagination' => ['pageSize' => 20],
        ]);

        return $this->render('templates', [
            'dataProvider' => $dataProvider,
        ]);
    }

    /**
     * Create KPI template.
     *
     * @return string|Response
     */
    public function actionTemplateCreate()
    {
        $model = new HrKpiTemplate();
        $request = Yii::$app->request;

        if ($model->load($request->post())) {
            $model->created_at = time();
            $model->updated_at = time();
            $model->created_by = Yii::$app->user->id;
            $model->updated_by = Yii::$app->user->id;

            $transaction = Yii::$app->db->beginTransaction();
            try {
                if (!$model->save()) {
                    throw new \Exception('فشل إنشاء قالب مؤشرات الأداء: ' . implode(', ', $model->getFirstErrors()));
                }

                // Create KPI items from POST data
                $itemsData = $request->post('KpiItems', []);
                $sortOrder = 0;
                foreach ($itemsData as $itemData) {
                    if (empty($itemData['name'])) {
                        continue;
                    }
                    $item = new HrKpiItem();
                    $item->template_id = $model->id;
                    $item->name = $itemData['name'];
                    $item->description = $itemData['description'] ?? '';
                    $item->weight = $itemData['weight'] ?? 10;
                    $item->target_value = $itemData['target_value'] ?? '';
                    $item->unit = $itemData['unit'] ?? '';
                    $item->sort_order = $sortOrder++;
                    if (!$item->save()) {
                        throw new \Exception('فشل إنشاء عنصر مؤشر أداء: ' . implode(', ', $item->getFirstErrors()));
                    }
                }

                $transaction->commit();
                Yii::$app->session->setFlash('success', 'تم إنشاء قالب مؤشرات الأداء بنجاح.');
                return $this->redirect(['templates']);
            } catch (\Exception $e) {
                $transaction->rollBack();
                Yii::$app->session->setFlash('error', $e->getMessage());
            }
        }

        // Department list
        $departments = ArrayHelper::map(
            (new Query())->select(['id', 'name'])->from('{{%department}}')->all(),
            'id',
            'name'
        );

        if ($request->isAjax) {
            Yii::$app->response->format = Response::FORMAT_JSON;
            return [
                'title' => 'إنشاء قالب مؤشرات أداء',
                'content' => $this->renderAjax('template-form', [
                    'model' => $model,
                    'items' => [],
                    'departments' => $departments,
                ]),
            ];
        }

        return $this->render('template-form', [
            'model' => $model,
            'items' => [],
            'departments' => $departments,
        ]);
    }

    /**
     * Update KPI template.
     *
     * @param int $id
     * @return string|Response
     */
    public function actionTemplateUpdate($id)
    {
        $model = HrKpiTemplate::find()
            ->where(['id' => $id, 'is_deleted' => 0])
            ->one();

        if ($model === null) {
            throw new NotFoundHttpException('قالب مؤشرات الأداء غير موجود.');
        }

        $request = Yii::$app->request;

        if ($model->load($request->post())) {
            $model->updated_at = time();
            $model->updated_by = Yii::$app->user->id;

            $transaction = Yii::$app->db->beginTransaction();
            try {
                if (!$model->save()) {
                    throw new \Exception('فشل تحديث قالب مؤشرات الأداء: ' . implode(', ', $model->getFirstErrors()));
                }

                // Update KPI items
                $itemsData = $request->post('KpiItems', []);

                // Delete removed items
                $keepIds = [];
                foreach ($itemsData as $itemData) {
                    if (!empty($itemData['id'])) {
                        $keepIds[] = (int) $itemData['id'];
                    }
                }
                if (!empty($keepIds)) {
                    HrKpiItem::deleteAll([
                        'and',
                        ['template_id' => $model->id],
                        ['not in', 'id', $keepIds],
                    ]);
                } else {
                    HrKpiItem::deleteAll(['template_id' => $model->id]);
                }

                // Update existing / create new items
                $sortOrder = 0;
                foreach ($itemsData as $itemData) {
                    if (empty($itemData['name'])) {
                        continue;
                    }

                    if (!empty($itemData['id'])) {
                        $item = HrKpiItem::findOne($itemData['id']);
                        if (!$item) {
                            $item = new HrKpiItem();
                            $item->template_id = $model->id;
                        }
                    } else {
                        $item = new HrKpiItem();
                        $item->template_id = $model->id;
                    }

                    $item->name = $itemData['name'];
                    $item->description = $itemData['description'] ?? '';
                    $item->weight = $itemData['weight'] ?? 10;
                    $item->target_value = $itemData['target_value'] ?? '';
                    $item->unit = $itemData['unit'] ?? '';
                    $item->sort_order = $sortOrder++;

                    if (!$item->save()) {
                        throw new \Exception('فشل حفظ عنصر مؤشر أداء: ' . implode(', ', $item->getFirstErrors()));
                    }
                }

                $transaction->commit();
                Yii::$app->session->setFlash('success', 'تم تحديث قالب مؤشرات الأداء بنجاح.');
                return $this->redirect(['templates']);
            } catch (\Exception $e) {
                $transaction->rollBack();
                Yii::$app->session->setFlash('error', $e->getMessage());
            }
        }

        // Existing KPI items
        $items = HrKpiItem::find()
            ->where(['template_id' => $model->id])
            ->orderBy(['sort_order' => SORT_ASC])
            ->all();

        $departments = ArrayHelper::map(
            (new Query())->select(['id', 'name'])->from('{{%department}}')->all(),
            'id',
            'name'
        );

        if ($request->isAjax) {
            Yii::$app->response->format = Response::FORMAT_JSON;
            return [
                'title' => 'تعديل قالب مؤشرات الأداء',
                'content' => $this->renderAjax('template-form', [
                    'model' => $model,
                    'items' => $items,
                    'departments' => $departments,
                ]),
            ];
        }

        return $this->render('template-form', [
            'model' => $model,
            'items' => $items,
            'departments' => $departments,
        ]);
    }

    /**
     * Finds the HrEvaluation model.
     *
     * @param int $id
     * @return HrEvaluation
     * @throws NotFoundHttpException
     */
    protected function findModel($id)
    {
        $model = HrEvaluation::find()
            ->where(['id' => $id, 'is_deleted' => 0])
            ->one();

        if ($model !== null) {
            return $model;
        }

        throw new NotFoundHttpException('التقييم المطلوب غير موجود.');
    }
}
