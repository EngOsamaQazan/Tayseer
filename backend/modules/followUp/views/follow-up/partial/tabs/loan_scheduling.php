<?php
/**
 * تبويب التسويات — تصميم Cards
 */
use yii\helpers\Html;
use yii\helpers\Url;

$settlements = \backend\modules\loanScheduling\models\LoanScheduling::find()
    ->where(['contract_id' => $contractCalculations->contract_model->id])
    ->orderBy(['id' => SORT_DESC])
    ->all();

// المدفوع الكلي على العقد
$totalPaid = (float)(\backend\modules\contractInstallment\models\ContractInstallment::find()
    ->where(['contract_id' => $contractCalculations->contract_model->id])
    ->sum('amount') ?? 0);
?>

<style>
.stl-cards-wrap{display:flex;flex-direction:column;gap:16px}
.stl-card{background:#fff;border:1px solid #e2e8f0;border-radius:12px;overflow:hidden;transition:box-shadow .2s}
.stl-card:hover{box-shadow:0 4px 16px rgba(0,0,0,.08)}
.stl-card__header{display:flex;justify-content:space-between;align-items:center;padding:12px 16px;background:linear-gradient(135deg,#f8fafc,#f1f5f9);border-bottom:1px solid #e2e8f0}
.stl-card__type{display:flex;align-items:center;gap:8px;font-weight:700;font-size:14px;color:#1e293b}
.stl-card__type i{width:32px;height:32px;border-radius:8px;display:flex;align-items:center;justify-content:center;font-size:14px}
.stl-card__type--monthly i{background:#ede9fe;color:#7c3aed}
.stl-card__type--weekly i{background:#e0f2fe;color:#0284c7}
.stl-card__badge{padding:3px 10px;border-radius:20px;font-size:11px;font-weight:600}
.stl-card__badge--monthly{background:#ede9fe;color:#7c3aed}
.stl-card__badge--weekly{background:#e0f2fe;color:#0284c7}
.stl-card__actions{display:flex;gap:6px}
.stl-card__actions a{width:30px;height:30px;border-radius:6px;display:flex;align-items:center;justify-content:center;font-size:12px;border:1px solid #e2e8f0;color:#64748b;transition:all .2s;text-decoration:none}
.stl-card__actions a:hover{background:#800020;color:#fff;border-color:#800020}
.stl-card__body{padding:16px}
.stl-card__grid{display:grid;grid-template-columns:repeat(3,1fr);gap:12px}
.stl-card__item{text-align:center;padding:8px;background:#f8fafc;border-radius:8px;border:1px solid #f0f0f0}
.stl-card__item-value{font-size:15px;font-weight:700;color:#1e293b}
.stl-card__item-label{font-size:11px;color:#64748b;margin-top:2px}
.stl-card__item--highlight{background:linear-gradient(135deg,#fdf2f4,#fff);border-color:#fecdd3}
.stl-card__item--highlight .stl-card__item-value{color:#800020}
.stl-card__item--success .stl-card__item-value{color:#059669}
.stl-card__dates{display:flex;gap:12px;margin-top:12px}
.stl-card__date{flex:1;display:flex;align-items:center;gap:8px;padding:8px 12px;background:#f8fafc;border-radius:8px;border:1px solid #f0f0f0;font-size:12px}
.stl-card__date i{color:#800020;font-size:14px}
.stl-card__date-label{color:#64748b;font-size:10px}
.stl-card__date-value{font-weight:700;color:#1e293b;font-size:13px}
.stl-card__notes{margin-top:12px;padding:8px 12px;background:#fffbeb;border:1px solid #fef3c7;border-radius:8px;font-size:12px;color:#92400e}
.stl-card__notes i{margin-left:4px}
.stl-card__progress{margin-top:12px}
.stl-card__progress-bar{background:#f0f0f0;border-radius:6px;height:6px;overflow:hidden}
.stl-card__progress-fill{height:100%;background:linear-gradient(90deg,#059669,#10b981);border-radius:6px;transition:width .3s}
.stl-card__progress-text{font-size:10px;color:#64748b;text-align:center;margin-top:3px}
.stl-card__meta{display:flex;justify-content:space-between;align-items:center;padding:8px 16px;background:#f8fafc;border-top:1px solid #f0f0f0;font-size:11px;color:#94a3b8}
.stl-empty{text-align:center;padding:40px 20px;color:#94a3b8}
.stl-empty i{font-size:40px;margin-bottom:12px;display:block}
.stl-add-btn{display:flex;align-items:center;justify-content:center;gap:8px;padding:12px;background:#800020;color:#fff;border-radius:8px;font-weight:600;font-size:13px;text-decoration:none;transition:background .2s;border:none;width:100%;cursor:pointer}
.stl-add-btn:hover{background:#600018;color:#fff;text-decoration:none}
@media(max-width:500px){
    .stl-card__grid{grid-template-columns:repeat(2,1fr)}
    .stl-card__dates{flex-direction:column}
}
</style>

<div class="stl-cards-wrap">
    <!-- زر إضافة تسوية — يفتح النافذة المنبثقة الجديدة -->
    <button type="button" class="stl-add-btn" data-toggle="modal" data-target="#settlementModal">
        <i class="fa fa-plus-circle"></i> إضافة تسوية جديدة
    </button>

    <?php if (empty($settlements)): ?>
    <div class="stl-empty">
        <i class="fa fa-balance-scale"></i>
        <div>لا توجد تسويات لهذا العقد</div>
    </div>
    <?php else: ?>
    <?php foreach ($settlements as $s):
        $type = $s->settlement_type ?? 'monthly';
        $typeLabel = $type === 'weekly' ? 'أسبوعي' : 'شهري';
        $stlDebt = (float)($s->total_debt ?? 0);       // صافي الدين عند إنشاء التسوية
        $stlFp = (float)($s->first_payment ?? 0);
        $stlInst = (float)($s->monthly_installment ?? 0);
        // عدد الأقساط = صافي الدين / القسط (محسوب ديناميكياً)
        $stlTotalCount = ($stlInst > 0 && $stlDebt > 0) ? (int)ceil($stlDebt / $stlInst) : 0;

        // المدفوع بعد التسوية
        $paidAfter = 0;
        if ($s->first_installment_date) {
            $paidAfter = (float)(\backend\modules\contractInstallment\models\ContractInstallment::find()
                ->where(['contract_id' => $s->contract_id])
                ->andWhere(['>=', 'date', $s->first_installment_date])
                ->sum('amount') ?? 0);
        }
        $stlRemaining = max(0, $stlDebt - $paidAfter);
        $progressPct = $stlDebt > 0 ? round(($paidAfter / $stlDebt) * 100) : 0;
        $remainingInst = ($stlInst > 0 && $stlRemaining > 0) ? (int)ceil($stlRemaining / $stlInst) : 0;
    ?>
    <div class="stl-card">
        <div class="stl-card__header">
            <div class="stl-card__type stl-card__type--<?= $type ?>">
                <i class="fa <?= $type === 'weekly' ? 'fa-calendar-o' : 'fa-calendar' ?>"></i>
                <span>تسوية <?= $typeLabel ?></span>
                <span class="stl-card__badge stl-card__badge--<?= $type ?>"><?= $typeLabel ?></span>
            </div>
            <div class="stl-card__actions">
                <a href="<?= Url::to(['/loanScheduling/loan-scheduling/update', 'id' => $s->id, 'contract_id' => $s->contract_id]) ?>"
                   role="modal-remote" title="تعديل"><i class="fa fa-pencil"></i></a>
                <a href="<?= Url::to(['/loanScheduling/loan-scheduling/delete-from-follow-up', 'id' => $s->id, 'contract_id' => $s->contract_id]) ?>"
                   data-confirm="هل أنت متأكد من حذف هذه التسوية؟" data-method="post" title="حذف"><i class="fa fa-trash"></i></a>
            </div>
        </div>

        <div class="stl-card__body">
            <div class="stl-card__grid">
                <div class="stl-card__item stl-card__item--highlight">
                    <div class="stl-card__item-value"><?= number_format($stlDebt) ?></div>
                    <div class="stl-card__item-label">صافي الدين عند التسوية</div>
                </div>
                <div class="stl-card__item stl-card__item--success">
                    <div class="stl-card__item-value"><?= number_format($paidAfter) ?></div>
                    <div class="stl-card__item-label">المدفوع</div>
                </div>
                <div class="stl-card__item">
                    <div class="stl-card__item-value"><?= number_format($stlRemaining) ?></div>
                    <div class="stl-card__item-label">المتبقي</div>
                </div>
                <?php if ($stlFp > 0): ?>
                <div class="stl-card__item">
                    <div class="stl-card__item-value"><?= number_format($stlFp) ?></div>
                    <div class="stl-card__item-label">الدفعة الأولى</div>
                </div>
                <?php endif ?>
                <div class="stl-card__item">
                    <div class="stl-card__item-value"><?= number_format($stlInst) ?></div>
                    <div class="stl-card__item-label">قيمة القسط</div>
                </div>
                <div class="stl-card__item">
                    <div class="stl-card__item-value"><?= $remainingInst ?> / <?= $stlTotalCount ?></div>
                    <div class="stl-card__item-label">أقساط متبقية / إجمالي</div>
                </div>
            </div>

            <div class="stl-card__dates">
                <div class="stl-card__date">
                    <i class="fa fa-calendar-check-o"></i>
                    <div>
                        <div class="stl-card__date-label">تاريخ الدفعة الأولى</div>
                        <div class="stl-card__date-value"><?= $s->first_installment_date ?: '—' ?></div>
                    </div>
                </div>
                <div class="stl-card__date">
                    <i class="fa fa-calendar-plus-o"></i>
                    <div>
                        <div class="stl-card__date-label">تاريخ القسط الجديد</div>
                        <div class="stl-card__date-value"><?= $s->new_installment_date ?: '—' ?></div>
                    </div>
                </div>
            </div>

            <?php if ($stlDebt > 0): ?>
            <div class="stl-card__progress">
                <div class="stl-card__progress-bar"><div class="stl-card__progress-fill" style="width:<?= $progressPct ?>%"></div></div>
                <div class="stl-card__progress-text">تقدم السداد: <?= $progressPct ?>%</div>
            </div>
            <?php endif ?>

            <?php if (!empty($s->notes)): ?>
            <div class="stl-card__notes"><i class="fa fa-sticky-note-o"></i> <?= Html::encode($s->notes) ?></div>
            <?php endif ?>
        </div>

        <div class="stl-card__meta">
            <span><i class="fa fa-clock-o"></i> <?= Yii::$app->formatter->asRelativeTime($s->created_at) ?></span>
            <?php if ($s->createdBy ?? null): ?>
            <span><i class="fa fa-user"></i> <?= Html::encode($s->createdBy->username ?? '') ?></span>
            <?php endif ?>
        </div>
    </div>
    <?php endforeach ?>
    <?php endif ?>
</div>
