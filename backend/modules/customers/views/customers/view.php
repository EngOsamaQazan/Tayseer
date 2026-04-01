<?php
/**
 * عرض تفاصيل العميل — تصميم حديث متوافق مع شاشة التعديل
 */

use yii\helpers\Html;
use yii\helpers\Url;

$this->title = 'العميل: ' . $model->name;
$this->params['breadcrumbs'][] = ['label' => 'العملاء', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;

$this->registerCssFile('@web/css/smart-onboarding.css', ['depends' => [\yii\web\JqueryAsset::class]]);
$this->registerCssFile(Yii::$app->request->baseUrl . '/css/customers-v2.css?v=' . Yii::$app->params['assetVersion']);
$this->registerCss('.content-header,.page-header { display: none !important; } .content-wrapper { padding-top: 0 !important; } .content { padding: 0 !important; }');

$db = Yii::$app->db;
$cid = $model->id;

try { $contractsCount = (int) $db->createCommand("SELECT COUNT(*) FROM os_contracts_customers WHERE customer_id=:cid", [':cid' => $cid])->queryScalar(); } catch (\Exception $e) { $contractsCount = 0; }
try { $activeContracts = (int) $db->createCommand("SELECT COUNT(*) FROM os_contracts_customers cc INNER JOIN os_contracts c ON c.id=cc.contract_id WHERE cc.customer_id=:cid AND c.status='active'", [':cid' => $cid])->queryScalar(); } catch (\Exception $e) { $activeContracts = 0; }
try { $totalPaid = (float) $db->createCommand("SELECT COALESCE(SUM(i.amount),0) FROM os_income i INNER JOIN os_contracts_customers cc ON cc.contract_id=i.contract_id WHERE cc.customer_id=:cid", [':cid' => $cid])->queryScalar(); } catch (\Exception $e) { $totalPaid = 0; }
try { $totalRemaining = (float) $db->createCommand("SELECT COALESCE(SUM(c.total_value),0) FROM os_contracts c INNER JOIN os_contracts_customers cc ON cc.contract_id=c.id WHERE cc.customer_id=:cid AND c.status='active'", [':cid' => $cid])->queryScalar() - $totalPaid; } catch (\Exception $e) { $totalRemaining = 0; }
try { $lastFollowUp = $db->createCommand("SELECT MAX(f.date_time) FROM os_follow_up f INNER JOIN os_contracts_customers cc ON cc.contract_id=f.contract_id WHERE cc.customer_id=:cid", [':cid' => $cid])->queryScalar(); } catch (\Exception $e) { $lastFollowUp = null; }
try { $imageCount = (int) $db->createCommand("SELECT COUNT(*) FROM os_ImageManager WHERE contractId=:cid AND groupName IN ('coustmers','customers','0','1','2','3','4','5','6','7','8','9')", [':cid' => $cid])->queryScalar(); } catch (\Exception $e) { $imageCount = 0; }

$jobName = $model->jobs ? $model->jobs->name : '—';
$cityName = '—';
if ($model->city) {
    try { $c = $db->createCommand("SELECT name FROM os_city WHERE id=:id", [':id' => $model->city])->queryScalar(); if ($c) $cityName = $c; } catch (\Exception $e) {}
}
$citizenName = '—';
if ($model->citizen) {
    try { $c = $db->createCommand("SELECT name FROM os_citizen WHERE id=:id", [':id' => $model->citizen])->queryScalar(); if ($c) $citizenName = $c; } catch (\Exception $e) {}
}
$bankName = '—';
if ($model->bank_name) {
    try { $b = $db->createCommand("SELECT name FROM os_banks WHERE id=:id", [':id' => $model->bank_name])->queryScalar(); $bankName = $b ?: $model->bank_name; } catch (\Exception $e) { $bankName = $model->bank_name; }
}

$financials = null;
try {
    $financials = $db->createCommand("SELECT * FROM os_customer_financials WHERE customer_id=:cid", [':cid' => $cid])->queryOne();
} catch (\Exception $e) {}

$jobTypeName = ($model->jobs && $model->jobs->jobType) ? $model->jobs->jobType->name : '—';

try { $addresses = $db->createCommand("SELECT * FROM os_address WHERE customers_id=:cid AND is_deleted=0", [':cid' => $cid])->queryAll(); } catch (\Exception $e) { $addresses = []; }
try { $phones = $db->createCommand("SELECT * FROM os_phone_numbers WHERE customers_id=:cid AND is_deleted=0", [':cid' => $cid])->queryAll(); } catch (\Exception $e) { $phones = []; }

try {
    $contracts = $db->createCommand(
        "SELECT c.id, c.total_value, c.status, c.created_at
         FROM os_contracts c INNER JOIN os_contracts_customers cc ON cc.contract_id=c.id
         WHERE cc.customer_id=:cid ORDER BY c.id DESC LIMIT 10",
        [':cid' => $cid]
    )->queryAll();
} catch (\Exception $e) { $contracts = []; }

$statusLabels = [
    'active' => ['نشط', '#059669', '#ecfdf5'],
    'legal_department' => ['قانوني', '#dc2626', '#fef2f2'],
    'finished' => ['منتهي', '#6b7280', '#f3f4f6'],
    'canceled' => ['ملغي', '#9ca3af', '#f9fafb'],
];
?>

<div class="so-page so-mode-edit">
    <div class="so-header">
        <h1><i class="fa fa-user"></i> <?= Html::encode($model->name) ?></h1>
        <div class="so-header-actions">
            <a href="<?= Url::to(['/contracts/contracts/create', 'customer_id' => $cid]) ?>" class="so-back-btn" style="background:#059669;color:#fff;border-color:#059669"><i class="fa fa-file-text-o"></i> إنشاء عقد</a>
            <a href="<?= Url::to(['update', 'id' => $cid]) ?>" class="so-back-btn" style="background:var(--clr-primary,#800020);color:#fff;border-color:var(--clr-primary,#800020)"><i class="fa fa-pencil"></i> تعديل البيانات</a>
            <a href="<?= Url::to(['index']) ?>" class="so-back-btn"><i class="fa fa-arrow-right"></i> العودة للقائمة</a>
        </div>
    </div>

    <div class="so-body">
        <div class="so-form-area" style="padding-bottom:40px">

            <!-- بطاقات إحصائية -->
            <div style="display:grid;grid-template-columns:repeat(4,1fr);gap:14px;margin-bottom:24px">
                <div style="background:#f0f9ff;border:1px solid #bae6fd;border-radius:var(--radius-md,10px);padding:16px;text-align:center">
                    <div style="font-size:28px;font-weight:800;color:#0369a1"><?= $contractsCount ?></div>
                    <div style="font-size:12px;color:#64748b;margin-top:2px">إجمالي العقود</div>
                </div>
                <div style="background:#f0fdf4;border:1px solid #bbf7d0;border-radius:var(--radius-md,10px);padding:16px;text-align:center">
                    <div style="font-size:28px;font-weight:800;color:#166534"><?= $activeContracts ?></div>
                    <div style="font-size:12px;color:#64748b;margin-top:2px">عقود نشطة</div>
                </div>
                <div style="background:#fefce8;border:1px solid #fde68a;border-radius:var(--radius-md,10px);padding:16px;text-align:center">
                    <div style="font-size:20px;font-weight:800;color:#92400e"><?= number_format($totalPaid, 0) ?></div>
                    <div style="font-size:12px;color:#64748b;margin-top:2px">إجمالي المدفوع</div>
                </div>
                <div style="background:#fdf2f8;border:1px solid #fbcfe8;border-radius:var(--radius-md,10px);padding:16px;text-align:center">
                    <div style="font-size:20px;font-weight:800;color:#9d174d"><?= number_format(max(0, $totalRemaining), 0) ?></div>
                    <div style="font-size:12px;color:#64748b;margin-top:2px">المتبقي</div>
                </div>
            </div>

            <!-- البيانات الشخصية -->
            <div class="so-fieldset">
                <h3 class="so-fieldset-title"><i class="fa fa-user"></i> البيانات الشخصية</h3>
                <div class="so-grid so-grid-3">
                    <div class="cv-field">
                        <span class="cv-label">اسم العميل</span>
                        <span class="cv-value"><?= Html::encode($model->name) ?></span>
                    </div>
                    <div class="cv-field">
                        <span class="cv-label">الرقم الوطني</span>
                        <span class="cv-value" dir="ltr"><?= Html::encode($model->id_number ?: '—') ?></span>
                    </div>
                    <div class="cv-field">
                        <span class="cv-label">الجنس</span>
                        <span class="cv-value"><?= $model->sex == 0 ? 'ذكر' : 'أنثى' ?></span>
                    </div>
                </div>
                <div class="so-grid so-grid-3" style="margin-top:16px">
                    <div class="cv-field">
                        <span class="cv-label">تاريخ الميلاد</span>
                        <span class="cv-value"><?= $model->birth_date ?: '—' ?></span>
                    </div>
                    <div class="cv-field">
                        <span class="cv-label">المدينة</span>
                        <span class="cv-value"><?= Html::encode($cityName) ?></span>
                    </div>
                    <div class="cv-field">
                        <span class="cv-label">الجنسية</span>
                        <span class="cv-value"><?= Html::encode($citizenName) ?></span>
                    </div>
                </div>
            </div>

            <!-- بيانات التواصل -->
            <div class="so-fieldset">
                <h3 class="so-fieldset-title"><i class="fa fa-phone"></i> بيانات التواصل</h3>
                <div class="so-grid so-grid-3">
                    <div class="cv-field">
                        <span class="cv-label">الهاتف الرئيسي</span>
                        <span class="cv-value" dir="ltr"><?= Html::encode($model->primary_phone_number ? \backend\helpers\PhoneHelper::toLocal($model->primary_phone_number) : '—') ?></span>
                    </div>
                    <div class="cv-field">
                        <span class="cv-label">البريد الإلكتروني</span>
                        <span class="cv-value"><?= $model->email ? Html::mailto(Html::encode($model->email), $model->email) : '—' ?></span>
                    </div>
                    <div class="cv-field">
                        <span class="cv-label">فيسبوك</span>
                        <span class="cv-value"><?= Html::encode($model->facebook_account ?: '—') ?></span>
                    </div>
                </div>
            </div>

            <!-- المعلومات المهنية -->
            <div class="so-fieldset">
                <h3 class="so-fieldset-title"><i class="fa fa-briefcase"></i> المعلومات المهنية</h3>
                <div class="so-grid so-grid-3">
                    <div class="cv-field">
                        <span class="cv-label">جهة العمل</span>
                        <span class="cv-value"><?= Html::encode($jobName) ?></span>
                    </div>
                    <div class="cv-field">
                        <span class="cv-label">المسمى الوظيفي</span>
                        <span class="cv-value"><?= Html::encode($financials['employer_name'] ?? '—') ?></span>
                    </div>
                    <div class="cv-field">
                        <span class="cv-label">نوع جهة العمل</span>
                        <span class="cv-value"><?= Html::encode($jobTypeName) ?></span>
                    </div>
                </div>
                <div class="so-grid so-grid-3" style="margin-top:16px">
                    <div class="cv-field">
                        <span class="cv-label">الرقم الوظيفي</span>
                        <span class="cv-value"><?= Html::encode($model->job_number ?: '—') ?></span>
                    </div>
                    <div class="cv-field">
                        <span class="cv-label">سنوات الخدمة</span>
                        <span class="cv-value"><?= $financials['years_at_current_job'] ?? '—' ?></span>
                    </div>
                    <div class="cv-field">
                        <span class="cv-label">آخر استعلام وظيفي</span>
                        <span class="cv-value"><?= $model->last_job_query_date ?: '—' ?></span>
                    </div>
                </div>
            </div>

            <!-- الدخل والالتزامات -->
            <div class="so-fieldset">
                <h3 class="so-fieldset-title"><i class="fa fa-money"></i> الدخل والالتزامات</h3>
                <div class="so-grid so-grid-4">
                    <div class="cv-field">
                        <span class="cv-label">الراتب الأساسي</span>
                        <span class="cv-value cv-money"><?= $model->total_salary ? number_format($model->total_salary, 0) : '—' ?></span>
                    </div>
                    <div class="cv-field">
                        <span class="cv-label">دخل إضافي</span>
                        <span class="cv-value cv-money"><?= isset($financials['additional_income']) && $financials['additional_income'] > 0 ? number_format($financials['additional_income'], 0) : '—' ?></span>
                    </div>
                    <div class="cv-field">
                        <span class="cv-label">الالتزامات الشهرية</span>
                        <span class="cv-value cv-money"><?= isset($financials['monthly_obligations']) && $financials['monthly_obligations'] > 0 ? number_format($financials['monthly_obligations'], 0) : '—' ?></span>
                    </div>
                    <div class="cv-field">
                        <span class="cv-label">عدد المعالين</span>
                        <span class="cv-value"><?= $financials['dependents_count'] ?? '—' ?></span>
                    </div>
                </div>
            </div>

            <!-- الحساب البنكي -->
            <div class="so-fieldset">
                <h3 class="so-fieldset-title"><i class="fa fa-university"></i> الحساب البنكي والضمانات</h3>
                <div class="so-grid so-grid-3">
                    <div class="cv-field">
                        <span class="cv-label">البنك</span>
                        <span class="cv-value"><?= Html::encode($bankName) ?></span>
                    </div>
                    <div class="cv-field">
                        <span class="cv-label">الفرع</span>
                        <span class="cv-value"><?= Html::encode($model->bank_branch ?: '—') ?></span>
                    </div>
                    <div class="cv-field">
                        <span class="cv-label">رقم الحساب</span>
                        <span class="cv-value" dir="ltr"><?= Html::encode($model->account_number ?: '—') ?></span>
                    </div>
                </div>
                <div class="so-grid so-grid-3" style="margin-top:16px">
                    <div class="cv-field">
                        <span class="cv-label">ضمان اجتماعي</span>
                        <span class="cv-value"><?= $model->is_social_security ? '<span style="color:#059669;font-weight:600">نعم</span>' : '<span style="color:#9ca3af">لا</span>' ?></span>
                    </div>
                    <?php if ($model->is_social_security): ?>
                    <div class="cv-field">
                        <span class="cv-label">رقم الضمان</span>
                        <span class="cv-value" dir="ltr"><?= Html::encode($model->social_security_number ?: '—') ?></span>
                    </div>
                    <?php endif ?>
                    <div class="cv-field">
                        <span class="cv-label">يملك عقارات</span>
                        <span class="cv-value"><?= $model->do_have_any_property ? '<span style="color:#059669;font-weight:600">نعم</span>' : '<span style="color:#9ca3af">لا</span>' ?></span>
                    </div>
                </div>
            </div>

            <!-- العناوين -->
            <?php if (!empty($addresses)): ?>
            <div class="so-fieldset">
                <h3 class="so-fieldset-title"><i class="fa fa-map-marker"></i> العناوين (<?= count($addresses) ?>)</h3>
                <?php foreach ($addresses as $i => $addr): ?>
                <div style="<?= $i > 0 ? 'margin-top:12px;padding-top:12px;border-top:1px solid #eee' : '' ?>">
                    <div class="cv-field">
                        <span class="cv-label">العنوان</span>
                        <span class="cv-value"><?= Html::encode($addr['address'] ?? '—') ?></span>
                    </div>
                </div>
                <?php endforeach ?>
            </div>
            <?php endif ?>

            <!-- المعرّفون -->
            <?php if (!empty($phones)): ?>
            <div class="so-fieldset">
                <h3 class="so-fieldset-title"><i class="fa fa-address-book"></i> المعرّفون (<?= count($phones) ?>)</h3>
                <?php foreach ($phones as $i => $ph): ?>
                <div style="<?= $i > 0 ? 'margin-top:12px;padding-top:12px;border-top:1px solid #eee' : '' ?>">
                    <div class="so-grid so-grid-3">
                        <div class="cv-field">
                            <span class="cv-label">الاسم</span>
                            <span class="cv-value"><?= Html::encode($ph['owner_name'] ?? '—') ?></span>
                        </div>
                        <div class="cv-field">
                            <span class="cv-label">الهاتف</span>
                            <span class="cv-value" dir="ltr"><?= Html::encode(!empty($ph['phone_number']) ? \backend\helpers\PhoneHelper::toLocal($ph['phone_number']) : '—') ?></span>
                        </div>
                        <div class="cv-field">
                            <span class="cv-label">صلة القرابة</span>
                            <span class="cv-value"><?= Html::encode($ph['phone_number_owner'] ?? '—') ?></span>
                        </div>
                    </div>
                </div>
                <?php endforeach ?>
            </div>
            <?php endif ?>

            <!-- ملاحظات -->
            <?php if (!empty($model->notes)): ?>
            <div class="so-fieldset">
                <h3 class="so-fieldset-title"><i class="fa fa-sticky-note"></i> ملاحظات</h3>
                <p style="font-size:14px;color:#334155;line-height:1.8;margin:0"><?= nl2br(Html::encode($model->notes)) ?></p>
            </div>
            <?php endif ?>

            <!-- العقود -->
            <?php if (!empty($contracts)): ?>
            <div class="so-fieldset">
                <h3 class="so-fieldset-title"><i class="fa fa-file-text-o"></i> العقود (<?= $contractsCount ?>)</h3>
                <div style="overflow-x:auto">
                    <table style="width:100%;border-collapse:collapse;font-size:13px">
                        <thead>
                            <tr style="background:#f8fafc;border-bottom:2px solid #e2e8f0">
                                <th style="padding:10px 12px;text-align:right;font-weight:600;color:#475569">#</th>
                                <th style="padding:10px 12px;text-align:right;font-weight:600;color:#475569">القيمة</th>
                                <th style="padding:10px 12px;text-align:right;font-weight:600;color:#475569">الحالة</th>
                                <th style="padding:10px 12px;text-align:right;font-weight:600;color:#475569">التاريخ</th>
                                <th style="padding:10px 12px;text-align:center;font-weight:600;color:#475569">إجراءات</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($contracts as $ct):
                            $st = $statusLabels[$ct['status']] ?? ['غير محدد', '#6b7280', '#f3f4f6'];
                        ?>
                            <tr style="border-bottom:1px solid #f1f5f9">
                                <td style="padding:10px 12px;font-weight:600"><?= $ct['id'] ?></td>
                                <td style="padding:10px 12px"><?= number_format($ct['total_value'], 0) ?></td>
                                <td style="padding:10px 12px">
                                    <span style="display:inline-block;padding:3px 12px;border-radius:20px;font-size:11px;font-weight:600;background:<?= $st[2] ?>;color:<?= $st[1] ?>"><?= $st[0] ?></span>
                                </td>
                                <td style="padding:10px 12px;color:#64748b"><?= $ct['created_at'] ? date('Y-m-d', strtotime($ct['created_at'])) : '—' ?></td>
                                <td style="padding:10px 12px;text-align:center">
                                    <a href="<?= Url::to(['/followUp/follow-up/panel', 'contract_id' => $ct['id']]) ?>" style="color:#0891b2;font-size:12px;text-decoration:none" title="لوحة التحكم"><i class="fa fa-dashboard"></i></a>
                                </td>
                            </tr>
                        <?php endforeach ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <?php endif ?>

            <!-- معلومات النظام -->
            <div style="padding:12px 16px;background:#f8fafc;border:1px solid #e2e8f0;border-radius:8px;font-size:12px;color:#64748b;display:flex;gap:20px;flex-wrap:wrap">
                <span><i class="fa fa-hashtag"></i> رقم العميل: <b>#<?= $cid ?></b></span>
                <?php if (!empty($model->created_at)): ?>
                    <span><i class="fa fa-calendar-plus-o"></i> تاريخ الإنشاء: <b><?= $model->created_at ?></b></span>
                <?php endif ?>
                <?php if (!empty($model->updated_at)): ?>
                    <span><i class="fa fa-clock-o"></i> آخر تعديل: <b><?= $model->updated_at ?></b></span>
                <?php endif ?>
            </div>

        </div>

        <!-- اللوحة الجانبية -->
        <div class="so-risk-panel">
            <div class="rp-mobile-handle">
                <div class="rp-mobile-summary"><span style="font-size:14px;font-weight:700">ملخص العميل</span></div>
                <div class="rp-mobile-handle-bar"></div>
            </div>

            <h3 class="rp-title"><i class="fa fa-user-circle"></i> ملخص سريع</h3>

            <?php $avatarPath = $model->selectedImagePath; ?>
            <div style="text-align:center;margin-bottom:14px">
                <?php if ($avatarPath): ?>
                <img src="<?= $avatarPath ?>" style="width:100px;height:100px;border-radius:50%;object-fit:cover;border:3px solid #e2e8f0" alt="<?= Html::encode($model->name) ?>">
                <?php else: ?>
                <div style="width:100px;height:100px;border-radius:50%;border:3px solid #e2e8f0;background:#f1f5f9;display:inline-flex;align-items:center;justify-content:center">
                    <i class="fa fa-user" style="font-size:40px;color:#94a3b8"></i>
                </div>
                <?php endif ?>
            </div>

            <div style="text-align:center;margin-bottom:16px">
                <div style="font-size:18px;font-weight:700;color:#1e293b"><?= Html::encode($model->name) ?></div>
                <div style="font-size:13px;color:#64748b;margin-top:2px" dir="ltr"><?= Html::encode($model->id_number ?: '') ?></div>
            </div>

            <div style="display:grid;grid-template-columns:1fr 1fr;gap:8px;margin-bottom:16px">
                <div style="background:#f0f9ff;border:1px solid #bae6fd;border-radius:8px;padding:10px;text-align:center">
                    <div style="font-size:22px;font-weight:800;color:#0369a1"><?= $contractsCount ?></div>
                    <div style="font-size:11px;color:#64748b">العقود</div>
                </div>
                <div style="background:#f0fdf4;border:1px solid #bbf7d0;border-radius:8px;padding:10px;text-align:center">
                    <div style="font-size:22px;font-weight:800;color:#166534"><?= $activeContracts ?></div>
                    <div style="font-size:11px;color:#64748b">نشطة</div>
                </div>
            </div>

            <div style="background:#f8fafc;border:1px solid #e2e8f0;border-radius:8px;padding:12px;font-size:12.5px;color:#334155">
                <div style="display:flex;justify-content:space-between;margin-bottom:8px">
                    <span><i class="fa fa-phone" style="color:#0891b2;width:16px"></i> الهاتف</span>
                    <b dir="ltr"><?= $model->primary_phone_number ? \backend\helpers\PhoneHelper::toLocal($model->primary_phone_number) : '—' ?></b>
                </div>
                <div style="display:flex;justify-content:space-between;margin-bottom:8px">
                    <span><i class="fa fa-money" style="color:#059669;width:16px"></i> الراتب</span>
                    <b><?= $model->total_salary ? number_format($model->total_salary, 0) : '—' ?></b>
                </div>
                <div style="display:flex;justify-content:space-between;margin-bottom:8px">
                    <span><i class="fa fa-briefcase" style="color:#7c3aed;width:16px"></i> العمل</span>
                    <b><?= Html::encode($jobName) ?></b>
                </div>
                <div style="display:flex;justify-content:space-between;margin-bottom:8px">
                    <span><i class="fa fa-calendar" style="color:#d97706;width:16px"></i> آخر متابعة</span>
                    <b><?= $lastFollowUp ? date('Y-m-d', strtotime($lastFollowUp)) : '—' ?></b>
                </div>
                <div style="display:flex;justify-content:space-between">
                    <span><i class="fa fa-image" style="color:#ec4899;width:16px"></i> الصور</span>
                    <b><?= $imageCount ?></b>
                </div>
            </div>

            <div style="margin-top:16px;display:flex;flex-direction:column;gap:6px">
                <a href="<?= Url::to(['update', 'id' => $cid]) ?>" style="display:flex;align-items:center;gap:8px;padding:10px 14px;background:var(--clr-primary,#800020);border-radius:8px;text-decoration:none;color:#fff;font-size:13px;font-weight:600;transition:all .2s">
                    <i class="fa fa-pencil"></i> تعديل البيانات
                </a>
                <a href="<?= Url::to(['/contracts/contracts/create', 'customer_id' => $cid]) ?>" style="display:flex;align-items:center;gap:8px;padding:10px 14px;background:#fff;border:1px solid #e2e8f0;border-radius:8px;text-decoration:none;color:#1e293b;font-size:13px;font-weight:600;transition:all .2s">
                    <i class="fa fa-file-text-o" style="color:#059669"></i> إنشاء عقد جديد
                </a>
                <a href="<?= Url::to(['index']) ?>" style="display:flex;align-items:center;gap:8px;padding:10px 14px;background:#fff;border:1px solid #e2e8f0;border-radius:8px;text-decoration:none;color:#1e293b;font-size:13px;font-weight:600;transition:all .2s">
                    <i class="fa fa-arrow-right" style="color:#64748b"></i> العودة للقائمة
                </a>
            </div>
        </div>
    </div>
</div>

