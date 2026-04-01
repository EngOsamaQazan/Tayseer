const router = require('express').Router();
const pool = require('../db');
const ExcelJS = require('exceljs');

async function getCasesData(filters = {}) {
  const conditions = ['j.is_deleted = 0'];
  const params = [];

  if (filters.court_id) { conditions.push('j.court_id = ?'); params.push(filters.court_id); }
  if (filters.type_id) { conditions.push('j.type_id = ?'); params.push(filters.type_id); }
  if (filters.lawyer_id) { conditions.push('j.lawyer_id = ?'); params.push(filters.lawyer_id); }
  if (filters.year) { conditions.push('j.year = ?'); params.push(filters.year); }
  if (filters.status) { conditions.push('j.case_status = ?'); params.push(filters.status); }

  const [rows] = await pool.query(
    `SELECT j.id, j.judiciary_number, j.year, j.income_date,
            j.case_cost, j.lawyer_cost, j.case_status,
            j.contract_id,
            c.name AS court_name, jt.name AS type_name, l.name AS lawyer_name,
            (SELECT GROUP_CONCAT(cust.name SEPARATOR ', ')
             FROM os_contracts_customers cc
             JOIN os_customers cust ON cust.id = cc.customer_id
             WHERE cc.contract_id = j.contract_id) AS parties
     FROM os_judiciary j
     LEFT JOIN os_court c          ON c.id  = j.court_id
     LEFT JOIN os_judiciary_type jt ON jt.id = j.type_id
     LEFT JOIN os_lawyers l        ON l.id  = j.lawyer_id
     WHERE ${conditions.join(' AND ')}
     ORDER BY j.id DESC`,
    params
  );
  return rows;
}

async function getActionsData(filters = {}) {
  const conditions = ['jca.is_deleted = 0'];
  const params = [];

  if (filters.year) { conditions.push('j.year = ?'); params.push(filters.year); }
  if (filters.court_id) { conditions.push('j.court_id = ?'); params.push(filters.court_id); }
  if (filters.lawyer_id) { conditions.push('j.lawyer_id = ?'); params.push(filters.lawyer_id); }

  const [rows] = await pool.query(
    `SELECT jca.id, jca.action_date, jca.note, jca.request_status,
            ja.name AS action_name, ja.action_nature,
            j.judiciary_number, j.year AS case_year, j.contract_id,
            cust.name AS customer_name, c.name AS court_name, l.name AS lawyer_name
     FROM os_judiciary_customers_actions jca
     JOIN os_judiciary j              ON j.id  = jca.judiciary_id
     LEFT JOIN os_judiciary_actions ja ON ja.id = jca.judiciary_actions_id
     LEFT JOIN os_customers cust      ON cust.id = jca.customers_id
     LEFT JOIN os_court c             ON c.id  = j.court_id
     LEFT JOIN os_lawyers l           ON l.id  = j.lawyer_id
     WHERE ${conditions.join(' AND ')}
     ORDER BY jca.action_date DESC`,
    params
  );
  return rows;
}

router.get('/excel/cases', async (req, res) => {
  try {
    const rows = await getCasesData(req.query);

    const workbook = new ExcelJS.Workbook();
    const sheet = workbook.addWorksheet('القضايا');
    sheet.views = [{ rightToLeft: true }];

    sheet.columns = [
      { header: '#', key: 'id', width: 8 },
      { header: 'رقم الدعوى', key: 'judiciary_number', width: 15 },
      { header: 'السنة', key: 'year', width: 8 },
      { header: 'المحكمة', key: 'court_name', width: 20 },
      { header: 'النوع', key: 'type_name', width: 18 },
      { header: 'المحامي', key: 'lawyer_name', width: 20 },
      { header: 'رقم العقد', key: 'contract_id', width: 12 },
      { header: 'الأطراف', key: 'parties', width: 30 },
      { header: 'تاريخ الورود', key: 'income_date', width: 14 },
      { header: 'رسوم القضية', key: 'case_cost', width: 12 },
      { header: 'أتعاب المحامي', key: 'lawyer_cost', width: 12 },
      { header: 'الحالة', key: 'case_status', width: 12 },
    ];

    sheet.getRow(1).font = { bold: true, color: { argb: 'FFFFFFFF' } };
    sheet.getRow(1).fill = { type: 'pattern', pattern: 'solid', fgColor: { argb: 'FF059669' } };

    rows.forEach((row) => sheet.addRow(row));

    res.setHeader('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    res.setHeader('Content-Disposition', 'attachment; filename=cases.xlsx');

    await workbook.xlsx.write(res);
    res.end();
  } catch (err) {
    console.error('Excel cases export error:', err);
    res.status(500).json({ success: false, error: err.message });
  }
});

router.get('/excel/actions', async (req, res) => {
  try {
    const rows = await getActionsData(req.query);

    const workbook = new ExcelJS.Workbook();
    const sheet = workbook.addWorksheet('الإجراءات');
    sheet.views = [{ rightToLeft: true }];

    sheet.columns = [
      { header: '#', key: 'id', width: 8 },
      { header: 'رقم الدعوى', key: 'judiciary_number', width: 15 },
      { header: 'السنة', key: 'case_year', width: 8 },
      { header: 'المدعى عليه', key: 'customer_name', width: 25 },
      { header: 'الإجراء', key: 'action_name', width: 25 },
      { header: 'التاريخ', key: 'action_date', width: 14 },
      { header: 'المحكمة', key: 'court_name', width: 20 },
      { header: 'المحامي', key: 'lawyer_name', width: 20 },
      { header: 'الحالة', key: 'request_status', width: 12 },
      { header: 'ملاحظات', key: 'note', width: 30 },
    ];

    sheet.getRow(1).font = { bold: true, color: { argb: 'FFFFFFFF' } };
    sheet.getRow(1).fill = { type: 'pattern', pattern: 'solid', fgColor: { argb: 'FF059669' } };

    rows.forEach((row) => sheet.addRow(row));

    res.setHeader('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    res.setHeader('Content-Disposition', 'attachment; filename=actions.xlsx');

    await workbook.xlsx.write(res);
    res.end();
  } catch (err) {
    console.error('Excel actions export error:', err);
    res.status(500).json({ success: false, error: err.message });
  }
});

router.get('/excel/persistence', async (req, res) => {
  try {
    const { search, color } = req.query;
    const conditions = [];
    const params = [];

    if (search) {
      conditions.push('(p.customer_name LIKE ? OR p.contract_id LIKE ? OR p.judiciary_number LIKE ?)');
      const term = `%${search}%`;
      params.push(term, term, term);
    }
    if (color) {
      conditions.push('p.persistence_status = ?');
      params.push(color);
    }

    const whereClause = conditions.length > 0 ? 'WHERE ' + conditions.join(' AND ') : '';

    const [rows] = await pool.query(
      `SELECT p.* FROM tbl_persistence_cache p ${whereClause} ORDER BY p.id DESC`,
      params
    );

    const workbook = new ExcelJS.Workbook();
    const sheet = workbook.addWorksheet('المثابرة');
    sheet.views = [{ rightToLeft: true }];

    sheet.columns = [
      { header: '#', key: 'id', width: 8 },
      { header: 'رقم الدعوى', key: 'judiciary_number', width: 15 },
      { header: 'السنة', key: 'case_year', width: 8 },
      { header: 'المحكمة', key: 'court_name', width: 20 },
      { header: 'رقم العقد', key: 'contract_id', width: 12 },
      { header: 'اسم العميل', key: 'customer_name', width: 25 },
      { header: 'آخر إجراء', key: 'last_action_name', width: 25 },
      { header: 'تاريخ آخر إجراء', key: 'last_action_date', width: 14 },
      { header: 'المثابرة', key: 'persistence_status', width: 12 },
      { header: 'المحامي', key: 'lawyer_name', width: 20 },
    ];

    sheet.getRow(1).font = { bold: true, color: { argb: 'FFFFFFFF' } };
    sheet.getRow(1).fill = { type: 'pattern', pattern: 'solid', fgColor: { argb: 'FF059669' } };

    rows.forEach((row) => sheet.addRow(row));

    res.setHeader('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    res.setHeader('Content-Disposition', 'attachment; filename=persistence.xlsx');

    await workbook.xlsx.write(res);
    res.end();
  } catch (err) {
    console.error('Excel persistence export error:', err);
    res.status(500).json({ success: false, error: err.message });
  }
});

router.get('/pdf/cases', async (req, res) => {
  try {
    const rows = await getCasesData(req.query);

    const tableBody = [
      ['#', 'رقم الدعوى', 'السنة', 'المحكمة', 'المحامي', 'الحالة'],
    ];

    rows.forEach((row, i) => {
      tableBody.push([
        String(i + 1),
        String(row.judiciary_number || '-'),
        String(row.year || '-'),
        row.court_name || '-',
        row.lawyer_name || '-',
        String(row.case_status ?? '-'),
      ]);
    });

    const html = `
      <html dir="rtl"><head><meta charset="utf-8">
      <style>body{font-family:sans-serif;direction:rtl}table{width:100%;border-collapse:collapse}
      th,td{border:1px solid #ddd;padding:6px 8px;text-align:right;font-size:12px}
      th{background:#059669;color:#fff}h1{color:#059669;font-size:18px}</style></head>
      <body><h1>تقرير القضايا</h1><p>العدد: ${rows.length}</p>
      <table>${tableBody.map((r,i) => `<tr>${r.map(c => i===0?`<th>${c}</th>`:`<td>${c}</td>`).join('')}</tr>`).join('')}</table>
      </body></html>
    `;

    res.setHeader('Content-Type', 'text/html; charset=utf-8');
    res.send(html);
  } catch (err) {
    console.error('PDF cases export error:', err);
    res.status(500).json({ success: false, error: err.message });
  }
});

router.get('/pdf/case/:id', async (req, res) => {
  try {
    const { id } = req.params;

    const [[caseRow]] = await pool.query(
      `SELECT j.*, c.name AS court_name, jt.name AS type_name,
              l.name AS lawyer_name
       FROM os_judiciary j
       LEFT JOIN os_court c          ON c.id  = j.court_id
       LEFT JOIN os_judiciary_type jt ON jt.id = j.type_id
       LEFT JOIN os_lawyers l        ON l.id  = j.lawyer_id
       WHERE j.id = ? AND j.is_deleted = 0`,
      [id]
    );

    if (!caseRow) {
      return res.status(404).json({ success: false, error: 'Case not found' });
    }

    const [parties] = await pool.query(
      `SELECT cust.name FROM os_contracts_customers cc
       JOIN os_customers cust ON cust.id = cc.customer_id
       WHERE cc.contract_id = ?`,
      [caseRow.contract_id]
    );

    const [actions] = await pool.query(
      `SELECT jca.action_date, jca.note, ja.name AS action_name, cust.name AS customer_name
       FROM os_judiciary_customers_actions jca
       LEFT JOIN os_judiciary_actions ja ON ja.id = jca.judiciary_actions_id
       LEFT JOIN os_customers cust ON cust.id = jca.customers_id
       WHERE jca.judiciary_id = ? AND jca.is_deleted = 0
       ORDER BY jca.action_date DESC`,
      [id]
    );

    let actionsHtml = '';
    if (actions.length > 0) {
      actionsHtml = `<h2>سجل الإجراءات</h2><table>
        <tr><th>التاريخ</th><th>الإجراء</th><th>المدعى عليه</th><th>ملاحظات</th></tr>
        ${actions.map(a => `<tr><td>${a.action_date||'-'}</td><td>${a.action_name||'-'}</td><td>${a.customer_name||'-'}</td><td>${a.note||'-'}</td></tr>`).join('')}
      </table>`;
    }

    const html = `
      <html dir="rtl"><head><meta charset="utf-8">
      <style>body{font-family:sans-serif;direction:rtl}table{width:100%;border-collapse:collapse;margin-top:10px}
      th,td{border:1px solid #ddd;padding:6px 8px;text-align:right;font-size:12px}
      th{background:#059669;color:#fff}h1{color:#059669}h2{color:#333;margin-top:20px}
      .info{display:flex;flex-wrap:wrap;gap:15px;margin:10px 0}.info div{flex:1;min-width:200px}</style></head>
      <body>
        <h1>قضية #${caseRow.judiciary_number || id}</h1>
        <div class="info">
          <div><strong>المحكمة:</strong> ${caseRow.court_name || '-'}</div>
          <div><strong>النوع:</strong> ${caseRow.type_name || '-'}</div>
          <div><strong>المحامي:</strong> ${caseRow.lawyer_name || '-'}</div>
          <div><strong>السنة:</strong> ${caseRow.year || '-'}</div>
          <div><strong>رسوم القضية:</strong> ${caseRow.case_cost || 0}</div>
          <div><strong>أتعاب المحامي:</strong> ${caseRow.lawyer_cost || 0}</div>
        </div>
        <h2>الأطراف</h2>
        <ul>${parties.map(p => `<li>${p.name}</li>`).join('')}</ul>
        ${actionsHtml}
      </body></html>
    `;

    res.setHeader('Content-Type', 'text/html; charset=utf-8');
    res.send(html);
  } catch (err) {
    console.error('PDF case export error:', err);
    res.status(500).json({ success: false, error: err.message });
  }
});

router.get('/excel/deadlines', async (req, res) => {
  try {
    const { status, deadline_type, date_from, date_to, search } = req.query;
    const conditions = ['d.is_deleted = 0'];
    const params = [];

    if (status && status !== 'all') { conditions.push('d.status = ?'); params.push(status); }
    if (deadline_type && deadline_type !== 'all') { conditions.push('d.deadline_type = ?'); params.push(deadline_type); }
    if (date_from) { conditions.push('d.deadline_date >= ?'); params.push(date_from); }
    if (date_to) { conditions.push('d.deadline_date <= ?'); params.push(date_to); }
    if (search) {
      conditions.push('(d.label LIKE ? OR d.notes LIKE ? OR j.judiciary_number LIKE ?)');
      const term = `%${search}%`;
      params.push(term, term, term);
    }

    const [rows] = await pool.query(
      `SELECT d.id, d.label, d.deadline_type, d.deadline_date, d.start_date,
              d.status, d.notes, d.day_type,
              j.judiciary_number, j.year AS case_year
       FROM os_judiciary_deadlines d
       LEFT JOIN os_judiciary j ON j.id = d.judiciary_id
       WHERE ${conditions.join(' AND ')}
       ORDER BY d.deadline_date ASC`,
      params
    );

    const typeLabels = {
      registration_3wd: 'تسجيل (3 أيام عمل)', notification_check: 'فحص تبليغ',
      notification_16cd: 'تبليغ (16 يوم)', request_decision: 'قرار طلب',
      correspondence_10wd: 'مراسلة (10 أيام عمل)', property_7cd: 'ملكية (7 أيام)',
      salary_3m: 'راتب (3 أشهر)', custom: 'مخصص',
    };
    const statusLabels = {
      pending: 'قائم', approaching: 'يقترب', expired: 'متأخر', completed: 'مكتمل',
    };

    const workbook = new ExcelJS.Workbook();
    const sheet = workbook.addWorksheet('المواعيد');
    sheet.views = [{ rightToLeft: true }];

    sheet.columns = [
      { header: '#', key: 'id', width: 8 },
      { header: 'رقم الدعوى', key: 'judiciary_number', width: 15 },
      { header: 'السنة', key: 'case_year', width: 8 },
      { header: 'العنوان', key: 'label', width: 25 },
      { header: 'النوع', key: 'type_label', width: 22 },
      { header: 'تاريخ البدء', key: 'start_date', width: 14 },
      { header: 'تاريخ الموعد', key: 'deadline_date', width: 14 },
      { header: 'الحالة', key: 'status_label', width: 12 },
      { header: 'ملاحظات', key: 'notes', width: 30 },
    ];

    sheet.getRow(1).font = { bold: true, color: { argb: 'FFFFFFFF' } };
    sheet.getRow(1).fill = { type: 'pattern', pattern: 'solid', fgColor: { argb: 'FF059669' } };

    rows.forEach((row) => {
      sheet.addRow({
        ...row,
        type_label: typeLabels[row.deadline_type] || row.deadline_type || '-',
        status_label: statusLabels[row.status] || row.status || '-',
      });
    });

    res.setHeader('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    res.setHeader('Content-Disposition', 'attachment; filename=deadlines.xlsx');
    await workbook.xlsx.write(res);
    res.end();
  } catch (err) {
    console.error('Excel deadlines export error:', err);
    res.status(500).json({ success: false, error: err.message });
  }
});

module.exports = router;
