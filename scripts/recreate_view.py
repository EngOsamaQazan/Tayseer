import sys, io
sys.stdout = io.TextIOWrapper(sys.stdout.buffer, encoding='utf-8', errors='replace')
import paramiko

ssh = paramiko.SSHClient()
ssh.set_missing_host_key_policy(paramiko.AutoAddPolicy())
ssh.connect('54.38.236.112', username='root', password='Hussain@1986', timeout=60, banner_timeout=60, auth_timeout=60)

def run(cmd):
    stdin, stdout, stderr = ssh.exec_command(cmd, timeout=30)
    return stdout.read().decode('utf-8', errors='replace'), stderr.read().decode('utf-8', errors='replace')

site = 'jadal'
root = f'/var/www/{site}.aqssat.co'

# Verify the deployed file has the fix
print("=== Checking expenses filter in deployed file ===")
out, err = run(f"grep -A1 'FROM os_expenses' {root}/backend/modules/followUpReport/controllers/FollowUpReportController.php")
print(out)

# Navigate to the follow-up report page to trigger VIEW recreation
print("\n=== Triggering VIEW recreation via curl ===")
out, err = run(f"curl -sLk -o /dev/null -w '%{{http_code}}' --cookie-jar /tmp/cookies.txt https://{site}.aqssat.co/site/login 2>&1")
print(f"Login page: {out}")

# We need to actually trigger the VIEW recreation. Let's do it via PHP CLI
print("\n=== Recreating VIEW via PHP CLI ===")
php_code = r"""<?php
opcache_reset();
defined('YII_DEBUG') or define('YII_DEBUG', false);
defined('YII_ENV') or define('YII_ENV', 'prod');
require __DIR__ . '/../../vendor/autoload.php';
require __DIR__ . '/../../vendor/yiisoft/yii2/Yii.php';
require __DIR__ . '/../../common/config/bootstrap.php';
$config = yii\helpers\ArrayHelper::merge(
    require __DIR__ . '/../../common/config/main.php',
    require __DIR__ . '/../../common/config/main-local.php',
    require __DIR__ . '/../../backend/config/main.php',
    require __DIR__ . '/../../backend/config/main-local.php'
);
new yii\web\Application($config);

$db = Yii::$app->db;

$sql = "CREATE OR REPLACE VIEW os_follow_up_report AS
SELECT
    c.*,
    f.date_time      AS last_follow_up,
    f.promise_to_pay_at,
    f.reminder,
    IFNULL(payments.total_paid, 0) AS total_paid,
    COALESCE(ls.monthly_installment, c.monthly_installment_value) AS effective_installment,
    GREATEST(0,
        PERIOD_DIFF(DATE_FORMAT(CURDATE(),'%Y%m'),
            DATE_FORMAT(COALESCE(ls.first_installment_date, c.first_installment_date),'%Y%m'))
        + CASE WHEN DAY(CURDATE()) >= DAY(COALESCE(ls.first_installment_date, c.first_installment_date))
               THEN 1 ELSE 0 END
    ) AS due_installments,
    CASE
        WHEN jud.jud_id IS NOT NULL AND ls.id IS NULL THEN
            GREATEST(0,
                c.total_value
                + IFNULL(exp_sum.total_expenses, 0)
                + IFNULL(jud.total_lawyer, 0)
                - IFNULL(adj.total_adjustments, 0)
                - IFNULL(payments.total_paid, 0)
            )
        ELSE
            GREATEST(0,
                (GREATEST(0,
                    PERIOD_DIFF(DATE_FORMAT(CURDATE(),'%Y%m'),
                        DATE_FORMAT(COALESCE(ls.first_installment_date, c.first_installment_date),'%Y%m'))
                    + CASE WHEN DAY(CURDATE()) >= DAY(COALESCE(ls.first_installment_date, c.first_installment_date))
                           THEN 1 ELSE 0 END
                ) * COALESCE(ls.monthly_installment, c.monthly_installment_value))
                - IFNULL(payments.total_paid, 0)
            )
    END AS due_amount,
    CASE WHEN f.id IS NULL THEN 1 ELSE 0 END AS never_followed
FROM os_contracts c
LEFT JOIN os_follow_up f ON f.contract_id = c.id
    AND f.id = (SELECT MAX(id) FROM os_follow_up WHERE contract_id = c.id)
LEFT JOIN os_loan_scheduling ls ON ls.contract_id = c.id
    AND ls.is_deleted = 0
    AND ls.id = (SELECT MAX(id) FROM os_loan_scheduling WHERE contract_id = c.id AND is_deleted = 0)
LEFT JOIN (
    SELECT contract_id, SUM(amount) AS total_paid
    FROM os_income GROUP BY contract_id
) payments ON c.id = payments.contract_id
LEFT JOIN (
    SELECT contract_id, MAX(id) AS jud_id, SUM(lawyer_cost) AS total_lawyer
    FROM os_judiciary WHERE is_deleted = 0
    GROUP BY contract_id
) jud ON jud.contract_id = c.id
LEFT JOIN (
    SELECT contract_id, SUM(amount) AS total_expenses
    FROM os_expenses
    WHERE (is_deleted = 0 OR is_deleted IS NULL)
    GROUP BY contract_id
) exp_sum ON exp_sum.contract_id = c.id
LEFT JOIN (
    SELECT contract_id, SUM(amount) AS total_adjustments
    FROM os_contract_adjustments WHERE is_deleted = 0
    GROUP BY contract_id
) adj ON adj.contract_id = c.id
WHERE
    c.status NOT IN ('finished','canceled')
    AND NOT (
        c.status = 'judiciary'
        AND (c.total_value + IFNULL(exp_sum.total_expenses, 0) + IFNULL(jud.total_lawyer, 0)
             - IFNULL(adj.total_adjustments, 0) - IFNULL(payments.total_paid, 0)) <= 0.01
    )
    AND (
        (c.is_can_not_contact = 0 AND (
            (jud.jud_id IS NOT NULL AND ls.id IS NULL AND
                (c.total_value + IFNULL(exp_sum.total_expenses, 0) + IFNULL(jud.total_lawyer, 0)
                 - IFNULL(adj.total_adjustments, 0) - IFNULL(payments.total_paid, 0)) > 5
            )
            OR
            ((jud.jud_id IS NULL OR ls.id IS NOT NULL) AND
                ((GREATEST(0,
                    PERIOD_DIFF(DATE_FORMAT(CURDATE(),'%Y%m'),
                        DATE_FORMAT(COALESCE(ls.first_installment_date, c.first_installment_date),'%Y%m'))
                    + CASE WHEN DAY(CURDATE()) >= DAY(COALESCE(ls.first_installment_date, c.first_installment_date))
                           THEN 1 ELSE 0 END
                ) * COALESCE(ls.monthly_installment, c.monthly_installment_value))
                - IFNULL(payments.total_paid, 0)) > 5
            )
        ))
        OR
        c.is_can_not_contact = 1
    )
ORDER BY
    CASE WHEN f.id IS NULL THEN 0 ELSE 1 END ASC,
    f.date_time ASC";

$db->createCommand($sql)->execute();
echo "VIEW recreated OK\n";

// Check if contract 3238 is excluded
$check = $db->createCommand("SELECT id FROM os_follow_up_report WHERE id = 3238")->queryScalar();
if ($check) {
    echo "WARNING: Contract 3238 still in report! (id=$check)\n";
    // Debug
    $val = $db->createCommand("SELECT 
        c.total_value,
        IFNULL((SELECT SUM(amount) FROM os_expenses WHERE contract_id=3238 AND (is_deleted=0 OR is_deleted IS NULL)),0) as exp,
        IFNULL((SELECT SUM(lawyer_cost) FROM os_judiciary WHERE contract_id=3238 AND is_deleted=0),0) as lawyer,
        IFNULL((SELECT SUM(amount) FROM os_contract_adjustments WHERE contract_id=3238 AND is_deleted=0),0) as adj,
        IFNULL((SELECT SUM(amount) FROM os_income WHERE contract_id=3238),0) as paid
    FROM os_contracts c WHERE c.id=3238")->queryOne();
    print_r($val);
    $total = $val['total_value'] + $val['exp'] + $val['lawyer'] - $val['adj'] - $val['paid'];
    echo "Calc: $total\n";
} else {
    echo "SUCCESS: Contract 3238 excluded from report!\n";
}
""";

sftp = ssh.open_sftp()
with sftp.file(f'{root}/backend/web/_recreate_view.php', 'w') as fh:
    fh.write(php_code)
sftp.close()

out, err = run(f'cd {root} && php backend/web/_recreate_view.php 2>&1')
print(out)
if err:
    print(f"ERR: {err}")

run(f'rm -f {root}/backend/web/_recreate_view.php')
ssh.close()
print("\nDone!")
