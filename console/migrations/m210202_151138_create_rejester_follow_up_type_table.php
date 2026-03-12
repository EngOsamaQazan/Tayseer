<?php

use yii\db\Migration;

/**
 * Handles the creation of table `{{%rejester_follow_up_type}}`.
 */
class m210202_151138_create_rejester_follow_up_type_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->createTable('{{%rejester_follow_up_type}}', [
            'id' => $this->primaryKey(),
            'name'=>$this->string(255)->notNull()
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropTable('{{%rejester_follow_up_type}}');
    }
}
