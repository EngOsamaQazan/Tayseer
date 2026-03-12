SELECT 
c.*,
cd.date_time,
cd.promise_to_pay_at,
cd.reminder 
FROM (
SELECT * from os_contracts WHERE id NOT IN ( 
    SELECT id as id FROM (
        SELECT t1.contract_id as id, 
        null as should_payments, 
        null as total_payments 
        FROM os_follow_up t1 
        JOIN (
            #get the reminders follow ups
            SELECT MAX(id) id 
            FROM os_follow_up GROUP BY contract_id
        ) t2 ON t1.id = t2.id WHERE 
        t1.reminder > CURRENT_DATE()
        		UNION ALL
            #get must be paid 
        SELECT c.id, 
            c.monthly_installment_value * (period_diff(date_format(now(), '%Y%m'), 
            date_format(c.first_installment_date, '%Y%m'))) as should_payments, 
        	sum(os_installment.amount) as total_payments 
        FROM os_contracts c 
        JOIN os_installment on c.id = os_installment.contract_id 
        WHERE c.id GROUP BY c.id 
        HAVING total_payments >= should_payments) t
) 
    ORDER BY `os_contracts`.`id` DESC) c 
    LEFT JOIN ( SELECT MAX(id) id, contract_id 
               FROM os_follow_up GROUP BY contract_id ) c_max ON (c_max.contract_id = c.id) 
               LEFT JOIN os_follow_up cd ON (cd.id = c_max.id) 
               ORDER BY `cd`.`date_time` 
               ASC
