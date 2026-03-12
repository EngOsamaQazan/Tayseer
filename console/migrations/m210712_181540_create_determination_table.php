<?php

use yii\db\Migration;

/**
 * Handles the creation of table `{{%determination}}`.
 */
class m210712_181540_create_determination_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->createTable('{{%determination}}', [
            'id' => $this->primaryKey(),
            'contract_id' => $this->integer(),
            'amount' => $this->double(),
            'date' => $this->date(),
            'created_at'=>$this->integer(),
            'updated_at'=>$this->integer(),
            'created_by'=>$this->integer(),
            'last_updated_by'=>$this->integer(),
            'is_deleted'=>$this->integer()
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropTable('{{%determination}}');
    }
}
