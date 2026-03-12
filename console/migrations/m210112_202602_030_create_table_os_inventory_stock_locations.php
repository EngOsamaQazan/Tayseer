<?php

use yii\db\Migration;

class m210112_202602_030_create_table_os_inventory_stock_locations extends Migration
{
    public function up()
    {
        $tableOptions = null;
        if ($this->db->driverName === 'mysql') {
            $tableOptions = 'CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE=InnoDB';
        }

        $this->createTable('{{%inventory_stock_locations}}', [
            'id' => $this->primaryKey(),
            'locations_name' => $this->string(250)->notNull(),
            'company_id' => $this->integer()->notNull(),
            'created_by' => $this->integer()->notNull(),
            'created_at' => $this->integer()->notNull(),
            'updated_at' => $this->integer()->notNull(),
            'last_update_by' => $this->integer(),
            'is_deleted' => $this->integer()->notNull()->defaultValue('0'),
        ], $tableOptions);

        $this->createIndex('last_update_by', '{{%inventory_stock_locations}}', 'last_update_by');
        $this->createIndex('company_id', '{{%inventory_stock_locations}}', 'company_id');
        $this->createIndex('created_by', '{{%inventory_stock_locations}}', 'created_by');
        $this->addForeignKey('os_inventory_stock_locations_ibfk_1', '{{%inventory_stock_locations}}', 'company_id', '{{%companies}}', 'id', 'RESTRICT', 'RESTRICT');
        $this->addForeignKey('os_inventory_stock_locations_ibfk_2', '{{%inventory_stock_locations}}', 'created_by', '{{%user}}', 'id', 'RESTRICT', 'RESTRICT');
        $this->addForeignKey('os_inventory_stock_locations_ibfk_3', '{{%inventory_stock_locations}}', 'last_update_by', '{{%user}}', 'id', 'RESTRICT', 'RESTRICT');
    }

    public function down()
    {
        $this->dropTable('{{%inventory_stock_locations}}');
    }
}
