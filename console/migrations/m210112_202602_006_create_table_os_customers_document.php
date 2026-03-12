<?php

use yii\db\Migration;

class m210112_202602_006_create_table_os_customers_document extends Migration
{
    public function up()
    {
        $tableOptions = null;
        if ($this->db->driverName === 'mysql') {
            $tableOptions = 'CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE=InnoDB';
        }

        $this->createTable('{{%customers_document}}', [
            'id' => $this->primaryKey(),
            'customer_id' => $this->integer()->notNull(),
            'document_type' => $this->string(100)->notNull(),
            'document_image' => $this->string()->notNull(),
            'document_number' => $this->string(100)->notNull(),
            'created_at' => $this->integer()->notNull(),
            'updated_at' => $this->integer()->notNull(),
            'created_by' => $this->integer()->notNull(),
        ], $tableOptions);

        $this->addForeignKey('customer_id_relation', '{{%customers_document}}', 'customer_id', '{{%customers}}', 'id', 'RESTRICT', 'RESTRICT');
    }

    public function down()
    {
        $this->dropTable('{{%customers_document}}');
    }
}
