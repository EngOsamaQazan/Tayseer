<?php

namespace backend\modules\judiciaryActions\controllers;

use backend\modules\judiciaryActions\models\JudiciaryActions;
use backend\modules\judiciaryActions\models\JudiciaryActionsSearch;
use yii\filters\AccessControl;
use yii\filters\VerbFilter;
use yii\helpers\Html;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\web\Response;
use backend\helpers\ExportHelper;
use backend\helpers\ExportTrait;
use Yii;

class JudiciaryActionsController extends Controller
{
    use ExportTrait;
    public function behaviors()
    {
        return [
            'access' => [
                'class' => AccessControl::class,
                'rules' => [
                    ['actions' => ['login', 'error'], 'allow' => true],
                    [
                        'actions' => ['logout', 'index', 'update', 'create', 'delete', 'confirm-delete', 'usage-details', 'view', 'bulk-delete', 'export-excel', 'export-pdf', 'quick-relink', 'inline-update'],
                        'allow' => true,
                        'roles' => ['@'],
                    ],
                ],
            ],
            'verbs' => [
                'class' => VerbFilter::class,
                'actions' => [
                    'delete' => ['post'],
                    'confirm-delete' => ['get', 'post'],
                    'usage-details' => ['get'],
                    'bulk-delete' => ['post'],
                    'quick-relink' => ['post'],
                    'inline-update' => ['post'],
                ],
            ],
        ];
    }

    /**
     * Index — list all actions
     */
    public function actionIndex()
    {
        $searchModel = new JudiciaryActionsSearch();
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams);
        $searchCounter = $searchModel->searchCounter(Yii::$app->request->queryParams);

        return $this->render('index', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
            'searchCounter' => $searchCounter,
        ]);
    }

    /**
     * Inline update — AJAX single-field update from grid
     */
    public function actionInlineUpdate()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        $request = Yii::$app->request;
        $id = (int)$request->post('id');
        $field = $request->post('field');
        $value = $request->post('value');

        $allowed = ['action_nature', 'action_type'];
        if (!in_array($field, $allowed)) {
            return ['success' => false, 'message' => 'حقل غير مسموح'];
        }

        $model = $this->findModel($id);
        $model->$field = $value;

        if ($model->save(false, [$field])) {
            return ['success' => true];
        }
        return ['success' => false, 'message' => 'فشل الحفظ'];
    }

    /**
     * View
     */
    public function actionView($id)
    {
        $request = Yii::$app->request;
        if ($request->isAjax) {
            Yii::$app->response->format = Response::FORMAT_JSON;
            return [
                'title' => 'عرض الإجراء #' . $id,
                'content' => $this->renderAjax('view', ['model' => $this->findModel($id)]),
                'footer' => Html::button('إغلاق', ['class' => 'btn btn-default pull-left', 'data-dismiss' => 'modal']) .
                    Html::a('<i class="fa fa-pencil"></i> تعديل', ['update', 'id' => $id], ['class' => 'btn btn-primary', 'role' => 'modal-remote']),
            ];
        }
        return $this->render('view', ['model' => $this->findModel($id)]);
    }

    /**
     * Create
     */
    public function actionCreate()
    {
        $request = Yii::$app->request;
        $model = new JudiciaryActions();

        if ($request->isAjax) {
            Yii::$app->response->format = Response::FORMAT_JSON;

            if ($request->isGet) {
                return [
                    'title' => '<i class="fa fa-plus"></i> إضافة إجراء قضائي جديد',
                    'content' => $this->renderAjax('create', ['model' => $model]),
                    'footer' => Html::button('إغلاق', ['class' => 'btn btn-default pull-left', 'data-dismiss' => 'modal']) .
                        Html::button('<i class="fa fa-plus"></i> إضافة', ['class' => 'btn btn-primary', 'type' => 'submit']),
                ];
            }

            if ($model->load($request->post())) {
                // Sync relationship fields from POST
                $this->syncRelationships($model, $request);

                if ($model->save()) {
                    return [
                        'forceReload' => '#crud-datatable-pjax',
                        'title' => 'إضافة إجراء قضائي',
                        'content' => '<div style="text-align:center;padding:20px"><i class="fa fa-check-circle" style="font-size:48px;color:#10B981"></i><h4 style="margin-top:12px;color:#1E293B">تم إضافة الإجراء بنجاح</h4><p style="color:#64748B">' . Html::encode($model->name) . '</p></div>',
                        'footer' => Html::button('إغلاق', ['class' => 'btn btn-default pull-left', 'data-dismiss' => 'modal']) .
                            Html::a('<i class="fa fa-plus"></i> إضافة آخر', ['create'], ['class' => 'btn btn-primary', 'role' => 'modal-remote']),
                    ];
                }
            }

            return [
                'title' => '<i class="fa fa-plus"></i> إضافة إجراء قضائي جديد',
                'content' => $this->renderAjax('create', ['model' => $model]),
                'footer' => Html::button('إغلاق', ['class' => 'btn btn-default pull-left', 'data-dismiss' => 'modal']) .
                    Html::button('<i class="fa fa-plus"></i> إضافة', ['class' => 'btn btn-primary', 'type' => 'submit']),
            ];
        }

        // Non-AJAX
        if ($model->load($request->post())) {
            $this->syncRelationships($model, $request);
            if ($model->save()) {
                return $this->redirect(['index']);
            }
        }
        return $this->render('create', ['model' => $model]);
    }

    /**
     * Update
     */
    public function actionUpdate($id)
    {
        $request = Yii::$app->request;
        $model = $this->findModel($id);

        if ($request->isAjax) {
            Yii::$app->response->format = Response::FORMAT_JSON;

            if ($request->isGet) {
                return [
                    'title' => '<i class="fa fa-pencil"></i> تعديل: ' . Html::encode($model->name),
                    'content' => $this->renderAjax('update', ['model' => $model]),
                    'footer' => Html::button('إغلاق', ['class' => 'btn btn-default pull-left', 'data-dismiss' => 'modal']) .
                        Html::button('<i class="fa fa-save"></i> حفظ', ['class' => 'btn btn-primary', 'type' => 'submit']),
                ];
            }

            if ($model->load($request->post())) {
                $this->syncRelationships($model, $request);

                if ($model->save()) {
                    return [
                        'forceReload' => '#crud-datatable-pjax',
                        'title' => 'تعديل الإجراء',
                        'content' => '<div style="text-align:center;padding:20px"><i class="fa fa-check-circle" style="font-size:48px;color:#10B981"></i><h4 style="margin-top:12px;color:#1E293B">تم حفظ التعديلات</h4></div>',
                        'footer' => Html::button('إغلاق', ['class' => 'btn btn-default pull-left', 'data-dismiss' => 'modal']),
                    ];
                }
            }

            return [
                'title' => '<i class="fa fa-pencil"></i> تعديل: ' . Html::encode($model->name),
                'content' => $this->renderAjax('update', ['model' => $model]),
                'footer' => Html::button('إغلاق', ['class' => 'btn btn-default pull-left', 'data-dismiss' => 'modal']) .
                    Html::button('<i class="fa fa-save"></i> حفظ', ['class' => 'btn btn-primary', 'type' => 'submit']),
            ];
        }

        // Non-AJAX
        if ($model->load($request->post())) {
            $this->syncRelationships($model, $request);
            if ($model->save()) {
                return $this->redirect(['index']);
            }
        }
        return $this->render('update', ['model' => $model]);
    }

    /**
     * Confirm Delete — shows migration dialog if records exist
     */
    public function actionConfirmDelete($id)
    {
        $request = Yii::$app->request;
        $model = $this->findModel($id);

        $usageCount = (int)(new \yii\db\Query())
            ->from('os_judiciary_customers_actions')
            ->where(['judiciary_actions_id' => $id])
            ->andWhere(['or', ['is_deleted' => 0], ['is_deleted' => null]])
            ->count();

        if ($request->isPost) {
            Yii::$app->response->format = Response::FORMAT_JSON;
            $deleteMode = $request->post('delete_mode', 'migrate');
            $migrateToId = $request->post('migrate_to_id');

            if ($usageCount > 0 && $deleteMode === 'migrate' && empty($migrateToId)) {
                $otherActions = \yii\helpers\ArrayHelper::map(
                    JudiciaryActions::find()
                        ->where(['is_deleted' => 0])
                        ->andWhere(['!=', 'id', $id])
                        ->orderBy(['name' => SORT_ASC])
                        ->all(),
                    'id', 'name'
                );
                return [
                    'title' => '<i class="fa fa-trash"></i> حذف الإجراء: ' . Html::encode($model->name),
                    'content' => $this->renderAjax('_confirm_delete', [
                        'model' => $model,
                        'usageCount' => $usageCount,
                        'otherActions' => $otherActions,
                        'error' => 'يجب اختيار إجراء بديل لترحيل السجلات إليه',
                    ]),
                    'footer' => Html::button('إلغاء', ['class' => 'btn btn-default pull-left', 'data-dismiss' => 'modal']) .
                        Html::button('<i class="fa fa-trash"></i> تأكيد الحذف', [
                            'class' => 'btn btn-danger',
                            'type' => 'submit',
                        ]),
                ];
            }

            if ($usageCount > 0) {
                if ($deleteMode === 'purge') {
                    Yii::$app->db->createCommand()->update(
                        'os_judiciary_customers_actions',
                        ['is_deleted' => 1],
                        ['and',
                            ['judiciary_actions_id' => $id],
                            ['or', ['is_deleted' => 0], ['is_deleted' => null]],
                        ]
                    )->execute();
                } elseif (!empty($migrateToId) && $migrateToId != $id) {
                    Yii::$app->db->createCommand()->update(
                        'os_judiciary_customers_actions',
                        ['judiciary_actions_id' => (int)$migrateToId],
                        ['and',
                            ['judiciary_actions_id' => $id],
                            ['or', ['is_deleted' => 0], ['is_deleted' => null]],
                        ]
                    )->execute();
                }
            }

            $model->is_deleted = 1;
            $model->save(false, ['is_deleted']);

            return ['forceClose' => true, 'forceReload' => '#crud-datatable-pjax'];
        }

        $otherActions = \yii\helpers\ArrayHelper::map(
            JudiciaryActions::find()
                ->where(['is_deleted' => 0])
                ->andWhere(['!=', 'id', $id])
                ->orderBy(['name' => SORT_ASC])
                ->all(),
            'id', 'name'
        );

        Yii::$app->response->format = Response::FORMAT_JSON;
        return [
            'title' => '<i class="fa fa-trash"></i> حذف الإجراء: ' . Html::encode($model->name),
            'content' => $this->renderAjax('_confirm_delete', [
                'model' => $model,
                'usageCount' => $usageCount,
                'otherActions' => $otherActions,
            ]),
            'footer' => Html::button('إلغاء', ['class' => 'btn btn-default pull-left', 'data-dismiss' => 'modal']) .
                Html::button('<i class="fa fa-trash"></i> تأكيد الحذف', [
                    'class' => 'btn btn-danger',
                    'type' => 'submit',
                ]),
        ];
    }

    /**
     * Usage Details — shows cases using this judiciary action
     */
    public function actionUsageDetails($id)
    {
        $model = $this->findModel($id);

        $rows = (new \yii\db\Query())
            ->select([
                'jca.id as jca_id',
                'jca.judiciary_id',
                'jca.customers_id',
                'jca.action_date',
                'jca.note',
                'j.judiciary_number',
                'j.contract_id',
                'j.year',
                'c.name as customer_name',
                'ct.type as contract_type',
                'court.name as court_name',
            ])
            ->from('os_judiciary_customers_actions jca')
            ->leftJoin('os_judiciary j', 'j.id = jca.judiciary_id')
            ->leftJoin('os_customers c', 'c.id = jca.customers_id')
            ->leftJoin('os_contracts ct', 'ct.id = j.contract_id')
            ->leftJoin('os_court court', 'court.id = j.court_id')
            ->where(['jca.judiciary_actions_id' => $id])
            ->andWhere(['or', ['jca.is_deleted' => 0], ['jca.is_deleted' => null]])
            ->orderBy(['jca.action_date' => SORT_DESC])
            ->all();

        Yii::$app->response->format = Response::FORMAT_JSON;
        return [
            'title' => '<i class="fa fa-list"></i> استخدامات: ' . Html::encode($model->name) . ' <span style="color:#94A3B8;font-size:13px">(' . count($rows) . ')</span>',
            'content' => $this->renderAjax('_usage_details', [
                'model' => $model,
                'rows' => $rows,
            ]),
            'footer' => Html::button('إغلاق', ['class' => 'btn btn-default', 'data-dismiss' => 'modal']),
        ];
    }

    /**
     * Delete (soft) — direct POST
     */
    public function actionDelete($id)
    {
        $request = Yii::$app->request;
        $model = $this->findModel($id);
        $model->is_deleted = 1;
        $model->save(false, ['is_deleted']);

        if ($request->isAjax) {
            Yii::$app->response->format = Response::FORMAT_JSON;
            return ['forceClose' => true, 'forceReload' => '#crud-datatable-pjax'];
        }
        return $this->redirect(['index']);
    }

    /**
     * Bulk Delete (soft)
     */
    public function actionBulkDelete()
    {
        $request = Yii::$app->request;
        $pks = explode(',', $request->post('pks'));
        foreach ($pks as $pk) {
            $model = $this->findModel($pk);
            $model->is_deleted = 1;
            $model->save(false, ['is_deleted']);
        }

        if ($request->isAjax) {
            Yii::$app->response->format = Response::FORMAT_JSON;
            return ['forceClose' => true, 'forceReload' => '#crud-datatable-pjax'];
        }
        return $this->redirect(['index']);
    }

    /**
     * Quick Relink — move a document/status to a new parent without opening the full edit form
     */
    public function actionQuickRelink()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        $request = Yii::$app->request;

        $itemId      = (int)$request->post('item_id');
        $newParentId = (int)$request->post('new_parent_id');
        $oldParentId = (int)$request->post('old_parent_id', 0);

        if (!$itemId || !$newParentId || $itemId === $newParentId) {
            return ['success' => false, 'message' => 'بيانات ناقصة أو غير صالحة'];
        }

        $item = $this->findModel($itemId);

        $parentIds = array_filter(array_map('intval', explode(',', $item->parent_request_ids ?: '')));
        if ($oldParentId) $parentIds = array_values(array_diff($parentIds, [$oldParentId]));
        if (!in_array($newParentId, $parentIds)) $parentIds[] = $newParentId;
        $item->parent_request_ids = implode(',', $parentIds) ?: null;
        $item->save(false, ['parent_request_ids']);

        return ['success' => true];
    }

    /**
     * Sync relationship fields from POST checkboxes to model comma-separated fields
     */
    private function syncRelationships($model, $request)
    {
        $parentIds = $request->post('rel_parent_ids', []);
        $model->parent_request_ids = is_array($parentIds) && !empty($parentIds) ? implode(',', array_map('intval', $parentIds)) : null;

        $model->allowed_documents = null;
        $model->allowed_statuses = null;

        $childIds = $request->post('rel_child_ids', []);
        $childIds = is_array($childIds) ? array_map('intval', $childIds) : [];

        $allActions = (new \yii\db\Query())
            ->select(['id', 'parent_request_ids'])
            ->from('os_judiciary_actions')
            ->where(['or', ['is_deleted' => 0], ['is_deleted' => null]])
            ->andWhere(['!=', 'id', $model->id])
            ->all();

        foreach ($allActions as $a) {
            $existing = !empty($a['parent_request_ids']) ? array_map('intval', explode(',', $a['parent_request_ids'])) : [];
            $hasThisParent = in_array($model->id, $existing);
            $shouldHave = in_array((int)$a['id'], $childIds);

            if ($shouldHave && !$hasThisParent) {
                $existing[] = $model->id;
                Yii::$app->db->createCommand()->update('os_judiciary_actions',
                    ['parent_request_ids' => implode(',', $existing)],
                    ['id' => $a['id']]
                )->execute();
            } elseif (!$shouldHave && $hasThisParent) {
                $existing = array_values(array_diff($existing, [$model->id]));
                Yii::$app->db->createCommand()->update('os_judiciary_actions',
                    ['parent_request_ids' => !empty($existing) ? implode(',', $existing) : null],
                    ['id' => $a['id']]
                )->execute();
            }
        }
    }

    public function actionExportExcel()
    {
        return $this->exportActionsLightweight('excel');
    }

    public function actionExportPdf()
    {
        return $this->exportActionsLightweight('pdf');
    }

    private function exportActionsLightweight($format)
    {
        $searchModel = new JudiciaryActionsSearch();
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams);

        $query = $dataProvider->query;
        $query->select(['id', 'name', 'action_nature', 'action_type', 'allowed_documents', 'allowed_statuses', 'parent_request_ids']);
        $rows = $query->asArray()->all();

        $natureLabels = JudiciaryActions::getNatureList();
        $stageLabels  = JudiciaryActions::getActionTypeList();
        $allNames = \yii\helpers\ArrayHelper::map(
            (new \yii\db\Query())->select(['id', 'name'])->from('os_judiciary_actions')->all(),
            'id', 'name'
        );

        $resolveIds = function ($csv) use ($allNames) {
            if (empty($csv)) return '—';
            $ids = array_map('intval', explode(',', $csv));
            $names = [];
            foreach ($ids as $id) {
                if ($id > 0) $names[] = $allNames[$id] ?? '#' . $id;
            }
            return !empty($names) ? implode('، ', $names) : '—';
        };

        $exportRows = [];
        foreach ($rows as $r) {
            $exportRows[] = [
                'name'         => $r['name'] ?? '—',
                'nature'       => $natureLabels[$r['action_nature'] ?? ''] ?? ($r['action_nature'] ?? '—'),
                'stage'        => $stageLabels[$r['action_type'] ?? ''] ?? ($r['action_type'] ?? 'عام'),
                'docs'         => $resolveIds($r['allowed_documents'] ?? ''),
                'statuses'     => $resolveIds($r['allowed_statuses'] ?? ''),
                'parents'      => $resolveIds($r['parent_request_ids'] ?? ''),
            ];
        }

        return $this->exportArrayData($exportRows, [
            'title'    => 'الإجراءات القضائية',
            'filename' => 'judiciary-actions',
            'headers'  => ['#', 'اسم الإجراء', 'الطبيعة', 'المرحلة', 'الكتب المسموحة', 'الحالات المسموحة', 'يتبع لطلبات'],
            'keys'     => ['#', 'name', 'nature', 'stage', 'docs', 'statuses', 'parents'],
            'widths'   => [6, 30, 16, 16, 30, 30, 30],
        ], $format);
    }

    /**
     * Find model
     */
    protected function findModel($id)
    {
        if (($model = JudiciaryActions::findOne($id)) !== null) {
            return $model;
        }
        throw new NotFoundHttpException('الصفحة المطلوبة غير موجودة');
    }
}
