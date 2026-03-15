<?php

use yii\helpers\Html;
use yii\helpers\Url;
use yii\widgets\ActiveForm;
use backend\modules\lawyers\models\Lawyers;

/* @var $this yii\web\View */
/* @var $model backend\modules\lawyers\models\Lawyers */

$isLawyer = ($model->representative_type === Lawyers::REP_TYPE_LAWYER);
$hasSignature = $model->signature_image && file_exists(Yii::getAlias('@backend/web/') . $model->signature_image);
$existingImages = [];
if (!$model->isNewRecord) {
    $existingImages = \backend\modules\LawyersImage\models\LawyersImage::find()
        ->where(['lawyer_id' => $model->id])->all();
}
?>

<style>
.lw-page{max-width:860px;margin:0 auto;font-family:'Cairo','Segoe UI',Tahoma,sans-serif}
.lw-card{background:#fff;border:1px solid #e2e8f0;border-radius:12px;overflow:hidden;margin-bottom:20px;box-shadow:0 1px 3px rgba(0,0,0,.05)}
.lw-card-head{padding:14px 20px;background:#fafbfc;border-bottom:1px solid #e2e8f0;display:flex;align-items:center;gap:8px;font-size:14px;font-weight:700;color:#334155}
.lw-card-head i{color:#800020;font-size:15px}
.lw-card-body{padding:20px}
.lw-grid{display:grid;gap:16px}
.lw-grid-2{grid-template-columns:repeat(2,1fr)}
.lw-grid-3{grid-template-columns:repeat(3,1fr)}
.lw-type-group{display:flex;gap:12px;justify-content:center}
.lw-type-btn{flex:1;max-width:260px;padding:20px 16px;border:2px solid #e2e8f0;border-radius:10px;text-align:center;cursor:pointer;transition:all .2s;background:#fff;position:relative;overflow:hidden}
.lw-type-btn:hover{border-color:#800020;background:rgba(128,0,32,.02)}
.lw-type-btn.active{border-color:#800020;background:linear-gradient(135deg,rgba(128,0,32,.04),rgba(128,0,32,.08))}
.lw-type-btn.active::after{content:'';position:absolute;top:0;right:0;left:0;height:3px;background:#800020}
.lw-type-btn .lw-ti{width:48px;height:48px;border-radius:50%;display:flex;align-items:center;justify-content:center;margin:0 auto 10px;font-size:18px;background:#f1f5f9;color:#64748b;transition:all .2s}
.lw-type-btn.active .lw-ti{background:#800020;color:#fff}
.lw-type-btn .lw-tn{font-weight:700;font-size:15px;color:#334155;margin-bottom:2px}
.lw-type-btn.active .lw-tn{color:#800020}
.lw-type-btn .lw-th{font-size:12px;color:#94a3b8}
.lw-sig-zone{border:2px dashed #cbd5e1;border-radius:10px;padding:24px;text-align:center;background:#fafbfc;cursor:pointer;transition:all .2s;min-height:160px;display:flex;flex-direction:column;align-items:center;justify-content:center;position:relative}
.lw-sig-zone.drag-over{border-color:#800020;background:rgba(128,0,32,.03)}
.lw-sig-preview{position:relative;display:inline-block}
.lw-sig-preview img{max-width:360px;max-height:130px;border:1px solid #e2e8f0;border-radius:8px;padding:8px;background:repeating-conic-gradient(#f0f0f0 0% 25%,transparent 0% 50%) 50%/14px 14px}
.lw-sig-del{position:absolute;top:-6px;left:-6px;width:24px;height:24px;border-radius:50%;border:none;background:#dc3545;color:#fff;font-size:11px;cursor:pointer;display:flex;align-items:center;justify-content:center;box-shadow:0 2px 4px rgba(0,0,0,.15)}
.lw-photos-zone{border:2px dashed #cbd5e1;border-radius:10px;padding:20px;text-align:center;background:#fafbfc;cursor:pointer;transition:all .2s;min-height:100px;display:flex;align-items:center;justify-content:center}
.lw-photos-zone.drag-over{border-color:#800020;background:rgba(128,0,32,.03)}
.lw-photos-grid{display:flex;gap:10px;flex-wrap:wrap;margin-top:14px}
.lw-photo-thumb{width:120px;height:85px;border-radius:8px;overflow:hidden;border:2px solid #e2e8f0;position:relative;transition:border-color .2s}
.lw-photo-thumb:hover{border-color:#800020}
.lw-photo-thumb img{width:100%;height:100%;object-fit:cover;cursor:pointer}
.lw-photo-thumb .lw-pd{position:absolute;top:3px;left:3px;width:20px;height:20px;border-radius:50%;border:none;background:#dc3545;color:#fff;font-size:9px;cursor:pointer;opacity:0;transition:opacity .2s;display:flex;align-items:center;justify-content:center}
.lw-photo-thumb:hover .lw-pd{opacity:1}
.lw-photo-thumb.is-new{border-style:dashed;border-color:#0ea5e9}
.lw-photo-thumb.is-new .lw-pd{opacity:1}
.lw-save-bar{background:#fff;border:1px solid #e2e8f0;border-radius:12px;padding:16px 24px;display:flex;align-items:center;justify-content:center;gap:10px;box-shadow:0 1px 3px rgba(0,0,0,.05);margin-bottom:30px}
@media(max-width:768px){
    .lw-grid-2,.lw-grid-3{grid-template-columns:1fr}
    .lw-type-group{flex-direction:column;align-items:stretch}
    .lw-type-btn{max-width:100%}
}
</style>

<div class="lw-page">

<?php
$form = ActiveForm::begin([
    'id' => 'lw-form',
    'options' => ['enctype' => 'multipart/form-data'],
]);
?>

<?= $form->field($model, 'representative_type')->hiddenInput(['id' => 'lw-rep-type'])->label(false) ?>

<!-- ───── نوع التمثيل ───── -->
<div class="lw-card">
    <div class="lw-card-head"><i class="fa fa-id-badge"></i> نوع التمثيل</div>
    <div class="lw-card-body">
        <div class="lw-type-group" id="lw-type-picker">
            <div class="lw-type-btn <?= !$isLawyer ? 'active' : '' ?>" data-val="<?= Lawyers::REP_TYPE_DELEGATE ?>">
                <div class="lw-ti"><i class="fa fa-user"></i></div>
                <div class="lw-tn">مفوض عادي</div>
                <div class="lw-th">تفويض رسمي لتمثيل الدائن</div>
            </div>
            <div class="lw-type-btn <?= $isLawyer ? 'active' : '' ?>" data-val="<?= Lawyers::REP_TYPE_LAWYER ?>">
                <div class="lw-ti"><i class="fa fa-gavel"></i></div>
                <div class="lw-tn">وكيل محامي</div>
                <div class="lw-th">وكالة قانونية من محامٍ مُرخّص</div>
            </div>
        </div>
    </div>
</div>

<!-- ───── المعلومات الأساسية ───── -->
<div class="lw-card">
    <div class="lw-card-head"><i class="fa fa-info-circle"></i> المعلومات الأساسية</div>
    <div class="lw-card-body">
        <div class="lw-grid lw-grid-2">
            <div><?= $form->field($model, 'name')->textInput(['maxlength' => true, 'placeholder' => 'الاسم الكامل'])->label('الاسم') ?></div>
            <div><?= $form->field($model, 'phone_number')->textInput(['maxlength' => true, 'placeholder' => '07XXXXXXXX'])->label('رقم الهاتف') ?></div>
        </div>
        <div class="lw-grid lw-grid-2" style="margin-top:4px">
            <div><?= $form->field($model, 'address')->textInput(['maxlength' => true, 'placeholder' => 'العنوان'])->label('العنوان') ?></div>
            <div><?= $form->field($model, 'status')->dropDownList(
                [0 => 'نشط', 1 => 'غير نشط'],
                ['prompt' => 'اختر الحالة...']
            )->label('الحالة') ?></div>
        </div>
        <div style="margin-top:4px">
            <?= $form->field($model, 'notes')->textarea(['rows' => 3, 'placeholder' => 'ملاحظات إضافية...'])->label('ملاحظات') ?>
        </div>
    </div>
</div>

<!-- ───── التوقيع الإلكتروني (وكيل محامي فقط) ───── -->
<div class="lw-card" id="lw-sig-card" style="<?= !$isLawyer ? 'display:none' : '' ?>">
    <div class="lw-card-head"><i class="fa fa-pencil"></i> التوقيع الإلكتروني</div>
    <div class="lw-card-body">
        <p style="font-size:12px;color:#64748b;margin:0 0 14px">ارفق صورة توقيع المحامي بصيغة PNG (يُفضّل خلفية شفافة) لاستخدامه تلقائياً في الطلبات التنفيذية.</p>
        <div class="lw-sig-zone" id="lw-sig-drop">
            <div class="lw-sig-preview" id="lw-sig-prev" style="<?= !$hasSignature ? 'display:none' : '' ?>">
                <img src="<?= $hasSignature ? Yii::$app->request->baseUrl . '/' . $model->signature_image : '' ?>" alt="التوقيع" id="lw-sig-img">
                <button type="button" class="lw-sig-del" id="lw-sig-rm"><i class="fa fa-times"></i></button>
            </div>
            <div id="lw-sig-ph" style="<?= $hasSignature ? 'display:none' : '' ?>">
                <i class="fa fa-cloud-upload" style="font-size:32px;color:#cbd5e1;margin-bottom:8px"></i>
                <p style="margin:0;font-size:13px;color:#64748b">اسحب صورة التوقيع هنا أو <span style="color:#800020;cursor:pointer">تصفّح</span></p>
                <small style="color:#94a3b8;font-size:11px">PNG فقط &bull; 400&times;150 بكسل</small>
            </div>
            <input type="file" name="signature_file" id="lw-sig-file" accept="image/png" style="display:none">
        </div>
        <?= $form->field($model, 'signature_image')->hiddenInput(['id' => 'lw-sig-path'])->label(false) ?>
    </div>
</div>

<!-- ───── صور الهوية والوثائق ───── -->
<div class="lw-card">
    <div class="lw-card-head"><i class="fa fa-id-card-o"></i> صور الهوية والوثائق</div>
    <div class="lw-card-body">
        <div class="lw-photos-zone" id="lw-photos-drop">
            <div>
                <i class="fa fa-picture-o" style="font-size:28px;color:#cbd5e1;margin-bottom:6px"></i>
                <p style="margin:0;font-size:13px;color:#64748b">اسحب صور الهوية هنا أو <span style="color:#800020;cursor:pointer">تصفّح</span></p>
                <small style="color:#94a3b8;font-size:11px">JPG, PNG &bull; حد أقصى صورتان</small>
            </div>
            <input type="file" name="Lawyers[image][]" id="lw-photos-file" accept="image/jpeg,image/png" multiple style="display:none">
        </div>
        <div class="lw-photos-grid" id="lw-photos-grid">
            <?php foreach ($existingImages as $img): ?>
            <div class="lw-photo-thumb" data-img-id="<?= $img->id ?>">
                <img src="<?= Yii::$app->request->baseUrl . '/' . $img->image ?>" alt="هوية" onclick="window.open(this.src,'_blank')">
                <button type="button" class="lw-pd" data-img-id="<?= $img->id ?>"><i class="fa fa-times"></i></button>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<!-- ───── حفظ ───── -->
<div class="lw-save-bar">
    <?= Html::submitButton(
        $model->isNewRecord ? '<i class="fa fa-plus"></i> إضافة' : '<i class="fa fa-check"></i> حفظ التعديلات',
        ['class' => $model->isNewRecord ? 'btn btn-success px-4' : 'btn btn-primary px-4']
    ) ?>
    <?= Html::a('<i class="fa fa-times"></i> إلغاء', ['index'], ['class' => 'btn btn-outline-secondary px-4']) ?>
</div>

<?php ActiveForm::end(); ?>

</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    var $ = jQuery;

    // ── Type picker ──
    $('#lw-type-picker').on('click', '.lw-type-btn', function() {
        var v = $(this).data('val');
        $('#lw-type-picker .lw-type-btn').removeClass('active');
        $(this).addClass('active');
        $('#lw-rep-type').val(v);
        if (v === 'lawyer') {
            $('#lw-sig-card').slideDown(250);
        } else {
            $('#lw-sig-card').slideUp(250);
        }
    });

    // ── Signature drop zone ──
    var $sigDrop = $('#lw-sig-drop'),
        $sigFile = $('#lw-sig-file');

    $sigDrop.on('click', function(e) {
        if (!$(e.target).closest('.lw-sig-del, input[type="file"]').length) $sigFile[0].click();
    });
    $sigDrop.on('dragover dragenter', function(e) { e.preventDefault(); $(this).addClass('drag-over'); });
    $sigDrop.on('dragleave drop', function(e) { e.preventDefault(); $(this).removeClass('drag-over'); });
    $sigDrop.on('drop', function(e) {
        var f = e.originalEvent.dataTransfer.files;
        if (f.length && f[0].type === 'image/png') { $sigFile[0].files = f; showSigPreview(f[0]); }
    });
    $sigFile.on('change', function() { if (this.files.length) showSigPreview(this.files[0]); });

    $('#lw-sig-rm').on('click', function(e) {
        e.stopPropagation();
        $('#lw-sig-prev').hide(); $('#lw-sig-ph').show();
        $sigFile.val(''); $('#lw-sig-path').val('__removed__');
    });

    function showSigPreview(file) {
        var r = new FileReader();
        r.onload = function(e) { $('#lw-sig-img').attr('src', e.target.result); $('#lw-sig-prev').show(); $('#lw-sig-ph').hide(); };
        r.readAsDataURL(file);
    }

    // ── Photos drop zone ──
    var $pDrop = $('#lw-photos-drop'), $pFile = $('#lw-photos-file'), $pGrid = $('#lw-photos-grid');

    $pDrop.on('click', function(e) {
        if (!$(e.target).closest('input[type="file"], .lw-pd').length) $pFile[0].click();
    });
    $pDrop.on('dragover dragenter', function(e) { e.preventDefault(); $(this).addClass('drag-over'); });
    $pDrop.on('dragleave drop', function(e) { e.preventDefault(); $(this).removeClass('drag-over'); });
    $pDrop.on('drop', function(e) {
        var f = e.originalEvent.dataTransfer.files;
        if (f.length) { $pFile[0].files = f; showPhotosPrev(f); }
    });
    $pFile.on('change', function() { if (this.files.length) showPhotosPrev(this.files); });

    function showPhotosPrev(files) {
        $pGrid.find('.is-new').remove();
        for (var i = 0; i < files.length; i++) {
            (function(f) {
                var r = new FileReader();
                r.onload = function(e) {
                    $pGrid.append(
                        '<div class="lw-photo-thumb is-new"><img src="'+e.target.result+'" alt="هوية" onclick="window.open(this.src,\'_blank\')"><button type="button" class="lw-pd is-new-del"><i class="fa fa-times"></i></button></div>'
                    );
                };
                r.readAsDataURL(f);
            })(files[i]);
        }
    }

    $pGrid.on('click', '.is-new-del', function(e) {
        e.stopPropagation();
        $(this).closest('.lw-photo-thumb').fadeOut(200, function() { $(this).remove(); });
        $pFile.val('');
    });

    $pGrid.on('click', '.lw-pd:not(.is-new-del)', function(e) {
        e.stopPropagation();
        var $t = $(this).closest('.lw-photo-thumb'), imgId = $t.data('img-id');
        if (!imgId || !confirm('هل أنت متأكد من حذف هذه الصورة؟')) return;
        $.post('<?= Url::to(["delete-photo"]) ?>', {id: imgId, _csrf: $('meta[name=csrf-token]').attr('content')})
            .done(function() { $t.fadeOut(200, function() { $(this).remove(); }); });
    });
});
</script>
