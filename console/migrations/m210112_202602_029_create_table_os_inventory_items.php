<?php

use yii\db\Migration;

class m210112_202602_029_create_table_os_inventory_items extends Migration
{
    public function up()
    {
        $tableOptions = null;
        if ($this->db->driverName === 'mysql') {
            $tableOptions = 'CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE=InnoDB';
        }

        $this->createTable('{{%inventory_items}}', [
            'id' => $this->integer()->notNull(),
            'item_name' => $this->string(50)->notNull(),
            'item_barcode' => $this->string(30)->notNull(),
            'created_at' => $this->integer()->notNull(),
            'updated_at' => $this->integer()->notNull(),
            'created_by' => $this->integer()->notNull(),
            'last_update_by' => $this->integer(),
            'is_deleted' => $this->integer()->notNull()->defaultValue('0'),
        ], $tableOptions);

        $this->createIndex('item_barcode', '{{%inventory_items}}', 'item_barcode', true);
        $this->createIndex('last_update_by', '{{%inventory_items}}', 'last_update_by');
        $this->createIndex('id', '{{%inventory_items}}', 'id');
        $this->createIndex('created_by', '{{%inventory_items}}', 'created_by');
        $this->addForeignKey('os_inventory_items_ibfk_1', '{{%inventory_items}}', 'created_by', '{{%user}}', 'id', 'RESTRICT', 'RESTRICT');
        $this->addForeignKey('os_inventory_items_ibfk_2', '{{%inventory_items}}', 'last_update_by', '{{%user}}', 'id', 'RESTRICT', 'RESTRICT');
    }

    public function down()
    {
        $this->dropTable('{{%inventory_items}}');
    }
}
