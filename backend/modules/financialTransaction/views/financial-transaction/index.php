<?php
/**
 * ═══════════════════════════════════════════════════════════════════
 *  شاشة الحركات المالية — Enterprise Comfort Mode
 *  ────────────────────────────────────────────────────────────────
 *  التحسينات الرئيسية:
 *  1. حذف dropdown العقود (7,301 عقد × 20 صف = 146,020 option)
 *     ← استبداله بـ input رقمي (يوفر ~97% من DOM)
 *  2. Eager loading لعلاقة الشركة (حذف N+1 queries)
 *  3. بيانات مرجعية مخزنة مؤقتاً (كاش) — تُحمّل مرة واحدة فقط
 *  4. Card view على الجوال بدل الجدول
 * ═══════════════════════════════════════════════════════════════════
 */

use yii\helpers\Url;
use yii\helpers\Html;
use yii\helpers\ArrayHelper;
use yii\bootstrap\Modal;
use backend\modules\financialTransaction\models\FinancialTransaction;
use common\helper\Permissions;

$this->title = 'الإدارة المالية';
$this->params['breadcrumbs'][] = $this->title;

/* ═══ صلاحيات ═══ */
$isManager    = Yii::$app->user->can(Permissions::MANAGER);
$canExport    = Yii::$app->user->can(Permissions::FINANCIAL_TRANSACTION_TO_EXPORT_DATA);

/*
 * Fallback: إذا الصلاحيات المفصّلة (FIN_EDIT, FIN_IMPORT, ...) غير موجودة بقاعدة البيانات،
 *           نرجع للصلاحية الأساسية "الحركات المالية".
 * ─────────────────────────────────────────────────────────────────
 * هذا يمنع مشكلة اختفاء الأزرار عند إعادة إنشاء الـ Docker volume
 * لأن الـ dump الأصلي (namaa_jadal.sql) لا يحتوي على الصلاحيات المفصّلة.
 */
$baseFin  = Permissions::FINANCIAL_TRANSACTION; // 'الحركات المالية'
$canFinView     = Permissions::can(Permissions::FIN_VIEW)     || Yii::$app->user->can($baseFin);
$canFinCreate   = Permissions::can(Permissions::FIN_CREATE)   || Yii::$app->user->can($baseFin);
$canFinEdit     = Permissions::can(Permissions::FIN_EDIT)     || Yii::$app->user->can($baseFin);
$canFinImport   = Permissions::can(Permissions::FIN_IMPORT)   || Yii::$app->user->can($baseFin);
$canFinTransfer = Permissions::can(Permissions::FIN_TRANSFER) || Yii::$app->user->can($baseFin);
$canFinDelete   = Permissions::can(Permissions::FIN_DELETE)   || Yii::$app->user->can($baseFin);
$typeIncome   = FinancialTransaction::TYPE_INCOME;
$typeOutcome  = FinancialTransaction::TYPE_OUTCOME;
$custPayments = FinancialTransaction::CUSTOMER_PAYMENTS;
$courtResp    = FinancialTransaction::COURT_RESPONSES;

/* ═══ بيانات مرجعية (كاش) — تُحمّل مرة واحدة فقط ═══ */
$cache = Yii::$app->cache;
$p = Yii::$app->params;
$d = $p['time_duration'];
$db = Yii::$app->db;

/* التصنيفات ونوع الدخل — أعداد صغيرة (25 و 11) = آمنة كـ dropdown */
$catItems = ArrayHelper::map(
    $cache->getOrSet($p['key_expenses_category'], fn() => $db->createCommand($p['expenses_category_query'])->queryAll(), $d), 'id', 'name');
$incTypes = ArrayHelper::map(
    $cache->getOrSet($p['key_income_category'], fn() => $db->createCommand($p['income_category_query'])->queryAll(), $d), 'id', 'name');

/*
 * ⚠ العقود (7,301) — لم تعد تُحمّل كـ dropdown!
 *   بدلاً من ذلك: input رقمي يسمح للمستخدم بكتابة رقم العقد مباشرة.
 *   هذا يوفّر ~146,000 عنصر DOM في كل تحميل للصفحة.
 */

/* ═══ حساب الرصيد ═══ */
$balance = $totalCredit - $totalDebit;
$balCls  = $balance >= 0 ? 'positive' : 'negative';

/*
 * ═══ Eager Loading ═══
 * بدلاً من أن يستعلم Yii عن الشركة لكل صف (N+1)،
 * نجبرالـ dataProvider على تحميلها دفعة واحدة مع الاستعلام الرئيسي
 */
$dataProvider->query->with(['company']);
?>

<?= $this->render('@app/views/layouts/_financial-tabs', ['activeTab' => 'transactions']) ?>

<div class="fin-page">

    <!-- ╔═══════════════════════════════════════════════╗
         ║  1. شريط الملخص المالي — Financial Overview  ║
         ╚═══════════════════════════════════════════════╝ -->
    <section class="fin-overview" aria-label="ملخص مالي">
        <div class="fin-ov-card fin-ov--count">
            <div class="fin-ov-icon"><i class="fa fa-list-ol"></i></div>
            <div class="fin-ov-body">
                <span class="fin-ov-num"><?= number_format($totalCount) ?></span>
                <span class="fin-ov-lbl">عدد الحركات</span>
            </div>
        </div>
        <div class="fin-ov-card fin-ov--credit">
            <div class="fin-ov-icon"><i class="fa fa-arrow-down"></i></div>
            <div class="fin-ov-body">
                <span class="fin-ov-num"><?= number_format($totalCredit, 2) ?></span>
                <span class="fin-ov-lbl">إجمالي الدائن</span>
            </div>
        </div>
        <div class="fin-ov-card fin-ov--debit">
            <div class="fin-ov-icon"><i class="fa fa-arrow-up"></i></div>
            <div class="fin-ov-body">
                <span class="fin-ov-num"><?= number_format($totalDebit, 2) ?></span>
                <span class="fin-ov-lbl">إجمالي المدين</span>
            </div>
        </div>
        <div class="fin-ov-card fin-ov--balance fin-ov--<?= $balCls ?>">
            <div class="fin-ov-icon"><i class="fa fa-balance-scale"></i></div>
            <div class="fin-ov-body">
                <span class="fin-ov-num"><?= number_format(abs($balance), 2) ?></span>
                <span class="fin-ov-lbl">الرصيد <?= $balance >= 0 ? '(موجب)' : '(سالب)' ?></span>
            </div>
        </div>
    </section>

    <!-- ╔═══════════════════════════════════════════════╗
         ║  2. شريط الأدوات — Actions & Buttons         ║
         ╚═══════════════════════════════════════════════╝ -->
    <section class="fin-actions" aria-label="إجراءات">
        <?php if ($canFinCreate): ?>
        <div class="fin-act-group">
            <?= Html::a('<i class="fa fa-plus"></i> <span>حركة جديدة</span>', ['create'], [
                'class' => 'fin-btn fin-btn--add', 'title' => 'إضافة حركة مالية جديدة',
            ]) ?>
        </div>
        <?php endif ?>
        <?php if ($canFinImport): ?>
        <div class="fin-act-group">
            <?= Html::a('<i class="fa fa-file-excel-o"></i> <span>استيراد</span>', ['financial-transaction/import-file'], [
                'class' => 'fin-btn fin-btn--import', 'title' => 'استيراد حركات من ملف Excel',
            ]) ?>
        </div>
        <?php endif ?>
        <?php if ($canFinTransfer): ?>
        <div class="fin-act-group">
            <?= Html::a('<i class="fa fa-share-square-o"></i> <span>ترحيل دفعات</span> <b>' . $dataTransfer . '</b>', ['financial-transaction/transfer-data'], [
                'class' => 'fin-btn fin-btn--transfer', 'title' => 'ترحيل الدفعات الدائنة',
            ]) ?>
            <?= Html::a('<i class="fa fa-share-square-o"></i> <span>ترحيل مصاريف</span> <b>' . $dataTransferExpenses . '</b>', ['financial-transaction/transfer-data-to-expenses'], [
                'class' => 'fin-btn fin-btn--expense', 'title' => 'ترحيل المصاريف المدينة',
            ]) ?>
        </div>
        <?php endif ?>
        <?php if ($canFinDelete): ?>
        <div class="fin-act-group">
            <button type="button" class="fin-btn fin-btn--undo" id="undoLastImportBtn" title="حذف جميع حركات آخر استيراد">
                <i class="fa fa-undo"></i> <span>تراجع عن آخر استيراد</span>
            </button>
        </div>
        <?php endif ?>
    </section>

    <!-- ╔═══════════════════════════════════════════════╗
         ║  3. فلاتر ذكية — Smart Filters               ║
         ╚═══════════════════════════════════════════════╝ -->
    <?= $this->render('_search', ['model' => $searchModel]) ?>

    <!-- ╔═══════════════════════════════════════════════╗
         ║  4. جدول البيانات (Desktop) / بطاقات (Mobile) ║
         ╚═══════════════════════════════════════════════╝ -->
    <section class="fin-data-section"
             x-data="{ showBulk: false, bulkCount: 0 }"
             @update-bulk.window="showBulk = $event.detail.show; bulkCount = $event.detail.count">
        <div class="fin-data-bar">
            <span class="fin-data-count"><i class="fa fa-table"></i> عرض <b><?= $dataProvider->getCount() ?></b> من <b><?= $dataProvider->getTotalCount() ?></b> حركة</span>
            <?php if ($canFinDelete): ?>
            <!-- شريط الحذف الجماعي — يظهر عند تحديد صفوف -->
            <div class="fin-bulk-bar" id="bulkBar" x-show="showBulk" x-transition x-cloak>
                <span class="fin-bulk-count"><i class="fa fa-check-square-o"></i> تم تحديد <b id="bulkCount" x-text="bulkCount">0</b> حركة</span>
                <button type="button" class="fin-btn fin-btn--del fin-btn--sm" id="bulkDeleteBtn" title="حذف المحدد">
                    <i class="fa fa-trash-o"></i> حذف المحدد
                </button>
            </div>
            <?php endif ?>
        </div>

        <!-- === عرض الجدول (Desktop / Tablet) === -->
        <?php
            /* ═══ تعريف الفرز — الأعمدة القابلة للترتيب ═══ */
            $sort = $dataProvider->sort;
            $sort->attributes = array_merge($sort->attributes, [
                'date'            => ['asc' => ['date' => SORT_ASC], 'desc' => ['date' => SORT_DESC], 'label' => 'التاريخ'],
                'description'     => ['asc' => ['description' => SORT_ASC], 'desc' => ['description' => SORT_DESC], 'label' => 'البيان'],
                'type'            => ['asc' => ['type' => SORT_ASC], 'desc' => ['type' => SORT_DESC], 'label' => 'مدين / دائن'],
                'amount'          => ['asc' => ['amount' => SORT_ASC], 'desc' => ['amount' => SORT_DESC], 'label' => 'المبلغ'],
                'category_id'     => ['asc' => ['category_id' => SORT_ASC], 'desc' => ['category_id' => SORT_DESC], 'label' => 'التصنيف'],
                'company_id'      => ['asc' => ['company_id' => SORT_ASC], 'desc' => ['company_id' => SORT_DESC], 'label' => 'الشركة'],
                'contract_id'     => ['asc' => ['contract_id' => SORT_ASC], 'desc' => ['contract_id' => SORT_DESC], 'label' => 'العقد'],
                'document_number' => ['asc' => ['document_number' => SORT_ASC], 'desc' => ['document_number' => SORT_DESC], 'label' => 'المستند'],
            ]);
            if (empty($sort->defaultOrder)) {
                $sort->defaultOrder = ['date' => SORT_DESC];
            }

            /**
             * دالة مساعدة — تولّد رابط فرز مع أيقونة السهم
             */
            $sortLink = function($attribute, $extraClass = '') use ($sort) {
                $orders = $sort->getAttributeOrders();
                $icon = '';
                if (isset($orders[$attribute])) {
                    $icon = $orders[$attribute] === SORT_ASC
                        ? ' <i class="fa fa-sort-asc"></i>'
                        : ' <i class="fa fa-sort-desc"></i>';
                } else {
                    $icon = ' <i class="fa fa-sort fin-sort-idle"></i>';
                }
                $link = $sort->createUrl($attribute);
                $label = $sort->attributes[$attribute]['label'] ?? $attribute;
                return '<a href="' . Html::encode($link) . '" class="fin-sort-link ' . $extraClass . '">' . $label . $icon . '</a>';
            };
        ?>
        <div class="fin-table-wrap">
            <table class="fin-table">
                <thead>
                    <tr>
                        <th class="fin-th fin-th--chk" style="width:36px"><input type="checkbox" id="chkAll" class="fin-chk" title="تحديد الكل"></th>
                        <th class="fin-th" style="width:100px"><?= $sortLink('date') ?></th>
                        <th class="fin-th"><?= $sortLink('description') ?></th>
                        <th class="fin-th fin-th--center" style="width:90px"><?= $sortLink('type', 'fin-sort--center') ?></th>
                        <th class="fin-th fin-th--num" style="width:120px"><?= $sortLink('amount', 'fin-sort--num') ?></th>
                        <th class="fin-th" style="width:130px"><?= $sortLink('category_id') ?></th>
                        <th class="fin-th" style="width:110px"><?= $sortLink('company_id') ?></th>
                        <th class="fin-th fin-th--center" style="width:75px"><?= $sortLink('contract_id', 'fin-sort--center') ?></th>
                        <th class="fin-th fin-th--center" style="width:80px"><?= $sortLink('document_number', 'fin-sort--center') ?></th>
                        <th class="fin-th fin-th--center" style="width:80px">إجراءات</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($dataProvider->getModels() as $m): ?>
                    <?php
                        $isCredit   = ($m->type == $typeIncome);
                        $rowCls     = $isCredit ? 'fin-row--credit' : 'fin-row--debit';
                        $typeLbl    = $isCredit ? 'دائنة' : 'مدينة';
                        $typeIcon   = $isCredit ? 'fa-arrow-down' : 'fa-arrow-up';
                        $pillCls    = $isCredit ? 'fin-pill--credit' : 'fin-pill--debit';
                        $amtCls     = $isCredit ? 'fin-amt--credit' : 'fin-amt--debit';
                        $desc       = $m->description ?: $m->bank_description;
                        $safeDesc   = Html::encode($desc);
                        /* الشركة: تم تحميلها مسبقاً (eager loading) — بدون استعلام إضافي */
                        $companyName = $m->company ? $m->company->name : '—';
                        /* العقد: متاح دائماً للتعديل — التحقق من الصحة يتم في الـ controller */
                    ?>
                    <tr class="fin-row <?= $rowCls ?>"
                        data-id="<?= $m->id ?>"
                        data-date="<?= Html::encode($m->date) ?>"
                        data-desc="<?= $safeDesc ?>"
                        data-amount="<?= number_format($m->amount, 2) ?>"
                        data-type="<?= $typeLbl ?>"
                        data-type-cls="<?= $pillCls ?>"
                        data-type-icon="<?= $typeIcon ?>"
                        data-company="<?= Html::encode($companyName) ?>">
                        <!-- تحديد -->
                        <td class="fin-td fin-td--chk"><input type="checkbox" class="fin-chk js-row-chk" value="<?= $m->id ?>"></td>
                        <!-- التاريخ -->
                        <td class="fin-td fin-td--date"><?= Html::encode($m->date) ?></td>
                        <!-- البيان -->
                        <td class="fin-td fin-td--desc"><span class="fin-desc" title="<?= $safeDesc ?>"><?= $safeDesc ?: '<span class="fin-na">—</span>' ?></span></td>
                        <!-- مدين / دائن -->
                        <td class="fin-td fin-td--type">
                            <?php if ($isManager): ?>
                                <?php $selCls = $isCredit ? 'fin-sel--credit' : 'fin-sel--debit'; ?>
                                <select class="fin-sel fin-sel--compact <?= $selCls ?> js-type-change" data-id="<?= $m->id ?>">
                                    <option value="">--</option>
                                    <option value="1" <?= $m->type == 1 ? 'selected' : '' ?>>دائنة</option>
                                    <option value="2" <?= $m->type == 2 ? 'selected' : '' ?>>مدينة</option>
                                </select>
                            <?php else: ?>
                                <span class="fin-pill <?= $pillCls ?>"><i class="fa <?= $typeIcon ?>"></i> <?= $typeLbl ?></span>
                            <?php endif ?>
                        </td>
                        <!-- المبلغ -->
                        <td class="fin-td fin-td--amount"><span class="fin-amt <?= $amtCls ?>"><?= number_format($m->amount, 2) ?></span></td>
                        <!-- التصنيف / نوع الدخل -->
                        <td class="fin-td fin-td--cat">
                            <?php if ($m->type == $typeOutcome): ?>
                                <select class="fin-sel fin-sel--compact js-category-change" data-id="<?= $m->id ?>">
                                    <option value="">-- تصنيف --</option>
                                    <?php foreach ($catItems as $cid => $cname): ?>
                                    <option value="<?= $cid ?>" <?= $m->category_id == $cid ? 'selected' : '' ?>><?= Html::encode($cname) ?></option>
                                    <?php endforeach ?>
                                </select>
                            <?php elseif ($m->type == $typeIncome): ?>
                                <select class="fin-sel fin-sel--compact js-income-type" data-id="<?= $m->id ?>">
                                    <?php foreach ($incTypes as $tid => $tname): ?>
                                    <option value="<?= $tid ?>" <?= $m->income_type == $tid ? 'selected' : '' ?>><?= Html::encode($tname) ?></option>
                                    <?php endforeach ?>
                                </select>
                            <?php else: ?>
                                <span class="fin-na">—</span>
                            <?php endif ?>
                        </td>
                        <!-- الشركة -->
                        <td class="fin-td fin-td--company"><?= Html::encode($companyName) ?></td>
                        <!-- العقد — input رقمي بدل dropdown (7,301 عقد!) -->
                        <td class="fin-td fin-td--contract">
                            <input type="number" class="fin-input fin-input--compact js-contract"
                                   value="<?= (int)$m->contract_id ?: '' ?>"
                                   data-id="<?= $m->id ?>"
                                   placeholder="رقم"
                                   min="1">
                        </td>
                        <!-- المستند -->
                        <td class="fin-td fin-td--doc">
                            <input type="text" class="fin-input fin-input--compact js-doc-number"
                                   value="<?= Html::encode($m->document_number) ?>"
                                   data-id="<?= $m->id ?>"
                                   placeholder="رقم">
                        </td>
                        <!-- إجراءات -->
                        <td class="fin-td fin-td--acts">
                            <div class="fin-acts-wrap">
                                <?php if (!empty($m->bank_description)): ?>
                                <button type="button" class="fin-act fin-act--notes js-notes-btn" data-id="<?= $m->id ?>" data-toggle="modal" data-target="#notesModal" title="ملاحظات"><i class="fa fa-sticky-note-o"></i></button>
                                <?php endif ?>
                                <?php if ($canFinEdit): ?>
                                <?= Html::a('<i class="fa fa-pencil"></i>', ['update', 'id' => $m->id], ['class' => 'fin-act fin-act--edit', 'title' => 'تعديل']) ?>
                                <?php endif ?>
                                <?php if ($canFinDelete): ?>
                                <?= Html::a('<i class="fa fa-trash-o"></i>', ['delete', 'id' => $m->id], ['class' => 'fin-act fin-act--del', 'title' => 'حذف', 'data' => ['method' => 'post', 'confirm' => 'هل أنت متأكد من حذف هذه الحركة؟']]) ?>
                                <?php endif ?>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach ?>
                    <?php if ($dataProvider->getCount() === 0): ?>
                    <tr><td colspan="10" class="fin-td--empty"><i class="fa fa-inbox"></i> لا توجد حركات مالية</td></tr>
                    <?php endif ?>
                </tbody>
            </table>
        </div>

        <!-- === عرض البطاقات (Mobile / Fold مطوي) === -->
        <div class="fin-cards-wrap">
            <?php foreach ($dataProvider->getModels() as $m): ?>
            <?php
                $isCredit = ($m->type == $typeIncome);
                $desc     = $m->description ?: $m->bank_description;
                $companyName = $m->company ? $m->company->name : '—';
            ?>
            <div class="fin-card <?= $isCredit ? 'fin-card--credit' : 'fin-card--debit' ?>">
                <div class="fin-card-head">
                    <span class="fin-card-date"><?= Html::encode($m->date) ?></span>
                    <span class="fin-pill <?= $isCredit ? 'fin-pill--credit' : 'fin-pill--debit' ?>">
                        <i class="fa <?= $isCredit ? 'fa-arrow-down' : 'fa-arrow-up' ?>"></i>
                        <?= $isCredit ? 'دائنة' : 'مدينة' ?>
                    </span>
                </div>
                <div class="fin-card-amount <?= $isCredit ? 'fin-amt--credit' : 'fin-amt--debit' ?>">
                    <?= number_format($m->amount, 2) ?> <small>د.أ</small>
                </div>
                <?php if (!empty($desc)): ?>
                <div class="fin-card-desc"><?= Html::encode($desc) ?></div>
                <?php endif ?>
                <div class="fin-card-meta">
                    <span><i class="fa fa-building-o"></i> <?= Html::encode($companyName) ?></span>
                    <?php if (!empty($m->contract_id)): ?>
                    <span><i class="fa fa-file-text-o"></i> عقد #<?= (int)$m->contract_id ?></span>
                    <?php endif ?>
                </div>
                <div class="fin-card-foot">
                    <?php if ($canFinEdit): ?>
                    <?= Html::a('<i class="fa fa-pencil"></i> تعديل', ['update', 'id' => $m->id], ['class' => 'fin-card-btn']) ?>
                    <?php endif ?>
                    <?php if ($canFinDelete): ?>
                    <?= Html::a('<i class="fa fa-trash-o"></i> حذف', ['delete', 'id' => $m->id], ['class' => 'fin-card-btn fin-card-btn--del', 'data' => ['method' => 'post', 'confirm' => 'هل أنت متأكد؟']]) ?>
                    <?php endif ?>
                </div>
            </div>
            <?php endforeach ?>
            <?php if ($dataProvider->getCount() === 0): ?>
            <div class="fin-card fin-card--empty"><i class="fa fa-inbox"></i> لا توجد حركات مالية</div>
            <?php endif ?>
        </div>

        <!-- Pagination -->
        <div class="fin-pager">
            <?= \yii\widgets\LinkPager::widget([
                'pagination' => $dataProvider->getPagination(),
                'options' => ['class' => 'pagination'],
                'prevPageLabel' => '<i class="fa fa-chevron-right"></i>',
                'nextPageLabel' => '<i class="fa fa-chevron-left"></i>',
            ]) ?>
        </div>
    </section>
</div>

<!-- ═══ نافذة الملاحظات ═══ -->
<div class="modal fade" id="notesModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content fin-modal">
            <div class="modal-header fin-modal-hd">
                <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
                <h4 class="modal-title"><i class="fa fa-sticky-note-o"></i> ملاحظات الحركة</h4>
            </div>
            <div class="modal-body" style="padding:20px">
                <textarea class="form-control js-notes-text" rows="5" placeholder="اكتب ملاحظاتك هنا..." style="resize:vertical;font-size:14px;line-height:1.7;font-family:inherit"></textarea>
                <input type="hidden" class="js-notes-id">
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">إغلاق</button>
                <button type="button" class="btn btn-primary js-save-notes"><i class="fa fa-save"></i> حفظ</button>
            </div>
        </div>
    </div>
</div>

<?php Modal::begin(['id' => 'ajaxCrudModal', 'footer' => '']) ?>
<?php Modal::end() ?>

<?php
/* ═══════════════════════════════════════════════════════════
   JavaScript مسجل عبر registerJs لضمان تحميل jQuery أولاً
   ═══════════════════════════════════════════════════════════ */
$jsUrls = 'var finUrls={'
    . 'category:"'   . Url::to(['/financialTransaction/financial-transaction/update-category']) . '",'
    . 'incomeType:"' . Url::to(['/financialTransaction/financial-transaction/update-type-income']) . '",'
    . 'contract:"'   . Url::to(['/financialTransaction/financial-transaction/contract']) . '",'
    . 'type:"'       . Url::to(['/financialTransaction/financial-transaction/update-type']) . '",'
    . 'document:"'   . Url::to(['/financialTransaction/financial-transaction/update-document']) . '",'
    . 'saveNotes:"'  . Url::to(['/financialTransaction/financial-transaction/save-notes']) . '",'
    . 'findNotes:"'  . Url::to(['/financialTransaction/financial-transaction/find-notes']) . '",'
    . 'bulkDelete:"' . Url::to(['/financialTransaction/financial-transaction/bulk-delete']) . '",'
    . 'undoLastImport:"' . Url::to(['/financialTransaction/financial-transaction/undo-last-import']) . '"'
    . '};';

$jsCode = <<<'JSBLOCK'

/* Tooltips — استثناء checkboxes */
$(".fin-act[title], .fin-btn[title]").tooltip({placement:"bottom",container:"body"});

/* تغيير التصنيف */
$(document).on("change",".js-category-change",function(){
    $.post(finUrls.category,{category_id:$(this).val(),id:$(this).data("id")});
});

/* تغيير النوع (مدين/دائن) */
$(document).on("change",".js-type-change",function(){
    $.post(finUrls.type,{type:$(this).val(),id:$(this).data("id")},function(){location.reload();});
});

/* تغيير نوع الدخل */
$(document).on("change",".js-income-type",function(){
    $.post(finUrls.incomeType,{type_income:$(this).val(),id:$(this).data("id")},function(){location.reload();});
});

/* تغيير رقم العقد — debounced مع تحقق */
var cTimer;
$(document).on("keyup change",".js-contract",function(){
    var el=$(this);
    clearTimeout(cTimer);
    cTimer=setTimeout(function(){
        var val=parseInt(el.val());
        el.removeClass("fin-input--ok fin-input--err");
        if(!val||val<=0){
            if(el.val()==="") $.post(finUrls.contract,{contract:0,id:el.data("id")});
            return;
        }
        $.post(finUrls.contract,{contract:val,id:el.data("id")},function(res){
            if(res.success){el.addClass("fin-input--ok");}
            else{el.addClass("fin-input--err");}
        },"json");
    },800);
});

/* تغيير رقم المستند — debounced */
var dTimer;
$(document).on("keyup",".js-doc-number",function(){
    var el=$(this);
    clearTimeout(dTimer);
    dTimer=setTimeout(function(){
        $.post(finUrls.document,{number:el.val(),id:el.data("id")});
    },600);
});

/* الملاحظات */
$(document).on("click",".js-notes-btn",function(){
    var id=$(this).data("id");
    $(".js-notes-id").val(id);
    $.post(finUrls.findNotes,{id:id},function(t){$(".js-notes-text").val(t);});
});
$(document).on("click",".js-save-notes",function(){
    var b=$(this);
    b.prop("disabled",1).html('<i class="fa fa-spinner fa-spin"></i>');
    $.post(finUrls.saveNotes,{text:$(".js-notes-text").val(),id:$(".js-notes-id").val()},function(){
        b.prop("disabled",0).html('<i class="fa fa-save"></i> حفظ');
        $("#notesModal").modal("hide");
    });
});

/* ═══ تحديد الصفوف والحذف الجماعي ═══ */
function updateBulkBar(){
    var c=$(".js-row-chk:checked").length;
    /* OLD jQuery - replaced by Alpine.js */
    // $("#bulkCount").text(c);
    // if(c>0){$("#bulkBar").stop().slideDown(200);}else{$("#bulkBar").stop().slideUp(200);}
    window.dispatchEvent(new CustomEvent('update-bulk',{detail:{show:c>0,count:c}}));
    var total=$(".js-row-chk").length;
    var chkAll=document.getElementById("chkAll");
    if(chkAll){
        chkAll.checked=(c>0 && c===total);
        chkAll.indeterminate=(c>0 && c<total);
    }
}

/* تحديد الكل */
$("#chkAll").on("click",function(){
    var checked=this.checked;
    $(".js-row-chk").each(function(){
        this.checked=checked;
        $(this).closest(".fin-row").toggleClass("fin-row--selected",checked);
    });
    updateBulkBar();
});

/* تحديد فردي */
$(document).on("click",".js-row-chk",function(){
    $(this).closest(".fin-row").toggleClass("fin-row--selected",this.checked);
    updateBulkBar();
});

/* حذف المحدد */
$("#bulkDeleteBtn").on("click",function(){
    var ids=[];
    $(".js-row-chk:checked").each(function(){ids.push(this.value);});
    if(!ids.length) return;
    if(!confirm("هل أنت متأكد من حذف "+ids.length+" حركة مالية؟")) return;
    var btn=$(this);
    btn.prop("disabled",1).html('<i class="fa fa-spinner fa-spin"></i> جاري الحذف...');
    $.ajax({
        url:finUrls.bulkDelete, type:"POST",
        data:{ids:ids}, dataType:"json",
        success:function(res){
            if(res.success){location.reload();}
            else{alert(res.message||"خطأ");btn.prop("disabled",0).html('<i class="fa fa-trash-o"></i> حذف المحدد');}
        },
        error:function(){alert("خطأ في الاتصال");btn.prop("disabled",0).html('<i class="fa fa-trash-o"></i> حذف المحدد');}
    });
});

/* ═══ التراجع عن آخر استيراد ═══ */
$("#undoLastImportBtn").on("click",function(){
    if(!confirm("سيتم حذف جميع الحركات من آخر عملية استيراد. هل أنت متأكد؟")) return;
    var btn=$(this);
    btn.prop("disabled",1).html('<i class="fa fa-spinner fa-spin"></i> جاري الحذف...');
    $.ajax({
        url:finUrls.undoLastImport, type:"POST",
        dataType:"json",
        success:function(res){
            if(res.success){alert(res.message);location.reload();}
            else{alert(res.message||"خطأ");btn.prop("disabled",0).html('<i class="fa fa-undo"></i> تراجع عن آخر استيراد');}
        },
        error:function(){alert("خطأ في الاتصال");btn.prop("disabled",0).html('<i class="fa fa-undo"></i> تراجع عن آخر استيراد');}
    });
});
JSBLOCK;

$this->registerJs($jsUrls . $jsCode, \yii\web\View::POS_READY);
?>
