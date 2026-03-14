<?php
use yii\helpers\Html;
use yii\helpers\Url;
use yii\widgets\ActiveForm;
use yii\helpers\ArrayHelper;
use kartik\select2\Select2;
use backend\modules\jobs\models\JobsType;
use backend\modules\jobs\models\Jobs;
use backend\modules\jobs\models\JobsPhone;
use backend\modules\jobs\models\JobsWorkingHours;

/* @var $this yii\web\View */
/* @var $model Jobs */
/* @var $form ActiveForm */

$this->registerCssFile(Yii::getAlias('@web') . '/css/fin-transactions.css', ['depends' => ['yii\web\YiiAsset']]);

$existingHours = [];
if (!$model->isNewRecord) {
    foreach (JobsWorkingHours::find()->where(['job_id' => $model->id])->all() as $h) {
        $existingHours[$h->day_of_week] = $h;
    }
}
$existingPhones = [];
if (!$model->isNewRecord) {
    $existingPhones = $model->getPhones()->all();
}

$dayNames = Jobs::getDayNames();
$searchSimilarUrl = Url::to(['search-similar']);
$resolveLocationUrl = Url::to(['resolve-location']);
$modelId = $model->isNewRecord ? 0 : $model->id;
?>

<style>
.job-form-page { --fin-border:#e2e8f0; --fin-r:10px; --fin-shadow:0 1px 3px rgba(0,0,0,.05); max-width:960px; margin:0 auto; font-family:'Cairo','Segoe UI',Tahoma,sans-serif; }
.job-section { background:#fff; border:1px solid var(--fin-border); border-radius:var(--fin-r); box-shadow:var(--fin-shadow); margin-bottom:20px; overflow:hidden; }
.job-section-hdr { padding:14px 20px; font-size:14px; font-weight:800; color:#334155; background:#f8fafc; border-bottom:1px solid var(--fin-border); display:flex; align-items:center; gap:8px; cursor:pointer; user-select:none; }
.job-section-hdr .toggle-icon { margin-right:auto; transition:transform .2s; }
.job-section-hdr.collapsed .toggle-icon { transform:rotate(-90deg); }
.job-section-body { padding:20px; }
.job-section-body.collapsed { display:none; }

/* Duplicate detection */
.job-similar-wrap { position:relative; }
.job-similar-list { position:absolute; z-index:100; top:100%; right:0; left:0; background:#fff; border:1px solid #fbbf24; border-radius:8px; box-shadow:0 8px 24px rgba(0,0,0,.12); max-height:220px; overflow-y:auto; display:none; }
.job-similar-list.show { display:block; }
.job-similar-hdr { padding:8px 14px; background:#fef3c7; font-weight:700; font-size:12px; color:#92400e; border-bottom:1px solid #fde68a; }
.job-similar-row { padding:8px 14px; border-bottom:1px solid #f1f5f9; font-size:13px; display:flex; align-items:center; gap:8px; }
.job-similar-row:last-child { border-bottom:none; }
.job-similar-row:hover { background:#fefce8; }
.job-similar-name { font-weight:700; color:#1e293b; }
.job-similar-meta { font-size:11px; color:#94a3b8; }

/* Map */
#job-map-container { height:350px; border-radius:8px; border:1px solid var(--fin-border); overflow:hidden; }
.map-search-wrap { position:relative; margin-bottom:12px; display:flex; gap:8px; }
.map-search-wrap input { flex:1; height:44px; font-size:14px; padding-right:14px; border-radius:8px; border:1.5px solid var(--fin-border); }
.map-search-btn { height:44px; min-width:44px; border:1.5px solid var(--fin-border); background:#f8fafc; border-radius:8px; cursor:pointer; color:#64748b; font-size:16px; display:flex; align-items:center; justify-content:center; transition:all .2s; }
.map-search-btn:hover { background:#e2e8f0; color:#334155; }
.map-search-results { position:absolute; z-index:200; top:100%; right:0; left:0; background:#fff; border:1px solid var(--fin-border); border-radius:8px; box-shadow:0 8px 24px rgba(0,0,0,.1); max-height:300px; overflow-y:auto; display:none; }
.map-search-results.show { display:block; }
.map-search-results .result-item { padding:10px 14px; border-bottom:1px solid #f1f5f9; cursor:pointer; font-size:13px; direction:rtl; display:flex; align-items:center; gap:10px; }
.map-search-results .result-item:hover { background:#f0f9ff; }
.map-search-results .result-item .result-icon { color:#94a3b8; font-size:16px; flex-shrink:0; }
.map-search-results .result-item .result-text { flex:1; min-width:0; }
.map-search-results .result-item .result-name { font-weight:600; color:#1e293b; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
.map-search-results .result-item .result-addr { font-size:11px; color:#94a3b8; margin-top:1px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
.map-search-loading { padding:14px; text-align:center; color:#94a3b8; font-size:13px; }
/* Google Places Autocomplete dropdown styling */
.pac-container { border-radius:8px; border:1px solid #e2e8f0; box-shadow:0 8px 24px rgba(0,0,0,.12); font-family:'Cairo','Segoe UI',Tahoma,sans-serif; z-index:10000; }
.pac-item { padding:10px 14px; font-size:13px; border-bottom:1px solid #f1f5f9; direction:rtl; }
.pac-item:hover { background:#f0f9ff; }
.pac-item-query { font-weight:600; color:#1e293b; }
#gmp-place-input { --gmpac-sc-input-border-radius:8px; --gmpac-sc-input-font-size:14px; --gmpac-sc-input-text-align:right; height:44px; direction:rtl; }
.geo-filled { border-color:#22c55e !important; box-shadow:0 0 0 3px rgba(34,197,94,.2) !important; transition:border-color .3s, box-shadow .3s; }
.pac-icon { display:inline-block; }

/* Smart location input */
.smart-loc-input { position:relative; }
.smart-loc-input textarea { font-family:monospace; font-size:13px; direction:ltr; resize:none; border-radius:8px; border:1.5px solid var(--fin-border); }
.smart-loc-hint { font-size:11px; color:#94a3b8; margin-top:4px; }
.smart-loc-parsed { margin-top:6px; padding:8px 12px; background:#dcfce7; border-radius:6px; font-size:12px; color:#15803d; font-weight:600; display:none; }
.smart-loc-parsed.show { display:block; }

/* Phone rows */
.phone-row { display:flex; flex-wrap:wrap; gap:10px; align-items:flex-end; padding:12px 0; border-bottom:1px solid #f1f5f9; }
.phone-row:last-child { border-bottom:none; }
.phone-row .form-group { margin-bottom:0; }
.phone-field { flex:1; min-width:140px; }
.phone-field label { font-size:11px; font-weight:700; color:#64748b; margin-bottom:3px; }
.phone-field input, .phone-field select { height:38px; font-size:13px; border-radius:6px; border:1.5px solid var(--fin-border); }

/* Working hours */
.wh-table { width:100%; border-collapse:collapse; }
.wh-table th { padding:8px 12px; font-size:12px; font-weight:700; color:#64748b; background:#f8fafc; text-align:right; }
.wh-table td { padding:8px 12px; border-bottom:1px solid #f1f5f9; }
.wh-table .closed-row td { background:#fef2f2; opacity:.6; }
.wh-table input[type="time"] { height:36px; border-radius:6px; border:1.5px solid var(--fin-border); font-size:13px; direction:ltr; }

/* Save bar */
.job-save-bar { display:flex; justify-content:center; gap:12px; padding:20px; }
.job-save-bar .btn { font-weight:700; border-radius:8px; padding:10px 32px; font-size:15px; }
</style>

<div class="job-form-page">
    <?php $form = ActiveForm::begin(['id' => 'jobs-form']); ?>

    <!-- ═══ 1. البيانات الأساسية ═══ -->
    <div class="job-section">
        <div class="job-section-hdr" onclick="$(this).toggleClass('collapsed').next().toggleClass('collapsed')">
            <i class="fa fa-building"></i> البيانات الأساسية
            <i class="fa fa-chevron-down toggle-icon"></i>
        </div>
        <div class="job-section-body">
            <div class="row">
                <div class="col-md-5">
                    <div class="job-similar-wrap">
                        <?= $form->field($model, 'name')->textInput([
                            'maxlength' => true,
                            'placeholder' => 'أدخل اسم جهة العمل',
                            'id' => 'job-name-input',
                            'autocomplete' => 'off',
                        ]) ?>
                        <div id="job-similar-list" class="job-similar-list"></div>
                    </div>
                </div>
                <div class="col-md-4">
                    <?= $form->field($model, 'job_type')->widget(Select2::class, [
                        'data' => ArrayHelper::map(JobsType::find()->all(), 'id', 'name'),
                        'options' => ['placeholder' => 'اختر نوع جهة العمل'],
                        'pluginOptions' => ['allowClear' => true],
                    ]) ?>
                </div>
                <div class="col-md-3">
                    <?= $form->field($model, 'status')->dropDownList([1 => 'فعال', 0 => 'غير فعال'], ['class' => 'form-control']) ?>
                </div>
            </div>
            <div class="row">
                <div class="col-md-4">
                    <?= $form->field($model, 'email')->textInput(['maxlength' => true, 'placeholder' => 'example@domain.com', 'dir' => 'ltr']) ?>
                </div>
                <div class="col-md-4">
                    <?= $form->field($model, 'website')->textInput(['maxlength' => true, 'placeholder' => 'https://www.example.com', 'dir' => 'ltr']) ?>
                </div>
                <div class="col-md-4">
                    <?= $form->field($model, 'notes')->textarea(['rows' => 1, 'placeholder' => 'ملاحظات إضافية']) ?>
                </div>
            </div>
        </div>
    </div>

    <!-- ═══ 2. أرقام الهواتف ═══ -->
    <div class="job-section">
        <div class="job-section-hdr" onclick="$(this).toggleClass('collapsed').next().toggleClass('collapsed')">
            <i class="fa fa-phone"></i> أرقام الهواتف وجهات الاتصال
            <span id="phone-count-badge" style="background:#e0e7ff;color:#4338ca;padding:2px 10px;border-radius:12px;font-size:12px;margin-right:6px">0</span>
            <i class="fa fa-chevron-down toggle-icon"></i>
        </div>
        <div class="job-section-body">
            <div id="phones-container">
                <?php if (!empty($existingPhones)): ?>
                    <p class="text-muted" style="font-size:12px"><i class="fa fa-info-circle"></i> الأرقام المحفوظة سابقاً تُدار من صفحة العرض. أضف أرقاماً جديدة هنا.</p>
                    <?php foreach ($existingPhones as $p): ?>
                    <div style="padding:6px 12px;background:#f1f5f9;border-radius:6px;margin-bottom:6px;font-size:13px;display:flex;gap:12px;align-items:center">
                        <i class="fa fa-<?= $p->phone_type === 'mobile' ? 'mobile' : 'phone' ?>" style="color:#64748b"></i>
                        <strong style="direction:ltr"><?= Html::encode($p->phone_number) ?></strong>
                        <?php if ($p->employee_name): ?><span style="color:#64748b">— <?= Html::encode($p->employee_name) ?></span><?php endif ?>
                        <?php if ($p->employee_position): ?><span style="color:#94a3b8">(<?= Html::encode($p->employee_position) ?>)</span><?php endif ?>
                    </div>
                    <?php endforeach ?>
                    <hr style="margin:12px 0">
                <?php endif ?>
                <!-- Dynamic rows inserted here by JS -->
            </div>
            <button type="button" class="btn btn-sm btn-success" id="btn-add-phone" style="border-radius:8px;font-weight:700">
                <i class="fa fa-plus"></i> إضافة رقم هاتف
            </button>
        </div>
    </div>

    <!-- ═══ 3. العنوان والموقع الجغرافي ═══ -->
    <div class="job-section">
        <div class="job-section-hdr" onclick="$(this).toggleClass('collapsed').next().toggleClass('collapsed')">
            <i class="fa fa-map-marker"></i> العنوان والموقع الجغرافي
            <i class="fa fa-chevron-down toggle-icon"></i>
        </div>
        <div class="job-section-body">
            <div class="row">
                <div class="col-md-3">
                    <?= $form->field($model, 'address_city')->textInput(['placeholder' => 'المدينة', 'id' => 'jobs-address_city', 'class' => 'form-control addr-field', 'data-addr' => 'city']) ?>
                </div>
                <div class="col-md-3">
                    <?= $form->field($model, 'address_area')->textInput(['placeholder' => 'المنطقة أو الحي', 'id' => 'jobs-address_area', 'class' => 'form-control addr-field', 'data-addr' => 'area']) ?>
                </div>
                <div class="col-md-3">
                    <?= $form->field($model, 'address_street')->textInput(['placeholder' => 'الشارع والعنوان التفصيلي', 'id' => 'jobs-address_street', 'class' => 'form-control addr-field', 'data-addr' => 'street']) ?>
                </div>
                <div class="col-md-3">
                    <?= $form->field($model, 'address_building')->textInput(['placeholder' => 'المبنى / الطابق / الرقم', 'id' => 'jobs-address_building']) ?>
                </div>
            </div>
            <div class="row">
                <div class="col-md-3">
                    <?= $form->field($model, 'postal_code')->textInput(['placeholder' => 'مثل 11937', 'id' => 'jobs-postal_code', 'dir' => 'ltr', 'style' => 'font-family:monospace']) ?>
                </div>
                <div class="col-md-3">
                    <?= $form->field($model, 'plus_code')->textInput(['placeholder' => 'مثل 8Q6G+4M', 'id' => 'jobs-plus_code', 'dir' => 'ltr', 'style' => 'font-family:monospace', 'readonly' => true]) ?>
                </div>
            </div>

            <!-- Smart location input -->
            <div class="row" style="margin-bottom:16px">
                <div class="col-md-12">
                    <div class="smart-loc-input">
                        <label style="font-weight:700;font-size:13px;color:#334155"><i class="fa fa-paste"></i> لصق موقع (من جوجل ماب أو أي مصدر)</label>
                        <textarea id="smart-location-paste" class="form-control" rows="2" placeholder="الصق هنا: إحداثيات (31.95, 35.91) أو رابط جوجل ماب أو Plus Code..."></textarea>
                        <div class="smart-loc-hint">يقبل: إحداثيات عددية، روابط Google Maps، Plus Codes (مثل 85RM+JV عمان)</div>
                        <div id="smart-loc-parsed" class="smart-loc-parsed"></div>
                    </div>
                </div>
            </div>

            <!-- Map search -->
            <div class="row">
                <div class="col-md-12">
                    <div class="map-search-wrap">
                        <input type="text" id="map-search-input" class="form-control" placeholder="ابحث عن موقع (مثل: مستشفى الأمير حمزة، شركة نماء عمان)..." autocomplete="off">
                        <button type="button" class="map-search-btn" id="btn-map-search" title="بحث"><i class="fa fa-search"></i></button>
                        <div id="map-search-results" class="map-search-results"></div>
                    </div>
                    <div id="job-map-container"></div>
                </div>
            </div>

            <!-- Lat/Lng hidden fields -->
            <div class="row" style="margin-top:12px">
                <div class="col-md-3">
                    <?= $form->field($model, 'latitude')->textInput(['placeholder' => 'خط العرض', 'dir' => 'ltr', 'id' => 'job-latitude', 'style' => 'background:#f8fafc;font-family:monospace;font-size:13px']) ?>
                </div>
                <div class="col-md-3">
                    <?= $form->field($model, 'longitude')->textInput(['placeholder' => 'خط الطول', 'dir' => 'ltr', 'id' => 'job-longitude', 'style' => 'background:#f8fafc;font-family:monospace;font-size:13px']) ?>
                </div>
                <div class="col-md-6" style="padding-top:26px">
                    <button type="button" class="btn btn-info btn-sm" id="btn-get-location" style="border-radius:8px;font-weight:600">
                        <i class="fa fa-crosshairs"></i> موقعي الحالي
                    </button>
                    <button type="button" class="btn btn-warning btn-sm" id="btn-clear-location" style="border-radius:8px;font-weight:600">
                        <i class="fa fa-eraser"></i> مسح الموقع
                    </button>
                    <?php if (!$model->isNewRecord && $model->latitude && $model->longitude): ?>
                        <a href="<?= $model->getMapUrl() ?>" target="_blank" class="btn btn-success btn-sm" style="border-radius:8px;font-weight:600">
                            <i class="fa fa-external-link"></i> فتح في جوجل ماب
                        </a>
                    <?php endif ?>
                </div>
            </div>
        </div>
    </div>

    <!-- ═══ 4. أوقات العمل ═══ -->
    <div class="job-section">
        <div class="job-section-hdr collapsed" onclick="$(this).toggleClass('collapsed').next().toggleClass('collapsed')">
            <i class="fa fa-clock-o"></i> أوقات العمل
            <i class="fa fa-chevron-down toggle-icon"></i>
        </div>
        <div class="job-section-body collapsed">
            <table class="wh-table">
                <thead><tr><th style="width:120px">اليوم</th><th style="width:140px">البداية</th><th style="width:140px">النهاية</th><th style="width:70px;text-align:center">مغلق</th><th>ملاحظات</th></tr></thead>
                <tbody>
                    <?php foreach ($dayNames as $dayNum => $dayName):
                        $existing = $existingHours[$dayNum] ?? null;
                        $isClosed = $existing ? $existing->is_closed : ($dayNum == 5 ? 1 : 0);
                        $openTime = $existing ? $existing->opening_time : ($dayNum == 5 ? '' : '08:00');
                        $closeTime = $existing ? $existing->closing_time : ($dayNum == 5 ? '' : '16:00');
                        $notes = $existing ? $existing->notes : '';
                    ?>
                    <tr class="working-hours-row <?= $isClosed ? 'closed-row' : '' ?>">
                        <td><strong><?= $dayName ?></strong><input type="hidden" name="WorkingHours[<?= $dayNum ?>][day_of_week]" value="<?= $dayNum ?>"></td>
                        <td><input type="time" class="form-control input-sm wh-time" name="WorkingHours[<?= $dayNum ?>][opening_time]" value="<?= $openTime ?>" <?= $isClosed ? 'disabled' : '' ?>></td>
                        <td><input type="time" class="form-control input-sm wh-time" name="WorkingHours[<?= $dayNum ?>][closing_time]" value="<?= $closeTime ?>" <?= $isClosed ? 'disabled' : '' ?>></td>
                        <td class="text-center"><input type="checkbox" class="wh-closed-toggle" name="WorkingHours[<?= $dayNum ?>][is_closed]" value="1" <?= $isClosed ? 'checked' : '' ?>></td>
                        <td><input type="text" class="form-control input-sm" name="WorkingHours[<?= $dayNum ?>][notes]" value="<?= Html::encode($notes) ?>" placeholder="ملاحظات" style="border-radius:6px;border:1.5px solid #e2e8f0"></td>
                    </tr>
                    <?php endforeach ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- ═══ أزرار الحفظ ═══ -->
    <div class="job-save-bar">
        <?= Html::submitButton(
            $model->isNewRecord ? '<i class="fa fa-plus"></i> إنشاء جهة العمل' : '<i class="fa fa-save"></i> حفظ التغييرات',
            ['class' => $model->isNewRecord ? 'btn btn-success btn-lg' : 'btn btn-primary btn-lg']
        ) ?>
        <?= Html::a('<i class="fa fa-times"></i> إلغاء', ['index'], ['class' => 'btn btn-default btn-lg']) ?>
    </div>

    <?php ActiveForm::end(); ?>
</div>

<!-- Leaflet CSS/JS from CDN -->
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

<?php
$googleMapsKey = \common\models\SystemSettings::get('google_maps', 'api_key', null)
    ?? Yii::$app->params['googleMapsApiKey'] ?? null;
if ($googleMapsKey): ?>
<script src="https://maps.googleapis.com/maps/api/js?key=<?= Html::encode($googleMapsKey) ?>&libraries=places&language=ar&loading=async" async defer></script>
<?php endif; ?>

<?php
$existingPhonesCount = count($existingPhones);
$js = <<<JS

/* ═══════════════════════════════════════════════════════════
 *  1. كشف الأسماء المتشابهة (Duplicate Detection)
 * ═══════════════════════════════════════════════════════════ */
var similarTimer = null;
$('#job-name-input').on('input', function(){
    clearTimeout(similarTimer);
    var q = $(this).val().trim();
    if (q.length < 2) { $('#job-similar-list').removeClass('show').empty(); return; }
    similarTimer = setTimeout(function(){
        $.getJSON('$searchSimilarUrl', { q: q, exclude: $modelId }, function(data){
            var results = data.results || [];
            if (results.length === 0) { $('#job-similar-list').removeClass('show').empty(); return; }
            var html = '<div class="job-similar-hdr"><i class="fa fa-exclamation-triangle"></i> جهات عمل مشابهة موجودة:</div>';
            results.forEach(function(r){
                html += '<div class="job-similar-row">';
                html += '<span class="job-similar-name">' + r.name + '</span>';
                if (r.city || r.type) html += '<span class="job-similar-meta">' + [r.type, r.city].filter(Boolean).join(' — ') + '</span>';
                html += '</div>';
            });
            $('#job-similar-list').html(html).addClass('show');
        });
    }, 400);
});
$('#job-name-input').on('blur', function(){ setTimeout(function(){ $('#job-similar-list').removeClass('show'); }, 300); });
$('#job-name-input').on('focus', function(){ if ($('#job-similar-list').children().length > 0) $('#job-similar-list').addClass('show'); });

/* ═══════════════════════════════════════════════════════════
 *  2. أرقام الهواتف الديناميكية
 * ═══════════════════════════════════════════════════════════ */
var phoneIdx = 0;
function updatePhoneBadge() {
    var count = $('#phones-container .phone-row').length;
    var existing = $existingPhonesCount;
    $('#phone-count-badge').text(count + existing);
}
$('#btn-add-phone').on('click', function(){
    var html = '<div class="phone-row" data-phone-idx="'+phoneIdx+'">';
    html += '<div class="phone-field" style="flex:0 0 130px"><label>رقم الهاتف</label><input type="text" name="Phones['+phoneIdx+'][phone_number]" class="form-control" placeholder="07XXXXXXXX" dir="ltr" required></div>';
    html += '<div class="phone-field" style="flex:0 0 120px"><label>النوع</label><select name="Phones['+phoneIdx+'][phone_type]" class="form-control"><option value="office">هاتف مكتب</option><option value="mobile">موبايل</option><option value="fax">فاكس</option><option value="whatsapp">واتساب</option></select></div>';
    html += '<div class="phone-field"><label>اسم المسؤول</label><input type="text" name="Phones['+phoneIdx+'][employee_name]" class="form-control" placeholder="اسم الشخص المسؤول"></div>';
    html += '<div class="phone-field"><label>المسمى الوظيفي</label><input type="text" name="Phones['+phoneIdx+'][employee_position]" class="form-control" placeholder="مدير، محاسب..."></div>';
    html += '<div style="padding-bottom:2px"><button type="button" class="btn btn-xs btn-danger btn-remove-phone" style="border-radius:6px;height:38px;width:38px"><i class="fa fa-times"></i></button></div>';
    html += '</div>';
    $('#phones-container').append(html);
    phoneIdx++;
    updatePhoneBadge();
});
$(document).on('click', '.btn-remove-phone', function(){ $(this).closest('.phone-row').remove(); updatePhoneBadge(); });

/* ═══════════════════════════════════════════════════════════
 *  3. الخريطة التفاعلية (Leaflet + OpenStreetMap)
 * ═══════════════════════════════════════════════════════════ */
var defaultLat = 31.95;
var defaultLng = 35.91;
var initLat = parseFloat($('#job-latitude').val()) || defaultLat;
var initLng = parseFloat($('#job-longitude').val()) || defaultLng;
var initZoom = ($('#job-latitude').val() && $('#job-longitude').val()) ? 15 : 8;

var map = L.map('job-map-container').setView([initLat, initLng], initZoom);

var googleStreets = L.tileLayer('https://mt1.google.com/vt/lyrs=m&x={x}&y={y}&z={z}&hl=ar', {
    attribution: '&copy; Google Maps',
    maxZoom: 21
});
var googleHybrid = L.tileLayer('https://mt1.google.com/vt/lyrs=y&x={x}&y={y}&z={z}&hl=ar', {
    attribution: '&copy; Google Maps',
    maxZoom: 21
});
var osmLayer = L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
    attribution: '&copy; OpenStreetMap',
    maxZoom: 19
});

googleStreets.addTo(map);
L.control.layers({
    'خريطة Google': googleStreets,
    'قمر صناعي': googleHybrid,
    'OpenStreetMap': osmLayer
}, null, {position: 'topright'}).addTo(map);

var marker = null;
function setMapMarker(lat, lng, flyTo) {
    if (marker) map.removeLayer(marker);
    marker = L.marker([lat, lng], {draggable: true}).addTo(map);
    marker.on('dragend', function(e){
        var pos = e.target.getLatLng();
        $('#job-latitude').val(pos.lat.toFixed(8));
        $('#job-longitude').val(pos.lng.toFixed(8));
        reverseGeocode(pos.lat, pos.lng);
    });
    $('#job-latitude').val(lat.toFixed ? lat.toFixed(8) : lat);
    $('#job-longitude').val(lng.toFixed ? lng.toFixed(8) : lng);
    if (flyTo !== false) map.flyTo([lat, lng], 16);
    reverseGeocode(lat, lng);
}

/* ─── Jordanian postal codes fallback table ─── */
var _joPostal = {
    'عمان':'11110','جبل عمان':'11181','العبدلي':'11190','الشميساني':'11194','جبل الحسين':'11118',
    'جبل اللويبدة':'11191','ماركا':'11511','طارق':'11947','الهاشمي':'11141','المقابلين':'11710',
    'أبو نصير':'11764','شفا بدران':'11934','الجبيهة':'11941','صويلح':'19110','تلاع العلي':'11183',
    'خلدا':'11953','الرابية':'11215','ضاحية الرشيد':'11593','ضاحية الأمير حسن':'11842',
    'الدوار السابع':'11195','أم أذينة':'11821','الصويفية':'11910','دير غبار':'11954',
    'طبربور':'11171','الرصيفة':'13710','الزرقاء':'13110','الهاشمية':'13125',
    'إربد':'21110','الحصن':'21510','الرمثا':'21410','حواره':'21146','المزار الشمالي':'21610',
    'دير أبي سعيد':'21710','الطيبة':'21810','كفرسوم':'21941','أم قيس':'21986',
    'جرش':'26110','عجلون':'26810','المفرق':'25110',
    'السلط':'19110','الفحيص':'19152','ماحص':'19160','عين الباشا':'19484',
    'مادبا':'17110','ذيبان':'17711',
    'الكرك':'61110','المزار الجنوبي':'61510','غور الصافي':'61710',
    'الطفيلة':'66110','بصيرا':'66165',
    'معان':'71110','الشوبك':'71810','البتراء':'71810',
    'العقبة':'77110',
    'الشونة الشمالية':'28110','الأغوار':'28110','ديرعلا':'25810','الشونة الجنوبية':'18110',
    'وادي السير':'11821','ناعور':'11710','الموقر':'11218','الجيزة':'11814',
    'سحاب':'11512','القويسمة':'11164','المدينة الرياضية':'11196','الياسمين':'11264',
    'الجامعة الأردنية':'11942','مطار الملكة علياء':'11104'
};

function lookupPostal(city, area) {
    if (!city && !area) return '';
    var normalize = function(s) { return s.replace(/[\u0640\u064B-\u065F]/g,'').replace(/[أإآ]/g,'ا').replace(/ة/g,'ه').replace(/ى/g,'ي').trim(); };
    if (area) { var na = normalize(area); for (var k in _joPostal) { if (normalize(k) === na) return _joPostal[k]; } }
    if (city) { var nc = normalize(city); for (var k in _joPostal) { if (normalize(k) === nc) return _joPostal[k]; } }
    if (city) { var nc = normalize(city); for (var k in _joPostal) { if (nc.indexOf(normalize(k)) !== -1 || normalize(k).indexOf(nc) !== -1) return _joPostal[k]; } }
    return '';
}

/* ─── Reverse Geocoding: coordinates → address fields ─── */
var _rgTimer = null;
function reverseGeocode(lat, lng) {
    clearTimeout(_rgTimer);

    var plusCode = encodePlusCode(lat, lng);
    $('#jobs-plus_code').val(plusCode);

    _rgTimer = setTimeout(function() {
        $.getJSON('https://nominatim.openstreetmap.org/reverse', {
            lat: lat, lon: lng, format: 'json', addressdetails: 1,
            'accept-language': 'ar', zoom: 18
        }, function(data) {
            var a = (data && data.address) ? data.address : {};

            var city = a.city || a.town || a.village || a.county || a.state || '';
            var area = a.suburb || a.neighbourhood || a.quarter || a.hamlet || '';
            var street = a.road || a.pedestrian || a.footway || '';
            var building = a.house_number || '';
            var postal = a.postcode || lookupPostal(city, area);

            $('#jobs-address_city').val(city);
            $('#jobs-address_area').val(area);
            $('#jobs-address_street').val(street);
            $('#jobs-address_building').val(building);
            $('#jobs-postal_code').val(postal);

            $('#jobs-address_city, #jobs-address_area, #jobs-address_street, #jobs-address_building, #jobs-postal_code').filter(function() { return $(this).val(); }).addClass('geo-filled');
            setTimeout(function() { $('.geo-filled').removeClass('geo-filled'); }, 2500);

            var popup = '<div style="direction:rtl;font-size:12px;line-height:1.6;max-width:240px">';
            var parts = [street, area, city].filter(Boolean);
            popup += '<strong>' + (parts.length ? parts.join('، ') : (data && data.display_name ? data.display_name.split('،').slice(0,3).join('،') : '')) + '</strong>';
            if (postal) popup += '<br><i class="fa fa-envelope-o" style="color:#94a3b8"></i> ' + postal;
            if (plusCode) popup += '<br><span style="color:#4285f4;font-family:monospace;font-size:11px"><i class="fa fa-plus-square"></i> ' + plusCode + '</span>';
            popup += '</div>';
            if (marker) marker.bindPopup(popup).openPopup();
        });
    }, 300);
}

/* ─── Plus Code (Open Location Code) encoder ─── */
function encodePlusCode(lat, lng) {
    var CHARS = '23456789CFGHJMPQRVWX';
    lat = Math.min(90, Math.max(-90, lat)) + 90;
    lng = Math.min(180, Math.max(-180, lng)) + 180;
    var code = '';
    var rLat = 20, rLng = 20;
    for (var i = 0; i < 5; i++) {
        var dLat = Math.floor(lat / rLat);
        var dLng = Math.floor(lng / rLng);
        lat -= dLat * rLat;
        lng -= dLng * rLng;
        code += CHARS.charAt(dLat) + CHARS.charAt(dLng);
        rLat /= 20; rLng /= 20;
        if (i === 3) code += '+';
    }
    return code;
}

if ($('#job-latitude').val() && $('#job-longitude').val()) {
    setMapMarker(initLat, initLng, false);
}

map.on('click', function(e){
    setMapMarker(e.latlng.lat, e.latlng.lng, false);
});

/* ─── بحث على الخريطة ─── */
var searchTimer = null;
var _googlePlacesActive = false;

function fallbackMapSearch(q) {
    if (!q || q.length < 2) { $('#map-search-results').removeClass('show').empty(); return; }
    $('#map-search-results').html('<div class="map-search-loading"><i class="fa fa-spinner fa-spin"></i> جاري البحث...</div>').addClass('show');
    var mapCenter = map.getCenter();
    $.getJSON('https://photon.komoot.io/api/', {
        q: q, lang: 'ar', lat: mapCenter.lat, lon: mapCenter.lng, limit: 6
    }, function(data){
        if (!data || !data.features || data.features.length === 0) {
            $.getJSON('https://nominatim.openstreetmap.org/search', {
                q: q, format: 'json', limit: 6, addressdetails: 1, 'accept-language': 'ar',
                viewbox: '34.8,33.4,39.3,29.1', bounded: 0
            }, function(nd){
                if (!nd || nd.length === 0) { $('#map-search-results').html('<div class="map-search-loading">لا توجد نتائج</div>').addClass('show'); return; }
                var html = '';
                nd.forEach(function(r){
                    html += '<div class="result-item" data-lat="'+r.lat+'" data-lng="'+r.lon+'">';
                    html += '<span class="result-icon"><i class="fa fa-map-marker"></i></span>';
                    html += '<span class="result-text"><span class="result-name">'+r.display_name+'</span></span>';
                    html += '</div>';
                });
                $('#map-search-results').html(html).addClass('show');
            });
            return;
        }
        var html = '';
        data.features.forEach(function(f){
            var p = f.properties, g = f.geometry;
            var name = p.name || p.street || '';
            var addr = [p.city, p.state, p.country].filter(Boolean).join('، ');
            var osmVal = p.osm_value || p.osm_key || '';
            var icon = 'fa-map-marker';
            if (['restaurant','cafe','fast_food','bar'].indexOf(osmVal) >= 0) icon = 'fa-cutlery';
            else if (['hospital','clinic','pharmacy','doctors'].indexOf(osmVal) >= 0) icon = 'fa-medkit';
            else if (['school','university','college'].indexOf(osmVal) >= 0) icon = 'fa-graduation-cap';
            else if (['supermarket','shop','mall','marketplace'].indexOf(osmVal) >= 0) icon = 'fa-shopping-cart';
            else if (['bank'].indexOf(osmVal) >= 0) icon = 'fa-university';
            else if (['hotel','hostel','guest_house'].indexOf(osmVal) >= 0) icon = 'fa-bed';
            else if (['fuel','gas'].indexOf(osmVal) >= 0) icon = 'fa-car';
            else if (['place_of_worship','mosque'].indexOf(osmVal) >= 0) icon = 'fa-moon-o';
            else if (['office','company','commercial'].indexOf(osmVal) >= 0) icon = 'fa-building';
            else if (p.osm_key === 'highway' || p.osm_key === 'road') icon = 'fa-road';
            else if (p.osm_key === 'place') icon = 'fa-map-pin';
            html += '<div class="result-item" data-lat="'+g.coordinates[1]+'" data-lng="'+g.coordinates[0]+'">';
            html += '<span class="result-icon"><i class="fa '+icon+'"></i></span>';
            html += '<span class="result-text"><span class="result-name">'+name+'</span>';
            if (addr) html += '<span class="result-addr">'+addr+'</span>';
            html += '</span></div>';
        });
        $('#map-search-results').html(html).addClass('show');
    }).fail(function(){
        doNominatimFallback(q);
    });
}
function doNominatimFallback(q) {
    $.getJSON('https://nominatim.openstreetmap.org/search', {
        q: q, format: 'json', limit: 6, addressdetails: 1, 'accept-language': 'ar',
        viewbox: '34.8,33.4,39.3,29.1', bounded: 1
    }, function(nd){
        if (!nd || nd.length === 0) { $('#map-search-results').html('<div class="map-search-loading">لا توجد نتائج</div>').addClass('show'); return; }
        var html = '';
        nd.forEach(function(r){
            html += '<div class="result-item" data-lat="'+r.lat+'" data-lng="'+r.lon+'">';
            html += '<span class="result-icon"><i class="fa fa-map-marker"></i></span>';
            html += '<span class="result-text"><span class="result-name">'+r.display_name+'</span></span>';
            html += '</div>';
        });
        $('#map-search-results').html(html).addClass('show');
    }).fail(function(){
        $('#map-search-results').html('<div class="map-search-loading">خطأ في البحث</div>').addClass('show');
    });
}

$('#map-search-input').on('input', function(){
    if (_googlePlacesActive) return;
    clearTimeout(searchTimer);
    var q = $(this).val().trim();
    if (q.length < 2) { $('#map-search-results').removeClass('show').empty(); return; }
    searchTimer = setTimeout(function(){ fallbackMapSearch(q); }, 350);
});
$('#map-search-input').on('keydown', function(e){
    if (_googlePlacesActive) return;
    if (e.keyCode === 13) { e.preventDefault(); clearTimeout(searchTimer); fallbackMapSearch($(this).val().trim()); }
});
$('#btn-map-search').on('click', function(){
    if (_googlePlacesActive) return;
    clearTimeout(searchTimer); fallbackMapSearch($('#map-search-input').val().trim());
});
$(document).on('click', '#map-search-results .result-item', function(){
    var lat = parseFloat($(this).data('lat'));
    var lng = parseFloat($(this).data('lng'));
    if (!isNaN(lat) && !isNaN(lng)) {
        setMapMarker(lat, lng, true);
        var name = $(this).find('.result-name').text().trim();
        $('#map-search-input').val(name);
    }
    $('#map-search-results').removeClass('show');
});
$('#map-search-input').on('blur', function(){
    if (!_googlePlacesActive) setTimeout(function(){ $('#map-search-results').removeClass('show'); }, 300);
});

/* ─── Google Places Autocomplete (tries New API first, falls back to Legacy) ─── */
function tryInitGooglePlaces() {
    if (typeof google === 'undefined' || !google.maps || !google.maps.places) return false;
    if (_googlePlacesActive) return true;

    var wrap = document.querySelector('.map-search-wrap');
    if (!wrap) return false;

    // Try New API (PlaceAutocompleteElement) — works with "Places API (New)"
    if (google.maps.places.PlaceAutocompleteElement) {
        try {
            var pac = new google.maps.places.PlaceAutocompleteElement({
                locationBias: { north: 33.4, south: 29.1, east: 39.3, west: 34.8 }
            });
            pac.id = 'gmp-place-input';
            pac.style.cssText = 'width:100%;';
            pac.setAttribute('placeholder', 'ابحث بالاسم: شركة، مستشفى، مطعم، شارع...');

            $('#map-search-input').hide();
            $('#btn-map-search').hide();
            $('#map-search-results').remove();
            wrap.insertBefore(pac, wrap.firstChild);

            pac.addEventListener('gmp-select', async function(e) {
                var place = e.placePrediction.toPlace();
                await place.fetchFields({ fields: ['displayName', 'formattedAddress', 'location'] });
                if (place.location) {
                    setMapMarker(place.location.lat(), place.location.lng(), true);
                }
            });

            _googlePlacesActive = true;
            return true;
        } catch(e) { /* fall through to legacy */ }
    }

    // Legacy API (Autocomplete) — works with old "Places API"
    if (google.maps.places.Autocomplete) {
        var input = document.getElementById('map-search-input');
        var autocomplete = new google.maps.places.Autocomplete(input, {
            fields: ['geometry', 'name', 'formatted_address']
        });
        autocomplete.setBounds(new google.maps.LatLngBounds(
            new google.maps.LatLng(29.1, 34.8),
            new google.maps.LatLng(33.4, 39.3)
        ));
        autocomplete.addListener('place_changed', function() {
            var place = autocomplete.getPlace();
            if (place && place.geometry) {
                setMapMarker(place.geometry.location.lat(), place.geometry.location.lng(), true);
                input.value = place.name || place.formatted_address || '';
            }
        });
        $('#map-search-input').off('input keydown');
        $('#btn-map-search').hide();
        $('#map-search-results').remove();
        _googlePlacesActive = true;
        return true;
    }

    return false;
}
if (!tryInitGooglePlaces()) {
    var _gpRetry = setInterval(function(){
        if (tryInitGooglePlaces()) clearInterval(_gpRetry);
    }, 800);
    setTimeout(function(){ clearInterval(_gpRetry); }, 12000);
}

/* ═══════════════════════════════════════════════════════════
 *  4. لصق الموقع الذكي (Smart Location Paste)
 * ═══════════════════════════════════════════════════════════ */
var smartLocTimer = null;
$('#smart-location-paste').on('input', function(){
    clearTimeout(smartLocTimer);
    var raw = $(this).val().trim();
    if (!raw) { $('#smart-loc-parsed').removeClass('show').removeAttr('style'); return; }

    var coords = parseLocationInput(raw);
    if (coords) {
        smartLocSuccess(coords.lat, coords.lng);
        return;
    }

    var isUrl = /^https?:\/\//i.test(raw);
    var isPlusCode = /[23456789CFGHJMPQRVWX]{2,}\+/i.test(raw);
    var delay = (isUrl || isPlusCode) ? 100 : 600;

    $('#smart-loc-parsed').html('<i class="fa fa-spinner fa-spin"></i> جاري التحليل...').css({background:'#fef3c7',color:'#92400e'}).addClass('show');

    smartLocTimer = setTimeout(function(){
        $.getJSON('$resolveLocationUrl', {q: raw}, function(data){
            if (data && data.success) {
                var lat = parseFloat(data.lat), lng = parseFloat(data.lng);
                setMapMarker(lat, lng, true);
                var msg = '<i class="fa fa-check-circle"></i> ';
                if (data.display_name) {
                    msg += data.display_name + ' (' + lat.toFixed(6) + ', ' + lng.toFixed(6) + ')';
                } else {
                    msg += 'تم التعرف على الموقع: ' + lat.toFixed(6) + ', ' + lng.toFixed(6);
                }
                $('#smart-loc-parsed').html(msg).css({background:'#dcfce7',color:'#15803d'}).addClass('show');
            } else {
                smartLocFail();
            }
        }).fail(function(){ smartLocFail(); });
    }, delay);
});

function smartLocSuccess(lat, lng) {
    setMapMarker(lat, lng, true);
    $('#smart-loc-parsed').html('<i class="fa fa-check-circle"></i> تم التعرف على الموقع: ' + lat.toFixed(6) + ', ' + lng.toFixed(6))
        .css({background:'#dcfce7',color:'#15803d'}).addClass('show');
}
function smartLocFail() {
    $('#smart-loc-parsed').html('<i class="fa fa-exclamation-circle"></i> لم يتم التعرف على الموقع. جرب إحداثيات عددية أو رابط جوجل ماب أو Plus Code.')
        .css({background:'#fee2e2',color:'#b91c1c'}).addClass('show');
}

function parseLocationInput(raw) {
    var m;
    /* 1. Decimal coordinates */
    m = raw.match(/(-?\d+\.?\d*)[,\s]+(-?\d+\.?\d*)/);
    if (m) {
        var a = parseFloat(m[1]), b = parseFloat(m[2]);
        if (!isNaN(a) && !isNaN(b)) {
            if (Math.abs(a) <= 90 && Math.abs(b) <= 180) return {lat: a, lng: b};
            if (Math.abs(b) <= 90 && Math.abs(a) <= 180) return {lat: b, lng: a};
        }
    }
    /* 2. Google Maps URL with coordinates */
    m = raw.match(/@(-?\d+\.\d+),(-?\d+\.\d+)/);
    if (m) return {lat: parseFloat(m[1]), lng: parseFloat(m[2])};
    m = raw.match(/[?&]q=(-?\d+\.\d+),(-?\d+\.\d+)/);
    if (m) return {lat: parseFloat(m[1]), lng: parseFloat(m[2])};
    m = raw.match(/!3d(-?\d+\.\d+).*!4d(-?\d+\.\d+)/);
    if (m) return {lat: parseFloat(m[1]), lng: parseFloat(m[2])};
    /* 3. DMS coordinates */
    var dmsRe = /(\d+)[°](\d+)[′''](\d+\.?\d*)[″""]?\s*([NSns])\s*,?\s*(\d+)[°](\d+)[′''](\d+\.?\d*)[″""]?\s*([EWew])/;
    m = raw.match(dmsRe);
    if (m) {
        var lat = parseInt(m[1]) + parseInt(m[2])/60 + parseFloat(m[3])/3600;
        if (m[4].toLowerCase() === 's') lat = -lat;
        var lng = parseInt(m[5]) + parseInt(m[6])/60 + parseFloat(m[7])/3600;
        if (m[8].toLowerCase() === 'w') lng = -lng;
        return {lat: lat, lng: lng};
    }
    return null;
}

/* ═══════════════════════════════════════════════════════════
 *  5. أدوات مساعدة
 * ═══════════════════════════════════════════════════════════ */
$('#btn-get-location').on('click', function(){
    var btn = $(this);
    btn.prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> جاري...');
    if (navigator.geolocation) {
        navigator.geolocation.getCurrentPosition(function(pos){
            setMapMarker(pos.coords.latitude, pos.coords.longitude, true);
            btn.prop('disabled', false).html('<i class="fa fa-crosshairs"></i> موقعي الحالي');
        }, function(err){
            alert('لم نتمكن من تحديد موقعك: ' + err.message);
            btn.prop('disabled', false).html('<i class="fa fa-crosshairs"></i> موقعي الحالي');
        }, {enableHighAccuracy: true, timeout: 10000});
    } else {
        alert('المتصفح لا يدعم تحديد الموقع');
        btn.prop('disabled', false).html('<i class="fa fa-crosshairs"></i> موقعي الحالي');
    }
});

$('#btn-clear-location').on('click', function(){
    if (marker) { map.removeLayer(marker); marker = null; }
    $('#job-latitude, #job-longitude').val('');
    $('#jobs-address_city, #jobs-address_area, #jobs-address_street, #jobs-address_building, #jobs-postal_code, #jobs-plus_code').val('');
    map.setView([defaultLat, defaultLng], 8);
    $('#smart-location-paste').val('');
    $('#smart-loc-parsed').removeClass('show');
});

/* ─── Address fields → map sync (forward geocoding) ─── */
var _addrGeoTimer = null;
$('.addr-field').on('change', function() {
    clearTimeout(_addrGeoTimer);
    _addrGeoTimer = setTimeout(function() {
        var parts = [
            $('#jobs-address_street').val(),
            $('#jobs-address_area').val(),
            $('#jobs-address_city').val()
        ].filter(Boolean);
        if (!parts.length) return;
        var q = parts.join(', ');
        $.getJSON('https://nominatim.openstreetmap.org/search', {
            q: q, format: 'json', limit: 1, 'accept-language': 'ar',
            viewbox: '34.8,33.4,39.3,29.1', bounded: 1
        }, function(results) {
            if (results && results.length > 0) {
                var r = results[0];
                setMapMarker(parseFloat(r.lat), parseFloat(r.lon), true);
            }
        });
    }, 500);
});

/* ─── Google Places autocomplete on address text fields ─── */
function initAddrAutocomplete() {
    if (typeof google === 'undefined' || !google.maps || !google.maps.places) return false;

    var fields = document.querySelectorAll('.addr-field');
    if (!fields.length) return false;

    fields.forEach(function(input) {
        if (input._addrAcInit) return;
        input._addrAcInit = true;

        // Try New API
        if (google.maps.places.PlaceAutocompleteElement) {
            var wrap = document.createElement('div');
            wrap.style.cssText = 'position:relative;';
            input.parentNode.insertBefore(wrap, input);
            wrap.appendChild(input);

            var pac = new google.maps.places.PlaceAutocompleteElement({
                locationBias: { north: 33.4, south: 29.1, east: 39.3, west: 34.8 }
            });
            pac.style.cssText = 'position:absolute;top:100%;right:0;left:0;z-index:500;display:none;';
            wrap.appendChild(pac);

            input.addEventListener('focus', function() { pac.style.display = ''; });
            input.addEventListener('blur', function() { setTimeout(function(){ pac.style.display = 'none'; }, 300); });

            pac.addEventListener('gmp-select', async function(e) {
                var place = e.placePrediction.toPlace();
                await place.fetchFields({ fields: ['displayName', 'formattedAddress', 'location', 'addressComponents'] });
                if (place.location) {
                    setMapMarker(place.location.lat(), place.location.lng(), true);
                }
                pac.style.display = 'none';
            });
            return;
        }

        // Legacy API fallback
        if (google.maps.places.Autocomplete) {
            var ac = new google.maps.places.Autocomplete(input, {
                fields: ['geometry', 'name', 'formatted_address', 'address_components'],
                types: ['geocode', 'establishment']
            });
            ac.setBounds(new google.maps.LatLngBounds(
                new google.maps.LatLng(29.1, 34.8),
                new google.maps.LatLng(33.4, 39.3)
            ));
            ac.addListener('place_changed', function() {
                var place = ac.getPlace();
                if (place && place.geometry) {
                    setMapMarker(place.geometry.location.lat(), place.geometry.location.lng(), true);
                }
            });
        }
    });
    return true;
}
if (!initAddrAutocomplete()) {
    var _addrAcRetry = setInterval(function(){
        if (initAddrAutocomplete()) clearInterval(_addrAcRetry);
    }, 1000);
    setTimeout(function(){ clearInterval(_addrAcRetry); }, 12000);
}

/* Working hours toggle */
$('.wh-closed-toggle').on('change', function(){
    var row = $(this).closest('tr');
    var times = row.find('.wh-time');
    if ($(this).is(':checked')) { times.prop('disabled', true).val(''); row.addClass('closed-row'); }
    else { times.prop('disabled', false); row.removeClass('closed-row'); }
});

/* Fix Leaflet map rendering (sometimes tiles don't load on hidden tabs) */
setTimeout(function(){ map.invalidateSize(); }, 500);

updatePhoneBadge();
JS;
$this->registerJs($js);
?>
