<?php

use yii\db\Migration;

/**
 * Handles the creation of table `{{%notification}}`.
 */
class m210518_082240_create_notification_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->createTable('{{%notification}}', [
            'id' => $this->primaryKey(),
            'sender_id'=>$this->integer(),
            'recipient_id'=>$this->integer(),
            'type_of_notification'=>$this->integer(),
            'title_html'=>$this->string(),
            'body_html'=>$this->text(),
            'href'=>$this->string(),
            'is_unread'=>$this->string(),
            'is_hidden'=>$this->string(),
            'created_time'=>$this->integer()
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropTable('{{%notification}}');
    }
}
