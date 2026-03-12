<?php
/**
 * كشف المثابره — صفحة طباعة
 * تُفتح في تبويب جديد وتعرض جميع السجلات حسب الفلتر الحالي
 */
use backend\helpers\NameHelper;
$totalRows = count($rows);
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>كشف المثابره — طباعة</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: 'Segoe UI', Tahoma, Arial, sans-serif;
            direction: rtl;
            background: #fff;
            color: #222;
            font-size: 11px;
            line-height: 1.4;
        }

        .print-header {
            text-align: center;
            padding: 15px 20px;
            border-bottom: 3px solid #1a5276;
            margin-bottom: 10px;
        }
        .print-header h1 {
            font-size: 22px;
            color: #1a5276;
            margin-bottom: 4px;
        }
        .print-header .filter-info {
            font-size: 13px;
            color: #555;
        }
        .print-header .meta-info {
            font-size: 11px;
            color: #888;
            margin-top: 4px;
        }

        .stats-bar {
            display: flex;
            justify-content: center;
            gap: 30px;
            padding: 8px 0;
            margin-bottom: 10px;
            border-bottom: 1px solid #ddd;
        }
        .stats-bar .stat {
            text-align: center;
            font-weight: bold;
        }
        .stats-bar .stat .num {
            font-size: 18px;
            display: block;
        }
        .stats-bar .stat .lbl {
            font-size: 10px;
            color: #666;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 10px;
        }
        thead th {
            background: #1a5276;
            color: #fff;
            padding: 6px 4px;
            text-align: center;
            font-size: 10px;
            white-space: nowrap;
        }
        tbody td {
            border: 1px solid #ccc;
            padding: 4px 5px;
            text-align: center;
            vertical-align: middle;
        }
        tbody tr:nth-child(even) {
            background: #f7f9fb;
        }

        /* ألوان المثابرة */
        .p-red    { background: #fde8e8 !important; color: #c0392b; font-weight: bold; }
        .p-orange { background: #fef5e7 !important; color: #e67e22; font-weight: bold; }
        .p-green  { background: #e8f8f0 !important; color: #27ae60; font-weight: bold; }
        .p-gray   { background: #f2f2f2 !important; color: #777; }

        .no-print-bar {
            position: fixed;
            top: 0; left: 0; right: 0;
            background: #1a5276;
            color: #fff;
            padding: 8px 20px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            z-index: 9999;
            box-shadow: 0 2px 8px rgba(0,0,0,.2);
        }
        .no-print-bar button {
            background: #fff;
            color: #1a5276;
            border: none;
            padding: 6px 22px;
            border-radius: 4px;
            font-size: 14px;
            font-weight: bold;
            cursor: pointer;
        }
        .no-print-bar button:hover { background: #dce6f0; }
        .no-print-bar .info { font-size: 13px; }

        body { padding-top: 50px; }

        @media print {
            body { padding-top: 0; }
            .no-print-bar { display: none !important; }
            thead { display: table-header-group; }
            tbody tr { page-break-inside: avoid; }
            @page {
                size: landscape;
                margin: 8mm;
            }
        }
    </style>
</head>
<body>

<!-- شريط أدوات (لا يُطبع) -->
<div class="no-print-bar">
    <div class="info">
        <strong>كشف المثابره</strong> — <?= htmlspecialchars($filterLabel) ?> — إجمالي: <?= number_format($totalRows) ?> سجل
    </div>
    <div>
        <button onclick="window.print()">🖨️ طباعة / حفظ PDF</button>
        <button onclick="window.close()" style="margin-right:8px; background:#e74c3c; color:#fff;">✕ إغلاق</button>
    </div>
</div>

<!-- رأس التقرير -->
<div class="print-header">
    <h1>كشف المثابره</h1>
    <div class="filter-info"><?= htmlspecialchars($filterLabel) ?></div>
    <div class="meta-info">
        تاريخ الطباعة: <?= date('Y-m-d H:i') ?> —
        عدد السجلات: <?= number_format($totalRows) ?>
    </div>
</div>

<?php
// حساب الإحصائيات من البيانات المعروضة
$cntRed = 0; $cntOrange = 0; $cntGreen = 0;
foreach ($rows as $r) {
    if ($r['persistence_color'] === 'red') $cntRed++;
    elseif ($r['persistence_color'] === 'orange') $cntOrange++;
    elseif ($r['persistence_color'] === 'green') $cntGreen++;
}
?>
<div class="stats-bar">
    <div class="stat">
        <span class="num"><?= number_format($totalRows) ?></span>
        <span class="lbl">إجمالي</span>
    </div>
    <div class="stat" style="color:#c0392b">
        <span class="num"><?= number_format($cntRed) ?></span>
        <span class="lbl">عاجل</span>
    </div>
    <div class="stat" style="color:#e67e22">
        <span class="num"><?= number_format($cntOrange) ?></span>
        <span class="lbl">قريب</span>
    </div>
    <div class="stat" style="color:#27ae60">
        <span class="num"><?= number_format($cntGreen) ?></span>
        <span class="lbl">جيد</span>
    </div>
</div>

<!-- جدول البيانات — نفس أعمدة الكشف الرئيسي -->
<table>
    <thead>
        <tr>
            <th>#</th>
            <th>رقم القضية</th>
            <th>السنة</th>
            <th>المحكمة</th>
            <th>رقم العقد</th>
            <th>اسم العميل</th>
            <th>الإجراء الأخير</th>
            <th>تاريخ آخر إجراء</th>
            <th>مؤشّر المثابرة</th>
            <th>آخر متابعة للعقد</th>
            <th>آخر تشييك وظيفة</th>
            <th>المحامي</th>
            <th>الوظيفة</th>
            <th>نوع الوظيفة</th>
        </tr>
    </thead>
    <tbody>
    <?php if (empty($rows)): ?>
        <tr><td colspan="14" style="padding:20px; color:#999;">لا توجد سجلات</td></tr>
    <?php else: ?>
        <?php $i = 0; foreach ($rows as $row): $i++; ?>
        <tr>
            <td><?= $i ?></td>
            <td><strong><?= htmlspecialchars($row['judiciary_number'] ?? '') ?></strong></td>
            <td><?= htmlspecialchars($row['case_year'] ?? '') ?></td>
            <td><?= htmlspecialchars($row['court_name'] ?? '') ?></td>
            <td><?= htmlspecialchars($row['contract_id'] ?? '') ?></td>
            <td title="<?= htmlspecialchars($row['customer_name'] ?? '') ?>"><?= htmlspecialchars(NameHelper::short($row['customer_name'] ?? '')) ?></td>
            <td><?= htmlspecialchars($row['last_action_name'] ?? '') ?></td>
            <td><?= htmlspecialchars($row['last_action_date'] ?? '') ?></td>
            <td class="p-<?= htmlspecialchars($row['persistence_color']) ?>">
                <?= $row['persistence_icon'] ?> <?= htmlspecialchars($row['persistence_label']) ?>
            </td>
            <td><?= htmlspecialchars($row['last_followup_date'] ?? '') ?></td>
            <td><?= htmlspecialchars($row['last_job_check_date'] ?? '') ?></td>
            <td title="<?= htmlspecialchars($row['lawyer_name'] ?? '') ?>"><?= htmlspecialchars(NameHelper::short($row['lawyer_name'] ?? '')) ?></td>
            <td><?= htmlspecialchars($row['job_title'] ?? '') ?></td>
            <td><?= htmlspecialchars($row['job_type'] ?? '') ?></td>
        </tr>
        <?php endforeach; ?>
    <?php endif; ?>
    </tbody>
</table>

<script>
// فتح نافذة الطباعة تلقائياً عند تحميل الصفحة
window.addEventListener('load', function() {
    setTimeout(function(){ window.print(); }, 500);
});
</script>

</body>
</html>
