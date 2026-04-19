<?php

use common\models\FahrasCheckLog;
use yii\helpers\Html;
use yii\helpers\Url;

/* @var $this  yii\web\View         */
/* @var $model FahrasCheckLog       */

$this->title = 'تفاصيل فحص #' . $model->id;
$this->params['breadcrumbs'] = [
    ['label' => 'العملاء', 'url' => ['/customers/customers/index']],
    ['label' => 'سجل فحوصات الفهرس', 'url' => ['index']],
    $this->title,
];

$users = [];
$ids = array_filter([$model->user_id, $model->override_user_id]);
if ($ids && class_exists(\common\models\User::class)) {
    $rows = \common\models\User::find()
        ->select(['id', 'username'])
        ->where(['id' => $ids])
        ->asArray()
        ->all();
    foreach ($rows as $u) $users[(int)$u['id']] = $u['username'];
}
$userName = static function (?int $id) use ($users) {
    if (!$id) return '—';
    return Html::encode($users[$id] ?? ('#' . $id));
};

$matches = $model->getMatches();
?>
<div class="fahras-log-view">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h2 class="m-0"><i class="fa fa-shield"></i> <?= Html::encode($this->title) ?></h2>
        <a href="<?= Url::to(['index']) ?>" class="btn btn-outline-secondary btn-sm">
            <i class="fa fa-arrow-right"></i> العودة للسجل
        </a>
    </div>

    <div class="card mb-3">
        <div class="card-body">
            <div class="d-flex flex-wrap gap-2 align-items-center mb-3">
                <span class="badge <?= $model->getVerdictBadgeClass() ?> fs-6">
                    <?= Html::encode($model->getVerdictLabel()) ?>
                </span>
                <?php if ($model->reason_code): ?>
                    <span class="badge bg-light text-dark"><?= Html::encode($model->reason_code) ?></span>
                <?php endif ?>
                <?php if ($model->from_cache): ?>
                    <span class="badge bg-info"><i class="fa fa-bolt"></i> من الذاكرة المؤقتة</span>
                <?php endif ?>
                <?php if ($model->override_user_id): ?>
                    <span class="badge bg-warning text-dark">
                        <i class="fa fa-key"></i> تم تجاوزه بواسطة <?= $userName((int)$model->override_user_id) ?>
                    </span>
                <?php endif ?>
            </div>

            <?php if ($model->reason_ar): ?>
                <p class="text-muted mb-3"><?= Html::encode($model->reason_ar) ?></p>
            <?php endif ?>

            <table class="table table-sm table-bordered">
                <tbody>
                    <tr>
                        <th style="width:200px">التاريخ</th>
                        <td dir="ltr"><?= Yii::$app->formatter->asDatetime($model->created_at, 'php:Y-m-d H:i:s') ?></td>
                    </tr>
                    <tr>
                        <th>الرقم الوطني</th>
                        <td dir="ltr" class="text-monospace"><?= Html::encode($model->id_number) ?></td>
                    </tr>
                    <tr>
                        <th>الاسم</th>
                        <td><?= Html::encode($model->name ?: '—') ?></td>
                    </tr>
                    <tr>
                        <th>الهاتف</th>
                        <td dir="ltr" class="text-monospace"><?= Html::encode($model->phone ?: '—') ?></td>
                    </tr>
                    <tr>
                        <th>المصدر</th>
                        <td><?= Html::encode($model->source ?: '—') ?></td>
                    </tr>
                    <tr>
                        <th>المستخدم الذي طلب الفحص</th>
                        <td><?= $userName((int)$model->user_id) ?></td>
                    </tr>
                    <?php if ($model->customer_id): ?>
                    <tr>
                        <th>العميل المُنشَأ</th>
                        <td>
                            <?= Html::a(
                                'عرض العميل #' . (int)$model->customer_id,
                                ['/customers/customers/view', 'id' => (int)$model->customer_id],
                                ['class' => 'btn btn-sm btn-outline-primary']
                            ) ?>
                        </td>
                    </tr>
                    <?php endif ?>
                    <tr>
                        <th>HTTP Status</th>
                        <td dir="ltr"><?= $model->http_status !== null ? (int)$model->http_status : '—' ?></td>
                    </tr>
                    <tr>
                        <th>مدة الاستجابة</th>
                        <td dir="ltr"><?= $model->duration_ms !== null ? ((int)$model->duration_ms . ' ms') : '—' ?></td>
                    </tr>
                    <tr>
                        <th>Request ID</th>
                        <td dir="ltr" class="text-monospace"><?= Html::encode($model->request_id ?: '—') ?></td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    <?php if ($model->override_user_id): ?>
    <div class="card mb-3 border-warning">
        <div class="card-header bg-warning text-dark">
            <i class="fa fa-key"></i> تفاصيل التجاوز
        </div>
        <div class="card-body">
            <p><strong>تم بواسطة:</strong> <?= $userName((int)$model->override_user_id) ?></p>
            <p><strong>السبب:</strong></p>
            <blockquote class="bg-light p-3 rounded border-start border-warning border-4">
                <?= nl2br(Html::encode((string)($model->override_reason ?? ''))) ?>
            </blockquote>
        </div>
    </div>
    <?php endif ?>

    <?php if ($matches): ?>
    <div class="card mb-3">
        <div class="card-header"><i class="fa fa-list"></i> المطابقات (<?= count($matches) ?>)</div>
        <div class="card-body p-0">
            <table class="table table-sm table-striped m-0">
                <thead>
                    <tr>
                        <th>المصدر</th>
                        <th>الاسم</th>
                        <th>الرقم الوطني</th>
                        <th>الهاتف</th>
                        <th>الحساب</th>
                        <th>التاريخ</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($matches as $m): ?>
                        <tr>
                            <td><?= Html::encode((string)($m['source'] ?? $m['company'] ?? '—')) ?></td>
                            <td><?= Html::encode((string)($m['name'] ?? $m['full_name'] ?? '—')) ?></td>
                            <td dir="ltr"><?= Html::encode((string)($m['id_number'] ?? $m['national_id'] ?? '—')) ?></td>
                            <td dir="ltr"><?= Html::encode((string)($m['phone'] ?? $m['mobile'] ?? '—')) ?></td>
                            <td><?= Html::encode((string)($m['account'] ?? '—')) ?></td>
                            <td dir="ltr"><?= Html::encode((string)($m['created_at'] ?? '—')) ?></td>
                        </tr>
                    <?php endforeach ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php endif ?>

    <?php if (!empty($model->matches_json)): ?>
    <details class="mt-3">
        <summary class="text-muted">عرض الاستجابة الخام (JSON)</summary>
        <pre class="bg-light p-3 mt-2 small text-monospace" dir="ltr"><?= Html::encode(
            is_string($model->matches_json)
                ? $model->matches_json
                : json_encode($model->matches_json, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT)
        ) ?></pre>
    </details>
    <?php endif ?>
</div>
