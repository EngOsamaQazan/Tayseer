const router = require('express').Router();
const pool = require('../db');

router.get('/case/:judiciaryId', async (req, res) => {
  try {
    const { judiciaryId } = req.params;

    const [actions] = await pool.query(
      `SELECT
        jca.id,
        'action' AS type,
        jca.action_date AS date,
        ja.name AS title,
        ja.action_nature,
        jca.note AS description,
        jca.request_status,
        cust.name AS related_party,
        u.username AS created_by_name,
        jca.created_at
       FROM os_judiciary_customers_actions jca
       LEFT JOIN os_judiciary_actions ja ON ja.id = jca.judiciary_actions_id
       LEFT JOIN os_customers cust      ON cust.id = jca.customers_id
       LEFT JOIN os_user u              ON u.id = jca.created_by
       WHERE jca.judiciary_id = ? AND jca.is_deleted = 0`,
      [judiciaryId]
    );

    const [correspondence] = await pool.query(
      `SELECT
        dc.id,
        'correspondence' AS type,
        dc.correspondence_date AS date,
        dc.purpose AS title,
        dc.direction,
        dc.notification_method,
        dc.notification_result,
        dc.reference_number,
        dc.recipient_type,
        dc.content_summary AS description,
        dc.status,
        dc.created_at
       FROM os_diwan_correspondence dc
       WHERE dc.related_module = 'judiciary' AND dc.related_record_id = ?
         AND dc.is_deleted = 0`,
      [judiciaryId]
    );

    const [deadlines] = await pool.query(
      `SELECT
        d.id,
        'deadline' AS type,
        d.deadline_date AS date,
        d.label AS title,
        d.deadline_type,
        d.status,
        d.notes AS description,
        d.start_date,
        d.created_at
       FROM os_judiciary_deadlines d
       WHERE d.judiciary_id = ? AND d.is_deleted = 0`,
      [judiciaryId]
    );

    const timeline = [...actions, ...correspondence, ...deadlines];

    timeline.sort((a, b) => {
      const dateA = a.date ? new Date(a.date) : new Date(0);
      const dateB = b.date ? new Date(b.date) : new Date(0);
      return dateB - dateA;
    });

    res.json({ success: true, data: timeline, total: timeline.length });
  } catch (err) {
    console.error('GET /timeline/case error:', err);
    res.status(500).json({ success: false, error: err.message });
  }
});

module.exports = router;
