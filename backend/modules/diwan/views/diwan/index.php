<?php
/**
 * قسم الديوان — لوحة المعلومات
 */

use yii\helpers\Html;
use yii\helpers\Url;
use common\helper\Permissions;

$this->title = 'قسم الديوان';

$baseDiwan    = Permissions::DIWAN;
$canDiwanView   = Permissions::can(Permissions::DIWAN_VIEW)   || Yii::$app->user->can($baseDiwan);
$canDiwanCreate = Permissions::can(Permissions::DIWAN_CREATE) || Yii::$app->user->can($baseDiwan);
$canDiwanDelete = Permissions::can(Permissions::DIWAN_DELETE) || Yii::$app->user->can($baseDiwan);
?>

<?= $this->render('@app/views/layouts/_diwan-tabs', ['activeTab' => 'dashboard']) ?>

<style>
.diwan-stats { display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 14px; margin-bottom: 22px; }
.dw-stat {
    background: #fff; border-radius: 10px; padding: 20px 16px; text-align: center;
    box-shadow: 0 1px 8px rgba(0,0,0,.05); border-right: 4px solid var(--fin-primary, #800020); transition: transform .2s;
}
.dw-stat:hover { transform: translateY(-2px); box-shadow: 0 4px 16px rgba(0,0,0,.08); }
.dw-stat .num { font-size: 32px; font-weight: 800; color: var(--fin-primary, #800020); line-height: 1; margin-bottom: 4px; }
.dw-stat .lbl { font-size: 12px; color: #888; font-weight: 600; }
.dw-stat.blue  { border-right-color: #2196F3; } .dw-stat.blue  .num { color: #2196F3; }
.dw-stat.green { border-right-color: #4CAF50; } .dw-stat.green .num { color: #4CAF50; }
.dw-stat.amber { border-right-color: #FF9800; } .dw-stat.amber .num { color: #FF9800; }
.dw-stat.plum  { border-right-color: #9C27B0; } .dw-stat.plum  .num { color: #9C27B0; }

.dw-recent { background: #fff; border-radius: 10px; box-shadow: 0 1px 8px rgba(0,0,0,.05); overflow: hidden; }
.dw-recent .hdr {
    background: linear-gradient(135deg, var(--fin-primary,#800020), #a02050); color: #fff;
    padding: 14px 18px; font-weight: 700; font-size: 14px; display: flex; align-items: center; gap: 8px;
}
.dw-recent table { margin: 0; }
.dw-recent table th { background: #f8f9fa; font-weight: 700; font-size: 12px; color: #555; border-top: none; }
.dw-recent table td { font-size: 13px; vertical-align: middle; }

.dw-badge { display: inline-block; padding: 3px 10px; border-radius: 20px; font-size: 11px; font-weight: 700; }
.dw-badge--recv { background: #e8f5e9; color: #2e7d32; }
.dw-badge--dlvr { background: #fff3e0; color: #e65100; }
</style>

<div class="diwan-dashboard">

    <!-- ═══ إحصائيات ═══ -->
    <div class="diwan-stats">
        <div class="dw-stat">
            <div class="num"><?= number_format($totalDocuments) ?></div>
            <div class="lbl">إجمالي الوثائق المتتبعة</div>
        </div>
        <div class="dw-stat blue">
            <div class="num"><?= number_format($totalTransactions) ?></div>
            <div class="lbl">إجمالي المعاملات</div>
        </div>
        <div class="dw-stat green">
            <div class="num"><?= number_format($todayReceive) ?></div>
            <div class="lbl">استلام اليوم</div>
        </div>
        <div class="dw-stat amber">
            <div class="num"><?= number_format($todayDeliver) ?></div>
            <div class="lbl">تسليم اليوم</div>
        </div>
        <div class="dw-stat plum">
            <div class="num"><?= number_format($todayTransactions) ?></div>
            <div class="lbl">معاملات اليوم</div>
        </div>
    </div>

    <!-- ═══ آخر المعاملات ═══ -->
    <div class="dw-recent">
        <div class="hdr"><i class="fa fa-clock-o"></i> آخر المعاملات</div>
        <table class="table table-hover" style="margin-bottom:0;">
            <thead>
                <tr>
                    <th style="width:50px">#</th>
                    <th>النوع</th>
                    <th>من</th>
                    <th>إلى</th>
                    <th>العقود</th>
                    <th>التاريخ</th>
                    <th>الإيصال</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($recentTransactions)): ?>
                <tr>
                    <td colspan="8" class="text-center" style="padding:40px; color:#999;">
                        <i class="fa fa-inbox" style="font-size:36px; display:block; margin-bottom:10px;"></i>
                        لا توجد معاملات بعد. ابدأ بإنشاء أول معاملة!
                    </td>
                </tr>
                <?php else: ?>
                    <?php foreach ($recentTransactions as $t): ?>
                    <tr>
                        <td><strong><?= $t->id ?></strong></td>
                        <td>
                            <span class="dw-badge <?= $t->transaction_type === 'استلام' ? 'dw-badge--recv' : 'dw-badge--dlvr' ?>">
                                <?= $t->transaction_type ?>
                            </span>
                        </td>
                        <td><?= Html::encode($t->fromEmployee ? ($t->fromEmployee->name ?: $t->fromEmployee->username) : '—') ?></td>
                        <td><?= Html::encode($t->toEmployee ? ($t->toEmployee->name ?: $t->toEmployee->username) : '—') ?></td>
                        <td><span class="badge" style="background:var(--fin-primary,#800020)"><?= count($t->details) ?> عقد</span></td>
                        <td style="font-size:12px"><?= Yii::$app->formatter->asDatetime($t->transaction_date, 'php:Y/m/d h:i A') ?></td>
                        <td><code style="font-size:11px"><?= Html::encode($t->receipt_number) ?></code></td>
                        <td>
                            <?= Html::a('<i class="fa fa-eye"></i>', ['view', 'id' => $t->id], ['class' => 'btn btn-xs btn-default', 'title' => 'عرض']) ?>
                            <?= Html::a('<i class="fa fa-print"></i>', ['receipt', 'id' => $t->id], ['class' => 'btn btn-xs btn-default', 'title' => 'طباعة']) ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
