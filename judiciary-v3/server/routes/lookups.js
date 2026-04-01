const router = require('express').Router();
const pool = require('../db');

router.get('/courts', async (req, res) => {
  try {
    const [rows] = await pool.query(
      'SELECT id, name FROM os_court WHERE is_deleted = 0 ORDER BY name'
    );
    res.json({ success: true, data: rows });
  } catch (err) {
    res.status(500).json({ success: false, error: err.message });
  }
});

router.get('/lawyers', async (req, res) => {
  try {
    const [rows] = await pool.query(
      'SELECT id, name, phone_number, representative_type FROM os_lawyers WHERE is_deleted = 0 ORDER BY name'
    );
    res.json({ success: true, data: rows });
  } catch (err) {
    res.status(500).json({ success: false, error: err.message });
  }
});

router.get('/case-types', async (req, res) => {
  try {
    const [rows] = await pool.query(
      'SELECT id, name FROM os_judiciary_type ORDER BY name'
    );
    res.json({ success: true, data: rows });
  } catch (err) {
    res.status(500).json({ success: false, error: err.message });
  }
});

router.get('/actions-catalog', async (req, res) => {
  try {
    const [rows] = await pool.query(
      'SELECT id, name, action_type, action_nature, parent_request_ids FROM os_judiciary_actions WHERE is_deleted = 0 ORDER BY action_type, name'
    );
    res.json({ success: true, data: rows });
  } catch (err) {
    res.status(500).json({ success: false, error: err.message });
  }
});

router.get('/inform-addresses', async (req, res) => {
  try {
    const [rows] = await pool.query(
      'SELECT id, address AS name FROM os_judiciary_inform_address WHERE is_deleted = 0 ORDER BY address'
    );
    res.json({ success: true, data: rows });
  } catch (err) {
    res.status(500).json({ success: false, error: err.message });
  }
});

router.get('/authorities', async (req, res) => {
  try {
    const [rows] = await pool.query(
      'SELECT id, name, authority_type FROM os_judiciary_authorities WHERE is_deleted = 0 ORDER BY name'
    );
    res.json({ success: true, data: rows });
  } catch (err) {
    res.status(500).json({ success: false, error: err.message });
  }
});

router.get('/users', async (req, res) => {
  try {
    const [rows] = await pool.query(
      'SELECT id, username, name FROM os_user ORDER BY username'
    );
    res.json({ success: true, data: rows });
  } catch (err) {
    res.status(500).json({ success: false, error: err.message });
  }
});

module.exports = router;
