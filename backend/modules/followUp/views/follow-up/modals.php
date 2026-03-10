<?php
/**
 * نوافذ منبثقة لشاشة المتابعة - بناء من الصفر
 * تشمل: إرسال رسالة، تغيير حالة العقد، صور العملاء، بيانات العميل، التدقيق، التسوية
 */
use yii\helpers\Html;
use yii\helpers\Url;
use backend\modules\contracts\models\Contracts;

$contractModel = $contractCalculations->contract_model;
?>

<!-- ═══ نافذة إرسال رسالة SMS ═══ -->
<div class="modal fade" id="smsModal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
                <h4 class="modal-title"><i class="fa fa-envelope"></i> إرسال رسالة نصية</h4>
            </div>
            <div class="modal-body">
                <input type="hidden" id="phone_number" value="0">
                <div class="form-group">
                    <label>نص الرسالة</label>
                    <textarea id="sms_text" class="form-control" rows="4" placeholder="اكتب نص الرسالة هنا..."></textarea>
                </div>
                <div class="text-muted">عدد الأحرف: <span id="char_count">0</span></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal"><i class="fa fa-times"></i> إلغاء</button>
                <button type="button" class="btn btn-primary" id="send_sms" data-dismiss="modal"><i class="fa fa-paper-plane"></i> إرسال</button>
            </div>
        </div>
    </div>
</div>

<!-- ═══ نافذة تغيير حالة العقد ═══ -->
<div class="modal fade" id="changeStatusModal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
                <h4 class="modal-title"><i class="fa fa-exchange"></i> تغيير حالة العقد</h4>
            </div>
            <div class="modal-body">
                <div class="form-group">
                    <label>الحالة الجديدة</label>
                    <select class="form-control status-content">
                        <option value="canceled">إلغاء العقد</option>
                        <option value="legal_department_toggle">تحويل / إلغاء تحويل للدائرة القانونية</option>
                    </select>
                    <p class="help-block" style="margin-top:8px;color:#888">
                        <i class="fa fa-info-circle"></i>
                        الحالات الأخرى (قضائي، منتهي، تسوية) تُحسب تلقائياً بناءً على الإجراءات الفعلية
                    </p>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal"><i class="fa fa-times"></i> إلغاء</button>
                <button type="button" class="btn btn-primary statse-change" contract-id="<?= $contractModel->id ?>">
                    <i class="fa fa-save"></i> حفظ التغيير
                </button>
            </div>
        </div>
    </div>
</div>

<!-- ═══ نافذة بيانات العميل (تعديل مباشر) ═══ -->
<style>
.ci-modal .modal-header{background:linear-gradient(135deg,var(--ocp-primary,#6B1D3D),#9B2C5A);color:#fff;border-radius:4px 4px 0 0;padding:14px 20px}
.ci-modal .modal-header .close{color:#fff;opacity:.7;text-shadow:none}
.ci-modal .modal-header .close:hover{opacity:1}
.ci-modal .modal-title{font-size:15px;font-weight:700}
.ci-modal .modal-body{padding:0}
.ci-section{padding:16px 20px;border-bottom:1px solid #F1F5F9}
.ci-section:last-child{border-bottom:none}
.ci-section-title{font-size:11px;font-weight:700;color:#94A3B8;text-transform:uppercase;letter-spacing:.4px;margin-bottom:10px;display:flex;align-items:center;gap:6px}
.ci-section-title i{color:var(--ocp-primary,#6B1D3D);font-size:13px}
.ci-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(180px,1fr));gap:10px}
.ci-field{background:#FAFBFC;border-radius:8px;padding:8px 12px;border:1px solid #F1F5F9;cursor:pointer;transition:all .2s}
.ci-field:hover{border-color:#CBD5E1;background:#F8FAFC}
.ci-field-label{font-size:10px;font-weight:600;color:#94A3B8;margin-bottom:2px}
.ci-field-value{font-size:13px;font-weight:600;color:#1E293B;min-height:18px}
.ci-field.full{grid-column:1/-1}
.ci-modal .modal-footer{border-top:1px solid #E2E8F0;padding:10px 20px;display:flex;gap:8px;justify-content:flex-end}
.ci-modal .modal-footer .btn{border-radius:8px;font-size:12px;font-weight:600;padding:8px 16px}
.ci-input{width:100%;border:1.5px solid #E2E8F0;border-radius:6px;padding:4px 8px;font-size:13px;font-weight:600;color:#1E293B;background:#fff;outline:none;transition:border-color .2s,box-shadow .2s}
.ci-input:focus{border-color:var(--ocp-primary,#6B1D3D);box-shadow:0 0 0 3px rgba(107,29,61,.1)}
.ci-input:disabled{background:#FAFBFC;border-color:transparent;color:#1E293B;cursor:pointer;-webkit-appearance:none;appearance:none}
.ci-input[disabled]::-webkit-calendar-picker-indicator{display:none}
select.ci-input:disabled{-webkit-appearance:none;-moz-appearance:none;background-image:none}
select.ci-input:not(:disabled){-webkit-appearance:auto;-moz-appearance:auto}
textarea.ci-input{resize:vertical;min-height:50px}
textarea.ci-input:disabled{resize:none}
.ci-field.ci-editing{border-color:var(--ocp-primary,#6B1D3D);background:#fff;box-shadow:0 0 0 3px rgba(107,29,61,.08)}
.ci-field .ci-edit-hint{font-size:9px;color:#CBD5E1;margin-top:2px;display:block;transition:opacity .2s}
.ci-field.ci-editing .ci-edit-hint{opacity:0}
.ci-save-bar{background:#F0FDF4;border:1px solid #BBF7D0;border-radius:8px;padding:10px 16px;margin:12px 20px;display:none;align-items:center;gap:10px}
.ci-save-bar.visible{display:flex}
.ci-save-bar .ci-save-text{flex:1;font-size:12px;color:#166534;font-weight:600}
.ci-save-bar .btn-ci-save{background:#16A34A;color:#fff;border:none;border-radius:6px;padding:6px 20px;font-size:12px;font-weight:700;cursor:pointer}
.ci-save-bar .btn-ci-save:hover{background:#15803D}
.ci-save-bar .btn-ci-cancel{background:none;border:1px solid #D1D5DB;border-radius:6px;padding:6px 14px;font-size:12px;color:#6B7280;cursor:pointer}
.ci-save-bar .btn-ci-cancel:hover{background:#F3F4F6}
@keyframes ciShake{0%,100%{transform:translateX(0)}20%,60%{transform:translateX(-4px)}40%,80%{transform:translateX(4px)}}
</style>
<?php
$cities = \yii\helpers\ArrayHelper::map(\backend\modules\city\models\City::find()->orderBy('name')->asArray()->all(), 'id', 'name');
$jobs = \yii\helpers\ArrayHelper::map(\backend\modules\jobs\models\Jobs::find()->orderBy('name')->asArray()->all(), 'id', 'name');
$banks = \yii\helpers\ArrayHelper::map(\backend\modules\bancks\models\Bancks::find()->orderBy('name')->asArray()->all(), 'id', 'name');
$statuses = \yii\helpers\ArrayHelper::map(\backend\modules\status\models\Status::find()->asArray()->all(), 'id', 'name');
$citizens = \yii\helpers\ArrayHelper::map(\backend\modules\citizen\models\Citizen::find()->asArray()->all(), 'id', 'name');
$hearAboutUs = \yii\helpers\ArrayHelper::map(\backend\modules\hearAboutUs\models\HearAboutUs::find()->asArray()->all(), 'id', 'name');

$selectOpts = function($items, $cls, $field) {
    $html = '<select class="ci-input ' . $cls . '" data-field="' . $field . '" disabled><option value="">—</option>';
    foreach ($items as $id => $name) {
        $html .= '<option value="' . Html::encode($id) . '">' . Html::encode($name) . '</option>';
    }
    $html .= '</select>';
    return $html;
};
?>
<div class="modal fade ci-modal" id="customerInfoModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
                <h4 class="modal-title" id="customerInfoTitle"><i class="fa fa-user-circle"></i> بيانات العميل</h4>
            </div>
            <div class="modal-body">
                <input type="hidden" id="ci-customer-id" value="">

                <div class="ci-save-bar" id="ciSaveBar">
                    <i class="fa fa-info-circle" style="color:#16A34A;font-size:16px"></i>
                    <span class="ci-save-text">تم تعديل بعض الحقول — اضغط "حفظ" لتطبيق التغييرات</span>
                    <button type="button" class="btn-ci-cancel" onclick="CiEdit.cancelAll()"><i class="fa fa-undo"></i> تراجع</button>
                    <button type="button" class="btn-ci-save" onclick="CiEdit.save()"><i class="fa fa-check"></i> حفظ التعديلات</button>
                </div>

                <div class="ci-section">
                    <div class="ci-section-title"><i class="fa fa-id-card"></i> المعلومات الشخصية</div>
                    <div class="ci-grid">
                        <div class="ci-field"><div class="ci-field-label">الاسم الكامل</div><input type="text" class="ci-input cu-name" data-field="name" disabled><span class="ci-edit-hint">انقر مرتين للتعديل</span></div>
                        <div class="ci-field"><div class="ci-field-label">الرقم الوطني</div><input type="text" class="ci-input cu-id-number" data-field="id_number" disabled><span class="ci-edit-hint">انقر مرتين للتعديل</span></div>
                        <div class="ci-field"><div class="ci-field-label">تاريخ الميلاد</div><input type="date" class="ci-input cu-birth-date" data-field="birth_date" disabled><span class="ci-edit-hint">انقر مرتين للتعديل</span></div>
                        <div class="ci-field"><div class="ci-field-label">المدينة</div><?= $selectOpts($cities, 'cu-city', 'city') ?><span class="ci-edit-hint">انقر مرتين للتعديل</span></div>
                        <div class="ci-field"><div class="ci-field-label">الجنس</div><select class="ci-input cu-sex" data-field="sex" disabled><option value="">—</option><option value="0">ذكر</option><option value="1">أنثى</option></select><span class="ci-edit-hint">انقر مرتين للتعديل</span></div>
                    </div>
                </div>
                <div class="ci-section">
                    <div class="ci-section-title"><i class="fa fa-briefcase"></i> معلومات العمل</div>
                    <div class="ci-grid">
                        <div class="ci-field"><div class="ci-field-label">الوظيفة</div><?= $selectOpts($jobs, 'cu-job-title', 'job_title') ?><span class="ci-edit-hint">انقر مرتين للتعديل</span></div>
                        <div class="ci-field"><div class="ci-field-label">الرقم الوظيفي</div><input type="text" class="ci-input cu-job-number" data-field="job_number" disabled><span class="ci-edit-hint">انقر مرتين للتعديل</span></div>
                        <div class="ci-field"><div class="ci-field-label">البريد الإلكتروني</div><input type="email" class="ci-input cu-email" data-field="email" disabled><span class="ci-edit-hint">انقر مرتين للتعديل</span></div>
                    </div>
                </div>
                <div class="ci-section">
                    <div class="ci-section-title"><i class="fa fa-university"></i> المعلومات المالية</div>
                    <div class="ci-grid">
                        <div class="ci-field"><div class="ci-field-label">البنك</div><?= $selectOpts($banks, 'cu-bank-name', 'bank_name') ?><span class="ci-edit-hint">انقر مرتين للتعديل</span></div>
                        <div class="ci-field"><div class="ci-field-label">رقم الحساب</div><input type="text" class="ci-input cu-account-number" data-field="account_number" disabled><span class="ci-edit-hint">انقر مرتين للتعديل</span></div>
                        <div class="ci-field"><div class="ci-field-label">الفرع</div><input type="text" class="ci-input cu-bank-branch" data-field="bank_branch" disabled><span class="ci-edit-hint">انقر مرتين للتعديل</span></div>
                        <div class="ci-field"><div class="ci-field-label">ضمان اجتماعي</div><select class="ci-input cu-is-social-security" data-field="is_social_security" disabled><option value="">—</option><option value="0">لا</option><option value="1">نعم</option></select><span class="ci-edit-hint">انقر مرتين للتعديل</span></div>
                        <div class="ci-field"><div class="ci-field-label">رقم الضمان</div><input type="text" class="ci-input cu-social-security-number" data-field="social_security_number" disabled><span class="ci-edit-hint">انقر مرتين للتعديل</span></div>
                        <div class="ci-field"><div class="ci-field-label">يملك عقارات</div><select class="ci-input cu-do-have-any-property" data-field="do_have_any_property" disabled><option value="">—</option><option value="0">لا</option><option value="1">نعم</option></select><span class="ci-edit-hint">انقر مرتين للتعديل</span></div>
                    </div>
                </div>
                <div class="ci-section">
                    <div class="ci-section-title"><i class="fa fa-sticky-note-o"></i> ملاحظات</div>
                    <div class="ci-field full"><div class="ci-field-label">الملاحظات</div><textarea class="ci-input cu-notes" data-field="notes" rows="2" disabled></textarea><span class="ci-edit-hint">انقر مرتين للتعديل</span></div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal"><i class="fa fa-times"></i> إغلاق</button>
                <button type="button" class="btn btn-success" id="ciFooterSaveBtn" onclick="CiEdit.save()" style="display:none;border-radius:8px;font-size:12px;font-weight:700;padding:8px 20px"><i class="fa fa-check"></i> حفظ التعديلات</button>
                <a class="btn btn-primary" id="cus-link" style="background:var(--ocp-primary,#6B1D3D);border-color:var(--ocp-primary,#6B1D3D)" target="_blank"><i class="fa fa-external-link"></i> فتح صفحة العميل</a>
            </div>
        </div>
    </div>
</div>

<!-- ═══ نافذة صور العملاء ═══ -->
<div class="modal fade" id="customerImagesModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
                <h4 class="modal-title"><i class="fa fa-image"></i> صور ومستندات العملاء</h4>
            </div>
            <div class="modal-body">
                <?php
                /**
                 * === نظام عرض صور العملاء (مُصحَّح) ===
                 *
                 * الحقائق المكتشفة من تحليل قاعدة البيانات:
                 * 1. جدول os_customers_document: كل السجلات document_image=NULL و images=0 (لا يوجد ربط)
                 * 2. كل الصور الفعلية محفوظة في os_ImageManager بنوع groupName='coustmers'
                 * 3. للعملاء القدامى: contractId = customer_id الحقيقي (11,200 صورة)
                 * 4. للعملاء الجدد (بسبب باغ عدم execute): contractId = رقم عشوائي (1,068 صورة يتيمة)
                 * 5. العمود الوحيد الذي يربط الصور اليتيمة: selected_image في os_customers
                 *    → يحتوي ID سجل ImageManager → منه نعرف contractId العشوائي → نجلب كل الصور
                 * 6. عمود image_manager_id غير موجود في قاعدة البيانات (خاصية PHP وهمية فقط)
                 */

                $contractCustomerIds = \backend\modules\customers\models\ContractsCustomers::find()
                    ->select('customer_id')
                    ->where(['contract_id' => $contractModel->id])
                    ->column();

                $hasAnyImages = false;

                /* ══════════════════════════════════════════
                   جلب جميع صور العملاء من ImageManager
                   (المصدر الوحيد الفعلي للصور)
                   ══════════════════════════════════════════ */
                $allImages = [];
                if (!empty($contractCustomerIds)) {

                    // === الاستعلام 1: الصور المربوطة مباشرة (contractId = customer_id) ===
                    try {
                        $directImages = \backend\modules\imagemanager\models\Imagemanager::find()
                            ->where(['groupName' => 'coustmers'])
                            ->andWhere(['contractId' => $contractCustomerIds])
                            ->all();
                        foreach ($directImages as $img) {
                            $allImages[$img->id] = $img; // مفتاح = id لمنع التكرار
                        }
                    } catch (\Exception $e) {}

                    // === الاستعلام 2: الصور اليتيمة عبر selected_image → contractId ===
                    try {
                        // جلب selected_image لكل عميل (هذا هو ID سجل ImageManager للصورة الأساسية)
                        $selectedImageIds = \backend\modules\customers\models\Customers::find()
                            ->select('selected_image')
                            ->where(['id' => $contractCustomerIds])
                            ->andWhere(['not', ['selected_image' => null]])
                            ->andWhere(['!=', 'selected_image', ''])
                            ->andWhere(['!=', 'selected_image', '0'])
                            ->column();

                        if (!empty($selectedImageIds)) {
                            // من كل selected_image، نجلب contractId (الرقم العشوائي أو customer_id)
                            $orphanContractIds = \backend\modules\imagemanager\models\Imagemanager::find()
                                ->select('contractId')
                                ->where(['id' => $selectedImageIds])
                                ->andWhere(['groupName' => 'coustmers'])
                                ->column();

                            // إزالة الأرقام التي هي فعلاً customer_id (تم جلبها بالاستعلام 1) — تطبيع أنواع للمقارنة
                            $customerIdsNormalized = array_map('strval', $contractCustomerIds);
                            $orphanContractIds = array_values(array_filter($orphanContractIds, function ($cid) use ($customerIdsNormalized) {
                                return !in_array((string) $cid, $customerIdsNormalized, true);
                            }));

                            if (!empty($orphanContractIds)) {
                                $orphanImages = \backend\modules\imagemanager\models\Imagemanager::find()
                                    ->where(['groupName' => 'coustmers'])
                                    ->andWhere(['contractId' => $orphanContractIds])
                                    ->all();
                                foreach ($orphanImages as $img) {
                                    $allImages[$img->id] = $img;
                                }
                            }
                        }
                    } catch (\Exception $e) {}
                }

                if (!empty($allImages)):
                    $hasAnyImages = true;
                    // ترتيب بالأحدث أولاً
                    krsort($allImages);
                ?>
                    <h5 style="margin-bottom:12px"><i class="fa fa-picture-o"></i> صور العملاء <span class="badge"><?= count($allImages) ?></span></h5>
                    <div class="row">
                        <?php
                            // على نماء نستخدم action تعمل كـ proxy وتجلب الصورة من جادل (نفس النطاق → لا مشاكل referrer/CORS)
                            $isNamaa = stripos((string) Yii::$app->request->hostInfo, 'namaa') !== false;
                        ?>
                        <?php foreach ($allImages as $ei): ?>
                            <?php
                            if (empty($ei->fileHash)) continue;
                            if ($isNamaa) {
                                $path = \yii\helpers\Url::to(['/followUp/follow-up/customer-image', 'id' => $ei->id]);
                            } else {
                                $ext = pathinfo((string) $ei->fileName, PATHINFO_EXTENSION) ?: 'jpg';
                                $relPath = '/images/imagemanager/' . (int) $ei->id . '_' . $ei->fileHash . '.' . $ext;
                                if (!is_file(Yii::getAlias('@webroot') . $relPath)) continue;
                                $imagesBase = (isset(Yii::$app->params['customerImagesBaseUrl']) && Yii::$app->params['customerImagesBaseUrl'] !== '')
                                    ? rtrim((string) Yii::$app->params['customerImagesBaseUrl'], '/')
                                    : (Yii::$app->request->baseUrl ?: '');
                                $path = $imagesBase . $relPath;
                            }
                            ?>
                            <div class="col-md-3 text-center" style="margin-bottom:12px">
                                <a href="<?= Html::encode($path) ?>" target="_blank">
                                    <img src="<?= Html::encode($path) ?>"
                                         style="width:120px;height:120px;object-fit:contain;border-radius:8px;border:1px solid #ddd;padding:4px;cursor:pointer"
                                         alt="صورة عميل"
                                         onerror="this.style.display='none'; this.parentNode.innerHTML='<span style=\'color:#999;font-size:11px\'>صورة غير متوفرة</span>';">
                                </a>
                            </div>
                        <?php endforeach ?>
                    </div>
                <?php endif ?>

                <?php if (!$hasAnyImages): ?>
                    <div class="alert alert-warning" style="text-align:center;border-radius:8px">
                        <i class="fa fa-info-circle"></i> لم يتم العثور على صور لهذا العقد
                    </div>
                <?php endif ?>
            </div>
        </div>
    </div>
</div>

<!-- ═══ نافذة التدقيق ═══ -->
<div class="modal fade" id="auditModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
                <h4 class="modal-title"><i class="fa fa-check-square-o"></i> تدقيق عقد #<?= $contract_id ?></h4>
            </div>
            <div class="modal-body" id="auditDisplay" ondblclick="copyText(this)" style="direction:rtl;text-align:right">
                <?php
                /* معلومات العملاء */
                $contractObj = Contracts::findOne($contract_id);
                if ($contractObj):
                    $allCustomers = $contractObj->customersAndGuarantor ?? [];
                ?>
                    <h4><i class="fa fa-users"></i> معلومات العملاء</h4>
                    <?php foreach ($allCustomers as $cust): ?>
                        <div class="well well-sm">
                            <strong>العميل:</strong> <?= Html::encode($cust->name) ?><br>
                            <strong>الرقم الوطني:</strong> <?= Html::encode($cust->id_number) ?><br>
                            <?php if (!empty($cust->city)):
                                $cityObj = \backend\modules\city\models\City::findOne($cust->city);
                            ?>
                                <strong>المدينة:</strong> <?= $cityObj ? Html::encode($cityObj->name) : 'لا يوجد' ?><br>
                            <?php endif ?>
                            <?php if (!empty($cust->job_title)):
                                $jobObj = \backend\modules\jobs\models\Jobs::findOne($cust->job_title);
                            ?>
                                <strong>الوظيفة:</strong> <?= $jobObj ? Html::encode($jobObj->name) : 'لا يوجد' ?><br>
                            <?php endif ?>

                            <?php
                            $addrs = \backend\modules\address\models\Address::find()->where(['customers_id' => $cust->id])->all();
                            if (!empty($addrs)): ?>
                                <strong>العناوين:</strong>
                                <ul style="padding-right:20px;margin:5px 0">
                                    <?php foreach ($addrs as $a): ?>
                                        <li><?= ($a->address_type == 1 ? 'عنوان العمل' : 'عنوان السكن') ?>: <?= Html::encode($a->address ?: 'لا يوجد') ?></li>
                                    <?php endforeach ?>
                                </ul>
                            <?php endif ?>
                        </div>
                    <?php endforeach ?>

                    <!-- المعرّفون -->
                    <h4><i class="fa fa-address-book"></i> المعرّفون</h4>
                    <?php foreach ($contractObj->contractsCustomers as $cc): ?>
                        <?php if ($cc->customer && $cc->customer->phoneNumbers): ?>
                            <?php foreach ($cc->customer->phoneNumbers as $pn): ?>
                                <?php $rel = \backend\modules\cousins\models\Cousins::findOne($pn->phone_number_owner); ?>
                                <span class="label label-info" style="margin-left:5px">
                                    <?= Html::encode($pn->owner_name) ?> (<?= $rel ? Html::encode($rel->name) : '—' ?>)
                                </span>
                            <?php endforeach ?>
                        <?php endif ?>
                    <?php endforeach ?>

                    <!-- معلومات قضائية -->
                    <?php
                    $judicaries = \backend\modules\judiciary\models\Judiciary::find()->where(['contract_id' => $contract_id])->all();
                    if (!empty($judicaries)): ?>
                        <h4 style="margin-top:15px"><i class="fa fa-gavel"></i> المعلومات القضائية</h4>
                        <?php foreach ($judicaries as $jud): ?>
                            <div class="well well-sm">
                                <strong>القضية:</strong> <?= $jud->judiciary_number ?>/<?= $jud->year ?><br>
                                <strong>تاريخ الورود:</strong> <?= $jud->income_date ?: 'لا يوجد' ?><br>
                                <?php $law = \backend\modules\lawyers\models\Lawyers::findOne($jud->lawyer_id); ?>
                                <?php if ($law): ?><strong>المحامي:</strong> <?= Html::encode($law->name) ?><br><?php endif ?>
                                <?php $court = \backend\modules\court\models\Court::findOne($jud->court_id); ?>
                                <?php if ($court): ?><strong>المحكمة:</strong> <?= Html::encode($court->name) ?><br><?php endif ?>
                            </div>
                        <?php endforeach ?>
                    <?php endif ?>
                <?php endif ?>
            </div>
        </div>
    </div>
</div>

<!-- ═══ نافذة إضافة تسوية (محدّثة) ═══ -->
<?php
/* ── حساب إجمالي الدين تلقائياً ── */
$_stlTotalDebt = (float)($contractModel->total_value ?? 0);
$_stlLawyerCost = 0;

// أتعاب المحاماة من القضايا
$_stlJudiciary = \backend\modules\judiciary\models\Judiciary::find()
    ->where(['contract_id' => $contractModel->id, 'is_deleted' => 0])->all();
if (!empty($_stlJudiciary)) {
    foreach ($_stlJudiciary as $j) {
        $_stlLawyerCost += (float)($j->lawyer_cost ?? 0);
    }
}

// مجموع كل مصاريف Outcome على العقد (جميع التصنيفات)
$_stlAllExpenses = (float)((new \yii\db\Query())
    ->from('os_expenses')
    ->where(['contract_id' => $contractModel->id])
    ->sum('amount') ?? 0);

// المدفوع (كل حركات Income)
$_stlPaidAmount = (float)(\backend\modules\contractInstallment\models\ContractInstallment::find()
    ->where(['contract_id' => $contractModel->id])
    ->sum('amount') ?? 0);

$_stlAutoTotal = $_stlTotalDebt + $_stlAllExpenses + $_stlLawyerCost;
$_stlNetDebt = max(0, $_stlAutoTotal - $_stlPaidAmount);
?>

<style>
.stl-modal .form-group{margin-bottom:14px}
.stl-modal label{font-size:13px;font-weight:600;color:#555;margin-bottom:5px;display:block}
.stl-modal label .fa{margin-left:4px;color:#800020;font-size:11px}
.stl-modal .form-control{border-radius:6px;height:40px;font-size:13px;border:1.5px solid #ddd;transition:border-color .2s}
.stl-modal .form-control:focus{border-color:#800020;box-shadow:0 0 0 3px rgba(128,0,32,.08)}
.stl-modal .stl-section{font-size:11px;font-weight:700;color:#999;text-transform:uppercase;letter-spacing:.4px;margin:14px 0 10px;padding-bottom:5px;border-bottom:2px solid #f0f0f0}
.stl-modal .stl-type-toggle{display:flex;gap:6px;margin-bottom:14px}
.stl-modal .stl-type-btn{flex:1;padding:10px 12px;border:2px solid #e2e8f0;border-radius:8px;text-align:center;cursor:pointer;transition:all .2s;background:#f8f9fa;font-weight:600;font-size:12px}
.stl-modal .stl-type-btn:hover{border-color:#800020;background:#fff}
.stl-modal .stl-type-btn.active{border-color:#800020;background:#800020;color:#fff}
.stl-modal .stl-type-btn i{display:block;font-size:18px;margin-bottom:3px}
.stl-modal .stl-preview{background:#f8fafc;border:1px solid #e2e8f0;border-radius:8px;padding:14px;margin-top:10px}
.stl-modal .stl-preview-row{display:flex;justify-content:space-between;padding:5px 0;border-bottom:1px solid #f0f0f0;font-size:12px}
.stl-modal .stl-preview-row:last-child{border-bottom:none}
.stl-modal .stl-preview-row .stl-lbl{color:#64748b}
.stl-modal .stl-preview-row .stl-val{font-weight:700;color:#1e293b}
.stl-modal .stl-amount{font-weight:600;text-align:center;font-size:15px!important}
.stl-modal .stl-debt-card{background:linear-gradient(135deg,#f0f4ff,#e8eeff);border:1px solid #c7d2fe;border-radius:8px;padding:14px;margin-bottom:14px}
.stl-modal .stl-debt-row{display:flex;justify-content:space-between;font-size:12px;padding:3px 0;color:#475569}
.stl-modal .stl-debt-row.stl-debt-total{border-top:2px solid #800020;margin-top:6px;padding-top:8px;font-size:14px;font-weight:700;color:#800020}
</style>

<div class="modal fade" id="settlementModal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content stl-modal">
            <div class="modal-header" style="background:linear-gradient(135deg,#800020,#a0003a);color:#fff;border-radius:4px 4px 0 0">
                <button type="button" class="close" data-dismiss="modal" style="color:#fff;opacity:.8"><span>&times;</span></button>
                <h4 class="modal-title"><i class="fa fa-balance-scale"></i> إضافة تسوية</h4>
            </div>
            <div class="modal-body">
                <div class="alert loan-alert" style="display:none;border-radius:6px"></div>

                <!-- إجمالي الدين (محسوب تلقائياً) -->
                <div class="stl-debt-card">
                    <div class="stl-section" style="margin-top:0;border-bottom:none">إجمالي الدين</div>
                    <div class="stl-debt-row"><span>المبلغ الأصلي للعقد</span><span><?= number_format($_stlTotalDebt, 2) ?> د.أ</span></div>
                    <?php if ($_stlAllExpenses > 0): ?>
                    <div class="stl-debt-row"><span>إجمالي المصاريف (Outcome)</span><span><?= number_format($_stlAllExpenses, 2) ?> د.أ</span></div>
                    <?php endif ?>
                    <?php if ($_stlLawyerCost > 0): ?>
                    <div class="stl-debt-row"><span>أتعاب المحاماة</span><span><?= number_format($_stlLawyerCost, 2) ?> د.أ</span></div>
                    <?php endif ?>
                    <div class="stl-debt-row" style="border-top:1px solid #c7d2fe;margin-top:4px;padding-top:6px"><span>الإجمالي قبل الخصم</span><span><?= number_format($_stlAutoTotal, 2) ?> د.أ</span></div>
                    <div class="stl-debt-row" style="color:#059669"><span><i class="fa fa-check-circle"></i> المدفوع</span><span style="color:#059669">- <?= number_format($_stlPaidAmount, 2) ?> د.أ</span></div>
                    <div class="stl-debt-row stl-debt-total"><span>صافي الدين</span><span id="stl_total_display"><?= number_format($_stlNetDebt, 2) ?> د.أ</span></div>
                </div>
                <input type="hidden" id="stl_total_debt" value="<?= $_stlNetDebt ?>">

                <!-- نوع التسوية -->
                <div class="stl-section">نوع التسوية</div>
                <div class="stl-type-toggle">
                    <div class="stl-type-btn active" data-type="monthly" onclick="StlForm.setType('monthly')">
                        <i class="fa fa-calendar"></i> شهري
                    </div>
                    <div class="stl-type-btn" data-type="weekly" onclick="StlForm.setType('weekly')">
                        <i class="fa fa-calendar-o"></i> أسبوعي
                    </div>
                </div>
                <input type="hidden" id="stl_settlement_type" value="monthly">

                <!-- تفاصيل التسوية -->
                <div class="stl-section">تفاصيل التسوية</div>
                <div class="row">
                    <div class="col-sm-6">
                        <div class="form-group">
                            <label><i class="fa fa-money"></i> الدفعة الأولى (مبلغ ثابت)</label>
                            <input type="number" step="0.01" class="form-control stl-amount" id="stl_first_payment" placeholder="0.00" oninput="StlForm.calculate()">
                        </div>
                    </div>
                    <div class="col-sm-6">
                        <div class="form-group">
                            <label><i class="fa fa-money"></i> <span id="stl_installment_label">القسط الشهري</span></label>
                            <input type="number" step="0.01" class="form-control stl-amount" id="monthly_installment" placeholder="0.00" oninput="StlForm.calculate()">
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-sm-6">
                        <div class="form-group">
                            <label><i class="fa fa-calendar"></i> تاريخ الدفعة الأولى للتسوية</label>
                            <input type="date" class="form-control" id="first_installment_date" onchange="StlForm.onFirstDateChange()">
                        </div>
                    </div>
                    <div class="col-sm-6">
                        <div class="form-group">
                            <label><i class="fa fa-calendar-plus-o"></i> تاريخ القسط الجديد</label>
                            <input type="date" class="form-control" id="new_installment_date" onchange="StlForm.validateNewDate()">
                            <span class="help-block" id="stl_date_error" style="display:none;color:#e74c3c;font-size:11px"></span>
                        </div>
                    </div>
                </div>

                <!-- معاينة الجدولة -->
                <div class="stl-preview" id="stl_preview_box" style="display:none">
                    <div class="stl-section" style="border-bottom:none;margin:0 0 6px">معاينة الجدولة</div>
                    <div class="stl-preview-row"><span class="stl-lbl">إجمالي الدين</span><span class="stl-val" id="stl_p_debt">—</span></div>
                    <div class="stl-preview-row"><span class="stl-lbl">الدفعة الأولى</span><span class="stl-val" id="stl_p_fp">—</span></div>
                    <div class="stl-preview-row"><span class="stl-lbl">المبلغ المتبقي بعد الدفعة</span><span class="stl-val" id="stl_p_after_fp">—</span></div>
                    <div class="stl-preview-row"><span class="stl-lbl">قيمة القسط</span><span class="stl-val" id="stl_p_inst">—</span></div>
                    <div class="stl-preview-row"><span class="stl-lbl">عدد الأقساط</span><span class="stl-val" id="stl_p_count">—</span></div>
                    <div class="stl-preview-row"><span class="stl-lbl">آخر قسط (تقريبي)</span><span class="stl-val" id="stl_p_last">—</span></div>
                    <div class="stl-preview-row"><span class="stl-lbl">المستحق الكلي (دفعة + أقساط)</span><span class="stl-val" id="stl_p_total_due">—</span></div>
                </div>

                <input type="hidden" id="stl_installments_count" value="">
                <input type="hidden" id="stl_remaining_debt" value="">

                <!-- ملاحظات -->
                <div class="form-group" style="margin-top:12px">
                    <label><i class="fa fa-sticky-note-o"></i> ملاحظات</label>
                    <textarea class="form-control" id="stl_notes" rows="2" placeholder="ملاحظات إضافية (اختياري)..." style="height:auto;border-radius:6px"></textarea>
                </div>

                <input type="hidden" value="<?= $contractModel->id ?>" id="contract_id">
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal"><i class="fa fa-times"></i> إلغاء</button>
                <button type="button" class="btn btn-primary" id="save" style="background:#800020;border-color:#800020">
                    <i class="fa fa-plus-circle"></i> إنشاء التسوية
                </button>
            </div>
        </div>
    </div>
</div>

<script>
var StlForm = (function(){
    function setType(type) {
        document.getElementById('stl_settlement_type').value = type;
        document.querySelectorAll('.stl-type-btn').forEach(function(btn){
            btn.classList.toggle('active', btn.getAttribute('data-type') === type);
        });
        document.getElementById('stl_installment_label').textContent = type === 'weekly' ? 'القسط الأسبوعي' : 'القسط الشهري';
        calculate();
    }
    function calculate() {
        var totalDebt = parseFloat(document.getElementById('stl_total_debt').value) || 0;
        var fp   = parseFloat(document.getElementById('stl_first_payment').value) || 0;
        var inst = parseFloat(document.getElementById('monthly_installment').value) || 0;
        var box  = document.getElementById('stl_preview_box');
        var afterFp = Math.max(0, totalDebt - fp);

        if (inst > 0 && afterFp > 0) {
            var count = Math.ceil(afterFp / inst);
            var type = document.getElementById('stl_settlement_type').value;
            var firstEl = document.getElementById('first_installment_date');
            var lastDate = '—';
            if (firstEl && firstEl.value) {
                var d = new Date(firstEl.value);
                if (type === 'weekly') d.setDate(d.getDate() + (count - 1) * 7);
                else d.setMonth(d.getMonth() + (count - 1));
                lastDate = d.toISOString().split('T')[0];
            }
            var totalDue = fp + (count * inst);

            document.getElementById('stl_p_debt').textContent = totalDebt.toLocaleString('ar-JO') + ' د.أ';
            document.getElementById('stl_p_fp').textContent = fp > 0 ? fp.toLocaleString('ar-JO') + ' د.أ' : 'لا يوجد';
            document.getElementById('stl_p_after_fp').textContent = afterFp.toLocaleString('ar-JO') + ' د.أ';
            document.getElementById('stl_p_inst').textContent = inst.toLocaleString('ar-JO') + ' د.أ ' + (type === 'weekly' ? '(أسبوعي)' : '(شهري)');
            document.getElementById('stl_p_count').textContent = count + ' قسط';
            document.getElementById('stl_p_last').textContent = lastDate;
            document.getElementById('stl_p_total_due').textContent = totalDue.toLocaleString('ar-JO') + ' د.أ';

            document.getElementById('stl_installments_count').value = count;
            document.getElementById('stl_remaining_debt').value = Math.max(0, afterFp - count * inst);
            box.style.display = 'block';
        } else if (fp > 0 && inst <= 0) {
            // فقط دفعة أولى بدون أقساط
            document.getElementById('stl_p_debt').textContent = totalDebt.toLocaleString('ar-JO') + ' د.أ';
            document.getElementById('stl_p_fp').textContent = fp.toLocaleString('ar-JO') + ' د.أ';
            document.getElementById('stl_p_after_fp').textContent = afterFp.toLocaleString('ar-JO') + ' د.أ';
            document.getElementById('stl_p_inst').textContent = '—';
            document.getElementById('stl_p_count').textContent = '—';
            document.getElementById('stl_p_last').textContent = '—';
            document.getElementById('stl_p_total_due').textContent = fp.toLocaleString('ar-JO') + ' د.أ';
            document.getElementById('stl_installments_count').value = 0;
            document.getElementById('stl_remaining_debt').value = afterFp;
            box.style.display = 'block';
        } else {
            box.style.display = 'none';
        }
    }
    function onFirstDateChange() {
        var firstEl = document.getElementById('first_installment_date');
        var newEl = document.getElementById('new_installment_date');
        if (firstEl.value) {
            var type = document.getElementById('stl_settlement_type').value;
            var d = new Date(firstEl.value);
            // اقتراح تاريخ القسط الجديد: أسبوع بعد الدفعة الأولى أو شهر حسب النوع
            if (type === 'weekly') {
                d.setDate(d.getDate() + 7);
            } else {
                d.setMonth(d.getMonth() + 1);
            }
            newEl.value = d.toISOString().split('T')[0];
            // تحديد الحد الأدنى: أسبوع بعد الدفعة الأولى
            var minDate = new Date(firstEl.value);
            minDate.setDate(minDate.getDate() + 7);
            newEl.min = minDate.toISOString().split('T')[0];
        }
        calculate();
        validateNewDate();
    }

    function validateNewDate() {
        var firstEl = document.getElementById('first_installment_date');
        var newEl = document.getElementById('new_installment_date');
        var errEl = document.getElementById('stl_date_error');
        if (!firstEl.value || !newEl.value) {
            errEl.style.display = 'none';
            return true;
        }
        var firstDate = new Date(firstEl.value);
        var newDate = new Date(newEl.value);
        var minDate = new Date(firstEl.value);
        minDate.setDate(minDate.getDate() + 7);

        if (newDate <= firstDate) {
            errEl.textContent = 'يجب أن يكون تاريخ القسط الجديد بعد تاريخ الدفعة الأولى';
            errEl.style.display = 'block';
            newEl.style.borderColor = '#e74c3c';
            return false;
        }
        if (newDate < minDate) {
            errEl.textContent = 'يجب أن يكون تاريخ القسط الجديد بعد الدفعة الأولى بأسبوع على الأقل';
            errEl.style.display = 'block';
            newEl.style.borderColor = '#e74c3c';
            return false;
        }
        errEl.style.display = 'none';
        newEl.style.borderColor = '#ddd';
        return true;
    }

    return { setType: setType, calculate: calculate, onFirstDateChange: onFirstDateChange, validateNewDate: validateNewDate };
})();
</script>
