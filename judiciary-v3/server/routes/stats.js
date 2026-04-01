const router = require('express').Router();
const pool = require('../db');

router.get('/', async (req, res) => {
  try {
    const results = await Promise.allSettled([
      pool.query('SELECT COUNT(*) AS cnt FROM os_judiciary WHERE is_deleted = 0'),
      pool.query('SELECT COUNT(*) AS cnt FROM os_judiciary_customers_actions WHERE is_deleted = 0'),
      pool.query(`
        SELECT
          COUNT(*) AS total,
          SUM(CASE WHEN persistence_status = 'red' THEN 1 ELSE 0 END) AS red,
          SUM(CASE WHEN persistence_status = 'orange' THEN 1 ELSE 0 END) AS orange,
          SUM(CASE WHEN persistence_status = 'green' THEN 1 ELSE 0 END) AS green
        FROM tbl_persistence_cache
      `),
      pool.query("SELECT COUNT(*) AS cnt FROM os_contracts WHERE status = 'judiciary' AND is_deleted = 0"),
      pool.query(`
        SELECT
          COUNT(*) AS cnt,
          COALESCE(SUM(COALESCE(total_amount, 0) - COALESCE(amount, 0)), 0) AS available
        FROM os_collection WHERE is_deleted = 0
      `),
      pool.query(`
        SELECT COUNT(*) AS cnt
        FROM os_judiciary_customers_actions
        WHERE request_status = 'pending' AND is_deleted = 0
      `),
    ]);

    const getValue = (result, path) => {
      if (result.status === 'fulfilled') {
        const rows = result.value[0];
        return rows[0] || {};
      }
      return {};
    };

    const totalCases = getValue(results[0]);
    const totalActions = getValue(results[1]);
    const persistence = getValue(results[2]);
    const legalContracts = getValue(results[3]);
    const collection = getValue(results[4]);
    const pendingRequests = getValue(results[5]);

    res.json({
      success: true,
      data: {
        total_cases: totalCases.cnt || 0,
        total_actions: totalActions.cnt || 0,
        persistence: {
          total: persistence.total || 0,
          red: persistence.red || 0,
          orange: persistence.orange || 0,
          green: persistence.green || 0,
        },
        legal_contracts: legalContracts.cnt || 0,
        collection: {
          count: collection.cnt || 0,
          available_amount: collection.available || 0,
        },
        pending_requests: pendingRequests.cnt || 0,
      },
    });
  } catch (err) {
    console.error('GET /stats error:', err);
    res.status(500).json({ success: false, error: err.message });
  }
});

module.exports = router;
