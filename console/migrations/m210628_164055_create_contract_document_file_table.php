<?php

use yii\db\Migration;

/**
 * Handles the creation of table `{{%contract_document_file}}`.
 */
class m210628_164055_create_contract_document_file_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->createTable('{{%contract_document_file}}', [
            'id' => $this->primaryKey(),
            'document_type'=>$this->string(),
            'contract_id'=>$this->integer()
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropTable('{{%contract_document_file}}');
    }
}
