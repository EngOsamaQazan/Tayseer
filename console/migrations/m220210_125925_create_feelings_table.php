<?php

use yii\db\Migration;

/**
 * Handles the creation of table `{{%feelings}}`.
 */
class m220210_125925_create_feelings_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->createTable('{{%feelings}}', [
            'id' => $this->primaryKey(),
            'name'=>$this->string()->notNull(),
            'created_at'=>$this->integer(),
            'updated_at'=>$this->integer(),
            'created_by'=>$this->integer(),
            'last_updated_by'=>$this->integer(),
            'is_deleted'=>$this->integer()->defaultValue(0)
        ]);
        $this->insert('{{%feelings}}',['name'=>'جيد']);
        $this->insert('{{%feelings}}',['name'=>'سيء']);
        $this->insert('{{%feelings}}',['name'=>'عادي']);

    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropTable('{{%feelings}}');
    }
}
