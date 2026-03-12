<?php
/**
 * شاشة متابعة العقد الرئيسية
 * تعرض نموذج إضافة متابعة + جدول المتابعات السابقة + أدوات إضافية
 */
use yii\helpers\Url;
use yii\helpers\Html;
use yii\bootstrap\Modal;
use kartik\grid\GridView;
use johnitvn\ajaxcrud\CrudAsset;
use common\helper\Permissions;

CrudAsset::register($this);
$this->title = 'متابعة العقد #' . $contract_id;
$this->params['breadcrumbs'][] = ['label' => 'العقود', 'url' => ['/contracts/contracts/index']];
$this->params['breadcrumbs'][] = $this->title;

$contractStatus = $contract_model->status ?? '';
$isLegalOrJudiciary = in_array($contractStatus, ['judiciary', 'legal_department']);
?>

<div class="follow-up-index">

    <!-- ═══ نموذج المتابعة ═══ -->
    <?php if (Permissions::can(Permissions::FOLLOWUP_CREATE)): ?>
    <div class="box box-primary">
        <div class="box-header with-border">
            <h3 class="box-title"><i class="fa fa-comments"></i> إضافة متابعة جديدة</h3>
        </div>
        <div class="box-body">
            <?= $this->render('_form', [
                'modelsPhoneNumbersFollwUps' => $modelsPhoneNumbersFollwUps,
                'contract_model' => $contract_model,
                'contract_id' => $contract_id,
                'model' => $model,
            ]) ?>
        </div>
    </div>
    <?php endif ?>

    <!-- ═══ جدول المتابعات السابقة ═══ -->
    <div class="box box-primary">
        <div class="box-header with-border">
            <h3 class="box-title"><i class="fa fa-history"></i> المتابعات السابقة</h3>
        </div>
        <div class="box-body">
            <?= GridView::widget([
                'dataProvider' => $dataProvider,
                'summary' => '<span class="text-muted" style="font-size:12px">عرض {begin}-{end} من {totalCount}</span>',
                'columns' => [
                    ['attribute' => 'date_time', 'label' => 'التاريخ', 'contentOptions' => ['style' => 'white-space:nowrap']],
                    ['attribute' => 'notes', 'label' => 'الملاحظات', 'contentOptions' => ['style' => 'white-space:pre-line;direction:rtl;max-width:300px']],
                    ['attribute' => 'promise_to_pay_at', 'label' => 'وعد بالدفع'],
                    ['attribute' => 'reminder', 'label' => 'التذكير'],
                    ['attribute' => 'created_by', 'label' => 'المتابِع', 'value' => fn($m) => $m->createdBy->username ?? '—'],
                    ['attribute' => 'feeling', 'label' => 'الانطباع', 'value' => fn($m) => Yii::t('app', $m->feeling)],
                    [
                        'class' => 'yii\grid\ActionColumn',
                        'header' => 'إجراءات',
                        'template' => '{view}{update}',
                        'buttons' => [
                            'view' => function ($url, $m) {
                                if (!Permissions::can(Permissions::FOLLOWUP_VIEW)) return '';
                                return Html::a('<i class="fa fa-eye"></i>', ['view', 'id' => $m->id, 'contract_id' => $m->contract_id], ['class' => 'btn btn-info btn-xs', 'data-pjax' => '0']);
                            },
                            'update' => function ($url, $m) {
                                if (!Permissions::can(Permissions::FOLLOWUP_UPDATE)) return '';
                                $created = new DateTime($m->date_time);
                                $now = new DateTime();
                                if ($m->created_by == Yii::$app->user->id && $created->diff($now)->days < 1) {
                                    return Html::a('<i class="fa fa-pencil"></i>', ['update', 'id' => $m->id, 'contract_id' => $m->contract_id], ['class' => 'btn btn-warning btn-xs', 'data-pjax' => '0']);
                                }
                                return '';
                            },
                        ],
                    ],
                ],
                'striped' => true, 'condensed' => true, 'responsive' => true,
                'export' => false,
            ]) ?>
        </div>
    </div>

    <!-- ═══ أدوات إضافية ═══ -->
    <?php if (!$isLegalOrJudiciary): ?>
        <div class="box box-warning">
            <div class="box-header with-border">
                <h3 class="box-title"><i class="fa fa-gavel"></i> التحويل للدائرة القانونية</h3>
            </div>
            <div class="box-body">
                <?= Html::a('<i class="fa fa-exchange"></i> تحويل', ['/contracts/contracts/to-legal-department', 'id' => $contract_id], ['class' => 'btn btn-warning']) ?>
            </div>
        </div>
    <?php endif ?>

    <div class="box box-info">
        <div class="box-header with-border">
            <h3 class="box-title"><i class="fa fa-user-md"></i> طلب مراجعة المدير</h3>
        </div>
        <div class="box-body">
            <?= Html::a('<i class="fa fa-paper-plane"></i> طلب مراجعة', ['/contracts/contracts/convert-to-manager', 'id' => $contract_id], ['class' => 'btn btn-info']) ?>
        </div>
    </div>

    <?= $this->render('partial/next_contract.php', ['model' => $model, 'contract_id' => $contract_id]) ?>
</div>

<?php Modal::begin(['id' => 'ajaxCrudModal', 'footer' => '']) ?>
<?php Modal::end() ?>
