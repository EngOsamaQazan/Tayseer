<?php

use yii\db\Migration;

/**
 * Handles the creation of table `{{%divisions_collection}}`.
 */
class m220313_123339_create_divisions_collection_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->createTable('{{%divisions_collection}}', [
            'id' => $this->primaryKey(),
            'collection_id'=>$this->integer(),
            'month'=>$this->integer(),
            'amount'=>$this->double(),
            'year'=>$this->integer(),
            'created_at'=>$this->integer(),
            'updated_at'=>$this->integer(),
            'created_by'=>$this->integer(),
            'last_updated_by'=>$this->integer(),
            'is_deleted'=>$this->integer()->defaultValue(0)
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropTable('{{%divisions_collection}}');
    }
}
