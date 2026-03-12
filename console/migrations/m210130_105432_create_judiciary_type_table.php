<?php

use yii\db\Migration;

/**
 * Handles the creation of table `{{%judiciary_type}}`.
 */
class m210130_105432_create_judiciary_type_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->createTable('{{%judiciary_type}}', [
            'id' => $this->primaryKey(),
            'name'=>$this->string(255)->notNull(),
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropTable('{{%judiciary_type}}');
    }
}
