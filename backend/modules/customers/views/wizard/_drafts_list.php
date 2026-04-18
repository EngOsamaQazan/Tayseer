<?php
/**
 * Saved-drafts list partial (used by the drafts picker modal — Phase 4).
 *
 * @var \common\models\WizardDraft[] $items
 */

use yii\helpers\Html;
use yii\helpers\Url;
?>
<?php if (empty($items)): ?>
    <div class="cw-placeholder">
        <div class="cw-placeholder__icon"><i class="fa fa-folder-open-o" aria-hidden="true"></i></div>
        <div class="cw-placeholder__title">لا توجد مسودات محفوظة بعد</div>
    </div>
<?php else: ?>
    <ul class="cw-drafts-list" role="list">
        <?php foreach ($items as $d): ?>
            <li class="cw-drafts-list__item">
                <div>
                    <div><strong><?= Html::encode($d->draft_label ?: 'مسودة') ?></strong></div>
                    <?php if ($d->items_summary): ?>
                        <div class="cw-text-muted"><?= Html::encode($d->items_summary) ?></div>
                    <?php endif ?>
                    <div class="cw-text-muted">
                        آخر تحديث: <?= Yii::$app->formatter->asRelativeTime($d->updated_at) ?>
                    </div>
                </div>
                <a class="cw-btn cw-btn--primary cw-btn--sm"
                   href="<?= Url::to(['/customers/wizard/resume', 'id' => $d->id]) ?>">
                    <i class="fa fa-folder-open" aria-hidden="true"></i>
                    استئناف
                </a>
            </li>
        <?php endforeach ?>
    </ul>
<?php endif ?>
