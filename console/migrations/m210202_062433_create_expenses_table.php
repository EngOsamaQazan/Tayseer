<?php

use yii\db\Migration;

/**
 * Handles the creation of table `{{%expenses}}`.
 */
class m210202_062433_create_expenses_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->createTable('{{%expenses}}', [
            'id' => $this->primaryKey(),
            'category_id' => $this->integer(),
            'created_at' => $this->integer()->notNull(),
            'created_by' => $this->integer()->notNull(),
            'updated_at' => $this->integer()->notNull(),
            'last_updated_by' => $this->integer()->notNull(),
            'is_deleted' => $this->integer()->notNull(),
            'description' => $this->text()->notNull(),
            'amount' => $this->float()->notNull(),
            'receiver_number' => $this->integer()->notNull(),
            'financial_transaction_id'=>$this->integer()
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropTable('{{%expenses}}');
    }
}
