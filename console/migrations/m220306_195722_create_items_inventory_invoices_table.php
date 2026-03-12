<?php

use yii\db\Migration;

/**
 * Handles the creation of table `{{%items_inventory_invoices}}`.
 */
class m220306_195722_create_items_inventory_invoices_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->createTable('{{%items_inventory_invoices}}', [
            'id' => $this->primaryKey(),
            'number'=>$this->integer(),
            'single_price'=>$this->double(),
            'created_at'=>$this->integer(),
            'updated_at'=>$this->integer(),
            'created_by'=>$this->integer(),
            'inventory_items_id'=>$this->integer(),
            'total_amount'=>$this->integer(),
            'inventory_invoices_id'=>$this->integer(),
            'last_updated_by'=>$this->integer(),
            'is_deleted'=>$this->integer()->defaultValue(0),
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropTable('{{%items_inventory_invoices}}');
    }
}
