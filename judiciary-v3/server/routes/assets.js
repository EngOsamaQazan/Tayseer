const router = require('express').Router();
const pool = require('../db');

router.get('/case/:judiciaryId', async (req, res) => {
  try {
    const { judiciaryId } = req.params;

    const [rows] = await pool.query(
      `SELECT * FROM os_judiciary_seized_assets
       WHERE judiciary_id = ? AND is_deleted = 0
       ORDER BY id DESC`,
      [judiciaryId]
    );

    res.json({ success: true, data: rows });
  } catch (err) {
    console.error('GET /assets/case error:', err);
    res.status(500).json({ success: false, error: err.message });
  }
});

router.post('/', async (req, res) => {
  try {
    const { judiciary_id, asset_type, description, value, note } = req.body;
    const now = Math.floor(Date.now() / 1000);
    const createdBy = req.query.user_id || 1;

    const [result] = await pool.query(
      `INSERT INTO os_judiciary_seized_assets
       (judiciary_id, asset_type, description, value, note,
        created_at, updated_at, created_by, is_deleted)
       VALUES (?, ?, ?, ?, ?, ?, ?, ?, 0)`,
      [judiciary_id, asset_type || null, description || null, value || 0, note || null, now, now, createdBy]
    );

    res.json({ success: true, data: { id: result.insertId } });
  } catch (err) {
    console.error('POST /assets error:', err);
    res.status(500).json({ success: false, error: err.message });
  }
});

router.put('/:id', async (req, res) => {
  try {
    const { id } = req.params;
    const { asset_type, description, value, note } = req.body;
    const now = Math.floor(Date.now() / 1000);

    await pool.query(
      `UPDATE os_judiciary_seized_assets SET
        asset_type = ?, description = ?, value = ?, note = ?, updated_at = ?
       WHERE id = ? AND is_deleted = 0`,
      [asset_type || null, description || null, value || 0, note || null, now, id]
    );

    res.json({ success: true, data: { id: parseInt(id) } });
  } catch (err) {
    console.error('PUT /assets/:id error:', err);
    res.status(500).json({ success: false, error: err.message });
  }
});

router.delete('/:id', async (req, res) => {
  try {
    const { id } = req.params;
    const now = Math.floor(Date.now() / 1000);

    await pool.query(
      'UPDATE os_judiciary_seized_assets SET is_deleted = 1, updated_at = ? WHERE id = ?',
      [now, id]
    );

    res.json({ success: true });
  } catch (err) {
    console.error('DELETE /assets/:id error:', err);
    res.status(500).json({ success: false, error: err.message });
  }
});

module.exports = router;
