<?php

use yii\db\Migration;

/**
 * Handles the creation of table `{{%inventory_invoices}}`.
 */
class m220306_135011_create_inventory_invoices_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->createTable('{{%inventory_invoices}}', [
            'id' => $this->primaryKey(),
            'inventory_items_id'=>$this->integer(),
            'company_id'=>$this->integer(),
            'total_amount'=>$this->double(),
            'number'=>$this->integer(),
            'single_price'=>$this->double(),
            'type'=>$this->integer(),
            'suppliers_id'=>$this->integer(),
            'created_at'=>$this->integer(),
            'updated_at'=>$this->integer(),
            'created_by'=>$this->integer(),
            'last_updated_by'=>$this->integer(),
            'is_deleted'=>$this->integer()->defaultValue(0),
            'date'=>$this->date(),
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropTable('{{%inventory_invoices}}');
    }
}
