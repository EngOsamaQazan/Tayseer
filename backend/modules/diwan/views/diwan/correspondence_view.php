<?php
use yii\helpers\Html;
use yii\widgets\DetailView;
use backend\modules\diwan\models\DiwanCorrespondence;

$this->title = 'مراسلة #' . $model->id;
$this->params['breadcrumbs'][] = ['label' => 'الديوان', 'url' => ['index']];
$this->params['breadcrumbs'][] = ['label' => 'المراسلات والتبليغات', 'url' => ['correspondence-index']];
$this->params['breadcrumbs'][] = $this->title;

$typeLabels = DiwanCorrespondence::getCommunicationTypeLabels();
$statusLabels = DiwanCorrespondence::getStatusLabels();
$purposeLabels = DiwanCorrespondence::getPurposeLabels();
$typeColors = [
    'notification' => '#F59E0B',
    'outgoing_letter' => '#3B82F6',
    'incoming_response' => '#10B981',
];
$statusColors = ['draft'=>'#9CA3AF','sent'=>'#3B82F6','delivered'=>'#10B981','responded'=>'#8B5CF6','closed'=>'#64748B'];
?>

<?= $this->render('@backend/views/layouts/_diwan-tabs', ['activeTab' => 'correspondence']) ?>

<style>
.cv-card{background:#fff;border:1px solid #E2E8F0;border-radius:12px;overflow:hidden;margin-bottom:20px}
.cv-header{padding:16px 24px;border-bottom:1px solid #E2E8F0;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px}
.cv-header h3{margin:0;font-size:18px;font-weight:700;color:#1E293B}
.cv-body{padding:24px}
.cv-badge{display:inline-block;padding:4px 14px;border-radius:16px;font-size:12px;font-weight:700;color:#fff}
.cv-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(280px,1fr));gap:16px}
.cv-item{padding:12px;background:#F8FAFC;border-radius:8px}
.cv-item label{display:block;font-size:12px;color:#64748B;margin-bottom:4px;font-weight:600}
.cv-item .cv-val{font-size:14px;color:#1E293B;font-weight:500}
</style>

<div style="direction:rtl;font-family:'Tajawal','Segoe UI',sans-serif">
    <div class="cv-card">
        <div class="cv-header">
            <h3>
                <span class="cv-badge" style="background:<?= $typeColors[$model->communication_type] ?? '#64748B' ?>">
                    <?= $typeLabels[$model->communication_type] ?? $model->communication_type ?>
                </span>
                &nbsp; <?= Html::encode($model->reference_number ?: 'بدون رقم مرجعي') ?>
            </h3>
            <div style="display:flex;gap:8px">
                <?= Html::a('<i class="fa fa-arrow-right"></i> الرجوع', ['correspondence-index'], ['class' => 'btn btn-outline-secondary btn-sm']) ?>
            </div>
        </div>
        <div class="cv-body">
            <div class="cv-grid">
                <div class="cv-item">
                    <label>نوع المراسلة</label>
                    <div class="cv-val"><?= $typeLabels[$model->communication_type] ?? $model->communication_type ?></div>
                </div>
                <div class="cv-item">
                    <label>تاريخ المراسلة</label>
                    <div class="cv-val"><?= $model->correspondence_date ?></div>
                </div>
                <div class="cv-item">
                    <label>الحالة</label>
                    <div class="cv-val">
                        <span style="color:<?= $statusColors[$model->status] ?? '#64748B' ?>;font-weight:700">
                            <?= $statusLabels[$model->status] ?? $model->status ?>
                        </span>
                    </div>
                </div>
                <div class="cv-item">
                    <label>المستلم / المرسل إليه</label>
                    <div class="cv-val"><?= Html::encode($model->getRecipientDisplayName()) ?></div>
                </div>
                <div class="cv-item">
                    <label>الرقم المرجعي</label>
                    <div class="cv-val"><?= Html::encode($model->reference_number ?: '—') ?></div>
                </div>
                <div class="cv-item">
                    <label>الغرض</label>
                    <div class="cv-val"><?= $purposeLabels[$model->purpose] ?? Html::encode($model->purpose ?: '—') ?></div>
                </div>
                <?php if ($model->communication_type === 'notification'): ?>
                <div class="cv-item">
                    <label>طريقة التبليغ</label>
                    <div class="cv-val"><?= Html::encode($model->notification_method ?: '—') ?></div>
                </div>
                <div class="cv-item">
                    <label>تاريخ التسليم</label>
                    <div class="cv-val"><?= $model->delivery_date ?: '—' ?></div>
                </div>
                <div class="cv-item">
                    <label>نتيجة التبليغ</label>
                    <div class="cv-val"><?= Html::encode($model->notification_result ?: '—') ?></div>
                </div>
                <?php endif; ?>
                <?php if ($model->communication_type === 'incoming_response'): ?>
                <div class="cv-item">
                    <label>نتيجة الرد</label>
                    <div class="cv-val"><?= Html::encode($model->response_result ?: '—') ?></div>
                </div>
                <div class="cv-item">
                    <label>المبلغ</label>
                    <div class="cv-val"><?= $model->response_amount ? number_format($model->response_amount, 2) . ' د.أ' : '—' ?></div>
                </div>
                <?php endif; ?>
                <div class="cv-item">
                    <label>تاريخ المتابعة</label>
                    <div class="cv-val"><?= $model->follow_up_date ?: '—' ?></div>
                </div>
            </div>

            <?php if ($model->content_summary): ?>
            <div style="margin-top:20px;padding:16px;background:#F8FAFC;border-radius:8px">
                <label style="font-size:12px;color:#64748B;font-weight:600;margin-bottom:8px;display:block">الملخص</label>
                <div style="font-size:14px;color:#1E293B;line-height:1.8"><?= nl2br(Html::encode($model->content_summary)) ?></div>
            </div>
            <?php endif; ?>

            <?php if ($model->notes): ?>
            <div style="margin-top:12px;padding:16px;background:#FFFBEB;border-radius:8px;border-right:3px solid #F59E0B">
                <label style="font-size:12px;color:#92400E;font-weight:600;margin-bottom:8px;display:block">ملاحظات</label>
                <div style="font-size:14px;color:#78350F;line-height:1.8"><?= nl2br(Html::encode($model->notes)) ?></div>
            </div>
            <?php endif; ?>

            <?php if ($model->image): ?>
            <div style="margin-top:20px">
                <label style="font-size:12px;color:#64748B;font-weight:600;margin-bottom:8px;display:block">المرفق</label>
                <img src="<?= Yii::getAlias('@web') . '/' . $model->image ?>" style="max-width:400px;border-radius:8px;border:1px solid #E2E8F0" alt="مرفق">
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>
