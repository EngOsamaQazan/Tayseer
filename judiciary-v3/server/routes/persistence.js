const router = require('express').Router();
const pool = require('../db');

router.get('/', async (req, res) => {
  try {
    const { search, color, page = 1, pageSize = 50 } = req.query;

    const conditions = [];
    const params = [];

    if (search) {
      conditions.push('(p.customer_name LIKE ? OR p.contract_id LIKE ? OR p.judiciary_number LIKE ? OR p.court_name LIKE ?)');
      const term = `%${search}%`;
      params.push(term, term, term, term);
    }
    if (color) {
      conditions.push('p.persistence_status = ?');
      params.push(color);
    }

    const whereClause = conditions.length > 0 ? 'WHERE ' + conditions.join(' AND ') : '';
    const offset = (Math.max(1, parseInt(page)) - 1) * parseInt(pageSize);

    const [[{ total }]] = await pool.query(
      `SELECT COUNT(*) AS total FROM tbl_persistence_cache p ${whereClause}`,
      params
    );

    const [rows] = await pool.query(
      `SELECT p.* FROM tbl_persistence_cache p ${whereClause}
       ORDER BY p.id DESC LIMIT ? OFFSET ?`,
      [...params, parseInt(pageSize), offset]
    );

    res.json({ success: true, data: rows, total, page: parseInt(page), pageSize: parseInt(pageSize) });
  } catch (err) {
    console.error('GET /persistence error:', err);
    res.status(500).json({ success: false, error: err.message });
  }
});

router.post('/refresh', async (req, res) => {
  try {
    await pool.query('CALL sp_refresh_persistence_cache()');
    res.json({ success: true, message: 'Persistence cache refreshed' });
  } catch (err) {
    console.error('POST /persistence/refresh error:', err);
    res.status(500).json({ success: false, error: err.message });
  }
});

module.exports = router;
