<?php
/**
 * ═══════════════════════════════════════════════════════════════
 *  كونترولر المخزون v2 — نظام احترافي متكامل
 *  ─────────────────────────────────────────────────────────────
 *  يتضمن: لوحة التحكم، حركات المخزون، الأصناف، الإعدادات
 *  + APIs للإضافة السريعة inline
 * ═══════════════════════════════════════════════════════════════
 */

namespace backend\modules\inventoryItems\controllers;

use Yii;
use backend\modules\inventoryItems\models\InventoryItems;
use backend\modules\inventoryItems\models\InventoryItemsSearch;
use backend\modules\inventoryItems\models\StockMovement;
use backend\modules\inventoryItems\models\InventorySerialNumber;
use backend\modules\inventoryItems\models\InventorySerialNumberSearch;
use backend\modules\inventorySuppliers\models\InventorySuppliers;
use backend\modules\inventoryStockLocations\models\InventoryStockLocations;
use backend\modules\inventoryItemQuantities\models\InventoryItemQuantities;
use yii\filters\AccessControl;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;
use yii\web\Response;
use yii\helpers\Html;
use yii\helpers\ArrayHelper;
use yii\data\ActiveDataProvider;
use common\helper\Permissions;
use backend\helpers\ExportTrait;


class InventoryItemsController extends Controller
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
                        'actions' => [
                            'index', 'items', 'view', 'movements', 'settings',
                            'search-items', 'item-query',
                            'serial-numbers', 'serial-view',
                            'export-excel', 'export-pdf',
                            'export-items-excel', 'export-items-pdf',
                            'export-serials-excel', 'export-serials-pdf',
                            'export-query-excel', 'export-query-pdf',
                        ],
                        'allow' => true,
                        'roles' => ['@'],
                        'matchCallback' => function () {
                            return Permissions::can(Permissions::INVITEM_VIEW);
                        },
                    ],
                    [
                        'actions' => [
                            'create', 'batch-create',
                            'quick-add-item', 'quick-add-supplier', 'quick-add-location',
                            'serial-create',
                        ],
                        'allow' => true,
                        'roles' => ['@'],
                        'matchCallback' => function () {
                            return Permissions::can(Permissions::INVITEM_CREATE);
                        },
                    ],
                    [
                        'actions' => [
                            'update', 'approve', 'reject', 'bulk-approve', 'bulk-reject',
                            'adjustment', 'serial-update', 'serial-change-status',
                        ],
                        'allow' => true,
                        'roles' => ['@'],
                        'matchCallback' => function () {
                            return Permissions::can(Permissions::INVITEM_UPDATE);
                        },
                    ],
                    [
                        'actions' => [
                            'delete', 'bulk-delete',
                            'serial-delete', 'serial-bulk-delete',
                            'delete-supplier', 'transfer-supplier-data',
                        ],
                        'allow' => true,
                        'roles' => ['@'],
                        'matchCallback' => function () {
                            return Permissions::can(Permissions::INVITEM_DELETE);
                        },
                    ],
                    [
                        'actions' => ['logout'],
                        'allow' => true,
                        'roles' => ['@'],
                    ],
                ],
            ],
            'verbs' => [
                'class' => VerbFilter::class,
                'actions' => [
                    'delete'              => ['post'],
                    'approve'             => ['post'],
                    'reject'              => ['post'],
                    'bulk-approve'        => ['post'],
                    'bulk-reject'         => ['post'],
                    'bulk-delete'         => ['post'],
                    'quick-add-item'      => ['post'],
                    'quick-add-supplier'  => ['post'],
                    'quick-add-location'  => ['post'],
                    'adjustment'          => ['post'],
                    'serial-delete'       => ['post'],
                    'serial-change-status'=> ['post'],
                    'serial-bulk-delete'  => ['post'],
                    'delete-supplier'     => ['post'],
                    'transfer-supplier-data' => ['post'],
                ],
            ],
        ];
    }

    /* ═══════════════════════════════════════════════════════════
     *  لوحة التحكم — الشاشة الرئيسية
     * ═══════════════════════════════════════════════════════════ */
    public function actionIndex()
    {
        /* إحصائيات */
        $stats = [
            'total'    => (int) InventoryItems::find()->count(),
            'pending'  => (int) InventoryItems::find()->andWhere(['status' => 'pending'])->count(),
            'approved' => (int) InventoryItems::find()->andWhere(['status' => 'approved'])->count(),
            'rejected' => (int) InventoryItems::find()->andWhere(['status' => 'rejected'])->count(),
            'invoices' => (int) \backend\modules\inventoryInvoices\models\InventoryInvoices::find()->count(),
            'suppliers'=> (int) InventorySuppliers::find()->count(),
        ];

        /* أصناف تحت الحد الأدنى */
        $lowStockItems = [];
        $allItems = InventoryItems::find()->andWhere(['>', 'min_stock_level', 0])->andWhere(['status' => 'approved'])->all();
        foreach ($allItems as $item) {
            $stock = $item->getTotalStock();
            if ($stock < $item->min_stock_level) {
                $lowStockItems[] = [
                    'item'    => $item,
                    'stock'   => $stock,
                    'deficit' => $item->min_stock_level - $stock,
                ];
            }
        }

        /* آخر الحركات */
        $recentMovements = StockMovement::find()
            ->orderBy(['created_at' => SORT_DESC])
            ->limit(10)
            ->all();

        /* آخر أوامر الشراء */
        $recentOrders = \backend\modules\inventoryInvoices\models\InventoryInvoices::find()
            ->orderBy(['created_at' => SORT_DESC])
            ->limit(5)
            ->all();

        return $this->render('dashboard', [
            'stats'           => $stats,
            'lowStockItems'   => $lowStockItems,
            'recentMovements' => $recentMovements,
            'recentOrders'    => $recentOrders,
        ]);
    }

    /* ═══════════════════════════════════════════════════════════
     *  قائمة الأصناف
     * ═══════════════════════════════════════════════════════════ */
    public function actionItems()
    {
        $searchModel  = new InventoryItemsSearch();
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams);

        return $this->render('items', [
            'searchModel'  => $searchModel,
            'dataProvider' => $dataProvider,
        ]);
    }

    /* ═══════════════════════════════════════════════════════════
     *  حركات المخزون
     * ═══════════════════════════════════════════════════════════ */
    public function actionMovements()
    {
        $query = StockMovement::find()->orderBy(['created_at' => SORT_DESC]);

        /* فلترة */
        $filterType   = Yii::$app->request->get('type');
        $filterItem   = Yii::$app->request->get('item_id');
        $filterFrom   = Yii::$app->request->get('from');
        $filterTo     = Yii::$app->request->get('to');

        if ($filterType) $query->andWhere(['movement_type' => $filterType]);
        if ($filterItem) $query->andWhere(['item_id' => $filterItem]);
        if ($filterFrom) $query->andWhere(['>=', 'created_at', strtotime($filterFrom)]);
        if ($filterTo)   $query->andWhere(['<=', 'created_at', strtotime($filterTo . ' 23:59:59')]);

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
            'pagination' => ['pageSize' => 20],
        ]);

        return $this->render('movements', [
            'dataProvider' => $dataProvider,
            'filterType'   => $filterType,
            'filterItem'   => $filterItem,
            'filterFrom'   => $filterFrom,
            'filterTo'     => $filterTo,
        ]);
    }

    /* ═══════════════════════════════════════════════════════════
     *  الإعدادات — الموردين + المواقع
     * ═══════════════════════════════════════════════════════════ */
    public function actionSettings()
    {
        /* جدول واحد موحّد — يشمل موردين خارجيين ومستخدمي نظام (user_id IS NOT NULL) */
        $suppliers = InventorySuppliers::find()->orderBy(['user_id' => SORT_DESC, 'name' => SORT_ASC])->all();
        $locations = InventoryStockLocations::find()->all();

        return $this->render('settings', [
            'suppliers' => $suppliers,
            'locations' => $locations,
        ]);
    }

    /* ═══════════════════════════════════════════════════════════
     *  CRUD الأصناف
     * ═══════════════════════════════════════════════════════════ */
    public function actionView($id)
    {
        $request = Yii::$app->request;
        $model = $this->findModel($id);

        if ($request->isAjax) {
            Yii::$app->response->format = Response::FORMAT_JSON;
            return [
                'title'   => 'تفاصيل الصنف #' . $id,
                'content' => $this->renderAjax('view', ['model' => $model]),
                'footer'  => Html::button('إغلاق', ['class' => 'btn btn-default pull-left', 'data-dismiss' => 'modal']) .
                             Html::a('تعديل', ['update', 'id' => $id], ['class' => 'btn btn-primary', 'role' => 'modal-remote']),
            ];
        }
        return $this->render('view', ['model' => $model]);
    }

    public function actionCreate()
    {
        $request = Yii::$app->request;
        $model = new InventoryItems();
        $model->status = $this->isSupplierUser() ? InventoryItems::STATUS_PENDING : InventoryItems::STATUS_APPROVED;

        if ($request->isAjax) {
            Yii::$app->response->format = Response::FORMAT_JSON;
            if ($request->isGet) {
                return [
                    'title'   => 'إضافة صنف جديد',
                    'content' => $this->renderAjax('create', ['model' => $model]),
                    'footer'  => Html::button('إغلاق', ['class' => 'btn btn-default pull-left', 'data-dismiss' => 'modal']) .
                                 Html::button('حفظ', ['class' => 'btn btn-primary', 'type' => 'submit']),
                ];
            }
            if ($model->load($request->post()) && $model->save()) {
                return [
                    'forceReload' => '#crud-datatable-pjax',
                    'forceClose'  => true,
                    'title'       => 'تمت الإضافة',
                    'content'     => '<span class="text-success">تم إضافة الصنف بنجاح</span>',
                    'footer'      => '',
                ];
            }
            return [
                'title'   => 'إضافة صنف جديد',
                'content' => $this->renderAjax('create', ['model' => $model]),
                'footer'  => Html::button('إغلاق', ['class' => 'btn btn-default pull-left', 'data-dismiss' => 'modal']) .
                             Html::button('حفظ', ['class' => 'btn btn-primary', 'type' => 'submit']),
            ];
        }

        if ($model->load($request->post()) && $model->save()) {
            return $this->redirect(['items']);
        }
        return $this->render('create', ['model' => $model]);
    }

    /**
     * إضافة مجموعة أصناف دفعة واحدة (AJAX)
     */
    public function actionBatchCreate()
    {
        $request = Yii::$app->request;

        if ($request->isAjax) {
            Yii::$app->response->format = Response::FORMAT_JSON;

            if ($request->isGet) {
                return [
                    'title'   => '<i class="fa fa-cubes"></i> إضافة مجموعة أصناف',
                    'content' => $this->renderAjax('_batch_form'),
                    'footer'  => Html::button('إغلاق', ['class' => 'btn btn-default pull-left', 'data-dismiss' => 'modal']) .
                                 Html::button('<i class="fa fa-plus"></i> إضافة الكل', ['class' => 'btn btn-success', 'type' => 'submit']),
                ];
            }

            // POST — معالجة الدفعة
            $items = $request->post('items', []);
            $status = $this->isSupplierUser() ? InventoryItems::STATUS_PENDING : InventoryItems::STATUS_APPROVED;
            $added = 0;
            $errors = [];

            foreach ($items as $idx => $row) {
                $name = trim($row['item_name'] ?? '');
                if ($name === '') continue;

                $barcode = trim($row['item_barcode'] ?? '');

                $model = new InventoryItems();
                $model->item_name = $name;
                $model->item_barcode = $barcode ?: ('ITM-' . time() . '-' . $idx);
                $model->category = trim($row['category'] ?? '') ?: null;
                $model->description = trim($row['description'] ?? '') ?: null;
                $model->status = $status;

                if ($model->save()) {
                    $added++;
                } else {
                    $errors[] = "سطر " . ($idx + 1) . ": " . implode('، ', $model->getFirstErrors());
                }
            }

            if ($added > 0) {
                $msg = "تم إضافة {$added} صنف بنجاح";
                if (!empty($errors)) {
                    $msg .= " — مع " . count($errors) . " أخطاء";
                }
                return [
                    'forceReload' => '#crud-datatable-pjax',
                    'forceClose'  => true,
                    'title'       => 'تمت الإضافة',
                    'content'     => '<span class="text-success">' . $msg . '</span>',
                    'footer'      => '',
                ];
            }

            return [
                'title'   => '<i class="fa fa-cubes"></i> إضافة مجموعة أصناف',
                'content' => '<div class="alert alert-danger">لم يتم إضافة أي صنف. تأكد من تعبئة البيانات.</div>' . $this->renderAjax('_batch_form'),
                'footer'  => Html::button('إغلاق', ['class' => 'btn btn-default pull-left', 'data-dismiss' => 'modal']) .
                             Html::button('<i class="fa fa-plus"></i> إضافة الكل', ['class' => 'btn btn-success', 'type' => 'submit']),
            ];
        }

        return $this->redirect(['items']);
    }

    public function actionUpdate($id)
    {
        $request = Yii::$app->request;
        $model = $this->findModel($id);

        if ($request->isAjax) {
            Yii::$app->response->format = Response::FORMAT_JSON;
            if ($request->isGet) {
                return [
                    'title'   => 'تعديل الصنف #' . $id,
                    'content' => $this->renderAjax('update', ['model' => $model]),
                    'footer'  => Html::button('إغلاق', ['class' => 'btn btn-default pull-left', 'data-dismiss' => 'modal']) .
                                 Html::button('حفظ', ['class' => 'btn btn-primary', 'type' => 'submit']),
                ];
            }
            if ($model->load($request->post()) && $model->save()) {
                return [
                    'forceReload' => '#crud-datatable-pjax',
                    'title'       => 'تفاصيل الصنف #' . $id,
                    'content'     => $this->renderAjax('view', ['model' => $model]),
                    'footer'      => Html::button('إغلاق', ['class' => 'btn btn-default pull-left', 'data-dismiss' => 'modal']) .
                                     Html::a('تعديل', ['update', 'id' => $id], ['class' => 'btn btn-primary', 'role' => 'modal-remote']),
                ];
            }
            return [
                'title'   => 'تعديل الصنف #' . $id,
                'content' => $this->renderAjax('update', ['model' => $model]),
                'footer'  => Html::button('إغلاق', ['class' => 'btn btn-default pull-left', 'data-dismiss' => 'modal']) .
                             Html::button('حفظ', ['class' => 'btn btn-primary', 'type' => 'submit']),
            ];
        }

        if ($model->load($request->post()) && $model->save()) {
            return $this->redirect(['items']);
        }
        return $this->render('update', ['model' => $model]);
    }

    public function actionDelete($id)
    {
        $this->findModel($id)->delete();

        if (Yii::$app->request->isAjax) {
            Yii::$app->response->format = Response::FORMAT_JSON;
            return ['forceClose' => true, 'forceReload' => '#crud-datatable-pjax'];
        }
        return $this->redirect(['items']);
    }

    public function actionBulkDelete()
    {
        $raw = Yii::$app->request->post('pks');
        if ($raw === null || $raw === '') {
            return $this->redirect(['items']);
        }
        $pks = is_array($raw) ? $raw : explode(',', (string)$raw);
        foreach ($pks as $pk) {
            $this->findModel($pk)->delete();
        }

        if (Yii::$app->request->isAjax) {
            Yii::$app->response->format = Response::FORMAT_JSON;
            return ['forceClose' => true, 'forceReload' => '#crud-datatable-pjax'];
        }
        return $this->redirect(['items']);
    }

    /* ═══════════════════════════════════════════════════════════
     *  سير عمل الموافقات
     * ═══════════════════════════════════════════════════════════ */
    public function actionApprove($id)
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        $model = $this->findModel($id);
        $model->status      = InventoryItems::STATUS_APPROVED;
        $model->approved_by = Yii::$app->user->id;
        $model->approved_at = time();
        $model->rejection_reason = null;

        if ($model->save(false)) {
            return ['success' => true, 'message' => 'تم اعتماد الصنف بنجاح'];
        }
        return ['success' => false, 'message' => 'حدث خطأ أثناء الاعتماد'];
    }

    public function actionReject($id)
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        $model = $this->findModel($id);
        $model->status           = InventoryItems::STATUS_REJECTED;
        $model->approved_by      = Yii::$app->user->id;
        $model->approved_at      = time();
        $model->rejection_reason = Yii::$app->request->post('reason', '');

        if ($model->save(false)) {
            return ['success' => true, 'message' => 'تم رفض الصنف'];
        }
        return ['success' => false, 'message' => 'حدث خطأ أثناء الرفض'];
    }

    public function actionBulkApprove()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        $pks = Yii::$app->request->post('pks', []);
        if (is_string($pks)) $pks = explode(',', $pks);

        $count = 0;
        foreach ($pks as $pk) {
            $model = InventoryItems::findOne($pk);
            if ($model && $model->status === InventoryItems::STATUS_PENDING) {
                $model->status      = InventoryItems::STATUS_APPROVED;
                $model->approved_by = Yii::$app->user->id;
                $model->approved_at = time();
                $model->save(false);
                $count++;
            }
        }
        return ['success' => true, 'message' => "تم اعتماد {$count} صنف بنجاح"];
    }

    public function actionBulkReject()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        $pks    = Yii::$app->request->post('pks', []);
        $reason = Yii::$app->request->post('reason', '');
        if (is_string($pks)) $pks = explode(',', $pks);

        $count = 0;
        foreach ($pks as $pk) {
            $model = InventoryItems::findOne($pk);
            if ($model && $model->status === InventoryItems::STATUS_PENDING) {
                $model->status           = InventoryItems::STATUS_REJECTED;
                $model->approved_by      = Yii::$app->user->id;
                $model->approved_at      = time();
                $model->rejection_reason = $reason;
                $model->save(false);
                $count++;
            }
        }
        return ['success' => true, 'message' => "تم رفض {$count} صنف"];
    }

    /* ═══════════════════════════════════════════════════════════
     *  تعديل يدوي للكمية (adjustment)
     * ═══════════════════════════════════════════════════════════ */
    public function actionAdjustment()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        $itemId    = Yii::$app->request->post('item_id');
        $newQty    = (int) Yii::$app->request->post('quantity');
        $notes     = Yii::$app->request->post('notes', '');
        $companyId = Yii::$app->request->post('company_id');

        $item = InventoryItems::findOne($itemId);
        if (!$item) return ['success' => false, 'message' => 'الصنف غير موجود'];

        $currentQty = $item->getTotalStock();
        $diff = $newQty - $currentQty;

        if ($diff == 0) return ['success' => true, 'message' => 'الكمية لم تتغير'];

        /* تحديث أو إنشاء سجل الكمية */
        $qtyRecord = InventoryItemQuantities::find()
            ->where(['item_id' => $itemId, 'is_deleted' => 0])
            ->andFilterWhere(['company_id' => $companyId])
            ->one();

        if ($qtyRecord) {
            $qtyRecord->quantity = $newQty;
            $qtyRecord->save(false);
        } else {
            $qtyRecord = new InventoryItemQuantities();
            $qtyRecord->item_id    = $itemId;
            $qtyRecord->quantity   = $newQty;
            $qtyRecord->company_id = $companyId;
            $qtyRecord->suppliers_id = 0;
            $qtyRecord->locations_id = 0;
            $qtyRecord->save(false);
        }

        /* تسجيل الحركة */
        StockMovement::record($itemId, StockMovement::TYPE_ADJUSTMENT, abs($diff), [
            'notes'      => $notes ?: ($diff > 0 ? 'زيادة يدوية' : 'نقص يدوي'),
            'company_id' => $companyId,
        ]);

        return ['success' => true, 'message' => 'تم تعديل الكمية بنجاح (الفرق: ' . ($diff > 0 ? "+$diff" : $diff) . ')'];
    }

    /* ═══════════════════════════════════════════════════════════
     *  APIs — إضافة سريعة inline
     * ═══════════════════════════════════════════════════════════ */

    /** إضافة صنف سريعة من نافذة الفاتورة */
    public function actionQuickAddItem()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        $model = new InventoryItems();
        $model->item_name    = Yii::$app->request->post('name');
        $model->item_barcode = Yii::$app->request->post('barcode', 'BR-' . time());
        $model->category     = Yii::$app->request->post('category', '');
        $model->unit_price   = Yii::$app->request->post('price', 0);
        $model->status       = InventoryItems::STATUS_APPROVED;

        if ($model->save()) {
            return ['success' => true, 'id' => $model->id, 'name' => $model->item_name, 'message' => 'تم إضافة الصنف'];
        }
        return ['success' => false, 'message' => 'خطأ: ' . implode(', ', array_map(function($e){ return implode(', ', $e); }, $model->getErrors()))];
    }

    /** إضافة مورد سريعة */
    public function actionQuickAddSupplier()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        $model = new InventorySuppliers();
        $model->name         = Yii::$app->request->post('name');
        $model->phone_number = Yii::$app->request->post('phone', '');
        $model->adress       = Yii::$app->request->post('address', '');
        $model->company_id   = Yii::$app->request->post('company_id', 0);

        if ($model->save()) {
            return ['success' => true, 'id' => $model->id, 'name' => $model->name, 'message' => 'تم إضافة المورد'];
        }
        return ['success' => false, 'message' => 'خطأ: ' . implode(', ', array_map(function($e){ return implode(', ', $e); }, $model->getErrors()))];
    }

    /**
     * حذف مورد خارجي (من جدول inventory_suppliers فقط).
     * إذا وُجدت فواتير أو أصناف أو أرقام تسلسلية أو حركات مرتبطة، يُرجع رسالة لِنقلها لمورد آخر.
     */
    public function actionDeleteSupplier()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        $id = (int) Yii::$app->request->post('id');
        $supplier = InventorySuppliers::findOne($id);
        if (!$supplier) {
            return ['success' => false, 'message' => 'المورد غير موجود.'];
        }

        $invoicesCount = (int) \backend\modules\inventoryInvoices\models\InventoryInvoices::find()->andWhere(['suppliers_id' => $id])->count();
        $itemsCount   = (int) InventoryItems::find()->andWhere(['supplier_id' => $id])->count();
        $serialsCount = (int) InventorySerialNumber::find()->andWhere(['supplier_id' => $id])->count();
        $movementsCount = (int) StockMovement::find()->andWhere(['supplier_id' => $id])->count();
        $quantitiesCount = (int) \backend\modules\inventoryItemQuantities\models\InventoryItemQuantities::find()->andWhere(['suppliers_id' => $id])->count();

        $total = $invoicesCount + $itemsCount + $serialsCount + $movementsCount + $quantitiesCount;
        if ($total > 0) {
            $parts = [];
            if ($invoicesCount) $parts[] = $invoicesCount . ' فاتورة';
            if ($itemsCount) $parts[] = $itemsCount . ' صنف';
            if ($serialsCount) $parts[] = $serialsCount . ' رقم تسلسلي';
            if ($movementsCount) $parts[] = $movementsCount . ' حركة';
            if ($quantitiesCount) $parts[] = $quantitiesCount . ' كمية';
            $msg = 'لا يمكن الحذف: توجد سجلات مرتبطة بهذا المورد (' . implode('، ', $parts) . '). انقلها لمورد آخر من خلال "نقل البيانات ثم حذف" ثم أعد المحاولة.';
            return [
                'success' => false,
                'message' => $msg,
                'linked' => [
                    'invoices' => $invoicesCount,
                    'items' => $itemsCount,
                    'serials' => $serialsCount,
                    'movements' => $movementsCount,
                    'quantities' => $quantitiesCount,
                ],
            ];
        }

        $supplier->delete(); // soft delete
        return ['success' => true, 'message' => 'تم حذف المورد.'];
    }

    /**
     * نقل كل الفواتير والأصناف والأرقام التسلسلية والحركات والكميات من مورد إلى آخر، ثم حذف المورد المصدر.
     * POST: from_id, to_id (مورد خارجي) أو to_user_id (مستخدم مصنّف كمورد — يُستدعى أو يُنشأ له سجل في inventory_suppliers)
     */
    public function actionTransferSupplierData()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        $fromId  = (int) Yii::$app->request->post('from_id');
        $toId    = (int) Yii::$app->request->post('to_id');
        $toUserId = (int) Yii::$app->request->post('to_user_id');
        if (!$fromId) {
            return ['success' => false, 'message' => 'حدد المورد المصدر.'];
        }
        if (!$toId && !$toUserId) {
            return ['success' => false, 'message' => 'حدد المورد المستهدف (مورد خارجي أو مستخدم مصنّف كمورد).'];
        }
        if ($toId && $fromId === $toId) {
            return ['success' => false, 'message' => 'المورد المستهدف لا يمكن أن يكون نفس المورد المراد حذفه.'];
        }

        $from = InventorySuppliers::findOne($fromId);
        if (!$from) {
            return ['success' => false, 'message' => 'المورد المصدر غير موجود.'];
        }

        if ($toUserId) {
            $user = \common\models\User::findOne($toUserId);
            if (!$user) {
                return ['success' => false, 'message' => 'المستخدم المختار غير موجود.'];
            }
            $to = $this->findOrCreateSupplierForUser($user);
            if (!$to) {
                return ['success' => false, 'message' => 'تعذر إنشاء أو العثور على سجل المورد لهذا المستخدم.'];
            }
            $toId = $to->id;
        } else {
            $to = InventorySuppliers::findOne($toId);
            if (!$to) {
                return ['success' => false, 'message' => 'المورد المستهدف غير موجود.'];
            }
        }

        $db = Yii::$app->db;
        $tr = $db->beginTransaction();
        try {
            \backend\modules\inventoryInvoices\models\InventoryInvoices::updateAll(
                ['suppliers_id' => $toId],
                ['suppliers_id' => $fromId]
            );
            InventoryItems::updateAll(['supplier_id' => $toId], ['supplier_id' => $fromId]);
            InventorySerialNumber::updateAll(['supplier_id' => $toId], ['supplier_id' => $fromId]);
            StockMovement::updateAll(['supplier_id' => $toId], ['supplier_id' => $fromId]);
            \backend\modules\inventoryItemQuantities\models\InventoryItemQuantities::updateAll(
                ['suppliers_id' => $toId],
                ['suppliers_id' => $fromId]
            );
            $from->delete(); // soft delete
            $tr->commit();
            return ['success' => true, 'message' => 'تم نقل البيانات وحذف المورد القديم.'];
        } catch (\Exception $e) {
            $tr->rollBack();
            return ['success' => false, 'message' => 'خطأ: ' . $e->getMessage()];
        }
    }

    /**
     * إيجاد أو إنشاء سجل مورد خارجي (inventory_suppliers) لمستخدم مصنّف كمورد — لاستخدامه في نقل البيانات
     */
    protected function findOrCreateSupplierForUser($user)
    {
        $name = trim(implode(' ', array_filter([$user->name ?? '', $user->middle_name ?? '', $user->last_name ?? ''])));
        if ($name === '' || strpos($name, '@') !== false) {
            $name = $user->username ?? 'مورد-' . $user->id;
        }
        $phone = !empty($user->mobile) ? $user->mobile : ('u' . $user->id);
        $existing = InventorySuppliers::find()
            ->andWhere(['or', ['phone_number' => $phone], ['name' => $name]])
            ->one();
        if ($existing) {
            return $existing;
        }
        $model = new InventorySuppliers();
        $model->name = $name;
        $model->phone_number = $phone;
        if ($model->save()) {
            return $model;
        }
        return null;
    }

    /** إضافة موقع سريعة */
    public function actionQuickAddLocation()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        $model = new InventoryStockLocations();
        $model->locations_name = Yii::$app->request->post('name');
        $model->company_id     = Yii::$app->request->post('company_id', 0);

        if ($model->save()) {
            return ['success' => true, 'id' => $model->id, 'name' => $model->locations_name, 'message' => 'تم إضافة الموقع'];
        }
        return ['success' => false, 'message' => 'خطأ: ' . implode(', ', array_map(function($e){ return implode(', ', $e); }, $model->getErrors()))];
    }

    /** بحث أصناف للـ autocomplete */
    public function actionSearchItems($q = '')
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        $items = InventoryItems::find()
            ->andWhere(['status' => 'approved'])
            ->andWhere(['or',
                ['like', 'item_name', $q],
                ['like', 'item_barcode', $q],
                ['like', 'serial_number', $q],
            ])
            ->limit(20)
            ->all();

        $results = [];
        foreach ($items as $item) {
            $results[] = [
                'id'      => $item->id,
                'text'    => $item->item_name . ' (' . $item->item_barcode . ')',
                'name'    => $item->item_name,
                'barcode' => $item->item_barcode,
                'price'   => $item->unit_price,
            ];
        }
        return ['results' => $results];
    }

    /* ═══ استعلام الأصناف ═══ */
    public function actionItemQuery()
    {
        $searchModel  = new InventoryItemsSearch();
        $dataProvider = $searchModel->itemQuery(Yii::$app->request->queryParams);

        return $this->render('index_item_query', [
            'searchModel'  => $searchModel,
            'dataProvider' => $dataProvider,
        ]);
    }

    /* ═══════════════════════════════════════════════════════════
     *  الأرقام التسلسلية — Serial Numbers
     * ═══════════════════════════════════════════════════════════ */

    /**
     * قائمة الأرقام التسلسلية
     */
    public function actionSerialNumbers()
    {
        $this->syncOrphanedSerials();

        $searchModel  = new InventorySerialNumberSearch();
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams);

        /* إحصائيات سريعة */
        $stats = [
            'total'     => (int) InventorySerialNumber::find()->count(),
            'available' => (int) InventorySerialNumber::find()->andWhere(['status' => 'available'])->count(),
            'reserved'  => (int) InventorySerialNumber::find()->andWhere(['status' => 'reserved'])->count(),
            'sold'      => (int) InventorySerialNumber::find()->andWhere(['status' => 'sold'])->count(),
            'returned'  => (int) InventorySerialNumber::find()->andWhere(['status' => 'returned'])->count(),
            'defective' => (int) InventorySerialNumber::find()->andWhere(['status' => 'defective'])->count(),
        ];

        return $this->render('serial-numbers', [
            'searchModel'  => $searchModel,
            'dataProvider' => $dataProvider,
            'stats'        => $stats,
        ]);
    }

    /**
     * مزامنة: إرجاع سيريالات "مباع" يتيمة (غير مرتبطة فعلياً بعقد) إلى "متاح"
     */
    private function syncOrphanedSerials()
    {
        Yii::$app->db->createCommand(
            "UPDATE os_inventory_serial_numbers
             SET status = 'available', contract_id = NULL, sold_at = NULL
             WHERE status = 'sold'
               AND is_deleted = 0
               AND id NOT IN (
                   SELECT serial_number_id
                   FROM os_contract_inventory_item
                   WHERE serial_number_id IS NOT NULL
               )"
        )->execute();
    }

    /**
     * إنشاء رقم تسلسلي جديد
     */
    public function actionSerialCreate()
    {
        $request = Yii::$app->request;
        $model = new InventorySerialNumber();

        if ($request->isAjax) {
            Yii::$app->response->format = Response::FORMAT_JSON;
            if ($request->isGet) {
                return [
                    'title'   => '<i class="fa fa-barcode"></i> إضافة رقم تسلسلي جديد',
                    'content' => $this->renderAjax('_serial_form', ['model' => $model]),
                    'footer'  => Html::button('إغلاق', ['class' => 'btn btn-default pull-left', 'data-dismiss' => 'modal']) .
                                 Html::button('حفظ', ['class' => 'btn btn-primary', 'type' => 'submit']),
                ];
            }
            if ($model->load($request->post()) && $model->save()) {
                StockMovement::record($model->item_id, StockMovement::TYPE_IN, 1, [
                    'reference_type' => 'serial_create',
                    'reference_id'   => $model->id,
                    'supplier_id'    => $model->supplier_id ?? null,
                    'company_id'     => $model->company_id ?? null,
                    'unit_cost'      => $model->purchase_price ?? null,
                    'notes'          => 'إضافة رقم تسلسلي: ' . $model->serial_number,
                ]);
                return [
                    'forceReload' => '#serial-datatable-pjax',
                    'title'       => 'إضافة رقم تسلسلي',
                    'content'     => '<span class="text-success"><i class="fa fa-check"></i> تم إضافة الرقم التسلسلي بنجاح</span>',
                    'footer'      => Html::button('إغلاق', ['class' => 'btn btn-default pull-left', 'data-dismiss' => 'modal']) .
                                     Html::a('إضافة المزيد', ['serial-create'], ['class' => 'btn btn-primary', 'role' => 'modal-remote']),
                ];
            }
            return [
                'title'   => '<i class="fa fa-barcode"></i> إضافة رقم تسلسلي جديد',
                'content' => $this->renderAjax('_serial_form', ['model' => $model]),
                'footer'  => Html::button('إغلاق', ['class' => 'btn btn-default pull-left', 'data-dismiss' => 'modal']) .
                             Html::button('حفظ', ['class' => 'btn btn-primary', 'type' => 'submit']),
            ];
        }

        if ($model->load($request->post()) && $model->save()) {
            StockMovement::record($model->item_id, StockMovement::TYPE_IN, 1, [
                'reference_type' => 'serial_create',
                'reference_id'   => $model->id,
                'supplier_id'    => $model->supplier_id ?? null,
                'company_id'     => $model->company_id ?? null,
                'unit_cost'      => $model->purchase_price ?? null,
                'notes'          => 'إضافة رقم تسلسلي: ' . $model->serial_number,
            ]);
            return $this->redirect(['serial-numbers']);
        }
        return $this->render('_serial_form', ['model' => $model]);
    }

    /**
     * تعديل رقم تسلسلي
     */
    public function actionSerialUpdate($id)
    {
        $request = Yii::$app->request;
        $model = $this->findSerialModel($id);

        if ($request->isAjax) {
            Yii::$app->response->format = Response::FORMAT_JSON;
            if ($request->isGet) {
                return [
                    'title'   => '<i class="fa fa-edit"></i> تعديل الرقم التسلسلي #' . $id,
                    'content' => $this->renderAjax('_serial_form', ['model' => $model]),
                    'footer'  => Html::button('إغلاق', ['class' => 'btn btn-default pull-left', 'data-dismiss' => 'modal']) .
                                 Html::button('حفظ', ['class' => 'btn btn-primary', 'type' => 'submit']),
                ];
            }
            if ($model->load($request->post()) && $model->save()) {
                return [
                    'forceReload' => '#serial-datatable-pjax',
                    'title'       => 'تعديل الرقم التسلسلي #' . $id,
                    'content'     => '<span class="text-success"><i class="fa fa-check"></i> تم التحديث بنجاح</span>',
                    'footer'      => Html::button('إغلاق', ['class' => 'btn btn-default pull-left', 'data-dismiss' => 'modal']),
                ];
            }
            return [
                'title'   => '<i class="fa fa-edit"></i> تعديل الرقم التسلسلي #' . $id,
                'content' => $this->renderAjax('_serial_form', ['model' => $model]),
                'footer'  => Html::button('إغلاق', ['class' => 'btn btn-default pull-left', 'data-dismiss' => 'modal']) .
                             Html::button('حفظ', ['class' => 'btn btn-primary', 'type' => 'submit']),
            ];
        }

        if ($model->load($request->post()) && $model->save()) {
            return $this->redirect(['serial-numbers']);
        }
        return $this->render('_serial_form', ['model' => $model]);
    }

    /**
     * عرض تفاصيل رقم تسلسلي
     */
    public function actionSerialView($id)
    {
        $request = Yii::$app->request;
        $model = $this->findSerialModel($id);

        if ($request->isAjax) {
            Yii::$app->response->format = Response::FORMAT_JSON;
            return [
                'title'   => '<i class="fa fa-barcode"></i> تفاصيل الرقم التسلسلي #' . $id,
                'content' => $this->renderAjax('_serial_view', ['model' => $model]),
                'footer'  => Html::button('إغلاق', ['class' => 'btn btn-default pull-left', 'data-dismiss' => 'modal']) .
                             Html::a('تعديل', ['serial-update', 'id' => $id], ['class' => 'btn btn-primary', 'role' => 'modal-remote']),
            ];
        }
        return $this->render('_serial_view', ['model' => $model]);
    }

    /**
     * حذف رقم تسلسلي (soft delete)
     */
    public function actionSerialDelete($id)
    {
        $model = $this->findSerialModel($id);
        $model->softDelete();

        if (Yii::$app->request->isAjax) {
            Yii::$app->response->format = Response::FORMAT_JSON;
            return ['forceClose' => true, 'forceReload' => '#serial-datatable-pjax'];
        }
        return $this->redirect(['serial-numbers']);
    }

    /**
     * حذف جماعي للأرقام التسلسلية
     */
    public function actionSerialBulkDelete()
    {
        $raw = Yii::$app->request->post('pks');
        if ($raw === null || $raw === '') {
            Yii::$app->response->format = Response::FORMAT_JSON;
            return ['forceClose' => true, 'forceReload' => '#serial-datatable-pjax'];
        }
        $pks = is_array($raw) ? $raw : explode(',', (string)$raw);
        foreach ($pks as $pk) {
            $model = InventorySerialNumber::findOne($pk);
            if ($model) $model->softDelete();
        }

        if (Yii::$app->request->isAjax) {
            Yii::$app->response->format = Response::FORMAT_JSON;
            return ['forceClose' => true, 'forceReload' => '#serial-datatable-pjax'];
        }
        return $this->redirect(['serial-numbers']);
    }

    /**
     * تغيير حالة رقم تسلسلي (AJAX)
     */
    public function actionSerialChangeStatus()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        $id     = Yii::$app->request->post('id');
        $status = Yii::$app->request->post('status');

        $model = InventorySerialNumber::findOne($id);
        if (!$model) return ['success' => false, 'message' => 'الرقم التسلسلي غير موجود'];

        $validStatuses = array_keys(InventorySerialNumber::getStatusList());
        if (!in_array($status, $validStatuses)) {
            return ['success' => false, 'message' => 'حالة غير صالحة'];
        }

        $oldStatus = $model->status;
        $model->status = $status;

        if ($status === InventorySerialNumber::STATUS_SOLD && $oldStatus !== InventorySerialNumber::STATUS_SOLD) {
            $model->sold_at = time();
        }

        if ($model->save(false)) {
            $this->recordSerialStatusMovement($model, $oldStatus, $status);
            return ['success' => true, 'message' => 'تم تغيير الحالة بنجاح'];
        }
        return ['success' => false, 'message' => 'حدث خطأ أثناء الحفظ'];
    }

    /**
     * البحث عن رقم تسلسلي معين عبر الجدول
     */
    protected function findSerialModel($id)
    {
        $model = InventorySerialNumber::findOne($id);
        if ($model !== null) {
            return $model;
        }
        throw new NotFoundHttpException('الرقم التسلسلي غير موجود.');
    }

    /**
     * تسجيل حركة مخزون عند تغيير حالة السيريال يدوياً
     */
    private function recordSerialStatusMovement($model, $oldStatus, $newStatus)
    {
        $statusLabels = InventorySerialNumber::getStatusList();
        $oldLabel = $statusLabels[$oldStatus] ?? $oldStatus;
        $newLabel = $statusLabels[$newStatus] ?? $newStatus;

        if ($newStatus === 'sold' && $oldStatus !== 'sold') {
            StockMovement::record($model->item_id, StockMovement::TYPE_OUT, 1, [
                'reference_type' => 'serial_status_change',
                'reference_id'   => $model->id,
                'company_id'     => $model->company_id ?? null,
                'notes'          => "تغيير حالة سيريال {$model->serial_number}: {$oldLabel} → {$newLabel}",
            ]);
        } elseif ($oldStatus === 'sold' && $newStatus !== 'sold') {
            StockMovement::record($model->item_id, StockMovement::TYPE_RETURN, 1, [
                'reference_type' => 'serial_status_change',
                'reference_id'   => $model->id,
                'company_id'     => $model->company_id ?? null,
                'notes'          => "تغيير حالة سيريال {$model->serial_number}: {$oldLabel} → {$newLabel}",
            ]);
        } elseif ($newStatus === 'defective' && $oldStatus !== 'defective') {
            StockMovement::record($model->item_id, StockMovement::TYPE_OUT, 1, [
                'reference_type' => 'serial_defective',
                'reference_id'   => $model->id,
                'company_id'     => $model->company_id ?? null,
                'notes'          => "جهاز معطّل: {$model->serial_number}",
            ]);
        } elseif ($oldStatus === 'defective' && $newStatus === 'available') {
            StockMovement::record($model->item_id, StockMovement::TYPE_RETURN, 1, [
                'reference_type' => 'serial_repaired',
                'reference_id'   => $model->id,
                'company_id'     => $model->company_id ?? null,
                'notes'          => "إصلاح جهاز: {$model->serial_number}",
            ]);
        }
    }

    /* ═══════════════════════════════════════════════════════════
     *  التصدير — Export Actions
     * ═══════════════════════════════════════════════════════════ */

    public function actionExportExcel()
    {
        $searchModel  = new InventoryItemsSearch();
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams);
        return $this->exportData($dataProvider, $this->getIndexExportConfig());
    }

    public function actionExportPdf()
    {
        $searchModel  = new InventoryItemsSearch();
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams);
        return $this->exportData($dataProvider, $this->getIndexExportConfig(), 'pdf');
    }

    public function actionExportItemsExcel()
    {
        $searchModel  = new InventoryItemsSearch();
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams);
        return $this->exportData($dataProvider, $this->getIndexExportConfig());
    }

    public function actionExportItemsPdf()
    {
        $searchModel  = new InventoryItemsSearch();
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams);
        return $this->exportData($dataProvider, $this->getIndexExportConfig(), 'pdf');
    }

    public function actionExportSerialsExcel()
    {
        $searchModel  = new InventorySerialNumberSearch();
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams);
        return $this->exportData($dataProvider, $this->getSerialsExportConfig());
    }

    public function actionExportSerialsPdf()
    {
        $searchModel  = new InventorySerialNumberSearch();
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams);
        return $this->exportData($dataProvider, $this->getSerialsExportConfig(), 'pdf');
    }

    public function actionExportQueryExcel()
    {
        $searchModel  = new InventoryItemsSearch();
        $dataProvider = $searchModel->itemQuery(Yii::$app->request->queryParams);
        return $this->exportData($dataProvider, $this->getQueryExportConfig());
    }

    public function actionExportQueryPdf()
    {
        $searchModel  = new InventoryItemsSearch();
        $dataProvider = $searchModel->itemQuery(Yii::$app->request->queryParams);
        return $this->exportData($dataProvider, $this->getQueryExportConfig(), 'pdf');
    }

    protected function getIndexExportConfig()
    {
        return [
            'title'    => 'أصناف المخزون',
            'filename' => 'inventory_items',
            'headers'  => ['#', 'اسم الصنف', 'الباركود', 'المخزون', 'السعر', 'الحالة', 'أنشئ بواسطة', 'التاريخ'],
            'widths'   => [6, 30, 20, 12, 14, 14, 18, 14],
            'keys'     => [
                '#',
                'item_name',
                'item_barcode',
                function ($model) {
                    return $model->getTotalStock();
                },
                function ($model) {
                    return $model->unit_price ? number_format($model->unit_price, 2) : '';
                },
                function ($model) {
                    return $model->getStatusLabel();
                },
                function ($model) {
                    return $model->createdBy ? $model->createdBy->username : '';
                },
                function ($model) {
                    return $model->created_at ? date('Y-m-d', $model->created_at) : '';
                },
            ],
        ];
    }

    protected function getSerialsExportConfig()
    {
        return [
            'title'    => 'الأرقام التسلسلية',
            'filename' => 'serial_numbers',
            'headers'  => ['#', 'الرقم التسلسلي', 'الصنف', 'الحالة', 'المورد', 'الموقع', 'ملاحظات', 'التاريخ'],
            'widths'   => [6, 24, 24, 14, 18, 18, 24, 14],
            'keys'     => [
                '#',
                'serial_number',
                function ($model) {
                    return $model->item ? $model->item->item_name : '';
                },
                function ($model) {
                    $statuses = InventorySerialNumber::getStatusList();
                    return $statuses[$model->status] ?? $model->status;
                },
                function ($model) {
                    return $model->supplier ? $model->supplier->name : '';
                },
                function ($model) {
                    return $model->location ? $model->location->locations_name : '';
                },
                function ($model) {
                    return $model->note ?? '';
                },
                function ($model) {
                    return $model->created_at ? date('Y-m-d', $model->created_at) : '';
                },
            ],
        ];
    }

    protected function getQueryExportConfig()
    {
        return [
            'title'    => 'استعلام الأصناف والكميات المتبقية',
            'filename' => 'item_query',
            'headers'  => ['#', 'اسم الصنف', 'الكمية المتبقية'],
            'widths'   => [6, 30, 18],
            'keys'     => [
                '#',
                'item_name',
                function ($model) {
                    return $model->remaining_amount;
                },
            ],
        ];
    }

    /* ═══ مساعدات ═══ */
    protected function isSupplierUser()
    {
        $u = Yii::$app->user;
        return !$u->can('العقود') && !$u->can('الحركات المالية') && $u->can('عناصر المخزون');
    }

    protected function findModel($id)
    {
        if (($model = InventoryItems::findOne($id)) !== null) {
            return $model;
        }
        throw new NotFoundHttpException('الصفحة المطلوبة غير موجودة.');
    }
}
