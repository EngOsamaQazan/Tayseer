<?php

use yii\db\Migration;

/**
 * Handles the creation of table `{{%expenses}}`.
 */
class m210117_113647_create_expenses_table extends Migration
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
            'receiver_number' => $this->integer()->notNull()
        ]);

        $this->createIndex('index_category_id', '{{%expenses}}', 'category_id');
        $this->addForeignKey('fk_category_id', '{{%expenses}}', 'category_id', '{{%expense_categories}}', 'id');

    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropIndex('index_category_id');
        $this->dropForeignKey('fk_category_id');
        $this->dropTable('{{%expenses}}');
    }
}
