<?php

use yii\helpers\Html;
use backend\modules\accounting\models\JournalEntry;

$this->title = 'قيد رقم ' . $model->entry_number;
$this->params['breadcrumbs'][] = ['label' => 'المحاسبة', 'url' => ['/accounting']];
$this->params['breadcrumbs'][] = ['label' => 'القيود اليومية', 'url' => ['index']];
$this->params['breadcrumbs'][] = $model->entry_number;
?>

<div class="row">
    <div class="col-md-9">
        <div class="box box-primary">
            <div class="box-header with-border">
                <h3 class="box-title"><i class="fa fa-book"></i> <?= Html::encode($this->title) ?></h3>
                <div class="box-tools">
                    <?php if ($model->status === JournalEntry::STATUS_DRAFT): ?>
                        <?= Html::a('<i class="fa fa-check"></i> ترحيل', ['post', 'id' => $model->id], [
                            'class' => 'btn btn-success btn-sm',
                            'data' => ['confirm' => 'هل أنت متأكد من ترحيل هذا القيد؟', 'method' => 'post'],
                        ]) ?>
                        <?= Html::a('<i class="fa fa-edit"></i> تعديل', ['update', 'id' => $model->id], ['class' => 'btn btn-primary btn-sm']) ?>
                        <?= Html::a('<i class="fa fa-trash"></i> حذف', ['delete', 'id' => $model->id], [
                            'class' => 'btn btn-danger btn-sm',
                            'data' => ['confirm' => 'هل أنت متأكد من حذف هذا القيد؟', 'method' => 'post'],
                        ]) ?>
                    <?php elseif ($model->status === JournalEntry::STATUS_POSTED): ?>
                        <?= Html::a('<i class="fa fa-undo"></i> عكس القيد', ['reverse', 'id' => $model->id], [
                            'class' => 'btn btn-warning btn-sm',
                            'data' => ['confirm' => 'هل أنت متأكد من عكس هذا القيد؟ سيتم إنشاء قيد عكسي جديد.', 'method' => 'post'],
                        ]) ?>
                    <?php endif; ?>
                </div>
            </div>
            <div class="box-body">
                <fieldset class="jadal-fieldset">
                    <legend>بيانات القيد</legend>
                    <div class="row">
                        <div class="col-md-3">
                            <div class="cv-field">
                                <span class="cv-label">رقم القيد</span>
                                <span class="cv-value" style="font-family:monospace; font-size:18px; font-weight:700;"><?= Html::encode($model->entry_number) ?></span>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="cv-field">
                                <span class="cv-label">التاريخ</span>
                                <span class="cv-value"><?= $model->entry_date ?></span>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="cv-field">
                                <span class="cv-label">الحالة</span>
                                <span class="cv-value"><?= $model->getStatusBadge() ?></span>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="cv-field">
                                <span class="cv-label">نوع المرجع</span>
                                <span class="cv-value"><?= JournalEntry::getReferenceTypes()[$model->reference_type] ?? $model->reference_type ?></span>
                            </div>
                        </div>
                    </div>
                    <div class="row" style="margin-top:10px;">
                        <div class="col-md-12">
                            <div class="cv-field">
                                <span class="cv-label">البيان</span>
                                <span class="cv-value"><?= Html::encode($model->description) ?></span>
                            </div>
                        </div>
                    </div>
                </fieldset>

                <fieldset class="jadal-fieldset">
                    <legend>بنود القيد</legend>
                    <table class="table table-bordered table-condensed table-striped">
                        <thead>
                            <tr style="background:#f5f6f8;">
                                <th class="text-center">#</th>
                                <th>الحساب</th>
                                <th>مركز التكلفة</th>
                                <th class="text-center">مدين</th>
                                <th class="text-center">دائن</th>
                                <th>البيان</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($model->lines as $i => $line): ?>
                            <tr>
                                <td class="text-center"><?= $i + 1 ?></td>
                                <td>
                                    <span style="font-family:monospace; color:var(--clr-primary, #800020);"><?= Html::encode($line->account->code) ?></span>
                                    <?= Html::encode($line->account->name_ar) ?>
                                </td>
                                <td><?= $line->costCenter ? Html::encode($line->costCenter->name) : '—' ?></td>
                                <td class="text-left" style="font-weight:600; <?= $line->debit > 0 ? 'color:#28a745;' : '' ?>">
                                    <?= $line->debit > 0 ? number_format($line->debit, 2) : '' ?>
                                </td>
                                <td class="text-left" style="font-weight:600; <?= $line->credit > 0 ? 'color:#dc3545;' : '' ?>">
                                    <?= $line->credit > 0 ? number_format($line->credit, 2) : '' ?>
                                </td>
                                <td><?= Html::encode($line->description) ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                        <tfoot>
                            <tr style="background:#f5f6f8; font-weight:800; font-size:15px;">
                                <td colspan="3" class="text-left">المجموع</td>
                                <td class="text-left" style="color:#28a745;"><?= number_format($model->total_debit, 2) ?></td>
                                <td class="text-left" style="color:#dc3545;"><?= number_format($model->total_credit, 2) ?></td>
                                <td></td>
                            </tr>
                        </tfoot>
                    </table>
                </fieldset>
            </div>
        </div>
    </div>

    <div class="col-md-3">
        <div class="box box-default">
            <div class="box-header"><h3 class="box-title">معلومات إضافية</h3></div>
            <div class="box-body">
                <table class="table table-condensed" style="font-size:13px;">
                    <tr><th>السنة المالية</th><td><?= $model->fiscalYear ? Html::encode($model->fiscalYear->name) : '—' ?></td></tr>
                    <tr><th>الفترة</th><td><?= $model->fiscalPeriod ? Html::encode($model->fiscalPeriod->name) : '—' ?></td></tr>
                    <tr><th>أنشئ بواسطة</th><td><?= $model->createdByUser ? Html::encode($model->createdByUser->username) : '—' ?></td></tr>
                    <tr><th>تاريخ الإنشاء</th><td><?= $model->created_at ? date('Y-m-d H:i', $model->created_at) : '—' ?></td></tr>
                    <?php if ($model->approved_at): ?>
                    <tr><th>تاريخ الاعتماد</th><td><?= date('Y-m-d H:i', $model->approved_at) ?></td></tr>
                    <?php endif; ?>
                    <?php if ($model->reversed_by): ?>
                    <tr><th>قيد العكس</th><td><?= Html::a($model->reversalEntry->entry_number, ['view', 'id' => $model->reversed_by]) ?></td></tr>
                    <?php endif; ?>
                </table>
            </div>
        </div>
    </div>
</div>
