<?php

use yii\db\Migration;

/**
 * Handles the creation of table `{{%judiciary_actions}}`.
 */
class m210221_174510_create_judiciary_actions_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->createTable('{{%judiciary_actions}}', [
            'id' => $this->primaryKey(),
            'name'=>$this->string(255)->notNull()
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropTable('{{%judiciary_actions}}');
    }
}
