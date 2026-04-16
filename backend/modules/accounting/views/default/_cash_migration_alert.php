<?php
/**
 * تنبيه مراجعة هجرة الصناديق — يظهر مرة واحدة بعد تشغيل migration
 * يعرض جدول الحسابات المنشأة تلقائياً + زر تأكيد المراجعة
 */

use yii\helpers\Html;

try {
    $linkedBanks = (new \yii\db\Query())
        ->select([
            'cb.id',
            'b.name  AS bank_name',
            'cb.bank_number',
            'cb.iban_number',
            'c.name  AS company_name',
            'a.code  AS gl_code',
            'a.name_ar AS gl_name',
        ])
        ->from('{{%company_banks}} cb')
        ->leftJoin('{{%bancks}} b', 'b.id = cb.bank_id')
        ->leftJoin('{{%companies}} c', 'c.id = cb.company_id')
        ->leftJoin('{{%accounts}} a', 'a.id = cb.account_id')
        ->where(['cb.is_deleted' => 0])
        ->andWhere(['not', ['cb.account_id' => null]])
        ->orderBy(['c.name' => SORT_ASC, 'cb.id' => SORT_ASC])
        ->all();
} catch (\Exception $e) {
    return;
}

if (empty($linkedBanks)) return;
?>

<div class="box box-warning" id="cash-migration-alert" style="margin-bottom:20px;">
    <div class="box-header with-border">
        <h3 class="box-title">
            <i class="fa fa-exclamation-triangle"></i>
            تقرير الهجرة التلقائية — حسابات الصناديق المنشأة
        </h3>
        <div class="box-tools">
            <button type="button" class="btn btn-success btn-sm" id="btn-dismiss-migration">
                <i class="fa fa-check"></i> تم المراجعة
            </button>
        </div>
    </div>
    <div class="box-body">
        <p style="margin-bottom:12px; color:var(--clr-text-muted, #777);">
            تم إنشاء الحسابات التالية تلقائياً من بنوك الشركات في شجرة الحسابات تحت <b>1101 — النقدية والبنوك</b>.
            يرجى مراجعتها والتأكد من صحة البيانات. يمكنك تعديلها من <?= Html::a('شجرة الحسابات', ['/accounting/chart-of-accounts/index']) ?>.
        </p>
        <div class="table-responsive">
            <table class="table table-hover table-bordered" style="margin-bottom:0;">
                <thead style="background:#f5f6f8;">
                    <tr>
                        <th style="width:50px;">#</th>
                        <th>البنك</th>
                        <th>رقم الحساب</th>
                        <th>الشركة</th>
                        <th>كود GL</th>
                        <th>اسم حساب GL</th>
                        <th style="width:70px;" class="text-center">الحالة</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($linkedBanks as $i => $row): ?>
                    <tr>
                        <td><?= $i + 1 ?></td>
                        <td><?= Html::encode($row['bank_name']) ?></td>
                        <td style="font-family:monospace;"><?= Html::encode($row['bank_number']) ?></td>
                        <td><?= Html::encode($row['company_name']) ?></td>
                        <td style="font-family:monospace; font-weight:700;"><?= Html::encode($row['gl_code']) ?></td>
                        <td><?= Html::encode($row['gl_name']) ?></td>
                        <td class="text-center"><span class="badge bg-success" style="background:#28a745;">مربوط</span></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php
$dismissUrl = \yii\helpers\Url::to(['/accounting/default/dismiss-cash-migration-alert']);
$this->registerJs(<<<JS
$('#btn-dismiss-migration').on('click', function(){
    var btn = $(this);
    btn.prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> جاري...');
    $.post('{$dismissUrl}', function(res) {
        if (res.success) {
            $('#cash-migration-alert').slideUp(300);
        }
    }).fail(function() {
        btn.prop('disabled', false).html('<i class="fa fa-check"></i> تم المراجعة');
    });
});
JS
);
?>
