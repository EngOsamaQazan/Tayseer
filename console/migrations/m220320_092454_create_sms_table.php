<?php

use yii\db\Migration;

/**
 * Handles the creation of table `{{%sms}}`.
 */
class m220320_092454_create_sms_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->createTable('{{%sms}}', [
            'id' => $this->primaryKey(),
            'date'=>$this->date(),
            'is_send'=>$this->boolean()
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropTable('{{%sms}}');
    }
}
