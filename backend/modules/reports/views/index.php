<?php
/**
 * لوحة تحكم التقارير — نظرة عامة احترافية
 */
use yii\helpers\Url;
use yii\helpers\Html;

$this->title = 'التقارير';
$this->registerCssFile(Yii::getAlias('@web') . '/css/fin-transactions.css', ['depends' => ['yii\web\YiiAsset']]);
?>

<?= $this->render('@app/views/layouts/_reports-tabs', ['activeTab' => 'overview']) ?>

<style>
:root { --rp-blue: #1d4ed8; --rp-blue-bg: #dbeafe; --rp-green: #15803d; --rp-green-bg: #dcfce7; --rp-amber: #d97706; --rp-amber-bg: #fef3c7; --rp-red: #dc2626; --rp-red-bg: #fee2e2; --rp-purple: #7c3aed; --rp-purple-bg: #ede9fe; --rp-gray: #64748b; }

.rp-stats { display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 16px; margin-bottom: 24px; }
.rp-stat { display: flex; align-items: center; gap: 16px; padding: 22px 24px; border-radius: 14px; background: #fff; box-shadow: 0 1px 6px rgba(0,0,0,0.06); border: 1px solid #e2e8f0; transition: all 0.2s; }
.rp-stat:hover { box-shadow: 0 6px 20px rgba(0,0,0,0.1); transform: translateY(-3px); }
.rp-stat-icon { width: 54px; height: 54px; border-radius: 14px; display: flex; align-items: center; justify-content: center; font-size: 22px; flex-shrink: 0; }
.rp-stat-body { display: flex; flex-direction: column; }
.rp-stat-num { font-size: 26px; font-weight: 800; line-height: 1.1; font-family: 'Cairo', sans-serif; }
.rp-stat-lbl { font-size: 12.5px; font-weight: 600; color: var(--rp-gray); margin-top: 3px; }

.rp-stat--unpaid .rp-stat-icon { background: var(--rp-red-bg); color: var(--rp-red); }
.rp-stat--unpaid .rp-stat-num { color: var(--rp-red); }
.rp-stat--due .rp-stat-icon { background: var(--rp-amber-bg); color: var(--rp-amber); }
.rp-stat--due .rp-stat-num { color: var(--rp-amber); }
.rp-stat--paid .rp-stat-icon { background: var(--rp-green-bg); color: var(--rp-green); }
.rp-stat--paid .rp-stat-num { color: var(--rp-green); }
.rp-stat--total .rp-stat-icon { background: var(--rp-blue-bg); color: var(--rp-blue); }
.rp-stat--total .rp-stat-num { color: var(--rp-blue); }

/* أزرار سريعة */
.rp-quick { display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 14px; margin-bottom: 24px; }
.rp-quick-card { display: flex; align-items: center; gap: 14px; padding: 18px 22px; border-radius: 12px; background: #fff; border: 2px solid #e2e8f0; text-decoration: none !important; transition: all 0.2s; cursor: pointer; }
.rp-quick-card:hover { border-color: var(--rp-blue); box-shadow: 0 4px 14px rgba(29,78,216,0.12); transform: translateY(-2px); }
.rp-qc-icon { width: 44px; height: 44px; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 18px; flex-shrink: 0; }
.rp-qc-body { display: flex; flex-direction: column; }
.rp-qc-title { font-weight: 700; font-size: 14px; color: #1e293b; }
.rp-qc-desc { font-size: 12px; color: #94a3b8; margin-top: 2px; }

.rp-qc--income .rp-qc-icon { background: var(--rp-green-bg); color: var(--rp-green); }
.rp-qc--followup .rp-qc-icon { background: var(--rp-blue-bg); color: var(--rp-blue); }
.rp-qc--judiciary .rp-qc-icon { background: var(--rp-purple-bg); color: var(--rp-purple); }
.rp-qc--actions .rp-qc-icon { background: var(--rp-amber-bg); color: var(--rp-amber); }
</style>

<div class="rp-overview">

    <!-- ═══ بطاقات الإحصائيات ═══ -->
    <div class="rp-stats">
        <div class="rp-stat rp-stat--total">
            <div class="rp-stat-icon"><i class="fa fa-calculator"></i></div>
            <div class="rp-stat-body">
                <span class="rp-stat-num"><?= $totalInstallment ? number_format($totalInstallment, 2) : '0.00' ?></span>
                <span class="rp-stat-lbl">إجمالي الأقساط</span>
            </div>
        </div>
        <div class="rp-stat rp-stat--paid">
            <div class="rp-stat-icon"><i class="fa fa-check-circle"></i></div>
            <div class="rp-stat-body">
                <span class="rp-stat-num"><?= $totalPaidInstallment ? number_format($totalPaidInstallment, 2) : '0.00' ?></span>
                <span class="rp-stat-lbl">الأقساط المدفوعة</span>
            </div>
        </div>
        <div class="rp-stat rp-stat--unpaid">
            <div class="rp-stat-icon"><i class="fa fa-times-circle"></i></div>
            <div class="rp-stat-body">
                <span class="rp-stat-num"><?= $totalUnpaidInstallment ? number_format($totalUnpaidInstallment, 2) : '0.00' ?></span>
                <span class="rp-stat-lbl">الأقساط غير المدفوعة</span>
            </div>
        </div>
        <div class="rp-stat rp-stat--due">
            <div class="rp-stat-icon"><i class="fa fa-exclamation-triangle"></i></div>
            <div class="rp-stat-body">
                <span class="rp-stat-num"><?= $totalDueInstallment ? number_format($totalDueInstallment, 2) : '0.00' ?></span>
                <span class="rp-stat-lbl">الأقساط المتأخرة</span>
            </div>
        </div>
    </div>

    <!-- ═══ أقسام التقارير ═══ -->
    <div class="rp-quick">
        <a href="<?= Url::to(['/reports/reports/total-customer-payments-index']) ?>" class="rp-quick-card rp-qc--income">
            <div class="rp-qc-icon"><i class="fa fa-money"></i></div>
            <div class="rp-qc-body">
                <span class="rp-qc-title">تقرير الإيرادات</span>
                <span class="rp-qc-desc">مدفوعات العملاء حسب الفترة والموظف والشركة</span>
            </div>
        </a>
        <a href="<?= Url::to(['/reports/reports/total-judiciary-customer-payments-index']) ?>" class="rp-quick-card rp-qc--income">
            <div class="rp-qc-icon"><i class="fa fa-gavel"></i></div>
            <div class="rp-qc-body">
                <span class="rp-qc-title">إيرادات القضايا</span>
                <span class="rp-qc-desc">مدفوعات عملاء القضايا القضائية</span>
            </div>
        </a>
        <a href="<?= Url::to(['/reports/reports/index2']) ?>" class="rp-quick-card rp-qc--followup">
            <div class="rp-qc-icon"><i class="fa fa-phone"></i></div>
            <div class="rp-qc-body">
                <span class="rp-qc-title">تقارير المتابعة</span>
                <span class="rp-qc-desc">نشاطات المتابعة والتواصل مع العملاء</span>
            </div>
        </a>
        <a href="<?= Url::to(['/reports/reports/judiciary-index']) ?>" class="rp-quick-card rp-qc--judiciary">
            <div class="rp-qc-icon"><i class="fa fa-balance-scale"></i></div>
            <div class="rp-qc-body">
                <span class="rp-qc-title">التقارير القضائية</span>
                <span class="rp-qc-desc">القضايا والمحاكم والمحامين</span>
            </div>
        </a>
        <a href="<?= Url::to(['/reports/reports/customers-judiciary-actions']) ?>" class="rp-quick-card rp-qc--actions">
            <div class="rp-qc-icon"><i class="fa fa-list-alt"></i></div>
            <div class="rp-qc-body">
                <span class="rp-qc-title">الحركات القضائية للعملاء</span>
                <span class="rp-qc-desc">تفاصيل الإجراءات القضائية لكل عميل</span>
            </div>
        </a>
    </div>

</div>
