const router = require('express').Router();
const pool = require('../db');

router.post('/cases', async (req, res) => {
  try {
    const {
      contract_ids, court_id, type_id, lawyer_id,
      year, judiciary_inform_address_id, company_id,
      lawyer_percentage,
    } = req.body;

    if (!Array.isArray(contract_ids) || contract_ids.length === 0) {
      return res.status(400).json({ success: false, error: 'contract_ids must be a non-empty array' });
    }

    const now = Math.floor(Date.now() / 1000);
    const createdBy = req.query.user_id || 1;
    const created = [];
    const errors = [];

    for (const contractId of contract_ids) {
      try {
        let lawyerCost = 0;
        if (lawyer_percentage) {
          const [[contract]] = await pool.query(
            'SELECT total_value FROM os_contracts WHERE id = ?',
            [contractId]
          );
          if (contract) {
            lawyerCost = (contract.total_value || 0) * (parseFloat(lawyer_percentage) / 100);
          }
        }

        const [result] = await pool.query(
          `INSERT INTO os_judiciary
           (court_id, type_id, lawyer_id, lawyer_cost, contract_id, year,
            judiciary_inform_address_id, company_id, case_status,
            furthest_stage, bottleneck_stage,
            created_at, updated_at, created_by, is_deleted)
           VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'تجهيز', 'case_preparation', 'case_preparation', ?, ?, ?, 0)`,
          [
            court_id || null, type_id || null, lawyer_id || null, lawyerCost,
            contractId, year || null, judiciary_inform_address_id || null,
            company_id || null, now, now, createdBy,
          ]
        );
        created.push({ contract_id: contractId, judiciary_id: result.insertId });
      } catch (err) {
        errors.push({ contract_id: contractId, error: err.message });
      }
    }

    res.json({
      success: true,
      data: { created, errors },
      total_created: created.length,
      total_errors: errors.length,
    });
  } catch (err) {
    console.error('POST /batch/cases error:', err);
    res.status(500).json({ success: false, error: err.message });
  }
});

router.post('/actions', async (req, res) => {
  try {
    const { actions } = req.body;

    if (!Array.isArray(actions) || actions.length === 0) {
      return res.status(400).json({ success: false, error: 'actions must be a non-empty array' });
    }

    const now = Math.floor(Date.now() / 1000);
    const createdBy = req.query.user_id || 1;
    const created = [];
    const errors = [];

    for (const action of actions) {
      const { judiciary_id, customers_id, judiciary_actions_id, action_date, note } = action;

      try {
        const [[caseRow]] = await pool.query(
          'SELECT id, contract_id FROM os_judiciary WHERE id = ? AND is_deleted = 0',
          [judiciary_id]
        );

        if (!caseRow) {
          errors.push({ judiciary_id, error: `القضية ${judiciary_id} غير موجودة` });
          continue;
        }

        let parentId = null;
        const [[actionDef]] = await pool.query(
          'SELECT id, parent_request_ids FROM os_judiciary_actions WHERE id = ?',
          [judiciary_actions_id]
        );

        if (actionDef && actionDef.parent_request_ids) {
          const parentIds = actionDef.parent_request_ids.split(',').map(s => s.trim()).filter(Boolean);
          if (parentIds.length > 0) {
            const [[prereq]] = await pool.query(
              `SELECT id FROM os_judiciary_customers_actions
               WHERE judiciary_id = ? AND customers_id = ?
                 AND judiciary_actions_id IN (${parentIds.map(() => '?').join(',')})
                 AND is_deleted = 0
               ORDER BY id DESC LIMIT 1`,
              [judiciary_id, customers_id, ...parentIds]
            );
            if (prereq) {
              parentId = prereq.id;
            }
          }
        }

        const [result] = await pool.query(
          `INSERT INTO os_judiciary_customers_actions
           (judiciary_id, customers_id, judiciary_actions_id,
            contract_id, action_date, note, parent_id,
            created_at, updated_at, created_by, is_deleted)
           VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 0)`,
          [
            judiciary_id, customers_id, judiciary_actions_id,
            caseRow.contract_id, action_date || null, note || null,
            parentId, now, now, createdBy,
          ]
        );

        created.push({ id: result.insertId, judiciary_id, customers_id });
      } catch (err) {
        errors.push({ judiciary_id, error: err.message });
      }
    }

    res.json({
      success: true,
      data: { created, errors },
      total_created: created.length,
      total_errors: errors.length,
    });
  } catch (err) {
    console.error('POST /batch/actions error:', err);
    res.status(500).json({ success: false, error: err.message });
  }
});

router.post('/parse-case-numbers', async (req, res) => {
  try {
    const { text } = req.body;

    if (!text) {
      return res.status(400).json({ success: false, error: 'text is required' });
    }

    const lines = text.split(/[\n,;]+/).map(s => s.trim()).filter(Boolean);
    const results = [];

    for (const line of lines) {
      const match = line.match(/(\d+)\s*[\/\-\\]\s*(\d{2,4})/);

      if (match) {
        const number = match[1];
        const yearRaw = match[2];
        const yearFull = yearRaw.length === 2 ? '20' + yearRaw : yearRaw;

        const [rows] = await pool.query(
          `SELECT j.id, j.judiciary_number, j.year, j.contract_id,
                  c.name AS court_name
           FROM os_judiciary j
           LEFT JOIN os_court c ON c.id = j.court_id
           WHERE j.judiciary_number = ? AND j.year = ? AND j.is_deleted = 0`,
          [number, yearFull]
        );

        if (rows.length > 0) {
          for (const r of rows) {
            const [parties] = await pool.query(
              `SELECT cc.customer_id, cc.customer_type, cust.name
               FROM os_contracts_customers cc
               JOIN os_customers cust ON cust.id = cc.customer_id
               WHERE cc.contract_id = ?`,
              [r.contract_id]
            );
            results.push({
              input: line, found: true,
              judiciary_id: r.id, judiciary_number: r.judiciary_number,
              year: r.year, court_name: r.court_name,
              contract_id: r.contract_id, parties,
            });
          }
        } else {
          results.push({ input: line, found: false, number, year: yearFull });
        }
      } else {
        const plainNum = line.replace(/\D/g, '');
        if (plainNum) {
          const [rows] = await pool.query(
            `SELECT j.id, j.judiciary_number, j.year, j.contract_id,
                    c.name AS court_name
             FROM os_judiciary j
             LEFT JOIN os_court c ON c.id = j.court_id
             WHERE j.judiciary_number = ? AND j.is_deleted = 0`,
            [plainNum]
          );

          if (rows.length > 0) {
            for (const r of rows) {
              const [parties] = await pool.query(
                `SELECT cc.customer_id, cc.customer_type, cust.name
                 FROM os_contracts_customers cc
                 JOIN os_customers cust ON cust.id = cc.customer_id
                 WHERE cc.contract_id = ?`,
                [r.contract_id]
              );
              results.push({
                input: line, found: true,
                judiciary_id: r.id, judiciary_number: r.judiciary_number,
                year: r.year, court_name: r.court_name,
                contract_id: r.contract_id, parties,
              });
            }
          } else {
            results.push({ input: line, found: false, number: plainNum });
          }
        } else {
          results.push({ input: line, found: false });
        }
      }
    }

    res.json({ success: true, data: results });
  } catch (err) {
    console.error('POST /batch/parse-case-numbers error:', err);
    res.status(500).json({ success: false, error: err.message });
  }
});

module.exports = router;
