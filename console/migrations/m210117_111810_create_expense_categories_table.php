<?php

use yii\db\Migration;

/**
 * Handles the creation of table `{{%expense_categories}}`.
 */
class m210117_111810_create_expense_categories_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $tableOptions = null;
        if ($this->db->driverName === 'mysql') {
            $tableOptions = 'CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE=InnoDB';
        }
        $this->createTable('{{%expense_categories}}', [
            'id' => $this->primaryKey(),
            'name' => $this->string(250)->notNull(),
            'created_at' => $this->integer()->notNull(),
            'created_by' => $this->integer()->notNull(),
            'updated_at' => $this->integer()->notNull(),
            'last_updated_by' => $this->integer()->notNull(),
            'is_deleted' => $this->integer()->notNull(),
            'description' => $this->text()->notNull(),
        ], $tableOptions);

        $this->createIndex('Index_created_by', '{{%expense_categories}}', 'created_by');
        $this->createIndex('Index_last_updated_by', '{{%expense_categories}}', 'last_updated_by');

        $this->addForeignKey('fk_created_by', '{{%expense_categories}}', 'created_by', '{{%user}}', 'id');
        $this->addForeignKey('fk_last_updated_by', '{{%expense_categories}}', 'last_updated_by', '{{%user}}', 'id');

    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropIndex('Index_created_by');
        $this->dropIndex('Index_last_updated_by');
        $this->addForeignKey('fk_created_by');
        $this->dropForeignKey('fk_last_updated_by');
        $this->dropTable('{{%expense_categories}}');

    }
}
