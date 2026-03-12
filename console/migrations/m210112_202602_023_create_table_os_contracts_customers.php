<?php

use yii\db\Migration;

class m210112_202602_023_create_table_os_contracts_customers extends Migration
{
    public function up()
    {
        $tableOptions = null;
        if ($this->db->driverName === 'mysql') {
            $tableOptions = 'CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE=InnoDB';
        }

        $this->createTable('{{%contracts_customers}}', [
            'id' => $this->primaryKey(),
            'contract_id' => $this->integer()->notNull(),
            'customer_id' => $this->integer()->notNull(),
            'customer_type' => $this->string()->notNull(),
        ], $tableOptions);

        $this->addForeignKey('contract', '{{%contracts_customers}}', 'contract_id', '{{%contracts}}', 'id', 'RESTRICT', 'RESTRICT');
        $this->addForeignKey('coustmer', '{{%contracts_customers}}', 'customer_id', '{{%customers}}', 'id', 'RESTRICT', 'RESTRICT');
    }

    public function down()
    {
        $this->dropTable('{{%contracts_customers}}');
    }
}
