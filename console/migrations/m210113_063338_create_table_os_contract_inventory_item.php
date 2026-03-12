<?php

use yii\db\Migration;

class m210113_063338_create_table_os_contract_inventory_item extends Migration
{
    public function up()
    {
        $tableOptions = null;
        if ($this->db->driverName === 'mysql') {
            $tableOptions = 'CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE=InnoDB';
        }

        $this->createTable('{{%contract_inventory_item}}', [
            'id' => $this->integer()->notNull(),
            'contract_id' => $this->integer()->notNull(),
            'item_id' => $this->integer()->notNull(),
            'code' => $this->string(250)->notNull(),
            'notes' => $this->string(500)->notNull(),
        ], $tableOptions);

        $this->createIndex('contract_id', '{{%contract_inventory_item}}', 'contract_id');
        $this->createIndex('item_id', '{{%contract_inventory_item}}', 'item_id');
        $this->addForeignKey('os_contract_inventory_item_ibfk_1', '{{%contract_inventory_item}}', 'contract_id', '{{%contracts}}', 'id', 'RESTRICT', 'RESTRICT');
        $this->addForeignKey('os_contract_inventory_item_ibfk_2', '{{%contract_inventory_item}}', 'item_id', '{{%items}}', 'id', 'RESTRICT', 'RESTRICT');
    }

    public function down()
    {
        $this->dropTable('{{%contract_inventory_item}}');
    }
}
