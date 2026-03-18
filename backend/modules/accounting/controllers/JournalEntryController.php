<?php

namespace backend\modules\accounting\controllers;

use Yii;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\web\Response;
use yii\filters\AccessControl;
use yii\filters\VerbFilter;
use yii\base\Model;
use backend\modules\accounting\models\JournalEntry;
use backend\modules\accounting\models\JournalEntryLine;
use backend\modules\accounting\models\JournalEntrySearch;
use backend\modules\accounting\models\FiscalPeriod;
use common\helper\Permissions;

class JournalEntryController extends Controller
{
    public function behaviors()
    {
        return [
            'access' => [
                'class' => AccessControl::class,
                'rules' => [
                    [
                        'actions' => ['index', 'view'],
                        'allow' => true,
                        'roles' => [Permissions::ACC_VIEW],
                    ],
                    [
                        'actions' => ['create'],
                        'allow' => true,
                        'roles' => [Permissions::ACC_CREATE],
                    ],
                    [
                        'actions' => ['update'],
                        'allow' => true,
                        'roles' => [Permissions::ACC_EDIT],
                    ],
                    [
                        'actions' => ['delete'],
                        'allow' => true,
                        'roles' => [Permissions::ACC_DELETE],
                    ],
                    [
                        'actions' => ['post'],
                        'allow' => true,
                        'roles' => [Permissions::ACC_POST],
                    ],
                    [
                        'actions' => ['reverse'],
                        'allow' => true,
                        'roles' => [Permissions::ACC_REVERSE],
                    ],
                ],
            ],
            'verbs' => [
                'class' => VerbFilter::class,
                'actions' => [
                    'delete' => ['post'],
                    'post' => ['post'],
                    'reverse' => ['post'],
                ],
            ],
        ];
    }

    public function actionIndex()
    {
        $searchModel = new JournalEntrySearch();
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams);

        return $this->render('index', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
        ]);
    }

    public function actionView($id)
    {
        $model = $this->findModel($id);
        return $this->render('view', ['model' => $model]);
    }

    public function actionCreate()
    {
        $model = new JournalEntry();
        $lines = [new JournalEntryLine()];

        if (Yii::$app->request->isPost) {
            $model->load(Yii::$app->request->post());
            $model->created_by = Yii::$app->user->id;
            $model->reference_type = JournalEntry::REF_MANUAL;

            // Auto-detect fiscal period
            if ($model->entry_date) {
                $period = FiscalPeriod::findByDate($model->entry_date, $model->company_id);
                if ($period) {
                    $model->fiscal_period_id = $period->id;
                    $model->fiscal_year_id = $period->fiscal_year_id;
                }
            }

            $linesData = Yii::$app->request->post('JournalEntryLine', []);
            $lines = [];
            foreach ($linesData as $lineData) {
                $line = new JournalEntryLine();
                $line->setAttributes($lineData);
                $lines[] = $line;
            }

            $transaction = Yii::$app->db->beginTransaction();
            try {
                if ($model->save()) {
                    $totalDebit = 0;
                    $totalCredit = 0;
                    $allLinesValid = true;

                    foreach ($lines as $line) {
                        $line->journal_entry_id = $model->id;
                        if (!$line->save()) {
                            $allLinesValid = false;
                            break;
                        }
                        $totalDebit += (float)$line->debit;
                        $totalCredit += (float)$line->credit;
                    }

                    if ($allLinesValid && abs($totalDebit - $totalCredit) < 0.005) {
                        $model->total_debit = $totalDebit;
                        $model->total_credit = $totalCredit;
                        $model->save(false, ['total_debit', 'total_credit']);
                        $transaction->commit();
                        Yii::$app->session->setFlash('success', 'تم إنشاء القيد رقم ' . $model->entry_number . ' بنجاح');
                        return $this->redirect(['view', 'id' => $model->id]);
                    } else {
                        $transaction->rollBack();
                        if (!$allLinesValid) {
                            Yii::$app->session->setFlash('error', 'خطأ في بنود القيد');
                        } else {
                            Yii::$app->session->setFlash('error', 'القيد غير متوازن: المدين (' . number_format($totalDebit, 2) . ') لا يساوي الدائن (' . number_format($totalCredit, 2) . ')');
                        }
                    }
                }
            } catch (\Exception $e) {
                $transaction->rollBack();
                Yii::$app->session->setFlash('error', 'حدث خطأ: ' . $e->getMessage());
            }
        }

        return $this->render('create', [
            'model' => $model,
            'lines' => $lines,
        ]);
    }

    public function actionUpdate($id)
    {
        $model = $this->findModel($id);

        if ($model->status !== JournalEntry::STATUS_DRAFT) {
            Yii::$app->session->setFlash('error', 'لا يمكن تعديل قيد مرحّل أو معكوس');
            return $this->redirect(['view', 'id' => $id]);
        }

        $lines = $model->lines;
        if (empty($lines)) {
            $lines = [new JournalEntryLine()];
        }

        if (Yii::$app->request->isPost) {
            $model->load(Yii::$app->request->post());

            if ($model->entry_date) {
                $period = FiscalPeriod::findByDate($model->entry_date, $model->company_id);
                if ($period) {
                    $model->fiscal_period_id = $period->id;
                    $model->fiscal_year_id = $period->fiscal_year_id;
                }
            }

            $linesData = Yii::$app->request->post('JournalEntryLine', []);

            $transaction = Yii::$app->db->beginTransaction();
            try {
                JournalEntryLine::deleteAll(['journal_entry_id' => $model->id]);

                $lines = [];
                $totalDebit = 0;
                $totalCredit = 0;
                $allLinesValid = true;

                foreach ($linesData as $lineData) {
                    $line = new JournalEntryLine();
                    $line->setAttributes($lineData);
                    $line->journal_entry_id = $model->id;
                    if (!$line->save()) {
                        $allLinesValid = false;
                    }
                    $totalDebit += (float)$line->debit;
                    $totalCredit += (float)$line->credit;
                    $lines[] = $line;
                }

                if ($allLinesValid && abs($totalDebit - $totalCredit) < 0.005) {
                    $model->total_debit = $totalDebit;
                    $model->total_credit = $totalCredit;
                    $model->save();
                    $transaction->commit();
                    Yii::$app->session->setFlash('success', 'تم تحديث القيد بنجاح');
                    return $this->redirect(['view', 'id' => $model->id]);
                } else {
                    $transaction->rollBack();
                    Yii::$app->session->setFlash('error', 'القيد غير متوازن');
                }
            } catch (\Exception $e) {
                $transaction->rollBack();
                Yii::$app->session->setFlash('error', 'حدث خطأ: ' . $e->getMessage());
            }
        }

        return $this->render('update', [
            'model' => $model,
            'lines' => $lines,
        ]);
    }

    public function actionPost($id)
    {
        $model = $this->findModel($id);
        if ($model->post()) {
            Yii::$app->session->setFlash('success', 'تم ترحيل القيد بنجاح');
        } else {
            Yii::$app->session->setFlash('error', 'فشل ترحيل القيد. تأكد أنه متوازن وفي حالة مسودة.');
        }
        return $this->redirect(['view', 'id' => $id]);
    }

    public function actionReverse($id)
    {
        $model = $this->findModel($id);
        $reversal = $model->reverse();
        if ($reversal) {
            Yii::$app->session->setFlash('success', 'تم عكس القيد وإنشاء قيد عكسي رقم ' . $reversal->entry_number);
            return $this->redirect(['view', 'id' => $reversal->id]);
        } else {
            Yii::$app->session->setFlash('error', 'فشل عكس القيد. تأكد أنه في حالة مرحّل.');
            return $this->redirect(['view', 'id' => $id]);
        }
    }

    public function actionDelete($id)
    {
        $model = $this->findModel($id);
        if ($model->status !== JournalEntry::STATUS_DRAFT) {
            Yii::$app->session->setFlash('error', 'لا يمكن حذف قيد مرحّل. استخدم العكس بدلا من ذلك.');
        } else {
            JournalEntryLine::deleteAll(['journal_entry_id' => $model->id]);
            $model->delete();
            Yii::$app->session->setFlash('success', 'تم حذف القيد');
        }
        return $this->redirect(['index']);
    }

    protected function findModel($id)
    {
        if (($model = JournalEntry::findOne($id)) !== null) {
            return $model;
        }
        throw new NotFoundHttpException('الصفحة المطلوبة غير موجودة');
    }
}
