<?php

use yii\db\Migration;

/**
 * Handles the creation of table `{{%contract_inventory_item}}`.
 */
class m210113_193903_create_contract_inventory_item_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->dropTable('{{%contract_inventory_item}}');
        $tableOptions = null;
        if ($this->db->driverName === 'mysql') {
            $tableOptions = 'CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE=InnoDB';
        }

        $this->createTable('{{%contract_inventory_item}}', [
            'id' => $this->primaryKey(),
            'contract_id' => $this->integer()->notNull(),
            'item_id' => $this->integer()->notNull(),
            'code' => $this->string(250),
            'notes' => $this->string(500),
        ],$tableOptions);
        $this->createIndex('contract_id', '{{%contract_inventory_item}}', 'contract_id');
        $this->createIndex('item_id', '{{%contract_inventory_item}}', 'item_id');
        $this->addForeignKey('os_contract_inventory_item_ibfk_1', '{{%contract_inventory_item}}', 'contract_id', '{{%contracts}}', 'id');
        $this->addForeignKey('os_contract_inventory_item_ibfk_2', '{{%contract_inventory_item}}', 'item_id', '{{%inventory_items}}', 'id');

    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
    }
}
