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
use yii\helpers\Url;
use yii\helpers\ArrayHelper;
use backend\modules\inventorySuppliers\models\InventorySuppliers;
use backend\modules\companies\models\Companies;
use common\helper\Permissions;
use common\models\WizardDraft;
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
                        'actions' => [
                            'create', 'create-wizard',
                            'save-wizard-draft', 'load-wizard-draft', 'clear-wizard-draft',
                            'list-wizard-drafts', 'save-wizard-draft-as',
                            'delete-wizard-draft', 'restore-wizard-draft',
                        ],
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
                    'reject-manager'       => ['post'],
                    'save-wizard-draft'    => ['post'],
                    'clear-wizard-draft'   => ['post'],
                    'save-wizard-draft-as' => ['post'],
                    'delete-wizard-draft'  => ['post'],
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
        $isAjax = $request->isAjax;

        if ($request->isPost) {
            $branchId = (int) $request->post('branch_id');
            $suppliersId = (int) ($request->post('suppliers_id') ?: 0);
            $companyId = (int) ($request->post('company_id') ?: 0);
            $rawItems = $request->post('ItemsInventoryInvoices', []);
            $errorMsg = null;

            if ($branchId <= 0) {
                $errorMsg = 'يرجى اختيار موقع التخزين.';
            } elseif ($suppliersId <= 0) {
                $errorMsg = 'يرجى اختيار المورد.';
            } elseif ($companyId <= 0) {
                $errorMsg = 'يرجى اختيار الشركة.';
            }

            if ($errorMsg) {
                if ($isAjax) {
                    Yii::$app->response->format = Response::FORMAT_JSON;
                    return ['ok' => false, 'msg' => $errorMsg];
                }
                Yii::$app->session->setFlash('error', $errorMsg);
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
                    $errorMsg = 'يرجى إضافة صنف واحد على الأقل في الخطوة 1 وتعبئة الكمية والسعر في الخطوة 2.';
                    if ($isAjax) {
                        Yii::$app->response->format = Response::FORMAT_JSON;
                        return ['ok' => false, 'msg' => $errorMsg];
                    }
                    Yii::$app->session->setFlash('error', $errorMsg);
                } else {
                    $rawSerials = $request->post('Serials', []);
                    $serialsValid = true;
                    $allSerials = [];
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
                        foreach ($serials as $sn) {
                            $allSerials[] = mb_substr((string) $sn, 0, 50);
                        }
                    }
                    if (!$serialsValid) {
                        $errorMsg = 'عدد الأرقام التسلسلية يجب أن يساوي الكمية بالضبط لكل صنف (لا أقل ولا أكثر).';
                        if ($isAjax) {
                            Yii::$app->response->format = Response::FORMAT_JSON;
                            return ['ok' => false, 'msg' => $errorMsg];
                        }
                        Yii::$app->session->setFlash('error', $errorMsg);
                    } else {

                    /* ── التحقق من تكرار السيريالات قبل الحفظ ── */
                    $batchDuplicates = array_diff_key($allSerials, array_unique($allSerials));
                    if (!empty($batchDuplicates)) {
                        $dupList = implode('، ', array_unique($batchDuplicates));
                        $errorMsg = 'يوجد أرقام تسلسلية مكررة ضمن الفاتورة نفسها: ' . $dupList;
                        if ($isAjax) {
                            Yii::$app->response->format = Response::FORMAT_JSON;
                            return ['ok' => false, 'msg' => $errorMsg];
                        }
                        Yii::$app->session->setFlash('error', $errorMsg);
                    } else {
                    $activeSerials = InventorySerialNumber::find()
                        ->select('serial_number')
                        ->where(['serial_number' => $allSerials])
                        ->column();
                    if (!empty($activeSerials)) {
                        $dupList = implode('، ', $activeSerials);
                        $errorMsg = 'الأرقام التسلسلية التالية مسجّلة مسبقاً في النظام: ' . $dupList;
                        if ($isAjax) {
                            Yii::$app->response->format = Response::FORMAT_JSON;
                            return ['ok' => false, 'msg' => $errorMsg];
                        }
                        Yii::$app->session->setFlash('error', $errorMsg);
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
                        }
                        /* حفظ الأرقام التسلسلية (إلزامي) — إعادة تفعيل المحذوف إن وُجد */
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
                                $snValue = mb_substr((string)$serials[$s], 0, 50);
                                $existing = InventorySerialNumber::findBySql(
                                    'SELECT * FROM ' . InventorySerialNumber::tableName() . ' WHERE serial_number = :sn AND is_deleted = 1 LIMIT 1',
                                    [':sn' => $snValue]
                                )->one();
                                if ($existing) {
                                    $existing->item_id = $itemId;
                                    $existing->company_id = $companyId;
                                    $existing->supplier_id = $supplierId;
                                    $existing->location_id = $locationId;
                                    $existing->status = InventorySerialNumber::STATUS_AVAILABLE;
                                    $existing->contract_id = null;
                                    $existing->sold_at = null;
                                    $existing->is_deleted = 0;
                                    if (!$existing->save(false)) {
                                        throw new \Exception('خطأ في إعادة تفعيل الرقم التسلسلي "' . $snValue . '"');
                                    }
                                } else {
                                    $sn = new InventorySerialNumber();
                                    $sn->item_id = $itemId;
                                    $sn->serial_number = $snValue;
                                    $sn->company_id = $companyId;
                                    $sn->supplier_id = $supplierId;
                                    $sn->location_id = $locationId;
                                    $sn->status = InventorySerialNumber::STATUS_AVAILABLE;
                                    if (!$sn->save()) {
                                        $snErrors = implode(' ', array_map(function($e){ return implode(' ', $e); }, $sn->getErrors()));
                                        throw new \Exception('خطأ في الرقم التسلسلي "' . $sn->serial_number . '": ' . $snErrors);
                                    }
                                }
                            }
                        }
                        $invoice->total_amount = $totalAmount;
                        $invoice->save(false);

                        $this->notifyApprovers($invoice, Notification::INVOICE_PENDING_RECEPTION,
                            'فاتورة توريد جديدة #' . $invoice->id . ' بانتظار الاستلام');
                        $transaction->commit();

                        if ($isAjax) {
                            Yii::$app->response->format = Response::FORMAT_JSON;
                            return ['ok' => true, 'redirect' => Url::to(['view', 'id' => $invoice->id])];
                        }
                        Yii::$app->session->setFlash('success', 'تم إرسال الفاتورة بنجاح.');
                        return $this->redirect(['view', 'id' => $invoice->id]);
                    } catch (\Exception $e) {
                        $transaction->rollBack();
                        $rawMsg = $e->getMessage();
                        if (strpos($rawMsg, 'Integrity constraint violation') !== false && strpos($rawMsg, 'uq_serial_number') !== false) {
                            preg_match("/Duplicate entry '([^']+)'/", $rawMsg, $m);
                            $dupSn = $m[1] ?? '';
                            $errorMsg = 'الرقم التسلسلي "' . $dupSn . '" مسجّل مسبقاً في النظام. يرجى التحقق وإزالة المكرر.';
                        } else {
                            $errorMsg = 'حدث خطأ أثناء حفظ الفاتورة. يرجى المحاولة مرة أخرى.';
                            Yii::error('Wizard save error: ' . $rawMsg, __METHOD__);
                        }
                        if ($isAjax) {
                            Yii::$app->response->format = Response::FORMAT_JSON;
                            return ['ok' => false, 'msg' => $errorMsg];
                        }
                        Yii::$app->session->setFlash('error', $errorMsg);
                    }
                    }
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

    /* ═══════════════════════════════════════════════════════════
     *  مسودة الويزارد — حفظ/تحميل/حذف (سيرفر)
     * ═══════════════════════════════════════════════════════════ */

    private const WIZARD_DRAFT_KEY = 'inv_wizard';

    public function actionSaveWizardDraft()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        $data = Yii::$app->request->post('draft_data', '');
        if (!$data) {
            return ['ok' => false, 'msg' => 'no data'];
        }
        $ok = WizardDraft::saveDraft(Yii::$app->user->id, self::WIZARD_DRAFT_KEY, $data);
        return ['ok' => $ok];
    }

    public function actionLoadWizardDraft()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        $draft = WizardDraft::loadDraft(Yii::$app->user->id, self::WIZARD_DRAFT_KEY);
        if (!$draft) {
            return ['ok' => true, 'data' => null];
        }
        return ['ok' => true, 'data' => json_decode($draft->draft_data, true)];
    }

    public function actionClearWizardDraft()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        WizardDraft::clearDraft(Yii::$app->user->id, self::WIZARD_DRAFT_KEY);
        return ['ok' => true];
    }

    /* ─── المسودات المحفوظة يدوياً (3 حد أقصى) ─── */

    public function actionListWizardDrafts()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        $drafts = WizardDraft::listSavedDrafts(Yii::$app->user->id, self::WIZARD_DRAFT_KEY);
        $result = [];
        foreach ($drafts as $d) {
            $result[] = [
                'id'      => $d->id,
                'label'   => $d->draft_label,
                'summary' => $d->items_summary,
                'date'    => date('Y-m-d H:i', $d->updated_at),
            ];
        }
        return ['ok' => true, 'drafts' => $result];
    }

    public function actionSaveWizardDraftAs()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        $data  = Yii::$app->request->post('draft_data', '');
        $label = trim((string) Yii::$app->request->post('draft_label', ''));
        if (!$data) {
            return ['ok' => false, 'msg' => 'no data'];
        }
        $ok = WizardDraft::saveDraftSlot(
            Yii::$app->user->id,
            self::WIZARD_DRAFT_KEY,
            $data,
            $label ?: null
        );
        return ['ok' => $ok];
    }

    public function actionDeleteWizardDraft()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        $draftId = (int) Yii::$app->request->post('draft_id', 0);
        if ($draftId <= 0) {
            return ['ok' => false];
        }
        WizardDraft::deleteSavedDraft(Yii::$app->user->id, self::WIZARD_DRAFT_KEY, $draftId);
        return ['ok' => true];
    }

    public function actionRestoreWizardDraft()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        $draftId = (int) Yii::$app->request->get('draft_id', 0);
        if ($draftId <= 0) {
            return ['ok' => false, 'data' => null];
        }
        $draft = WizardDraft::loadSavedDraft(Yii::$app->user->id, self::WIZARD_DRAFT_KEY, $draftId);
        if (!$draft) {
            return ['ok' => false, 'data' => null];
        }
        return ['ok' => true, 'data' => json_decode($draft->draft_data, true)];
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
     * جميع user_ids الذين يحملون تصنيف معيّن (slug)
     */
    protected function getUserIdsByCategory($slug)
    {
        $cat = \backend\models\UserCategory::find()->where(['slug' => $slug, 'is_active' => 1])->one();
        if (!$cat) return [];
        return \backend\models\UserCategoryMap::find()
            ->select('user_id')
            ->where(['category_id' => $cat->id])
            ->column();
    }

    /**
     * إشعار جميع المعتمدين (manager + sales_employee) بفاتورة جديدة أو تغيير حالة
     */
    protected function notifyApprovers($invoice, $notificationType, $title)
    {
        if (!Yii::$app->has('notifications')) return;
        $href = \yii\helpers\Url::to(['/inventoryInvoices/inventory-invoices/view', 'id' => $invoice->id]);
        $senderId = Yii::$app->user->id;
        $managerIds = $this->getUserIdsByCategory('manager');
        $salesIds = $this->getUserIdsByCategory('sales_employee');
        $recipientIds = array_unique(array_merge($managerIds, $salesIds));
        foreach ($recipientIds as $rid) {
            if ((int)$rid === (int)$senderId) continue;
            Yii::$app->notifications->add($href, $notificationType, $title, '', $senderId, (int)$rid);
        }
    }

    /**
     * إشعار مُنشئ الفاتورة بتحديث الحالة
     */
    protected function notifyCreator($invoice, $title)
    {
        if (!Yii::$app->has('notifications') || !$invoice->created_by) return;
        if ((int)$invoice->created_by === (int)Yii::$app->user->id) return;
        $href = \yii\helpers\Url::to(['/inventoryInvoices/inventory-invoices/view', 'id' => $invoice->id]);
        Yii::$app->notifications->add($href, Notification::GENERAL, $title, '', Yii::$app->user->id, (int)$invoice->created_by);
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
        if ($invoice->status !== InventoryInvoices::STATUS_PENDING_RECEPTION) {
            Yii::$app->session->setFlash('error', 'الفاتورة ليست بانتظار الاستلام.');
            return $this->redirect(['view', 'id' => $id]);
        }
        $invoice->status = InventoryInvoices::STATUS_PENDING_MANAGER;
        $invoice->approved_by = Yii::$app->user->id;
        $invoice->approved_at = time();
        $invoice->rejection_reason = null;
        if ($invoice->save(false)) {
            $this->notifyApprovers($invoice, Notification::INVOICE_PENDING_MANAGER,
                'فاتورة توريد #' . $invoice->id . ' بانتظار موافقة المدير');
            Yii::$app->session->setFlash('success', 'تمت الموافقة على الاستلام وتم إشعار المدير.');
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
        if ($invoice->status !== InventoryInvoices::STATUS_PENDING_RECEPTION) {
            Yii::$app->session->setFlash('error', 'الفاتورة ليست بانتظار الاستلام.');
            return $this->redirect(['view', 'id' => $id]);
        }
        $request = Yii::$app->request;
        if ($request->isPost) {
            $invoice->status = InventoryInvoices::STATUS_REJECTED_RECEPTION;
            $invoice->rejection_reason = trim((string) $request->post('rejection_reason', ''));
            $invoice->approved_by = Yii::$app->user->id;
            $invoice->approved_at = time();
            $invoice->save(false);
            $this->notifyCreator($invoice, 'تم رفض استلام الفاتورة #' . $invoice->id . ': ' . $invoice->rejection_reason);
            Yii::$app->session->setFlash('success', 'تم رفض الاستلام وإشعار مُنشئ الفاتورة.');
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
                $this->notifyCreator($invoice, 'تمت الموافقة النهائية على الفاتورة #' . $invoice->id . ' وترحيلها إلى المخزون');
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
        $reason = trim((string) Yii::$app->request->post('rejection_reason', ''));
        $invoice->status = InventoryInvoices::STATUS_REJECTED_MANAGER;
        $invoice->rejection_reason = $reason;
        $invoice->approved_by = Yii::$app->user->id;
        $invoice->approved_at = time();
        if ($invoice->save(false)) {
            $this->notifyCreator($invoice, 'تم رفض الفاتورة #' . $invoice->id . ' من المدير' . ($reason ? ': ' . $reason : ''));
            Yii::$app->session->setFlash('success', 'تم رفض الفاتورة وإشعار مُنشئها.');
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
