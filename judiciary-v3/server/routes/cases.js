const router = require('express').Router();
const pool = require('../db');

router.get('/', async (req, res) => {
  try {
    const {
      judiciary_number, contract_id, party_name,
      court_id, type_id, lawyer_id, year,
      from_income_date, to_income_date,
      last_party_action, status, pending_requests,
      furthest_stage,
      page = 1, pageSize = 25,
    } = req.query;

    const conditions = ['j.is_deleted = 0'];
    const params = [];

    if (judiciary_number) {
      conditions.push('j.judiciary_number LIKE ?');
      params.push(`%${judiciary_number}%`);
    }
    if (contract_id) {
      conditions.push('j.contract_id = ?');
      params.push(contract_id);
    }
    if (court_id) {
      conditions.push('j.court_id = ?');
      params.push(court_id);
    }
    if (type_id) {
      conditions.push('j.type_id = ?');
      params.push(type_id);
    }
    if (lawyer_id) {
      conditions.push('j.lawyer_id = ?');
      params.push(lawyer_id);
    }
    if (year) {
      conditions.push('j.year = ?');
      params.push(year);
    }
    if (from_income_date) {
      conditions.push('j.income_date >= ?');
      params.push(from_income_date);
    }
    if (to_income_date) {
      conditions.push('j.income_date <= ?');
      params.push(to_income_date);
    }
    if (status) {
      conditions.push('j.case_status = ?');
      params.push(status);
    }

    if (party_name) {
      const nameParts = party_name.trim().split(/\s+/);
      const nameConditions = nameParts.map(() => 'cust.name LIKE ?');
      conditions.push(
        `j.contract_id IN (SELECT cc2.contract_id FROM os_contracts_customers cc2
         JOIN os_customers cust ON cust.id = cc2.customer_id
         WHERE ${nameConditions.join(' AND ')})`
      );
      nameParts.forEach((part) => params.push(`%${part}%`));
    }

    if (last_party_action) {
      conditions.push(
        `j.id IN (
          SELECT jca.judiciary_id FROM os_judiciary_customers_actions jca
          WHERE jca.judiciary_actions_id = ? AND jca.is_deleted = 0
        )`
      );
      params.push(last_party_action);
    }

    if (pending_requests === '1') {
      conditions.push(
        `j.id IN (
          SELECT jca.judiciary_id FROM os_judiciary_customers_actions jca
          WHERE jca.request_status = 'pending' AND jca.is_deleted = 0
        )`
      );
    }

    if (furthest_stage) {
      conditions.push('j.furthest_stage = ?');
      params.push(furthest_stage);
    }

    const whereClause = conditions.join(' AND ');
    const offset = (Math.max(1, parseInt(page)) - 1) * parseInt(pageSize);

    const countSql = `SELECT COUNT(*) AS total FROM os_judiciary j WHERE ${whereClause}`;
    const [[{ total }]] = await pool.query(countSql, params);

    const dataSql = `
      SELECT
        j.id, j.judiciary_number, j.year, j.income_date,
        j.case_cost, j.lawyer_cost, j.case_status,
        j.contract_id, j.furthest_stage, j.bottleneck_stage,
        c.name  AS court_name,
        jt.name AS type_name,
        l.name  AS lawyer_name,
        (
          SELECT GROUP_CONCAT(
            CONCAT(cust2.name, '||', IFNULL(cc2.customer_type,'client'), '||', IFNULL(cust2.id_number,''), '||', IFNULL(cust2.job_title,''))
            SEPARATOR ';;'
          )
          FROM os_contracts_customers cc2
          JOIN os_customers cust2 ON cust2.id = cc2.customer_id
          WHERE cc2.contract_id = j.contract_id
        ) AS parties_raw,
        (
          SELECT CONCAT(ja2.name, '||', IFNULL(ja2.action_nature,''), '||', IFNULL(cust3.name,''), '||', IFNULL(jca2.action_date,''))
          FROM os_judiciary_customers_actions jca2
          JOIN os_judiciary_actions ja2 ON ja2.id = jca2.judiciary_actions_id
          LEFT JOIN os_customers cust3 ON cust3.id = jca2.customers_id
          WHERE jca2.judiciary_id = j.id AND jca2.is_deleted = 0
          ORDER BY jca2.action_date DESC, jca2.id DESC
          LIMIT 1
        ) AS last_action_raw
      FROM os_judiciary j
      LEFT JOIN os_court c          ON c.id  = j.court_id
      LEFT JOIN os_judiciary_type jt ON jt.id = j.type_id
      LEFT JOIN os_lawyers l        ON l.id  = j.lawyer_id
      WHERE ${whereClause}
      ORDER BY j.id DESC
      LIMIT ? OFFSET ?
    `;

    const [rows] = await pool.query(dataSql, [...params, parseInt(pageSize), offset]);

    const data = rows.map(row => {
      let parties = [];
      if (row.parties_raw) {
        parties = row.parties_raw.split(';;').map(p => {
          const [name, type, id_number, job_title] = p.split('||');
          return { name, type, id_number, job_title };
        });
      }

      let last_action = null;
      if (row.last_action_raw) {
        const [action_name, action_nature, customer_name, action_date] = row.last_action_raw.split('||');
        last_action = { action_name, action_nature, customer_name, action_date };
      }

      return { ...row, parties, last_action, parties_raw: undefined, last_action_raw: undefined };
    });

    res.json({ success: true, data, total, page: parseInt(page), pageSize: parseInt(pageSize) });
  } catch (err) {
    console.error('GET /cases error:', err);
    res.status(500).json({ success: false, error: err.message });
  }
});

router.get('/:id', async (req, res) => {
  try {
    const { id } = req.params;

    const [[caseRow]] = await pool.query(
      `SELECT j.*,
              c.name  AS court_name,
              jt.name AS type_name,
              l.name  AS lawyer_name,
              l.phone_number AS lawyer_phone,
              u.username AS created_by_name
       FROM os_judiciary j
       LEFT JOIN os_court c          ON c.id  = j.court_id
       LEFT JOIN os_judiciary_type jt ON jt.id = j.type_id
       LEFT JOIN os_lawyers l        ON l.id  = j.lawyer_id
       LEFT JOIN os_user u           ON u.id  = j.created_by
       WHERE j.id = ? AND j.is_deleted = 0`,
      [id]
    );

    if (!caseRow) {
      return res.status(404).json({ success: false, error: 'Case not found' });
    }

    const [parties] = await pool.query(
      `SELECT cc.customer_id, cc.customer_type,
              cust.name AS customer_name, cust.primary_phone_number AS phone,
              cust.id_number, cust.job_title
       FROM os_contracts_customers cc
       JOIN os_customers cust ON cust.id = cc.customer_id
       WHERE cc.contract_id = ?`,
      [caseRow.contract_id]
    );

    const [stages] = await pool.query(
      `SELECT ds.*, cust.name AS customer_name
       FROM os_judiciary_defendant_stage ds
       LEFT JOIN os_customers cust ON cust.id = ds.customer_id
       WHERE ds.judiciary_id = ?`,
      [id]
    );

    const [deadlines] = await pool.query(
      `SELECT * FROM os_judiciary_deadlines
       WHERE judiciary_id = ? AND is_deleted = 0
       ORDER BY deadline_date ASC`,
      [id]
    );

    const [assets] = await pool.query(
      `SELECT * FROM os_judiciary_seized_assets
       WHERE judiciary_id = ? AND is_deleted = 0`,
      [id]
    );

    const [correspondence] = await pool.query(
      `SELECT * FROM os_diwan_correspondence
       WHERE related_module = 'judiciary' AND related_record_id = ?
         AND is_deleted = 0
       ORDER BY id DESC`,
      [id]
    );

    const [actions] = await pool.query(
      `SELECT jca.*, ja.name AS action_name, ja.action_nature,
              cust.name AS customer_name,
              u.username AS created_by_name
       FROM os_judiciary_customers_actions jca
       LEFT JOIN os_judiciary_actions ja ON ja.id = jca.judiciary_actions_id
       LEFT JOIN os_customers cust      ON cust.id = jca.customers_id
       LEFT JOIN os_user u              ON u.id = jca.created_by
       WHERE jca.judiciary_id = ? AND jca.is_deleted = 0
       ORDER BY jca.action_date DESC, jca.id DESC`,
      [id]
    );

    res.json({
      success: true,
      data: {
        ...caseRow,
        parties,
        stages,
        deadlines,
        assets,
        correspondence,
        actions,
      },
    });
  } catch (err) {
    console.error('GET /cases/:id error:', err);
    res.status(500).json({ success: false, error: err.message });
  }
});

router.post('/', async (req, res) => {
  try {
    const {
      court_id, type_id, case_cost, lawyer_cost, lawyer_id,
      company_id, contract_id, income_date, judiciary_number,
      year, judiciary_inform_address_id, case_status,
    } = req.body;

    const now = Math.floor(Date.now() / 1000);
    const createdBy = req.query.user_id || 1;

    const [result] = await pool.query(
      `INSERT INTO os_judiciary
       (court_id, type_id, case_cost, lawyer_cost, lawyer_id,
        company_id, contract_id, income_date, judiciary_number,
        year, judiciary_inform_address_id, case_status,
        furthest_stage, bottleneck_stage,
        created_at, updated_at, created_by, is_deleted)
       VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'case_preparation', 'case_preparation', ?, ?, ?, 0)`,
      [
        court_id, type_id, case_cost || 0, lawyer_cost || 0, lawyer_id,
        company_id || null, contract_id || null, income_date || null,
        judiciary_number || null, year || null, judiciary_inform_address_id || null,
        case_status || 'تجهيز', now, now, createdBy,
      ]
    );

    res.json({ success: true, data: { id: result.insertId } });
  } catch (err) {
    console.error('POST /cases error:', err);
    res.status(500).json({ success: false, error: err.message });
  }
});

router.put('/:id', async (req, res) => {
  try {
    const { id } = req.params;
    const {
      court_id, type_id, case_cost, lawyer_cost, lawyer_id,
      company_id, contract_id, income_date, judiciary_number,
      year, judiciary_inform_address_id, case_status,
    } = req.body;

    const now = Math.floor(Date.now() / 1000);
    const updatedBy = req.query.user_id || 1;

    await pool.query(
      `UPDATE os_judiciary SET
        court_id = ?, type_id = ?, case_cost = ?, lawyer_cost = ?,
        lawyer_id = ?, company_id = ?, contract_id = ?,
        income_date = ?, judiciary_number = ?, year = ?,
        judiciary_inform_address_id = ?, case_status = ?,
        updated_at = ?, last_update_by = ?
       WHERE id = ? AND is_deleted = 0`,
      [
        court_id, type_id, case_cost || 0, lawyer_cost || 0,
        lawyer_id, company_id || null, contract_id || null,
        income_date || null, judiciary_number || null, year || null,
        judiciary_inform_address_id || null, case_status || 'تجهيز',
        now, updatedBy, id,
      ]
    );

    res.json({ success: true, data: { id: parseInt(id) } });
  } catch (err) {
    console.error('PUT /cases/:id error:', err);
    res.status(500).json({ success: false, error: err.message });
  }
});

router.delete('/:id', async (req, res) => {
  try {
    const { id } = req.params;
    const now = Math.floor(Date.now() / 1000);

    await pool.query(
      'UPDATE os_judiciary SET is_deleted = 1, updated_at = ? WHERE id = ?',
      [now, id]
    );

    res.json({ success: true });
  } catch (err) {
    console.error('DELETE /cases/:id error:', err);
    res.status(500).json({ success: false, error: err.message });
  }
});

module.exports = router;
