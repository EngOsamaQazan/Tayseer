const router = require('express').Router();
const pool = require('../db');

router.get('/', async (req, res) => {
  try {
    const { page = 1, pageSize = 25, search } = req.query;

    const conditions = ['col.is_deleted = 0'];
    const params = [];

    if (search) {
      conditions.push('(CAST(col.contract_id AS CHAR) LIKE ? OR col.notes LIKE ?)');
      const term = `%${search}%`;
      params.push(term, term);
    }

    const whereClause = conditions.join(' AND ');
    const offset = (Math.max(1, parseInt(page)) - 1) * parseInt(pageSize);

    const [[{ total }]] = await pool.query(
      `SELECT COUNT(*) AS total FROM os_collection col WHERE ${whereClause}`,
      params
    );

    const [rows] = await pool.query(
      `SELECT col.*,
              u.username AS employee_name
       FROM os_collection col
       LEFT JOIN os_user u ON u.id = col.created_by
       WHERE ${whereClause}
       ORDER BY col.id DESC
       LIMIT ? OFFSET ?`,
      [...params, parseInt(pageSize), offset]
    );

    res.json({ success: true, data: rows, total, page: parseInt(page), pageSize: parseInt(pageSize) });
  } catch (err) {
    console.error('GET /collection error:', err);
    res.status(500).json({ success: false, error: err.message });
  }
});

router.post('/', async (req, res) => {
  try {
    const { contract_id, date, amount, total_amount, notes, judiciary_id } = req.body;

    const now = Math.floor(Date.now() / 1000);
    const createdBy = req.query.user_id || 1;

    const [result] = await pool.query(
      `INSERT INTO os_collection
       (contract_id, date, amount, total_amount, notes, judiciary_id,
        created_at, updated_at, created_by, is_deleted)
       VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 0)`,
      [
        contract_id || null, date || null, amount || 0,
        total_amount || 0, notes || null, judiciary_id || null,
        now, now, createdBy,
      ]
    );

    res.json({ success: true, data: { id: result.insertId } });
  } catch (err) {
    console.error('POST /collection error:', err);
    res.status(500).json({ success: false, error: err.message });
  }
});

router.put('/:id', async (req, res) => {
  try {
    const { id } = req.params;
    const { contract_id, date, amount, total_amount, notes } = req.body;

    const now = Math.floor(Date.now() / 1000);

    await pool.query(
      `UPDATE os_collection SET
        contract_id = ?, date = ?, amount = ?,
        total_amount = ?, notes = ?, updated_at = ?
       WHERE id = ? AND is_deleted = 0`,
      [
        contract_id || null, date || null, amount || 0,
        total_amount || 0, notes || null, now, id,
      ]
    );

    res.json({ success: true, data: { id: parseInt(id) } });
  } catch (err) {
    console.error('PUT /collection/:id error:', err);
    res.status(500).json({ success: false, error: err.message });
  }
});

router.delete('/:id', async (req, res) => {
  try {
    const { id } = req.params;
    const now = Math.floor(Date.now() / 1000);

    await pool.query(
      'UPDATE os_collection SET is_deleted = 1, updated_at = ? WHERE id = ?',
      [now, id]
    );

    res.json({ success: true });
  } catch (err) {
    console.error('DELETE /collection/:id error:', err);
    res.status(500).json({ success: false, error: err.message });
  }
});

module.exports = router;
