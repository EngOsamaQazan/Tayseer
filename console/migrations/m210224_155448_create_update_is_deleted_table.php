<?php

use yii\db\Migration;

/**
 * Handles the creation of table `{{%update_is_deleted}}`.
 */
class m210224_155448_create_update_is_deleted_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->alterColumn('{{%expenses}}', 'is_deleted', $this->integer(1)->defaultValue(0));
        $this->alterColumn('{{%expense_categories}}', 'is_deleted', $this->integer(1)->defaultValue(0));
        $this->alterColumn('{{%financial_transaction}}', 'is_deleted', $this->integer(1)->defaultValue(0));
        $this->alterColumn('{{%judiciary_customers_actions}}', 'is_deleted', $this->integer(1)->defaultValue(0));
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropTable('{{%update_is_deleted}}');
    }
}
