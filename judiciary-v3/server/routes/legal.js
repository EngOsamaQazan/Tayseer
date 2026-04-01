const router = require('express').Router();
const pool = require('../db');

router.get('/', async (req, res) => {
  try {
    const { page = 1, pageSize = 25, search } = req.query;

    const conditions = ["con.status = 'judiciary'", 'con.is_deleted = 0'];
    const params = [];

    if (search) {
      conditions.push('(CAST(con.id AS CHAR) LIKE ? OR cust.name LIKE ?)');
      const term = `%${search}%`;
      params.push(term, term);
    }

    const whereClause = conditions.join(' AND ');
    const offset = (Math.max(1, parseInt(page)) - 1) * parseInt(pageSize);

    const countSql = `
      SELECT COUNT(DISTINCT con.id) AS total
      FROM os_contracts con
      LEFT JOIN os_contracts_customers cc ON cc.contract_id = con.id
      LEFT JOIN os_customers cust ON cust.id = cc.customer_id
      WHERE ${whereClause}
    `;
    const [[{ total }]] = await pool.query(countSql, params);

    const dataSql = `
      SELECT
        con.id, con.total_value, con.Date_of_sale,
        con.status, con.monthly_installment_value,
        con.first_installment_value,
        GROUP_CONCAT(DISTINCT cust.name SEPARATOR ', ') AS customer_names
      FROM os_contracts con
      LEFT JOIN os_contracts_customers cc ON cc.contract_id = con.id
      LEFT JOIN os_customers cust ON cust.id = cc.customer_id
      WHERE ${whereClause}
      GROUP BY con.id
      ORDER BY con.id DESC
      LIMIT ? OFFSET ?
    `;

    const [rows] = await pool.query(dataSql, [...params, parseInt(pageSize), offset]);

    res.json({ success: true, data: rows, total, page: parseInt(page), pageSize: parseInt(pageSize) });
  } catch (err) {
    console.error('GET /legal error:', err);
    res.status(500).json({ success: false, error: err.message });
  }
});

module.exports = router;
