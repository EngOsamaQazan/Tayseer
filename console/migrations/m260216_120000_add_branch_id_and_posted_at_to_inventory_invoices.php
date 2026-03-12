<?php

use yii\db\Migration;

/**
 * Adds branch_id (FK to os_location) and posted_at to os_inventory_invoices.
 */
class m260216_120000_add_branch_id_and_posted_at_to_inventory_invoices extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $table = '{{%inventory_invoices}}';
        $schema = $this->db->getTableSchema($table, true);
        if ($schema && !$schema->getColumn('branch_id')) {
            $this->addColumn($table, 'branch_id', $this->integer()->null());
            $this->addForeignKey(
                'fk_inventory_invoices_branch_id',
                $table,
                'branch_id',
                '{{%inventory_stock_locations}}',
                'id',
                'SET NULL',
                'RESTRICT'
            );
        }
        if ($schema && !$schema->getColumn('posted_at')) {
            $this->addColumn($table, 'posted_at', $this->dateTime()->null());
        }
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $table = '{{%inventory_invoices}}';
        $schema = $this->db->getTableSchema($table, true);
        if ($schema && $schema->getColumn('branch_id')) {
            $this->dropForeignKey('fk_inventory_invoices_branch_id', $table);
            $this->dropColumn($table, 'branch_id');
        }
        if ($schema && $schema->getColumn('posted_at')) {
            $this->dropColumn($table, 'posted_at');
        }
    }
}
