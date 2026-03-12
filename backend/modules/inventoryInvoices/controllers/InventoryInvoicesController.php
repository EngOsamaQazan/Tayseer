<?php
/**
 * ═══════════════════════════════════════════════════════════════
 *  كونترولر أوامر الشراء v2 — مصلح ومعاد بناؤه
 *  ─────────────────────────────────────────────────────────────
 *  إصلاحات: AccessControl, var_dump, undefined vars, quantity logic
 * ═══════════════════════════════════════════════════════════════
 */

namespace backend\modules\inventoryInvoices\controllers;

use Yii;
use backend\modules\inventoryInvoices\models\InventoryInvoices;
use backend\modules\inventoryInvoices\models\InventoryInvoicesSearch;
use backend\modules\inventoryInvoices\services\InventoryInvoicePostingService;
use backend\modules\inventoryItemQuantities\models\InventoryItemQuantities;
use backend\modules\itemsInventoryInvoices\models\ItemsInventoryInvoices;
use backend\modules\inventoryItems\models\StockMovement;
use backend\modules\inventoryItems\models\InventorySerialNumber;
use backend\modules\inventoryStockLocations\models\InventoryStockLocations;
use backend\modules\notification\models\Notification;
use common\models\Model;
use yii\filters\AccessControl;
use yii\web\Controller;
use yii\web\ForbiddenHttpException;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;
use yii\web\Response;
use yii\helpers\Html;
use yii\helpers\ArrayHelper;
use backend\modules\inventorySuppliers\models\InventorySuppliers;
use backend\modules\companies\models\Companies;
use common\helper\Permissions;
use backend\helpers\ExportTrait;

class InventoryInvoicesController extends Controller
{
    use ExportTrait;
    /* مصلح: كان بدون AccessControl */
    public function behaviors()
    {
        return [
            'access' => [
                'class' => AccessControl::class,
                'rules' => [
                    ['actions' => ['login', 'error'], 'allow' => true],
                    [
                        'actions' => ['index', 'view', 'export-excel', 'export-pdf'],
                        'allow' => true,
                        'roles' => ['@'],
                        'matchCallback' => function () {
                            return Permissions::can(Permissions::INVINV_VIEW);
                        },
                    ],
                    [
                        'actions' => ['create', 'create-wizard'],
                        'allow' => true,
                        'roles' => ['@'],
                        'matchCallback' => function () {
                            return Permissions::can(Permissions::INVINV_CREATE);
                        },
                    ],
                    [
                        'actions' => ['update'],
                        'allow' => true,
                        'roles' => ['@'],
                        'matchCallback' => function () {
                            return Permissions::can(Permissions::INVINV_UPDATE);
                        },
                    ],
                    [
                        'actions' => ['delete', 'bulk-delete'],
                        'allow' => true,
                        'roles' => ['@'],
                        'matchCallback' => function () {
                            return Permissions::can(Permissions::INVINV_DELETE);
                        },
                    ],
                    [
                        'actions' => ['approve-reception', 'reject-reception', 'approve-manager', 'reject-manager'],
                        'allow' => true,
                        'roles' => ['@'],
                        'matchCallback' => function () {
                            return Permissions::can(Permissions::INVINV_APPROVE);
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
                    'delete'           => ['post'],
                    'bulk-delete'      => ['post'],
                    'approve-reception' => ['post'],
                    'reject-reception' => ['get', 'post'],
                    'approve-manager'  => ['post'],
                    'reject-manager'   => ['post'],
                ],
            ],
        ];
    }

    public function actionIndex()
    {
        $params = Yii::$app->request->queryParams;
        $searchModel = new InventoryInvoicesSearch();
        $dataProvider = $searchModel->search($params);
        $isVendor = $this->isVendorUser();

        return $this->render('index', [
            'searchModel'  => $searchModel,
            'dataProvider' => $dataProvider,
            'isVendor'     => $isVendor,
        ]);
    }

    public function actionExportExcel()
    {
        $searchModel = new InventoryInvoicesSearch();
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams);

        return $this->exportData($dataProvider, $this->getExportConfig());
    }

    public function actionExportPdf()
    {
        $searchModel = new InventoryInvoicesSearch();
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams);

        return $this->exportData($dataProvider, $this->getExportConfig(), 'pdf');
    }

    protected function getExportConfig()
    {
        return [
            'title' => 'أوامر الشراء',
            'filename' => 'purchase_orders',
            'headers' => ['#', 'رقم الأمر', 'موقع التخزين', 'المورد', 'الشركة', 'نوع الدفع', 'المبلغ', 'التاريخ', 'بواسطة'],
            'keys' => [
                '#',
                'id',
                function ($model) {
                    return $model->stockLocation ? $model->stockLocation->locations_name : '—';
                },
                function ($model) {
                    return $model->suppliers ? $model->suppliers->name : '—';
                },
                function ($model) {
                    return $model->company ? $model->company->name : '—';
                },
                function ($model) {
                    return $model->getTypeLabel();
                },
                function ($model) {
                    return $model->total_amount ? number_format($model->total_amount, 2) : '—';
                },
                function ($model) {
                    return $model->date ?: ($model->created_at ? date('Y-m-d', $model->created_at) : '—');
                },
                function ($model) {
                    return $model->createdBy ? $model->createdBy->username : '—';
                },
            ],
            'widths' => [6, 12, 22, 22, 22, 14, 16, 14, 16],
        ];
    }

    /**
     * معالج (Wizard) إضافة فاتورة توريد جديدة — للمورد: بحث/إضافة أصناف، بيانات الفاتورة، أسعار، سيريالات، إنهاء.
     */
    public function actionCreateWizard()
    {
        $activeBranches = $this->getActiveBranches();
        $allSuppliers = InventorySuppliers::find()->orderBy(['name' => SORT_ASC])->all();
        $suppliersList = [];
        foreach ($allSuppliers as $sup) {
            $suppliersList[$sup->id] = $sup->name . ($sup->isSystemUser ? ' ✓' : '');
        }
        $companiesList = ArrayHelper::map(Companies::find()->orderBy(['name' => SORT_ASC])->all(), 'id', 'name');
        $request = Yii::$app->request;

        if ($request->isPost) {
            $branchId = (int) $request->post('branch_id');
            $suppliersId = (int) ($request->post('suppliers_id') ?: 0);
            $companyId = (int) ($request->post('company_id') ?: 0);
            $rawItems = $request->post('ItemsInventoryInvoices', []);

            if ($branchId <= 0) {
                Yii::$app->session->setFlash('error', 'يرجى اختيار موقع التخزين.');
            } elseif ($suppliersId <= 0) {
                Yii::$app->session->setFlash('error', 'يرجى اختيار المورد.');
            } elseif ($companyId <= 0) {
                Yii::$app->session->setFlash('error', 'يرجى اختيار الشركة.');
            } else {
                $lineItems = [];
                foreach ($rawItems as $row) {
                    $itemId = (int) ($row['inventory_items_id'] ?? 0);
                    $qty = (int) ($row['number'] ?? 0);
                    $price = (float) ($row['single_price'] ?? 0);
                    if ($itemId <= 0 || $qty <= 0 || $price < 0) continue;
                    $lineItems[] = [
                        'inventory_items_id' => $itemId,
                        'number' => $qty,
                        'single_price' => $price,
                    ];
                }
                if (empty($lineItems)) {
                    Yii::$app->session->setFlash('error', 'يرجى إضافة صنف واحد على الأقل في الخطوة 1 وتعبئة الكمية والسعر في الخطوة 2.');
                } else {
                    $rawSerials = $request->post('Serials', []);
                    $serialsValid = true;
                    foreach ($lineItems as $idx => $row) {
                        $serialLines = isset($rawSerials[$idx]) ? $rawSerials[$idx] : '';
                        if (is_array($serialLines)) {
                            $serialLines = implode("\n", $serialLines);
                        }
                        $serials = array_values(array_filter(array_map('trim', explode("\n", (string) $serialLines))));
                        if (count($serials) !== (int) $row['number']) {
                            $serialsValid = false;
                            break;
                        }
                    }
                    if (!$serialsValid) {
                        Yii::$app->session->setFlash('error', 'عدد الأرقام التسلسلية يجب أن يساوي الكمية بالضبط لكل صنف (لا أقل ولا أكثر).');
                    } else {
                    $invoice = new InventoryInvoices();
                    $invoice->branch_id = $branchId;
                    $invoice->status = InventoryInvoices::STATUS_PENDING_RECEPTION;
                    $invoice->suppliers_id = $suppliersId;
                    $invoice->company_id = $companyId;
                    $invoice->type = (int) ($request->post('type') ?: InventoryInvoices::TYPE_CASH);
                    $invoice->date = $request->post('date') ?: date('Y-m-d');
                    $invoice->invoice_notes = trim((string) $request->post('invoice_notes', ''));

                    $transaction = Yii::$app->db->beginTransaction();
                    try {
                        if (!$invoice->save(false)) {
                            throw new \Exception('فشل حفظ الفاتورة.');
                        }
                        $totalAmount = 0;
                        foreach ($lineItems as $row) {
                            $lineItem = new ItemsInventoryInvoices();
                            $lineItem->inventory_invoices_id = $invoice->id;
                            $lineItem->inventory_items_id = $row['inventory_items_id'];
                            $lineItem->number = $row['number'];
                            $lineItem->single_price = $row['single_price'];
                            $lineItem->total_amount = (int) round($lineItem->single_price * $lineItem->number);
                            $totalAmount += $lineItem->total_amount;
                            if (!$lineItem->save(false)) {
                                throw new \Exception('فشل حفظ بند الفاتورة');
                            }
                            $this->updateItemQuantity($invoice, $lineItem, 'add');
                            StockMovement::record($lineItem->inventory_items_id, StockMovement::TYPE_IN, $lineItem->number, [
                                'reference_type' => 'invoice',
                                'reference_id'   => $invoice->id,
                                'unit_cost'      => $lineItem->single_price,
                                'supplier_id'    => $invoice->suppliers_id,
                                'company_id'     => $invoice->company_id,
                            ]);
                        }
                        /* حفظ الأرقام التسلسلية (إلزامي) */
                        $companyId = (int) ($invoice->company_id ?: 0);
                        $supplierId = (int) ($invoice->suppliers_id ?: 0);
                        $locationId = (int) ($invoice->branch_id ?: 0);
                        foreach ($lineItems as $idx => $row) {
                            $serialLines = isset($rawSerials[$idx]) ? $rawSerials[$idx] : '';
                            if (is_array($serialLines)) {
                                $serialLines = implode("\n", $serialLines);
                            }
                            $serials = array_values(array_filter(array_map('trim', explode("\n", (string) $serialLines))));
                            $qty = (int) $row['number'];
                            $itemId = (int) $row['inventory_items_id'];
                            for ($s = 0; $s < $qty && isset($serials[$s]); $s++) {
                                $sn = new InventorySerialNumber();
                                $sn->item_id = $itemId;
                                $sn->serial_number = mb_substr((string)$serials[$s], 0, 50);
                                $sn->company_id = $companyId;
                                $sn->supplier_id = $supplierId;
                                $sn->location_id = $locationId;
                                $sn->status = InventorySerialNumber::STATUS_AVAILABLE;
                                if (!$sn->save(false)) {
                                    throw new \Exception('فشل حفظ الرقم التسلسلي: ' . $sn->serial_number);
                                }
                            }
                        }
                        $invoice->total_amount = $totalAmount;
                        $invoice->save(false);

                        $recipientId = $this->getBranchSalesUserId($invoice->branch_id);
                        if ($recipientId && Yii::$app->has('notifications')) {
                            $locName = $invoice->stockLocation ? $invoice->stockLocation->locations_name : '';
                            $href = \yii\helpers\Url::to(['/inventoryInvoices/inventory-invoices/view', 'id' => $invoice->id]);
                            Yii::$app->notifications->add(
                                $href,
                                Notification::INVOICE_PENDING_RECEPTION,
                                'فاتورة توريد جديدة #' . $invoice->id . ' بانتظار الاستلام - موقع: ' . $locName,
                                '',
                                Yii::$app->user->id,
                                $recipientId
                            );
                        }
                        $transaction->commit();
                        Yii::$app->session->setFlash('success', 'تم إرسال الفاتورة بنجاح.');
                        return $this->redirect(['view', 'id' => $invoice->id]);
                    } catch (\Exception $e) {
                        $transaction->rollBack();
                        Yii::$app->session->setFlash('error', 'خطأ: ' . $e->getMessage());
                    }
                    }
                }
            }
        }

        return $this->render('create-wizard', [
            'activeBranches' => $activeBranches,
            'suppliersList'  => $suppliersList,
            'companiesList'  => $companiesList,
        ]);
    }

    protected function isVendorUser()
    {
        $userId = Yii::$app->user->id;
        if (!$userId) return false;
        $vendorCat = \backend\models\UserCategory::find()->where(['slug' => 'vendor', 'is_active' => 1])->one();
        if (!$vendorCat) return false;
        return \backend\models\UserCategoryMap::find()
            ->where(['user_id' => $userId, 'category_id' => $vendorCat->id])
            ->exists();
    }

    /**
     * User ID of branch sales (sales_employee) for the given branch, or null.
     */
    protected function getBranchSalesUserId($branchId)
    {
        if (!$branchId) return null;
        $cat = \backend\models\UserCategory::find()->where(['slug' => 'sales_employee', 'is_active' => 1])->one();
        if (!$cat) return null;
        $user = \common\models\User::find()
            ->alias('u')
            ->innerJoin(\backend\models\UserCategoryMap::tableName() . ' m', 'm.user_id = u.id')
            ->where(['u.location' => $branchId, 'm.category_id' => $cat->id])
            ->one();
        return $user ? (int) $user->id : null;
    }

    /**
     * User ID of system manager (e.g. first user with admin role), or null.
     */
    protected function getSystemManagerUserId()
    {
        $userId = Yii::$app->params['systemManagerUserId'] ?? null;
        if ($userId) return (int) $userId;
        $assignment = \backend\modules\authAssignment\models\AuthAssignment::find()
            ->where(['item_name' => 'admin'])
            ->one();
        return $assignment ? (int) $assignment->user_id : null;
    }

    /**
     * مواقع التخزين النشطة لقائمة الويزارد المنسدلة.
     */
    protected function getActiveBranches()
    {
        return InventoryStockLocations::find()
            ->orderBy(['locations_name' => SORT_ASC])
            ->all();
    }

    public function actionView($id)
    {
        $request = Yii::$app->request;
        $model = $this->findModel($id);

        if ($request->isAjax) {
            Yii::$app->response->format = Response::FORMAT_JSON;
            return [
                'title'   => 'أمر شراء #' . $id,
                'content' => $this->renderAjax('view', ['model' => $model]),
                'footer'  => Html::button('إغلاق', ['class' => 'btn btn-default pull-left', 'data-dismiss' => 'modal']) .
                             Html::a('تعديل', ['update', 'id' => $id], ['class' => 'btn btn-primary', 'role' => 'modal-remote']),
            ];
        }
        return $this->render('view', ['model' => $model]);
    }

    /**
     * إنشاء أمر شراء جديد — معاد بناؤه بالكامل
     * يحفظ الفاتورة + بنود الأصناف + يحدث الكميات + يسجل الحركات
     */
    public function actionCreate()
    {
        $request = Yii::$app->request;
        $model = new InventoryInvoices();
        $itemsInventoryInvoices = [new ItemsInventoryInvoices];

        if ($request->isAjax) {
            Yii::$app->response->format = Response::FORMAT_JSON;
            if ($request->isGet) {
                return [
                    'title'   => 'أمر شراء جديد',
                    'content' => $this->renderAjax('create', ['model' => $model]),
                    'footer'  => Html::button('إغلاق', ['class' => 'btn btn-default pull-left', 'data-dismiss' => 'modal']) .
                                 Html::button('حفظ', ['class' => 'btn btn-primary', 'type' => 'submit']),
                ];
            }
            if ($model->load($request->post()) && $model->save()) {
                return [
                    'forceReload' => '#crud-datatable-pjax',
                    'title'       => 'أمر شراء جديد',
                    'content'     => '<span class="text-success">تم إنشاء أمر الشراء بنجاح</span>',
                    'footer'      => Html::button('إغلاق', ['class' => 'btn btn-default pull-left', 'data-dismiss' => 'modal']),
                ];
            }
            return [
                'title'   => 'أمر شراء جديد',
                'content' => $this->renderAjax('create', ['model' => $model]),
                'footer'  => Html::button('إغلاق', ['class' => 'btn btn-default pull-left', 'data-dismiss' => 'modal']) .
                             Html::button('حفظ', ['class' => 'btn btn-primary', 'type' => 'submit']),
            ];
        }

        /* Non-AJAX: full form with line items */
        if ($model->load($request->post())) {
            $itemsInventoryInvoices = Model::createMultiple(ItemsInventoryInvoices::class);
            Model::loadMultiple($itemsInventoryInvoices, $request->post());

            $valid = $model->validate();
            $valid = Model::validateMultiple($itemsInventoryInvoices) && $valid;

            if ($valid) {
                $transaction = Yii::$app->db->beginTransaction();
                try {
                    if ($model->save(false)) {
                        $totalAmount = 0;

                        foreach ($itemsInventoryInvoices as $lineItem) {
                            $lineItem->inventory_invoices_id = $model->id;
                            $lineItem->total_amount = $lineItem->single_price * $lineItem->number;
                            $totalAmount += $lineItem->total_amount;

                            if (!$lineItem->save(false)) {
                                throw new \Exception('فشل حفظ بند الفاتورة');
                            }

                            /* تحديث الكمية */
                            $this->updateItemQuantity($model, $lineItem, 'add');

                            /* تسجيل حركة مخزون */
                            StockMovement::record($lineItem->inventory_items_id, StockMovement::TYPE_IN, $lineItem->number, [
                                'reference_type' => 'invoice',
                                'reference_id'   => $model->id,
                                'unit_cost'      => $lineItem->single_price,
                                'supplier_id'    => $model->suppliers_id,
                                'company_id'     => $model->company_id,
                            ]);
                        }

                        /* تحديث إجمالي الفاتورة */
                        $model->total_amount = $totalAmount;
                        $model->save(false);

                        $transaction->commit();
                        Yii::$app->session->setFlash('success', 'تم إنشاء أمر الشراء بنجاح');
                        return $this->redirect(['index']);
                    }
                } catch (\Exception $e) {
                    $transaction->rollBack();
                    Yii::$app->session->setFlash('error', 'خطأ: ' . $e->getMessage());
                }
            }
        }

        return $this->render('create', [
            'model' => $model,
            'itemsInventoryInvoices' => $itemsInventoryInvoices,
        ]);
    }

    /**
     * تعديل أمر شراء — معاد بناؤه بالكامل
     */
    public function actionUpdate($id)
    {
        $request = Yii::$app->request;
        $model = $this->findModel($id);
        $itemsInventoryInvoices = ItemsInventoryInvoices::find()
            ->where(['inventory_invoices_id' => $id])
            ->all();

        if ($request->isAjax) {
            Yii::$app->response->format = Response::FORMAT_JSON;
            if ($request->isGet) {
                return [
                    'title'   => 'تعديل أمر الشراء #' . $id,
                    'content' => $this->renderAjax('update', ['model' => $model]),
                    'footer'  => Html::button('إغلاق', ['class' => 'btn btn-default pull-left', 'data-dismiss' => 'modal']) .
                                 Html::button('حفظ', ['class' => 'btn btn-primary', 'type' => 'submit']),
                ];
            }
            if ($model->load($request->post()) && $model->save()) {
                return [
                    'forceReload' => '#crud-datatable-pjax',
                    'title'       => 'أمر الشراء #' . $id,
                    'content'     => $this->renderAjax('view', ['model' => $model]),
                    'footer'      => Html::button('إغلاق', ['class' => 'btn btn-default pull-left', 'data-dismiss' => 'modal']),
                ];
            }
            return [
                'title'   => 'تعديل أمر الشراء #' . $id,
                'content' => $this->renderAjax('update', ['model' => $model]),
                'footer'  => Html::button('إغلاق', ['class' => 'btn btn-default pull-left', 'data-dismiss' => 'modal']) .
                             Html::button('حفظ', ['class' => 'btn btn-primary', 'type' => 'submit']),
            ];
        }

        /* Non-AJAX */
        if ($model->load($request->post())) {
            $oldLineItems = $itemsInventoryInvoices;
            $oldIDs = ArrayHelper::map($oldLineItems, 'id', 'id');

            $itemsInventoryInvoices = Model::createMultiple(ItemsInventoryInvoices::class, $itemsInventoryInvoices);
            Model::loadMultiple($itemsInventoryInvoices, $request->post());
            $deletedIDs = array_diff($oldIDs, array_filter(ArrayHelper::map($itemsInventoryInvoices, 'id', 'id')));

            $transaction = Yii::$app->db->beginTransaction();
            try {
                if ($model->save(false)) {
                    /* إزالة الكميات القديمة */
                    foreach ($oldLineItems as $old) {
                        $this->updateItemQuantity($model, $old, 'subtract');
                    }

                    /* حذف البنود المحذوفة */
                    if (!empty($deletedIDs)) {
                        ItemsInventoryInvoices::deleteAll(['id' => $deletedIDs]);
                    }

                    /* حفظ البنود الجديدة/المعدلة */
                    $totalAmount = 0;
                    foreach ($itemsInventoryInvoices as $lineItem) {
                        $lineItem->inventory_invoices_id = $model->id;
                        $lineItem->total_amount = $lineItem->single_price * $lineItem->number;
                        $totalAmount += $lineItem->total_amount;

                        if (!$lineItem->save(false)) {
                            throw new \Exception('فشل حفظ بند الفاتورة');
                        }

                        /* إضافة الكميات الجديدة */
                        $this->updateItemQuantity($model, $lineItem, 'add');
                    }

                    $discount = (float) ($model->discount_amount ?? 0);
                    $model->total_amount = max(0, $totalAmount - $discount);
                    $model->save(false);

                    $transaction->commit();
                    Yii::$app->session->setFlash('success', 'تم تحديث أمر الشراء بنجاح');
                    return $this->redirect(['index']);
                }
            } catch (\Exception $e) {
                $transaction->rollBack();
                Yii::$app->session->setFlash('error', 'خطأ: ' . $e->getMessage());
            }
        }

        return $this->render('update', [
            'model' => $model,
            'itemsInventoryInvoices' => empty($itemsInventoryInvoices) ? [new ItemsInventoryInvoices] : $itemsInventoryInvoices,
        ]);
    }

    /**
     * حذف أمر شراء — مصلح بالكامل
     */
    public function actionDelete($id)
    {
        $model = $this->findModel($id);
        $lineItems = ItemsInventoryInvoices::find()
            ->where(['inventory_invoices_id' => $id])
            ->all();

        $transaction = Yii::$app->db->beginTransaction();
        try {
            /* إزالة الكميات لكل بند */
            foreach ($lineItems as $lineItem) {
                $this->updateItemQuantity($model, $lineItem, 'subtract');

                /* تسجيل حركة إلغاء */
                StockMovement::record($lineItem->inventory_items_id, StockMovement::TYPE_OUT, $lineItem->number, [
                    'reference_type' => 'invoice_cancel',
                    'reference_id'   => $model->id,
                    'notes'          => 'إلغاء أمر شراء #' . $model->id,
                    'company_id'     => $model->company_id,
                ]);

                $lineItem->delete();
            }
            $model->delete();
            $transaction->commit();
        } catch (\Exception $e) {
            $transaction->rollBack();
            Yii::$app->session->setFlash('error', 'خطأ في الحذف: ' . $e->getMessage());
        }

        if (Yii::$app->request->isAjax) {
            Yii::$app->response->format = Response::FORMAT_JSON;
            return ['forceClose' => true, 'forceReload' => '#crud-datatable-pjax'];
        }
        return $this->redirect(['index']);
    }

    public function actionBulkDelete()
    {
        $raw = Yii::$app->request->post('pks');
        if ($raw === null || $raw === '') {
            return $this->redirect(['index']);
        }
        $pks = is_array($raw) ? $raw : explode(',', (string)$raw);
        foreach ($pks as $pk) {
            $this->actionDelete($pk);
        }

        if (Yii::$app->request->isAjax) {
            Yii::$app->response->format = Response::FORMAT_JSON;
            return ['forceClose' => true, 'forceReload' => '#crud-datatable-pjax'];
        }
        return $this->redirect(['index']);
    }

    /**
     * موافقة مسؤولة الفرع (استلام) — التحقق من الفرع داخل الـ action إلزامي.
     */
    public function actionApproveReception($id)
    {
        $invoice = $this->findModel($id);
        $user = Yii::$app->user->identity;
        if (!$user || !$user->hasCategory('sales_employee')) {
            throw new ForbiddenHttpException('غير مصرح لك بالموافقة على هذه الفاتورة.');
        }
        if ($invoice->status !== InventoryInvoices::STATUS_PENDING_RECEPTION) {
            Yii::$app->session->setFlash('error', 'الفاتورة ليست بانتظار الاستلام.');
            return $this->redirect(['view', 'id' => $id]);
        }
        $invoice->status = InventoryInvoices::STATUS_PENDING_MANAGER;
        $invoice->approved_by = Yii::$app->user->id;
        $invoice->approved_at = time();
        if ($invoice->save(false)) {
            $managerId = $this->getSystemManagerUserId();
            if ($managerId && Yii::$app->has('notifications')) {
                $locName = $invoice->stockLocation ? $invoice->stockLocation->locations_name : '';
                $href = \yii\helpers\Url::to(['/inventoryInvoices/inventory-invoices/view', 'id' => $invoice->id]);
                Yii::$app->notifications->add(
                    $href,
                    Notification::INVOICE_PENDING_MANAGER,
                    'فاتورة توريد بانتظار موافقة المدير - موقع: ' . $locName,
                    '',
                    Yii::$app->user->id,
                    $managerId
                );
            }
            Yii::$app->session->setFlash('success', 'تمت الموافقة وتم إشعار المدير.');
        } else {
            Yii::$app->session->setFlash('error', 'فشل تحديث الحالة.');
        }
        return $this->redirect(['view', 'id' => $id]);
    }

    /**
     * رفض استلام من مسؤول الفرع — إدخال سبب الرفض، يبقى الحساب بانتظار الاستلام لاحتمال التعديل ثم الموافقة مجدداً.
     */
    public function actionRejectReception($id)
    {
        $invoice = $this->findModel($id);
        $user = Yii::$app->user->identity;
        if (!$user || !$user->hasCategory('sales_employee')) {
            throw new ForbiddenHttpException('غير مصرح لك برفض هذه الفاتورة.');
        }
        if ($invoice->status !== InventoryInvoices::STATUS_PENDING_RECEPTION) {
            Yii::$app->session->setFlash('error', 'الفاتورة ليست بانتظار الاستلام.');
            return $this->redirect(['view', 'id' => $id]);
        }
        $request = Yii::$app->request;
        if ($request->isPost) {
            $invoice->rejection_reason = trim((string) $request->post('rejection_reason', ''));
            $invoice->save(false);
            Yii::$app->session->setFlash('success', 'تم تسجيل رفض الاستلام. يمكن للمورد التعديل ثم إعادة الإرسال، أو يمكنك الموافقة بعد التعديل.');
            return $this->redirect(['view', 'id' => $id]);
        }
        return $this->render('reject-reception', ['model' => $invoice]);
    }

    /**
     * موافقة المدير النهائية — تحديث الحالة ثم استدعاء Posting Service.
     */
    public function actionApproveManager($id)
    {
        $invoice = $this->findModel($id);
        if ($invoice->status !== InventoryInvoices::STATUS_PENDING_MANAGER) {
            Yii::$app->session->setFlash('error', 'الفاتورة ليست بانتظار موافقة المدير.');
            return $this->redirect(['view', 'id' => $id]);
        }
        $invoice->status = InventoryInvoices::STATUS_APPROVED_FINAL;
        $invoice->approved_by = Yii::$app->user->id;
        $invoice->approved_at = time();
        if ($invoice->save(false)) {
            try {
                InventoryInvoicePostingService::post($invoice->id);
                Yii::$app->session->setFlash('success', 'تمت الموافقة وترحيل الفاتورة إلى المخزون.');
            } catch (\Exception $e) {
                Yii::$app->session->setFlash('error', 'تمت الموافقة لكن فشل الترحيل: ' . $e->getMessage());
            }
        } else {
            Yii::$app->session->setFlash('error', 'فشل تحديث الحالة.');
        }
        return $this->redirect(['view', 'id' => $id]);
    }

    /**
     * رفض المدير.
     */
    public function actionRejectManager($id)
    {
        $invoice = $this->findModel($id);
        if ($invoice->status !== InventoryInvoices::STATUS_PENDING_MANAGER) {
            Yii::$app->session->setFlash('error', 'الفاتورة ليست بانتظار موافقة المدير.');
            return $this->redirect(['view', 'id' => $id]);
        }
        $reason = Yii::$app->request->post('rejection_reason', '');
        $invoice->status = InventoryInvoices::STATUS_REJECTED_MANAGER;
        $invoice->rejection_reason = $reason;
        $invoice->approved_by = Yii::$app->user->id;
        $invoice->approved_at = time();
        if ($invoice->save(false)) {
            Yii::$app->session->setFlash('success', 'تم رفض الفاتورة.');
        }
        return $this->redirect(['view', 'id' => $id]);
    }

    /* ═══════════════════════════════════════════════════════════
     *  مساعدات — تحديث الكميات (مصلح بالكامل)
     * ═══════════════════════════════════════════════════════════ */

    /**
     * تحديث كمية الصنف في المخزون
     * @param InventoryInvoices $invoice
     * @param ItemsInventoryInvoices $lineItem
     * @param string $operation 'add' | 'subtract'
     */
    private function updateItemQuantity($invoice, $lineItem, $operation)
    {
        if (!$lineItem->inventory_items_id || !$lineItem->number) return;

        $qtyRecord = InventoryItemQuantities::find()
            ->where(['item_id' => $lineItem->inventory_items_id, 'is_deleted' => 0])
            ->andFilterWhere(['company_id' => $invoice->company_id])
            ->one();

        if ($operation === 'add') {
            if ($qtyRecord) {
                $qtyRecord->quantity += $lineItem->number;
                $qtyRecord->save(false);
            } else {
                $qtyRecord = new InventoryItemQuantities();
                $qtyRecord->item_id      = $lineItem->inventory_items_id;
                $qtyRecord->quantity      = $lineItem->number;
                $qtyRecord->company_id    = $invoice->company_id;
                $qtyRecord->suppliers_id  = $invoice->suppliers_id ?: 0;
                $qtyRecord->locations_id  = $invoice->branch_id ?: 0;
                $qtyRecord->save(false);
            }
        } elseif ($operation === 'subtract') {
            if ($qtyRecord) {
                $qtyRecord->quantity = max(0, $qtyRecord->quantity - $lineItem->number);
                if ($qtyRecord->quantity <= 0) {
                    $qtyRecord->delete();
                } else {
                    $qtyRecord->save(false);
                }
            }
        }
    }

    protected function findModel($id)
    {
        if (($model = InventoryInvoices::findOne($id)) !== null) {
            return $model;
        }
        throw new NotFoundHttpException('الصفحة المطلوبة غير موجودة.');
    }
}
