<?php

use yii\helpers\Html;
use yii\helpers\Url;
use backend\modules\companies\models\Companies;
use common\helper\Permissions;

/** @var yii\web\View $this */
/** @var backend\modules\shareholders\models\Shareholders $model */

$this->title = 'عرض المساهم: ' . $model->name;
$this->params['breadcrumbs'][] = ['label' => 'المساهمين', 'url' => ['index']];
$this->params['breadcrumbs'][] = $model->name;

$primaryCompany = Companies::find()->where(['is_primary_company' => 1])->one();
$companyTotalShares = $primaryCompany ? (int) $primaryCompany->total_shares : 0;
$ownershipPct = ($companyTotalShares > 0) ? round(($model->share_count / $companyTotalShares) * 100, 2) : 0;

$canManage = Permissions::can('المستثمرين');
?>

<style>
:root {
    --sh-primary: #0ea5e9;
    --sh-border: #e2e8f0;
    --sh-bg: #f8fafc;
    --sh-r: 12px;
    --sh-shadow: 0 1px 3px rgba(0,0,0,.06), 0 1px 2px rgba(0,0,0,.04);
}
.sh-view { max-width: 900px; margin: 0 auto; padding: 20px; }
.sh-view-card { background: #fff; border-radius: var(--sh-r); box-shadow: var(--sh-shadow); border: 1px solid var(--sh-border); margin-bottom: 18px; overflow: hidden; }
.sh-view-header { display: flex; align-items: center; gap: 20px; padding: 24px; background: var(--sh-bg); border-bottom: 1px solid var(--sh-border); }
.sh-view-avatar { width: 64px; height: 64px; border-radius: 50%; background: linear-gradient(135deg, #0ea5e9, #0284c7); display: flex; align-items: center; justify-content: center; color: #fff; font-size: 26px; font-weight: 700; flex-shrink: 0; }
.sh-view-name { font-size: 20px; font-weight: 700; color: #1e293b; }
.sh-view-sub { font-size: 13px; color: #64748b; margin-top: 2px; }
.sh-view-badge-active { display: inline-block; background: #dcfce7; color: #15803d; font-size: 11px; font-weight: 600; padding: 2px 10px; border-radius: 6px; margin-right: 8px; }
.sh-view-badge-inactive { display: inline-block; background: #fee2e2; color: #dc2626; font-size: 11px; font-weight: 600; padding: 2px 10px; border-radius: 6px; margin-right: 8px; }
.sh-view-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 0; }
.sh-view-item { padding: 14px 20px; border-bottom: 1px solid #f1f5f9; }
.sh-view-item:nth-child(odd) { border-left: 1px solid #f1f5f9; }
.sh-view-label { font-size: 12px; color: #94a3b8; margin-bottom: 2px; }
.sh-view-value { font-size: 14px; color: #1e293b; font-weight: 500; }
.sh-view-notes { padding: 16px 20px; }
.sh-view-notes-title { font-size: 13px; color: #94a3b8; margin-bottom: 6px; }
.sh-view-notes-text { font-size: 14px; color: #334155; line-height: 1.7; white-space: pre-wrap; }
.sh-view-actions { padding: 16px 20px; display: flex; gap: 8px; justify-content: flex-end; background: var(--sh-bg); border-top: 1px solid var(--sh-border); }
.sh-view-actions .btn { border-radius: 8px; font-size: 13px; font-weight: 600; }
@media (max-width: 600px) { .sh-view-grid { grid-template-columns: 1fr; } }
</style>

<div class="sh-view">
    <div class="sh-view-card">
        <div class="sh-view-header">
            <div class="sh-view-avatar"><?= mb_substr($model->name, 0, 1) ?></div>
            <div>
                <div class="sh-view-name">
                    <?= Html::encode($model->name) ?>
                    <?php if ($model->is_active): ?>
                        <span class="sh-view-badge-active">فعّال</span>
                    <?php else: ?>
                        <span class="sh-view-badge-inactive">غير فعّال</span>
                    <?php endif ?>
                </div>
                <div class="sh-view-sub">مساهم #<?= $model->id ?></div>
            </div>
        </div>

        <div class="sh-view-grid">
            <div class="sh-view-item">
                <div class="sh-view-label">الهاتف</div>
                <div class="sh-view-value" dir="ltr" style="text-align:right"><?= Html::encode($model->phone ?: '—') ?></div>
            </div>
            <div class="sh-view-item">
                <div class="sh-view-label">البريد الإلكتروني</div>
                <div class="sh-view-value"><?= Html::encode($model->email ?: '—') ?></div>
            </div>
            <div class="sh-view-item">
                <div class="sh-view-label">رقم الهوية</div>
                <div class="sh-view-value"><?= Html::encode($model->national_id ?: '—') ?></div>
            </div>
            <div class="sh-view-item">
                <div class="sh-view-label">عدد الأسهم</div>
                <div class="sh-view-value"><?= number_format($model->share_count) ?></div>
            </div>
            <div class="sh-view-item">
                <div class="sh-view-label">نسبة الملكية</div>
                <div class="sh-view-value" style="color:var(--sh-primary);font-weight:700"><?= $ownershipPct ?>%</div>
            </div>
            <div class="sh-view-item">
                <div class="sh-view-label">تاريخ الانضمام</div>
                <div class="sh-view-value"><?= Html::encode($model->join_date ?: '—') ?></div>
            </div>
            <div class="sh-view-item">
                <div class="sh-view-label">تاريخ الإنشاء</div>
                <div class="sh-view-value"><?= $model->created_at ? date('Y-m-d H:i', $model->created_at) : '—' ?></div>
            </div>
            <div class="sh-view-item">
                <div class="sh-view-label">أنشئ بواسطة</div>
                <div class="sh-view-value"><?= Html::encode($model->createdBy->username ?? '—') ?></div>
            </div>
        </div>

        <?php if (!empty($model->notes)): ?>
            <div class="sh-view-notes">
                <div class="sh-view-notes-title">ملاحظات</div>
                <div class="sh-view-notes-text"><?= Html::encode($model->notes) ?></div>
            </div>
        <?php endif ?>

        <div class="sh-view-actions">
            <?= Html::a('<i class="fa fa-arrow-right"></i> العودة', ['index'], ['class' => 'btn btn-default']) ?>
            <?php if ($canManage): ?>
                <?= Html::a('<i class="fa fa-pencil"></i> تعديل', ['update', 'id' => $model->id], ['class' => 'btn btn-primary']) ?>
            <?php endif ?>
        </div>
    </div>
</div>
