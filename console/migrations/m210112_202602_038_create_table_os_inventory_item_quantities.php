<?php

use yii\db\Migration;

class m210112_202602_038_create_table_os_inventory_item_quantities extends Migration
{
    public function up()
    {
        $tableOptions = null;
        if ($this->db->driverName === 'mysql') {
            $tableOptions = 'CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE=InnoDB';
        }

        $this->createTable('{{%inventory_item_quantities}}', [
            'id' => $this->primaryKey(),
            'item_id' => $this->integer()->notNull(),
            'locations_id' => $this->integer()->notNull(),
            'suppliers_id' => $this->integer()->notNull(),
            'quantity' => $this->integer()->notNull(),
            'created_at' => $this->integer()->notNull(),
            'created_by' => $this->integer()->notNull(),
        ], $tableOptions);

        $this->createIndex('locations_id', '{{%inventory_item_quantities}}', 'locations_id');
        $this->createIndex('suppliers_id', '{{%inventory_item_quantities}}', 'suppliers_id');
        $this->createIndex('created_by', '{{%inventory_item_quantities}}', 'created_by');
        $this->createIndex('item_id', '{{%inventory_item_quantities}}', 'item_id');
        $this->addForeignKey('os_inventory_item_quantities_ibfk_2', '{{%inventory_item_quantities}}', 'locations_id', '{{%inventory_stock_locations}}', 'id', 'RESTRICT', 'RESTRICT');
        $this->addForeignKey('os_inventory_item_quantities_ibfk_3', '{{%inventory_item_quantities}}', 'suppliers_id', '{{%inventory_suppliers}}', 'id', 'RESTRICT', 'RESTRICT');
        $this->addForeignKey('os_inventory_item_quantities_ibfk_4', '{{%inventory_item_quantities}}', 'created_by', '{{%user}}', 'id', 'RESTRICT', 'RESTRICT');
        $this->addForeignKey('os_inventory_item_quantities_ibfk_5', '{{%inventory_item_quantities}}', 'item_id', '{{%inventory_items}}', 'id', 'RESTRICT', 'RESTRICT');
    }

    public function down()
    {
        $this->dropTable('{{%inventory_item_quantities}}');
    }
}
