<?php
/**
 * تبويب المحولين للشكوى — GridView أصلي (بدون iframe)
 * يُعرض عبر AJAX داخل الشاشة الموحدة — مطابق لتبويب القضايا
 */
use yii\helpers\Url;
use yii\helpers\Html;
use yii\widgets\Pjax;
use kartik\grid\GridView;
use common\helper\Permissions;
use backend\widgets\ExportButtons;
?>

<script>
$('#lh-badge-legal').text('<?= $dataCount ?>');
</script>

<?php Pjax::begin(['id' => 'legal-pjax', 'timeout' => 10000]) ?>
<div id="legalDatatable">
    <?= GridView::widget([
        'id' => 'legal-datatable',
        'dataProvider' => $dataProvider,
        'toggleData' => false,
        'columns' => (require __DIR__ . '/_legal_columns.php'),
        'summary' => '<span class="text-muted" style="font-size:12px">عرض {begin}-{end} من {totalCount} عقد</span>',
        'pjax' => true,
        'pjaxSettings' => [
            'options' => ['id' => 'legal-grid-pjax'],
            'neverTimeout' => true,
        ],
        'toolbar' => [
            [
                'content' =>
                    Html::a('<i class="fa fa-refresh"></i>', ['tab-legal'], ['data-pjax' => 1, 'class' => 'btn btn-secondary', 'title' => 'تحديث']) .
                    ExportButtons::widget([
                        'excelRoute' => '/contracts/contracts/export-legal-excel',
                        'pdfRoute'   => '/contracts/contracts/export-legal-pdf',
                    ])
            ],
        ],
        'striped' => true,
        'condensed' => true,
        'responsive' => true,
        'panel' => [
            'heading' => '<i class="fa fa-legal"></i> المحولين للشكوى <span class="badge">' . $dataCount . '</span>',
        ],
    ]) ?>
</div>
<?php Pjax::end() ?>
