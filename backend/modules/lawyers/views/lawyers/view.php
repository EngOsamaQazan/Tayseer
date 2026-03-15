<?php

use yii\helpers\Html;
use backend\modules\lawyers\models\Lawyers;

$this->title = $model->name;
$this->params['breadcrumbs'][] = ['label' => 'المفوضين والوكلاء', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;

$isLawyer = ($model->representative_type === Lawyers::REP_TYPE_LAWYER);
$hasSignature = $model->signature_image && file_exists(Yii::getAlias('@backend/web/') . $model->signature_image);
$images = \backend\modules\LawyersImage\models\LawyersImage::find()->where(['lawyer_id' => $model->id])->all();
?>

<style>
.lv-page{max-width:860px;margin:0 auto;font-family:'Cairo','Segoe UI',Tahoma,sans-serif}
.lv-card{background:#fff;border:1px solid #e2e8f0;border-radius:12px;overflow:hidden;margin-bottom:20px;box-shadow:0 1px 3px rgba(0,0,0,.05)}
.lv-card-head{padding:14px 20px;background:#fafbfc;border-bottom:1px solid #e2e8f0;display:flex;align-items:center;justify-content:space-between;font-size:14px;font-weight:700;color:#334155}
.lv-card-head i{color:#800020;font-size:15px;margin-left:6px}
.lv-card-body{padding:20px}
.lv-grid{display:grid;gap:0;grid-template-columns:repeat(2,1fr)}
.lv-field{padding:14px 20px;border-bottom:1px solid #f1f5f9}
.lv-field:nth-child(odd){border-left:1px solid #f1f5f9}
.lv-label{font-size:11px;font-weight:600;color:#94a3b8;text-transform:uppercase;letter-spacing:.5px;margin-bottom:4px}
.lv-value{font-size:14px;color:#1e293b;font-weight:500}
.lv-badge{display:inline-flex;align-items:center;gap:4px;padding:3px 10px;border-radius:20px;font-size:12px;font-weight:600}
.lv-badge--lawyer{background:rgba(128,0,32,.1);color:#800020}
.lv-badge--delegate{background:rgba(100,116,139,.1);color:#475569}
.lv-badge--active{background:rgba(40,167,69,.1);color:#28a745}
.lv-badge--inactive{background:rgba(220,53,69,.1);color:#dc3545}
.lv-photos{display:flex;gap:10px;flex-wrap:wrap;margin-top:6px}
.lv-photo{width:180px;height:130px;border-radius:8px;overflow:hidden;border:2px solid #e2e8f0}
.lv-photo img{width:100%;height:100%;object-fit:cover;cursor:pointer}
.lv-sig-img{max-width:300px;max-height:120px;padding:8px;border:1px solid #e2e8f0;border-radius:8px;background:repeating-conic-gradient(#f0f0f0 0% 25%,transparent 0% 50%) 50%/14px 14px}
.lv-actions{display:flex;gap:8px;flex-direction:row-reverse}
@media(max-width:768px){.lv-grid{grid-template-columns:1fr}.lv-field:nth-child(odd){border-left:none}}
</style>

<div class="lv-page">

    <div class="lv-card">
        <div class="lv-card-head">
            <span><i class="fa fa-user"></i> بيانات المفوض / الوكيل</span>
            <div class="lv-actions">
                <?= Html::a('<i class="fa fa-edit"></i> تعديل', ['update', 'id' => $model->id], ['class' => 'btn btn-sm btn-primary']) ?>
                <?= Html::a('<i class="fa fa-arrow-right"></i> الرجوع', ['index'], ['class' => 'btn btn-sm btn-outline-secondary']) ?>
            </div>
        </div>
        <div class="lv-card-body" style="padding:0">
            <div class="lv-grid">
                <div class="lv-field">
                    <div class="lv-label">الاسم</div>
                    <div class="lv-value"><?= Html::encode($model->name) ?></div>
                </div>
                <div class="lv-field">
                    <div class="lv-label">نوع التمثيل</div>
                    <div class="lv-value">
                        <?php if ($isLawyer): ?>
                            <span class="lv-badge lv-badge--lawyer"><i class="fa fa-gavel"></i> وكيل محامي</span>
                        <?php else: ?>
                            <span class="lv-badge lv-badge--delegate"><i class="fa fa-user"></i> مفوض عادي</span>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="lv-field">
                    <div class="lv-label">رقم الهاتف</div>
                    <div class="lv-value"><?= Html::encode($model->phone_number ?: '—') ?></div>
                </div>
                <div class="lv-field">
                    <div class="lv-label">العنوان</div>
                    <div class="lv-value"><?= Html::encode($model->address ?: '—') ?></div>
                </div>
                <div class="lv-field">
                    <div class="lv-label">الحالة</div>
                    <div class="lv-value">
                        <?php if ($model->status == 0): ?>
                            <span class="lv-badge lv-badge--active">نشط</span>
                        <?php else: ?>
                            <span class="lv-badge lv-badge--inactive">غير نشط</span>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="lv-field">
                    <div class="lv-label">أنشئ بواسطة</div>
                    <div class="lv-value"><?= $model->createdBy ? Html::encode($model->createdBy->username) : '—' ?></div>
                </div>
                <?php if ($model->notes): ?>
                <div class="lv-field" style="grid-column:1/-1">
                    <div class="lv-label">ملاحظات</div>
                    <div class="lv-value"><?= nl2br(Html::encode($model->notes)) ?></div>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <?php if ($isLawyer): ?>
    <div class="lv-card">
        <div class="lv-card-head"><span><i class="fa fa-pencil"></i> التوقيع الإلكتروني</span></div>
        <div class="lv-card-body">
            <?php if ($hasSignature): ?>
                <img class="lv-sig-img" src="<?= Yii::$app->request->baseUrl . '/' . $model->signature_image ?>" alt="التوقيع">
            <?php else: ?>
                <span style="color:#94a3b8;font-size:13px">لم يتم رفع التوقيع بعد</span>
            <?php endif; ?>
        </div>
    </div>
    <?php endif; ?>

    <?php if ($images): ?>
    <div class="lv-card">
        <div class="lv-card-head"><span><i class="fa fa-id-card-o"></i> صور الهوية</span></div>
        <div class="lv-card-body">
            <div class="lv-photos">
                <?php foreach ($images as $img): ?>
                <div class="lv-photo">
                    <img src="<?= Yii::$app->request->baseUrl . '/' . $img->image ?>" alt="هوية" onclick="window.open(this.src,'_blank')">
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
    <?php endif; ?>

</div>
