<?php

use yii\db\Migration;

/**
 * Handles the creation of table `{{%document_holder}}`.
 */
class m210627_111548_create_document_holder_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->createTable('{{%document_holder}}', [
            'id' => $this->primaryKey(),
            'created_by' => $this->integer()->notNull(),
            'updated_by' => $this->integer()->notNull(),
            'created_at' => $this->integer(),
            'updated_at' => $this->integer(),
            'approved_by_manager' => $this->integer(),
            'approved_by_employee' => $this->integer(),
            'approved_at' => $this->date(),
            'reason' => $this->text(),
            'ready' => $this->integer()->defaultValue(0),
            'contract_id' => $this->integer()->notNull(),
            'status' => $this->string(),
            'type' => $this->string(),
            'approved' => $this->integer()->defaultValue(0),
            'image' => $this->string(),
        ]);

    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropTable('{{%document_holder}}');
    }
}
