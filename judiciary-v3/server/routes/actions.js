const router = require('express').Router();
const pool = require('../db');

router.get('/', async (req, res) => {
  try {
    const {
      judiciary_number, customers_id, judiciary_actions_id,
      year, contract_id, court_id, lawyer_id, created_by,
      from_action_date, to_action_date,
      page = 1, pageSize = 25,
    } = req.query;

    const conditions = ['jca.is_deleted = 0'];
    const params = [];

    if (judiciary_number) {
      conditions.push('j.judiciary_number LIKE ?');
      params.push(`%${judiciary_number}%`);
    }
    if (customers_id) {
      conditions.push('cust.name LIKE ?');
      params.push(`%${customers_id}%`);
    }
    if (judiciary_actions_id) {
      const ids = String(judiciary_actions_id).split(',').map((s) => s.trim()).filter(Boolean);
      if (ids.length > 0) {
        conditions.push(`jca.judiciary_actions_id IN (${ids.map(() => '?').join(',')})`);
        params.push(...ids);
      }
    }
    if (year) {
      conditions.push('j.year = ?');
      params.push(year);
    }
    if (contract_id) {
      conditions.push('j.contract_id = ?');
      params.push(contract_id);
    }
    if (court_id) {
      conditions.push('j.court_id = ?');
      params.push(court_id);
    }
    if (lawyer_id) {
      conditions.push('j.lawyer_id = ?');
      params.push(lawyer_id);
    }
    if (created_by) {
      conditions.push('jca.created_by = ?');
      params.push(created_by);
    }
    if (from_action_date) {
      conditions.push('jca.action_date >= ?');
      params.push(from_action_date);
    }
    if (to_action_date) {
      conditions.push('jca.action_date <= ?');
      params.push(to_action_date);
    }

    const whereClause = conditions.join(' AND ');
    const offset = (Math.max(1, parseInt(page)) - 1) * parseInt(pageSize);

    const countSql = `
      SELECT COUNT(*) AS total
      FROM os_judiciary_customers_actions jca
      JOIN os_judiciary j ON j.id = jca.judiciary_id
      LEFT JOIN os_customers cust ON cust.id = jca.customers_id
      WHERE ${whereClause}
    `;
    const [[{ total }]] = await pool.query(countSql, params);

    const dataSql = `
      SELECT jca.id, jca.judiciary_id, jca.customers_id, jca.judiciary_actions_id,
             jca.action_date, jca.note, jca.request_status, jca.amount,
             jca.request_target, jca.decision_text, jca.parent_id,
             ja.name   AS action_name,
             ja.action_nature,
             j.judiciary_number,
             j.year    AS case_year,
             j.contract_id,
             cust.name AS customer_name,
             c.name    AS court_name,
             l.name    AS lawyer_name,
             u.username AS created_by_name
      FROM os_judiciary_customers_actions jca
      JOIN os_judiciary j               ON j.id  = jca.judiciary_id
      LEFT JOIN os_judiciary_actions ja  ON ja.id = jca.judiciary_actions_id
      LEFT JOIN os_customers cust       ON cust.id = jca.customers_id
      LEFT JOIN os_court c              ON c.id  = j.court_id
      LEFT JOIN os_lawyers l            ON l.id  = j.lawyer_id
      LEFT JOIN os_user u               ON u.id  = jca.created_by
      WHERE ${whereClause}
      ORDER BY jca.action_date DESC, jca.id DESC
      LIMIT ? OFFSET ?
    `;

    const [rows] = await pool.query(dataSql, [...params, parseInt(pageSize), offset]);

    res.json({ success: true, data: rows, total, page: parseInt(page), pageSize: parseInt(pageSize) });
  } catch (err) {
    console.error('GET /actions error:', err);
    res.status(500).json({ success: false, error: err.message });
  }
});

router.post('/', async (req, res) => {
  try {
    const {
      judiciary_id, customers_id, judiciary_actions_id,
      action_date, note, parent_id, contract_id,
    } = req.body;

    const now = Math.floor(Date.now() / 1000);
    const createdBy = req.query.user_id || 1;

    const [result] = await pool.query(
      `INSERT INTO os_judiciary_customers_actions
       (judiciary_id, customers_id, judiciary_actions_id,
        contract_id, action_date, note, parent_id,
        created_at, updated_at, created_by, is_deleted)
       VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 0)`,
      [
        judiciary_id, customers_id, judiciary_actions_id,
        contract_id || null, action_date || null, note || null, parent_id || null,
        now, now, createdBy,
      ]
    );

    res.json({ success: true, data: { id: result.insertId } });
  } catch (err) {
    console.error('POST /actions error:', err);
    res.status(500).json({ success: false, error: err.message });
  }
});

router.put('/:id', async (req, res) => {
  try {
    const { id } = req.params;
    const {
      judiciary_id, customers_id, judiciary_actions_id,
      action_date, note, parent_id,
    } = req.body;

    const now = Math.floor(Date.now() / 1000);

    await pool.query(
      `UPDATE os_judiciary_customers_actions SET
        judiciary_id = ?, customers_id = ?, judiciary_actions_id = ?,
        action_date = ?, note = ?, parent_id = ?, updated_at = ?
       WHERE id = ? AND is_deleted = 0`,
      [
        judiciary_id, customers_id, judiciary_actions_id,
        action_date || null, note || null, parent_id || null,
        now, id,
      ]
    );

    res.json({ success: true, data: { id: parseInt(id) } });
  } catch (err) {
    console.error('PUT /actions/:id error:', err);
    res.status(500).json({ success: false, error: err.message });
  }
});

router.patch('/:id/status', async (req, res) => {
  try {
    const { id } = req.params;
    const { request_status } = req.body;
    const now = Math.floor(Date.now() / 1000);

    await pool.query(
      `UPDATE os_judiciary_customers_actions
       SET request_status = ?, updated_at = ?
       WHERE id = ? AND is_deleted = 0`,
      [request_status, now, id]
    );

    res.json({ success: true, data: { id: parseInt(id), request_status } });
  } catch (err) {
    console.error('PATCH /actions/:id/status error:', err);
    res.status(500).json({ success: false, error: err.message });
  }
});

router.delete('/:id', async (req, res) => {
  try {
    const { id } = req.params;
    const now = Math.floor(Date.now() / 1000);

    await pool.query(
      'UPDATE os_judiciary_customers_actions SET is_deleted = 1, updated_at = ? WHERE id = ?',
      [now, id]
    );

    res.json({ success: true });
  } catch (err) {
    console.error('DELETE /actions/:id error:', err);
    res.status(500).json({ success: false, error: err.message });
  }
});

module.exports = router;
