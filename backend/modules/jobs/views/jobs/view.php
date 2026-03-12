<?php

use yii\helpers\Html;
use yii\helpers\Url;
use yii\widgets\DetailView;
use backend\modules\jobs\models\Jobs;
use backend\modules\jobs\models\JobsPhone;
use backend\modules\jobs\models\JobsRating;
use backend\helpers\NameHelper;

/* @var $this yii\web\View */
/* @var $model backend\modules\jobs\models\Jobs */
/* @var $phones backend\modules\jobs\models\JobsPhone[] */
/* @var $workingHours backend\modules\jobs\models\JobsWorkingHours[] */
/* @var $ratings backend\modules\jobs\models\JobsRating[] */

$this->title = $model->name;
$this->params['breadcrumbs'][] = ['label' => 'جهات العمل', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;

$avgRating = $model->getAverageRating();
$avgJudicialRating = $model->getAverageRating('judicial_response');
$customersCount = $model->getCustomersCount();
?>

<div class="jobs-view">

    <!-- =============== Header with Actions =============== -->
    <div class="row" style="margin-bottom: 15px;">
        <div class="col-md-8">
            <h3 style="margin-top: 0;">
                <i class="fa fa-building"></i> <?= Html::encode($model->name) ?>
                <?= $model->getStatusBadge() ?>
            </h3>
            <?php if ($model->jobType): ?>
                <span class="label label-info"><?= Html::encode($model->jobType->name) ?></span>
            <?php endif; ?>
        </div>
        <div class="col-md-4 text-left">
            <?= Html::a('<i class="fa fa-pencil"></i> تعديل', ['update', 'id' => $model->id], ['class' => 'btn btn-primary']) ?>
            <?= Html::a('<i class="fa fa-trash"></i> حذف', ['delete', 'id' => $model->id], [
                'class' => 'btn btn-danger',
                'data' => [
                    'confirm' => 'هل أنت متأكد من حذف جهة العمل هذه؟',
                    'method' => 'post',
                ],
            ]) ?>
            <?= Html::a('<i class="fa fa-arrow-right"></i> رجوع', ['index'], ['class' => 'btn btn-default']) ?>
        </div>
    </div>

    <!-- =============== Summary Cards =============== -->
    <div class="row">
        <div class="col-md-3">
            <div class="info-box">
                <span class="info-box-icon bg-aqua"><i class="fa fa-users"></i></span>
                <div class="info-box-content">
                    <span class="info-box-text">العملاء المرتبطين</span>
                    <span class="info-box-number"><?= $customersCount ?></span>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="info-box">
                <span class="info-box-icon bg-green"><i class="fa fa-phone"></i></span>
                <div class="info-box-content">
                    <span class="info-box-text">أرقام الهواتف</span>
                    <span class="info-box-number"><?= count($phones) ?></span>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="info-box">
                <span class="info-box-icon bg-yellow"><i class="fa fa-star"></i></span>
                <div class="info-box-content">
                    <span class="info-box-text">التقييم العام</span>
                    <span class="info-box-number"><?= $avgRating ? number_format($avgRating, 1) . ' / 5' : 'لا يوجد' ?></span>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="info-box">
                <span class="info-box-icon bg-red"><i class="fa fa-gavel"></i></span>
                <div class="info-box-content">
                    <span class="info-box-text">الاستجابة القضائية</span>
                    <span class="info-box-number"><?= $avgJudicialRating ? number_format($avgJudicialRating, 1) . ' / 5' : 'لا يوجد' ?></span>
                </div>
            </div>
        </div>
    </div>

    <!-- =============== Tabs =============== -->
    <div class="nav-tabs-custom">
        <ul class="nav nav-tabs">
            <li class="active">
                <a href="#tab-info" data-toggle="tab">
                    <i class="fa fa-info-circle"></i> البيانات الأساسية
                </a>
            </li>
            <li>
                <a href="#tab-phones" data-toggle="tab">
                    <i class="fa fa-phone"></i> أرقام الهواتف
                    <span class="badge"><?= count($phones) ?></span>
                </a>
            </li>
            <li>
                <a href="#tab-hours" data-toggle="tab">
                    <i class="fa fa-clock-o"></i> أوقات العمل
                </a>
            </li>
            <li>
                <a href="#tab-ratings" data-toggle="tab">
                    <i class="fa fa-star"></i> التقييمات
                    <span class="badge"><?= count($ratings) ?></span>
                </a>
            </li>
            <li>
                <a href="#tab-customers" data-toggle="tab">
                    <i class="fa fa-users"></i> العملاء
                    <span class="badge"><?= $customersCount ?></span>
                </a>
            </li>
        </ul>

        <div class="tab-content">

            <!-- ====== Tab: Basic Info ====== -->
            <div class="tab-pane active" id="tab-info">
                <div class="row">
                    <div class="col-md-6">
                        <h4><i class="fa fa-building-o"></i> معلومات جهة العمل</h4>
                        <?= DetailView::widget([
                            'model' => $model,
                            'options' => ['class' => 'table table-striped table-bordered detail-view'],
                            'attributes' => [
                                'id',
                                [
                                    'attribute' => 'name',
                                    'label' => 'اسم جهة العمل',
                                ],
                                [
                                    'attribute' => 'job_type',
                                    'label' => 'النوع',
                                    'value' => $model->jobType ? $model->jobType->name : '-',
                                ],
                                [
                                    'attribute' => 'status',
                                    'label' => 'الحالة',
                                    'format' => 'raw',
                                    'value' => $model->getStatusBadge(),
                                ],
                                [
                                    'attribute' => 'email',
                                    'format' => 'email',
                                    'value' => $model->email ?: '-',
                                ],
                                [
                                    'attribute' => 'website',
                                    'format' => 'raw',
                                    'value' => $model->website
                                        ? Html::a($model->website, $model->website, ['target' => '_blank'])
                                        : '-',
                                ],
                                [
                                    'attribute' => 'notes',
                                    'value' => $model->notes ?: '-',
                                ],
                            ],
                        ]) ?>
                    </div>
                    <div class="col-md-6">
                        <h4><i class="fa fa-map-marker"></i> العنوان والموقع</h4>
                        <?= DetailView::widget([
                            'model' => $model,
                            'options' => ['class' => 'table table-striped table-bordered detail-view'],
                            'attributes' => [
                                [
                                    'label' => 'العنوان الكامل',
                                    'format' => 'raw',
                                    'value' => $model->getFullAddress() ?: '<span class="text-muted">لم يتم تحديد العنوان</span>',
                                ],
                                [
                                    'attribute' => 'address_city',
                                    'label' => 'المدينة',
                                    'value' => $model->address_city ?: '-',
                                ],
                                [
                                    'attribute' => 'address_area',
                                    'label' => 'المنطقة/الحي',
                                    'value' => $model->address_area ?: '-',
                                ],
                                [
                                    'attribute' => 'address_street',
                                    'label' => 'الشارع',
                                    'value' => $model->address_street ?: '-',
                                ],
                                [
                                    'attribute' => 'address_building',
                                    'label' => 'المبنى',
                                    'value' => $model->address_building ?: '-',
                                ],
                                [
                                    'label' => 'الموقع على الخريطة',
                                    'format' => 'raw',
                                    'value' => ($model->latitude && $model->longitude)
                                        ? Html::a(
                                            '<i class="fa fa-map"></i> فتح في خرائط جوجل (' . $model->latitude . ', ' . $model->longitude . ')',
                                            $model->getMapUrl(),
                                            ['target' => '_blank', 'class' => 'btn btn-sm btn-info']
                                        )
                                        : '<span class="text-muted">لم يتم تحديد الموقع</span>',
                                ],
                            ],
                        ]) ?>

                        <?php if ($model->latitude && $model->longitude): ?>
                            <div id="view-map" style="height: 250px; border: 1px solid #ddd; border-radius: 4px;">
                                <div class="text-center" style="padding-top: 80px;">
                                    <i class="fa fa-map-marker fa-3x text-danger"></i><br>
                                    <strong><?= $model->latitude ?>, <?= $model->longitude ?></strong><br>
                                    <a href="<?= $model->getMapUrl() ?>" target="_blank" class="btn btn-sm btn-info" style="margin-top: 10px;">
                                        <i class="fa fa-external-link"></i> فتح في خرائط جوجل
                                    </a>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- ====== Tab: Phone Numbers ====== -->
            <div class="tab-pane" id="tab-phones" x-data="{ showPhoneForm: false }">
                <div class="row" style="margin-bottom: 15px;">
                    <div class="col-md-12">
                        <button type="button" class="btn btn-success btn-sm" id="btn-add-phone" @click="showPhoneForm = !showPhoneForm">
                            <i class="fa fa-plus"></i> إضافة رقم هاتف
                        </button>
                    </div>
                </div>

                <!-- Add Phone Form (hidden by default) -->
                <div id="phone-form-container" x-show="showPhoneForm" x-transition x-cloak>
                    <div class="box box-success">
                        <div class="box-header with-border">
                            <h4 class="box-title">إضافة رقم هاتف جديد</h4>
                        </div>
                        <div class="box-body">
                            <form id="add-phone-form">
                                <input type="hidden" name="<?= Yii::$app->request->csrfParam ?>" value="<?= Yii::$app->request->csrfToken ?>">
                                <div class="row">
                                    <div class="col-md-3">
                                        <div class="form-group">
                                            <label>رقم الهاتف <span class="text-danger">*</span></label>
                                            <input type="text" class="form-control" name="JobsPhone[phone_number]" placeholder="رقم الهاتف" dir="ltr" required>
                                        </div>
                                    </div>
                                    <div class="col-md-2">
                                        <div class="form-group">
                                            <label>نوع الرقم</label>
                                            <select class="form-control" name="JobsPhone[phone_type]">
                                                <?php foreach (JobsPhone::getPhoneTypes() as $key => $label): ?>
                                                    <option value="<?= $key ?>"><?= $label ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-2">
                                        <div class="form-group">
                                            <label>اسم الموظف</label>
                                            <input type="text" class="form-control" name="JobsPhone[employee_name]" placeholder="اسم الموظف">
                                        </div>
                                    </div>
                                    <div class="col-md-2">
                                        <div class="form-group">
                                            <label>المنصب</label>
                                            <input type="text" class="form-control" name="JobsPhone[employee_position]" placeholder="منصبه">
                                        </div>
                                    </div>
                                    <div class="col-md-2">
                                        <div class="form-group">
                                            <label>القسم</label>
                                            <input type="text" class="form-control" name="JobsPhone[department]" placeholder="القسم">
                                        </div>
                                    </div>
                                    <div class="col-md-1">
                                        <div class="form-group">
                                            <label>&nbsp;</label>
                                            <div>
                                                <button type="submit" class="btn btn-success btn-block">
                                                    <i class="fa fa-check"></i>
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label>ملاحظات</label>
                                            <input type="text" class="form-control" name="JobsPhone[notes]" placeholder="ملاحظات إضافية">
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="form-group">
                                            <label>&nbsp;</label>
                                            <div>
                                                <label>
                                                    <input type="checkbox" name="JobsPhone[is_primary]" value="1"> رقم أساسي
                                                </label>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- Phones Table -->
                <div id="phones-list">
                    <?php if (empty($phones)): ?>
                        <div class="alert alert-info">
                            <i class="fa fa-info-circle"></i> لا توجد أرقام هواتف مسجلة لجهة العمل هذه.
                        </div>
                    <?php else: ?>
                        <table class="table table-bordered table-striped table-hover">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>رقم الهاتف</th>
                                    <th>النوع</th>
                                    <th>اسم الموظف</th>
                                    <th>المنصب</th>
                                    <th>القسم</th>
                                    <th>ملاحظات</th>
                                    <th style="width:80px">إجراءات</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($phones as $i => $phone): ?>
                                    <tr id="phone-row-<?= $phone->id ?>">
                                        <td><?= $i + 1 ?></td>
                                        <td dir="ltr" class="text-right">
                                            <?php if ($phone->is_primary): ?>
                                                <i class="fa fa-star text-warning" title="رقم أساسي"></i>
                                            <?php endif; ?>
                                            <strong><?= Html::encode($phone->phone_number) ?></strong>
                                        </td>
                                        <td>
                                            <?php
                                            $typeIcons = ['office' => 'fa-phone', 'mobile' => 'fa-mobile', 'fax' => 'fa-fax', 'whatsapp' => 'fa-whatsapp text-success'];
                                            $icon = $typeIcons[$phone->phone_type] ?? 'fa-phone';
                                            ?>
                                            <i class="fa <?= $icon ?>"></i> <?= $phone->getPhoneTypeLabel() ?>
                                        </td>
                                        <td><?= Html::encode($phone->employee_name) ?: '-' ?></td>
                                        <td><?= Html::encode($phone->employee_position) ?: '-' ?></td>
                                        <td><?= Html::encode($phone->department) ?: '-' ?></td>
                                        <td><?= Html::encode($phone->notes) ?: '-' ?></td>
                                        <td>
                                            <button type="button" class="btn btn-xs btn-danger btn-delete-phone" data-id="<?= $phone->id ?>">
                                                <i class="fa fa-trash"></i>
                                            </button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php endif; ?>
                </div>
            </div>

            <!-- ====== Tab: Working Hours ====== -->
            <div class="tab-pane" id="tab-hours">
                <?php if (empty($workingHours)): ?>
                    <div class="alert alert-info">
                        <i class="fa fa-info-circle"></i> لم يتم تحديد أوقات العمل بعد.
                        <?= Html::a('تعديل أوقات العمل', ['update', 'id' => $model->id], ['class' => 'btn btn-sm btn-primary']) ?>
                    </div>
                <?php else: ?>
                    <table class="table table-bordered table-striped">
                        <thead>
                            <tr>
                                <th style="width: 150px">اليوم</th>
                                <th style="width: 120px">وقت البداية</th>
                                <th style="width: 120px">وقت النهاية</th>
                                <th style="width: 100px" class="text-center">الحالة</th>
                                <th>ملاحظات</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $dayNames = Jobs::getDayNames();
                            $hoursMap = [];
                            foreach ($workingHours as $wh) {
                                $hoursMap[$wh->day_of_week] = $wh;
                            }
                            for ($d = 0; $d <= 6; $d++):
                                $wh = isset($hoursMap[$d]) ? $hoursMap[$d] : null;
                            ?>
                                <tr class="<?= ($wh && $wh->is_closed) ? 'danger' : '' ?>">
                                    <td><strong><?= $dayNames[$d] ?></strong></td>
                                    <td dir="ltr" class="text-center"><?= ($wh && !$wh->is_closed) ? $wh->opening_time : '-' ?></td>
                                    <td dir="ltr" class="text-center"><?= ($wh && !$wh->is_closed) ? $wh->closing_time : '-' ?></td>
                                    <td class="text-center">
                                        <?php if ($wh && $wh->is_closed): ?>
                                            <span class="label label-danger">مغلق</span>
                                        <?php elseif ($wh): ?>
                                            <span class="label label-success">مفتوح</span>
                                        <?php else: ?>
                                            <span class="text-muted">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?= ($wh && $wh->notes) ? Html::encode($wh->notes) : '-' ?></td>
                                </tr>
                            <?php endfor; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>

            <!-- ====== Tab: Ratings ====== -->
            <div class="tab-pane" id="tab-ratings" x-data="{ showRatingForm: false }">
                <div class="row" style="margin-bottom: 15px;">
                    <div class="col-md-12">
                        <button type="button" class="btn btn-success btn-sm" id="btn-add-rating" @click="showRatingForm = !showRatingForm">
                            <i class="fa fa-plus"></i> إضافة تقييم
                        </button>
                    </div>
                </div>

                <!-- Add Rating Form (hidden by default) -->
                <div id="rating-form-container" x-show="showRatingForm" x-transition x-cloak>
                    <div class="box box-success">
                        <div class="box-header with-border">
                            <h4 class="box-title">إضافة تقييم جديد</h4>
                        </div>
                        <div class="box-body">
                            <form id="add-rating-form">
                                <input type="hidden" name="<?= Yii::$app->request->csrfParam ?>" value="<?= Yii::$app->request->csrfToken ?>">
                                <div class="row">
                                    <div class="col-md-3">
                                        <div class="form-group">
                                            <label>نوع التقييم <span class="text-danger">*</span></label>
                                            <select class="form-control" name="JobsRating[rating_type]" required>
                                                <option value="">-- اختر --</option>
                                                <?php foreach (JobsRating::getRatingTypes() as $key => $label): ?>
                                                    <option value="<?= $key ?>"><?= $label ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="form-group">
                                            <label>التقييم <span class="text-danger">*</span></label>
                                            <div class="rating-stars-input" style="font-size: 24px; cursor: pointer;">
                                                <?php for ($i = 1; $i <= 5; $i++): ?>
                                                    <i class="fa fa-star-o rating-star" data-value="<?= $i ?>"></i>
                                                <?php endfor; ?>
                                                <input type="hidden" name="JobsRating[rating_value]" id="rating-value-input" required>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label>تفاصيل التقييم</label>
                                            <textarea class="form-control" name="JobsRating[review_text]" rows="2" placeholder="أضف تفاصيل أو ملاحظات عن التقييم..."></textarea>
                                        </div>
                                    </div>
                                    <div class="col-md-2">
                                        <div class="form-group">
                                            <label>&nbsp;</label>
                                            <div>
                                                <button type="submit" class="btn btn-success btn-block">
                                                    <i class="fa fa-check"></i> حفظ التقييم
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- Ratings Summary -->
                <?php if (!empty($ratings)): ?>
                    <div class="row" style="margin-bottom: 20px;">
                        <?php
                        $ratingTypes = JobsRating::getRatingTypes();
                        foreach ($ratingTypes as $typeKey => $typeLabel):
                            $typeAvg = $model->getAverageRating($typeKey);
                        ?>
                            <div class="col-md-3">
                                <div class="small-box bg-<?= $typeKey === 'judicial_response' ? 'red' : ($typeKey === 'cooperation' ? 'blue' : ($typeKey === 'speed' ? 'green' : 'yellow')) ?>">
                                    <div class="inner">
                                        <h3><?= $typeAvg ? number_format($typeAvg, 1) : '-' ?></h3>
                                        <p><?= $typeLabel ?></p>
                                    </div>
                                    <div class="icon">
                                        <i class="fa fa-<?= $typeKey === 'judicial_response' ? 'gavel' : ($typeKey === 'cooperation' ? 'handshake-o' : ($typeKey === 'speed' ? 'bolt' : 'star')) ?>"></i>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <!-- Ratings List -->
                <div id="ratings-list">
                    <?php if (empty($ratings)): ?>
                        <div class="alert alert-info">
                            <i class="fa fa-info-circle"></i> لا توجد تقييمات بعد لجهة العمل هذه.
                        </div>
                    <?php else: ?>
                        <table class="table table-bordered table-striped table-hover">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>نوع التقييم</th>
                                    <th class="text-center">التقييم</th>
                                    <th>التفاصيل</th>
                                    <th>المُقيّم</th>
                                    <th>التاريخ</th>
                                    <th style="width:80px">إجراءات</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($ratings as $i => $rating): ?>
                                    <tr id="rating-row-<?= $rating->id ?>">
                                        <td><?= $i + 1 ?></td>
                                        <td><?= $rating->getRatingTypeLabel() ?></td>
                                        <td class="text-center" style="white-space: nowrap;">
                                            <?= $rating->getStarsHtml() ?>
                                        </td>
                                        <td><?= Html::encode($rating->review_text) ?: '-' ?></td>
                                        <td>
                                            <?php
                                            $rater = $rating->rater;
                                            echo $rater ? Html::encode($rater->username) : '-';
                                            ?>
                                        </td>
                                        <td dir="ltr" class="text-right"><?= Yii::$app->formatter->asDatetime($rating->rated_at) ?></td>
                                        <td>
                                            <button type="button" class="btn btn-xs btn-danger btn-delete-rating" data-id="<?= $rating->id ?>">
                                                <i class="fa fa-trash"></i>
                                            </button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php endif; ?>
                </div>
            </div>

            <!-- ====== Tab: Customers ====== -->
            <div class="tab-pane" id="tab-customers">
                <?php
                $customers = \backend\modules\customers\models\Customers::find()
                    ->where(['job_title' => $model->id])
                    ->limit(50)
                    ->all();
                ?>
                <?php if (empty($customers)): ?>
                    <div class="alert alert-info">
                        <i class="fa fa-info-circle"></i> لا يوجد عملاء مرتبطين بجهة العمل هذه.
                    </div>
                <?php else: ?>
                    <table class="table table-bordered table-striped table-hover">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>اسم العميل</th>
                                <th>رقم الهوية</th>
                                <th>رقم الهاتف</th>
                                <th style="width:80px">عرض</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($customers as $i => $customer): ?>
                                <tr>
                                    <td><?= $i + 1 ?></td>
                                    <td title="<?= Html::encode($customer->name) ?>"><?= Html::encode(NameHelper::short($customer->name)) ?></td>
                                    <td><?= Html::encode($customer->id_number) ?: '-' ?></td>
                                    <td dir="ltr" class="text-right"><?= Html::encode(\backend\helpers\PhoneHelper::toLocal($customer->primary_phone_number)) ?: '-' ?></td>
                                    <td>
                                        <?= Html::a('<i class="fa fa-eye"></i>', ['/customers/view', 'id' => $customer->id], [
                                            'class' => 'btn btn-xs btn-info',
                                            'target' => '_blank',
                                        ]) ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    <?php if ($customersCount > 50): ?>
                        <div class="text-center text-muted">
                            يتم عرض أول 50 عميل من أصل <?= $customersCount ?>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>
            </div>

        </div>
    </div>
</div>

<?php
$addPhoneUrl = Url::to(['add-phone', 'jobId' => $model->id]);
$deletePhoneUrl = Url::to(['delete-phone']);
$addRatingUrl = Url::to(['add-rating', 'jobId' => $model->id]);
$deleteRatingUrl = Url::to(['delete-rating']);

$js = <<<JS
// ========================
// Phone Number Management
// ========================
/* OLD jQuery - replaced by Alpine.js */
// $('#btn-add-phone').on('click', function() {
//     $('#phone-form-container').slideToggle(300);
// });

$('#add-phone-form').on('submit', function(e) {
    e.preventDefault();
    var form = $(this);
    $.ajax({
        url: '{$addPhoneUrl}',
        type: 'POST',
        data: form.serialize(),
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                location.reload();
            } else {
                var msg = 'خطأ في الحفظ';
                if (response.errors) {
                    msg = Object.values(response.errors).flat().join('\\n');
                }
                alert(msg);
            }
        },
        error: function() {
            alert('حدث خطأ في الاتصال');
        }
    });
});

$(document).on('click', '.btn-delete-phone', function() {
    if (!confirm('هل أنت متأكد من حذف هذا الرقم؟')) return;
    var id = $(this).data('id');
    $.ajax({
        url: '{$deletePhoneUrl}?id=' + id,
        type: 'POST',
        data: { _csrf: yii.getCsrfToken() },
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                $('#phone-row-' + id).fadeOut(300, function() { $(this).remove(); });
            } else {
                alert(response.message || 'خطأ في الحذف');
            }
        }
    });
});

// ========================
// Rating Management
// ========================
/* OLD jQuery - replaced by Alpine.js */
// $('#btn-add-rating').on('click', function() {
//     $('#rating-form-container').slideToggle(300);
// });

// Star rating input
$('.rating-star').on('click', function() {
    var value = $(this).data('value');
    $('#rating-value-input').val(value);
    $('.rating-star').each(function() {
        if ($(this).data('value') <= value) {
            $(this).removeClass('fa-star-o').addClass('fa-star text-warning');
        } else {
            $(this).removeClass('fa-star text-warning').addClass('fa-star-o');
        }
    });
});

$('.rating-star').on('mouseenter', function() {
    var value = $(this).data('value');
    $('.rating-star').each(function() {
        if ($(this).data('value') <= value) {
            $(this).removeClass('fa-star-o').addClass('fa-star text-warning');
        } else {
            $(this).removeClass('fa-star text-warning').addClass('fa-star-o');
        }
    });
}).on('mouseleave', function() {
    var selected = parseInt($('#rating-value-input').val()) || 0;
    $('.rating-star').each(function() {
        if ($(this).data('value') <= selected) {
            $(this).removeClass('fa-star-o').addClass('fa-star text-warning');
        } else {
            $(this).removeClass('fa-star text-warning').addClass('fa-star-o');
        }
    });
});

$('#add-rating-form').on('submit', function(e) {
    e.preventDefault();
    var ratingVal = $('#rating-value-input').val();
    if (!ratingVal) {
        alert('الرجاء اختيار التقييم');
        return;
    }
    var form = $(this);
    $.ajax({
        url: '{$addRatingUrl}',
        type: 'POST',
        data: form.serialize(),
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                location.reload();
            } else {
                var msg = 'خطأ في الحفظ';
                if (response.errors) {
                    msg = Object.values(response.errors).flat().join('\\n');
                }
                alert(msg);
            }
        },
        error: function() {
            alert('حدث خطأ في الاتصال');
        }
    });
});

$(document).on('click', '.btn-delete-rating', function() {
    if (!confirm('هل أنت متأكد من حذف هذا التقييم؟')) return;
    var id = $(this).data('id');
    $.ajax({
        url: '{$deleteRatingUrl}?id=' + id,
        type: 'POST',
        data: { _csrf: yii.getCsrfToken() },
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                $('#rating-row-' + id).fadeOut(300, function() { $(this).remove(); });
            } else {
                alert(response.message || 'خطأ في الحذف');
            }
        }
    });
});
JS;
$this->registerJs($js);
?>
