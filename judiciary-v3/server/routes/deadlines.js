const router = require('express').Router();
const pool = require('../db');

async function refreshStatuses() {
  const today = new Date();
  today.setHours(0, 0, 0, 0);
  const todayStr = today.toISOString().slice(0, 10);
  const threeDays = new Date(today);
  threeDays.setDate(threeDays.getDate() + 3);
  const threeStr = threeDays.toISOString().slice(0, 10);
  const now = Math.floor(Date.now() / 1000);

  await pool.query(
    `UPDATE os_judiciary_deadlines
     SET status = 'expired', updated_at = ?
     WHERE status IN ('pending','approaching')
       AND deadline_date < ?
       AND is_deleted = 0`,
    [now, todayStr]
  );

  await pool.query(
    `UPDATE os_judiciary_deadlines
     SET status = 'approaching', updated_at = ?
     WHERE status = 'pending'
       AND deadline_date >= ? AND deadline_date <= ?
       AND is_deleted = 0`,
    [now, todayStr, threeStr]
  );
}

function addWorkingDays(startDate, days) {
  let current = new Date(startDate);
  let added = 0;
  while (added < days) {
    current.setDate(current.getDate() + 1);
    const dow = current.getDay();
    if (dow !== 5 && dow !== 6) added++;
  }
  return current.toISOString().slice(0, 10);
}

function addCalendarDays(startDate, days) {
  const d = new Date(startDate);
  d.setDate(d.getDate() + days);
  return d.toISOString().slice(0, 10);
}

function addMonths(startDate, months) {
  const d = new Date(startDate);
  d.setMonth(d.getMonth() + months);
  return d.toISOString().slice(0, 10);
}

router.post('/sync', async (req, res) => {
  try {
    const now = Math.floor(Date.now() / 1000);
    let created = 0;

    const [existingKeys] = await pool.query(
      `SELECT CONCAT(judiciary_id, ':', deadline_type, ':', COALESCE(related_communication_id,''), ':', COALESCE(related_customer_action_id,'')) AS k
       FROM os_judiciary_deadlines WHERE is_deleted = 0`
    );
    const existSet = new Set(existingKeys.map(r => r.k));

    function makeKey(judId, type, commId, actId) {
      return `${judId}:${type}:${commId || ''}:${actId || ''}`;
    }

    async function insertDeadline(judiciaryId, customerId, type, dayType, label, startDate, deadlineDate, commId, actId) {
      const key = makeKey(judiciaryId, type, commId, actId);
      if (existSet.has(key)) return false;
      existSet.add(key);

      await pool.query(
        `INSERT INTO os_judiciary_deadlines
         (judiciary_id, customer_id, deadline_type, day_type,
          label, start_date, deadline_date, status,
          related_communication_id, related_customer_action_id,
          notes, created_at, updated_at, created_by, is_deleted)
         VALUES (?, ?, ?, ?, ?, ?, ?, 'pending', ?, ?, ?, ?, ?, 1, 0)`,
        [judiciaryId, customerId, type, dayType, label,
         startDate, deadlineDate, commId || null, actId || null,
         'ترحيل تلقائي', now, now]
      );
      created++;
      return true;
    }

    const [cases] = await pool.query(
      `SELECT j.id, j.income_date, j.contract_id,
              (SELECT cc.customer_id FROM os_contracts_customers cc
               WHERE cc.contract_id = j.contract_id LIMIT 1) AS customer_id
       FROM os_judiciary j
       WHERE j.is_deleted = 0 AND j.income_date IS NOT NULL`
    );

    for (const c of cases) {
      if (!c.income_date) continue;
      const dl = addWorkingDays(c.income_date, 3);
      await insertDeadline(
        c.id, c.customer_id,
        'registration_3wd', 'working',
        'فحص حالة التبليغ بعد التسجيل',
        c.income_date, dl, null, null
      );
    }

    const [actions] = await pool.query(
      `SELECT jca.id, jca.judiciary_id, jca.customers_id, jca.action_date,
              ja.action_nature
       FROM os_judiciary_customers_actions jca
       LEFT JOIN os_judiciary_actions ja ON ja.id = jca.judiciary_actions_id
       WHERE jca.is_deleted = 0 AND jca.action_date IS NOT NULL
         AND ja.action_nature = 'request'`
    );

    for (const a of actions) {
      if (!a.action_date) continue;
      const dl = addWorkingDays(a.action_date, 3);
      await insertDeadline(
        a.judiciary_id, a.customers_id,
        'request_decision', 'working',
        'قرار القاضي على الطلب',
        a.action_date, dl, null, a.id
      );
    }

    let hasCorrespondence = true;
    try {
      const [corrRows] = await pool.query(
        `SELECT dc.id, dc.related_record_id, dc.customer_id,
                dc.correspondence_date, dc.delivery_date,
                dc.correspondence_type, dc.direction
         FROM os_diwan_correspondence dc
         WHERE dc.related_module = 'judiciary'
           AND dc.correspondence_date IS NOT NULL
         ORDER BY dc.id`
      );

      for (const c of corrRows) {
        if (!c.related_record_id || !c.correspondence_date) continue;

        if (c.direction === 'outgoing' || !c.direction) {
          const dl = addWorkingDays(c.correspondence_date, 10);
          await insertDeadline(
            c.related_record_id, c.customer_id,
            'correspondence_10wd', 'working',
            'رد الجهة على الكتاب',
            c.correspondence_date, dl, c.id, null
          );
        }

        if (c.correspondence_type === 'notification' || c.correspondence_type === 'تبليغ') {
          const checkDl = addWorkingDays(c.correspondence_date, 3);
          await insertDeadline(
            c.related_record_id, c.customer_id,
            'notification_check', 'working',
            'فحص نتيجة التبليغ',
            c.correspondence_date, checkDl, c.id, null
          );

          if (c.delivery_date) {
            const periodDl = addCalendarDays(c.delivery_date, 16);
            await insertDeadline(
              c.related_record_id, c.customer_id,
              'notification_16cd', 'calendar',
              'انتهاء مدة التبليغ (16 يوم)',
              c.delivery_date, periodDl, c.id, null
            );
          }
        }

        if (c.correspondence_type === 'property' || c.correspondence_type === 'عقار') {
          const dl = addCalendarDays(c.correspondence_date, 7);
          await insertDeadline(
            c.related_record_id, c.customer_id,
            'property_7cd', 'calendar',
            'إخطار عقار (7 أيام)',
            c.correspondence_date, dl, c.id, null
          );
        }

        if (c.correspondence_type === 'salary' || c.correspondence_type === 'راتب') {
          const dl = addMonths(c.correspondence_date, 3);
          await insertDeadline(
            c.related_record_id, c.customer_id,
            'salary_3m', 'calendar',
            'إعادة كتاب حسم راتب (3 أشهر)',
            c.correspondence_date, dl, c.id, null
          );
        }
      }
    } catch (e) {
      hasCorrespondence = false;
    }

    await refreshStatuses();

    res.json({
      success: true,
      data: {
        created,
        sources: {
          cases: cases.length,
          actions: actions.length,
          correspondence: hasCorrespondence,
        },
      },
    });
  } catch (err) {
    console.error('POST /deadlines/sync error:', err);
    res.status(500).json({ success: false, error: err.message });
  }
});

router.get('/stats', async (req, res) => {
  try {
    await refreshStatuses();

    const today = new Date();
    today.setHours(0, 0, 0, 0);
    const todayStr = today.toISOString().slice(0, 10);
    const sevenDays = new Date(today);
    sevenDays.setDate(sevenDays.getDate() + 7);
    const sevenStr = sevenDays.toISOString().slice(0, 10);

    const [[counts]] = await pool.query(`
      SELECT
        COUNT(*) AS total,
        SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) AS completed,
        SUM(CASE WHEN status != 'completed' AND deadline_date < ? THEN 1 ELSE 0 END) AS overdue,
        SUM(CASE WHEN status != 'completed' AND deadline_date >= ? AND deadline_date <= ? THEN 1 ELSE 0 END) AS approaching,
        SUM(CASE WHEN status != 'completed' AND deadline_date > ? THEN 1 ELSE 0 END) AS pending,
        SUM(CASE WHEN status != 'completed' AND deadline_date = ? THEN 1 ELSE 0 END) AS today_count
      FROM os_judiciary_deadlines
      WHERE is_deleted = 0
    `, [todayStr, todayStr, sevenStr, sevenStr, todayStr]);

    const [[typeCounts]] = await pool.query(`
      SELECT
        COUNT(DISTINCT judiciary_id) AS cases_with_deadlines,
        COUNT(DISTINCT deadline_type) AS type_count
      FROM os_judiciary_deadlines WHERE is_deleted = 0
    `);

    res.json({
      success: true,
      data: {
        total: counts.total || 0,
        overdue: counts.overdue || 0,
        approaching: counts.approaching || 0,
        pending: counts.pending || 0,
        completed: counts.completed || 0,
        today: counts.today_count || 0,
        cases_with_deadlines: typeCounts.cases_with_deadlines || 0,
      },
    });
  } catch (err) {
    console.error('GET /deadlines/stats error:', err);
    res.status(500).json({ success: false, error: err.message });
  }
});

router.get('/', async (req, res) => {
  try {
    await refreshStatuses();

    const {
      status: filterStatus,
      deadline_type,
      date_from,
      date_to,
      search,
      page = 1,
      pageSize = 100,
    } = req.query;

    const conditions = ['d.is_deleted = 0'];
    const params = [];

    if (filterStatus && filterStatus !== 'all') {
      conditions.push('d.status = ?');
      params.push(filterStatus);
    }
    if (deadline_type && deadline_type !== 'all') {
      conditions.push('d.deadline_type = ?');
      params.push(deadline_type);
    }
    if (date_from) {
      conditions.push('d.deadline_date >= ?');
      params.push(date_from);
    }
    if (date_to) {
      conditions.push('d.deadline_date <= ?');
      params.push(date_to);
    }
    if (search) {
      conditions.push('(d.label LIKE ? OR d.notes LIKE ? OR j.judiciary_number LIKE ?)');
      const term = `%${search}%`;
      params.push(term, term, term);
    }

    const whereClause = conditions.join(' AND ');
    const offset = (Math.max(1, parseInt(page)) - 1) * parseInt(pageSize);

    const [[{ total }]] = await pool.query(
      `SELECT COUNT(*) AS total FROM os_judiciary_deadlines d
       LEFT JOIN os_judiciary j ON j.id = d.judiciary_id
       WHERE ${whereClause}`,
      params
    );

    const [rows] = await pool.query(
      `SELECT d.*, j.judiciary_number, j.year AS case_year,
              j.contract_id,
              (SELECT GROUP_CONCAT(cust.name SEPARATOR ', ')
               FROM os_contracts_customers cc
               JOIN os_customers cust ON cust.id = cc.customer_id
               WHERE cc.contract_id = j.contract_id LIMIT 1) AS party_name
       FROM os_judiciary_deadlines d
       LEFT JOIN os_judiciary j ON j.id = d.judiciary_id
       WHERE ${whereClause}
       ORDER BY d.deadline_date ASC
       LIMIT ? OFFSET ?`,
      [...params, parseInt(pageSize), offset]
    );

    const grouped = { overdue: [], approaching: [], pending: [], completed: [] };
    const today = new Date();
    today.setHours(0, 0, 0, 0);
    const sevenDays = new Date(today);
    sevenDays.setDate(sevenDays.getDate() + 7);

    for (const row of rows) {
      if (row.status === 'completed') {
        grouped.completed.push(row);
      } else {
        const dd = row.deadline_date ? new Date(row.deadline_date) : null;
        if (dd && dd < today) {
          grouped.overdue.push(row);
        } else if (dd && dd <= sevenDays) {
          grouped.approaching.push(row);
        } else {
          grouped.pending.push(row);
        }
      }
    }

    res.json({
      success: true,
      data: filterStatus && filterStatus !== 'all' ? rows : grouped,
      total,
      page: parseInt(page),
      pageSize: parseInt(pageSize),
    });
  } catch (err) {
    console.error('GET /deadlines error:', err);
    res.status(500).json({ success: false, error: err.message });
  }
});

router.get('/case/:judiciaryId', async (req, res) => {
  try {
    const { judiciaryId } = req.params;

    const [rows] = await pool.query(
      `SELECT * FROM os_judiciary_deadlines
       WHERE judiciary_id = ? AND is_deleted = 0
       ORDER BY deadline_date ASC`,
      [judiciaryId]
    );

    res.json({ success: true, data: rows });
  } catch (err) {
    console.error('GET /deadlines/case error:', err);
    res.status(500).json({ success: false, error: err.message });
  }
});

router.post('/', async (req, res) => {
  try {
    const {
      judiciary_id, customer_id, deadline_type, day_type,
      label, start_date, deadline_date, notes,
    } = req.body;
    const now = Math.floor(Date.now() / 1000);
    const createdBy = req.query.user_id || 1;

    const [result] = await pool.query(
      `INSERT INTO os_judiciary_deadlines
       (judiciary_id, customer_id, deadline_type, day_type,
        label, start_date, deadline_date, status, notes,
        created_at, updated_at, created_by, is_deleted)
       VALUES (?, ?, ?, ?, ?, ?, ?, 'pending', ?, ?, ?, ?, 0)`,
      [
        judiciary_id, customer_id || null, deadline_type || 'custom',
        day_type || 'calendar', label || null, start_date || null,
        deadline_date, notes || null, now, now, createdBy,
      ]
    );

    res.json({ success: true, data: { id: result.insertId } });
  } catch (err) {
    console.error('POST /deadlines error:', err);
    res.status(500).json({ success: false, error: err.message });
  }
});

router.put('/:id', async (req, res) => {
  try {
    const { id } = req.params;
    const { label, deadline_date, notes, status } = req.body;
    const now = Math.floor(Date.now() / 1000);

    await pool.query(
      `UPDATE os_judiciary_deadlines SET
        label = ?, deadline_date = ?, notes = ?, status = ?, updated_at = ?
       WHERE id = ? AND is_deleted = 0`,
      [label, deadline_date, notes || null, status || 'pending', now, id]
    );

    res.json({ success: true, data: { id: parseInt(id) } });
  } catch (err) {
    console.error('PUT /deadlines/:id error:', err);
    res.status(500).json({ success: false, error: err.message });
  }
});

router.patch('/:id/complete', async (req, res) => {
  try {
    const { id } = req.params;
    const now = Math.floor(Date.now() / 1000);

    await pool.query(
      `UPDATE os_judiciary_deadlines SET status = 'completed', updated_at = ?
       WHERE id = ? AND is_deleted = 0`,
      [now, id]
    );

    res.json({ success: true, data: { id: parseInt(id) } });
  } catch (err) {
    console.error('PATCH /deadlines/:id/complete error:', err);
    res.status(500).json({ success: false, error: err.message });
  }
});

router.delete('/:id', async (req, res) => {
  try {
    const { id } = req.params;
    const now = Math.floor(Date.now() / 1000);

    await pool.query(
      'UPDATE os_judiciary_deadlines SET is_deleted = 1, updated_at = ? WHERE id = ?',
      [now, id]
    );

    res.json({ success: true });
  } catch (err) {
    console.error('DELETE /deadlines/:id error:', err);
    res.status(500).json({ success: false, error: err.message });
  }
});

module.exports = router;
