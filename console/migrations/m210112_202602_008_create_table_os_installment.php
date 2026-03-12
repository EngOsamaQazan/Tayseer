<?php

use yii\db\Migration;

class m210112_202602_008_create_table_os_installment extends Migration
{
    public function up()
    {
        $tableOptions = null;
        if ($this->db->driverName === 'mysql') {
            $tableOptions = 'CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE=InnoDB';
        }

        $this->createTable('{{%Income}}', [
            'id' => $this->primaryKey(),
            'contract_id' => $this->integer()->notNull(),
            'date' => $this->date()->notNull(),
            'amount' => $this->double()->notNull(),
            'created_by' => $this->integer(),
            'payment_type' => $this->string()->notNull(),
            '_by' => $this->string(),
            'receipt_bank' => $this->string(),
            'payment_purpose' => $this->string(),
        ], $tableOptions);

    }

    public function down()
    {
        $this->dropTable('{{%Income}}');
    }
}
