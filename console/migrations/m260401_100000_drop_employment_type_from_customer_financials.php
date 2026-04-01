<?php

use yii\db\Migration;

class m260401_100000_drop_employment_type_from_customer_financials extends Migration
{
    public function safeUp()
    {
        $table = 'os_customer_financials';

        $cols = $this->db->getTableSchema($table);
        if ($cols === null) {
            echo "    > Table {$table} does not exist, skipping.\n";
            return true;
        }

        if (isset($cols->columns['employment_type'])) {
            $this->dropColumn($table, 'employment_type');
            echo "    > Dropped column employment_type from {$table}.\n";
        } else {
            echo "    > Column employment_type already removed from {$table}, skipping.\n";
        }

        return true;
    }

    public function safeDown()
    {
        $table = 'os_customer_financials';

        $cols = $this->db->getTableSchema($table);
        if ($cols === null) {
            return true;
        }

        if (!isset($cols->columns['employment_type'])) {
            $this->addColumn($table, 'employment_type', $this->string(20)->null()->after('employer_name'));
        }

        return true;
    }
}
