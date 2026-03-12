<?php

use yii\db\Migration;

class m210112_202602_009_create_table_os_inventory_suppliers extends Migration
{
    public function up()
    {
        $tableOptions = null;
        if ($this->db->driverName === 'mysql') {
            $tableOptions = 'CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE=InnoDB';
        }

        $this->createTable('{{%inventory_suppliers}}', [
            'id' => $this->primaryKey(),
            'company_id' => $this->integer()->notNull(),
            'name' => $this->string(250)->notNull(),
            'adress' => $this->string(250)->notNull(),
            'phone_number' => $this->string(50)->notNull(),
            'created_by' => $this->integer()->notNull(),
            'created_at' => $this->integer()->notNull(),
            'updated_at' => $this->integer()->notNull(),
            'last_update_by' => $this->integer(),
            'is_deleted' => $this->integer()->notNull()->defaultValue('0'),
        ], $tableOptions);

        $this->createIndex('name', '{{%inventory_suppliers}}', 'name', true);
        $this->createIndex('company_id', '{{%inventory_suppliers}}', 'company_id');
        $this->createIndex('phone_number', '{{%inventory_suppliers}}', 'phone_number', true);
        $this->addForeignKey('os_inventory_suppliers_ibfk_1', '{{%inventory_suppliers}}', 'company_id', '{{%companies}}', 'id', 'RESTRICT', 'RESTRICT');
    }

    public function down()
    {
        $this->dropTable('{{%inventory_suppliers}}');
    }
}
