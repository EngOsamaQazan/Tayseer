const router = require('express').Router();
const pool = require('../db');

router.get('/case/:judiciaryId', async (req, res) => {
  try {
    const { judiciaryId } = req.params;

    const [rows] = await pool.query(
      `SELECT * FROM os_diwan_correspondence
       WHERE related_module = 'judiciary' AND related_record_id = ?
       ORDER BY id DESC`,
      [judiciaryId]
    );

    res.json({ success: true, data: rows });
  } catch (err) {
    console.error('GET /correspondence/case error:', err);
    res.status(500).json({ success: false, error: err.message });
  }
});

module.exports = router;
