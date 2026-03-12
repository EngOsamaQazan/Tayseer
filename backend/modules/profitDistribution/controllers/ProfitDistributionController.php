<?php

namespace backend\modules\profitDistribution\controllers;

use Yii;
use backend\modules\profitDistribution\models\ProfitDistributionModel;
use backend\modules\profitDistribution\models\ProfitDistributionLine;
use backend\modules\profitDistribution\models\ProfitDistributionSearch;
use backend\modules\companies\models\Companies;
use backend\modules\shareholders\models\Shareholders;
use common\helper\Permissions;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\web\ForbiddenHttpException;
use yii\filters\VerbFilter;
use yii\filters\AccessControl;

class ProfitDistributionController extends Controller
{
    public function behaviors()
    {
        return [
            'access' => [
                'class' => AccessControl::class,
                'rules' => [
                    [
                        'actions' => ['index', 'view', 'create-portfolio', 'create-shareholders', 'approve', 'mark-paid'],
                        'allow' => true,
                        'roles' => ['@'],
                        'matchCallback' => function ($rule, $action) {
                            return Permissions::can(Permissions::COMPAINES) || Permissions::can(Permissions::COMP_VIEW);
                        },
                    ],
                ],
            ],
            'verbs' => [
                'class' => VerbFilter::class,
                'actions' => [
                    'approve' => ['post'],
                    'mark-paid' => ['post'],
                ],
            ],
        ];
    }

    public function actionIndex()
    {
        $searchModel = new ProfitDistributionSearch();
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams);

        return $this->render('index', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
        ]);
    }

    public function actionCreatePortfolio()
    {
        $model = new ProfitDistributionModel();
        $model->distribution_type = ProfitDistributionModel::TYPE_PORTFOLIO;
        $calcResult = null;

        if ($model->load(Yii::$app->request->post())) {
            $calcResult = $model->calculatePortfolioProfit(
                $model->company_id,
                $model->period_from,
                $model->period_to
            );

            $model->total_revenue = $calcResult['total_revenue'];
            $model->direct_expenses = $calcResult['direct_expenses'];
            $model->shared_expenses = $calcResult['shared_expenses'];
            $model->net_profit = $calcResult['net_profit'];
            $model->investor_share_pct = $calcResult['investor_pct'];
            $model->investor_amount = $calcResult['investor_amount'];
            $model->parent_amount = $calcResult['parent_amount'];
            $model->distribution_amount = $calcResult['parent_amount'];
            $model->status = ProfitDistributionModel::STATUS_DRAFT;
            $model->created_by = Yii::$app->user->id;

            if (Yii::$app->request->post('save_draft')) {
                if ($model->save()) {
                    Yii::$app->session->setFlash('success', 'تم حفظ احتساب أرباح المحفظة بنجاح.');
                    return $this->redirect(['view', 'id' => $model->id]);
                }
            }
        }

        $companies = Companies::find()
            ->where(['is_primary_company' => 0])
            ->orWhere(['is_primary_company' => null])
            ->all();

        return $this->render('create-portfolio', [
            'model' => $model,
            'companies' => $companies,
            'calcResult' => $calcResult,
        ]);
    }

    public function actionCreateShareholders()
    {
        $model = new ProfitDistributionModel();
        $model->distribution_type = ProfitDistributionModel::TYPE_SHAREHOLDERS;
        $shareholders = Shareholders::find()->where(['is_active' => 1])->all();

        $primaryCompany = Companies::find()->where(['is_primary_company' => 1])->one();
        $totalShares = $primaryCompany ? (int) $primaryCompany->total_shares : 0;

        if ($model->load(Yii::$app->request->post()) && Yii::$app->request->post('save_distribution')) {
            $model->status = ProfitDistributionModel::STATUS_DRAFT;
            $model->created_by = Yii::$app->user->id;
            $model->net_profit = $model->distribution_amount;

            $transaction = Yii::$app->db->beginTransaction();
            try {
                if (!$model->save()) {
                    throw new \Exception('فشل حفظ التوزيع: ' . implode(', ', $model->getFirstErrors()));
                }

                foreach ($shareholders as $sh) {
                    $line = new ProfitDistributionLine();
                    $line->distribution_id = $model->id;
                    $line->shareholder_id = $sh->id;
                    $line->share_count_snapshot = (int) $sh->share_count;
                    $line->total_shares_snapshot = $totalShares;
                    $line->percentage = $totalShares > 0
                        ? round(($sh->share_count / $totalShares) * 100, 4)
                        : 0;
                    $line->amount = $totalShares > 0
                        ? round(($sh->share_count / $totalShares) * (float) $model->distribution_amount, 2)
                        : 0;
                    $line->payment_status = ProfitDistributionLine::PAYMENT_PENDING;

                    if (!$line->save()) {
                        throw new \Exception('فشل حفظ سطر التوزيع: ' . implode(', ', $line->getFirstErrors()));
                    }
                }

                $transaction->commit();
                Yii::$app->session->setFlash('success', 'تم حفظ توزيع الأرباح على المساهمين بنجاح.');
                return $this->redirect(['view', 'id' => $model->id]);
            } catch (\Exception $e) {
                $transaction->rollBack();
                Yii::$app->session->setFlash('error', $e->getMessage());
            }
        }

        return $this->render('create-shareholders', [
            'model' => $model,
            'shareholders' => $shareholders,
            'totalShares' => $totalShares,
        ]);
    }

    public function actionView($id)
    {
        $model = $this->findModel($id);

        return $this->render('view', [
            'model' => $model,
        ]);
    }

    public function actionApprove($id)
    {
        $model = $this->findModel($id);

        if ($model->status !== ProfitDistributionModel::STATUS_DRAFT) {
            Yii::$app->session->setFlash('warning', 'لا يمكن اعتماد هذا التوزيع.');
            return $this->redirect(['view', 'id' => $id]);
        }

        $model->status = ProfitDistributionModel::STATUS_APPROVED;
        $model->approved_by = Yii::$app->user->id;
        $model->approved_at = time();

        if ($model->save(false)) {
            Yii::$app->session->setFlash('success', 'تم اعتماد التوزيع بنجاح.');
        } else {
            Yii::$app->session->setFlash('error', 'فشل اعتماد التوزيع.');
        }

        return $this->redirect(['view', 'id' => $id]);
    }

    public function actionMarkPaid($lineId)
    {
        $line = ProfitDistributionLine::findOne($lineId);
        if (!$line) {
            throw new NotFoundHttpException('السطر المطلوب غير موجود.');
        }

        $line->payment_status = ProfitDistributionLine::PAYMENT_PAID;
        $line->payment_date = date('Y-m-d');
        $line->payment_method = Yii::$app->request->post('payment_method', '');
        $line->payment_reference = Yii::$app->request->post('payment_reference', '');

        if ($line->save(false)) {
            $distribution = $line->distribution;
            $allPaid = ProfitDistributionLine::find()
                ->where(['distribution_id' => $distribution->id])
                ->andWhere(['!=', 'payment_status', ProfitDistributionLine::PAYMENT_PAID])
                ->count() == 0;

            if ($allPaid) {
                $distribution->status = ProfitDistributionModel::STATUS_DISTRIBUTED;
                $distribution->save(false);
            }

            Yii::$app->session->setFlash('success', 'تم تسجيل الدفع بنجاح.');
        } else {
            Yii::$app->session->setFlash('error', 'فشل تسجيل الدفع.');
        }

        return $this->redirect(['view', 'id' => $line->distribution_id]);
    }

    protected function findModel($id)
    {
        if (($model = ProfitDistributionModel::findOne($id)) !== null) {
            return $model;
        }

        throw new NotFoundHttpException('الصفحة المطلوبة غير موجودة.');
    }
}
