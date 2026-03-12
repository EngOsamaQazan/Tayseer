<?php

use yii\db\Migration;

/**
 * Adds discount_amount to os_inventory_invoices for manager discount (plan §4).
 */
class m260216_140000_add_discount_amount_to_inventory_invoices extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $table = '{{%inventory_invoices}}';
        $schema = $this->db->getTableSchema($table, true);
        if ($schema && !$schema->getColumn('discount_amount')) {
            $this->addColumn($table, 'discount_amount', $this->decimal(14, 2)->defaultValue(0)->notNull());
        }
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $table = '{{%inventory_invoices}}';
        $schema = $this->db->getTableSchema($table, true);
        if ($schema && $schema->getColumn('discount_amount')) {
            $this->dropColumn($table, 'discount_amount');
        }
    }
}
