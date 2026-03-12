<?php

use yii\db\Migration;

/**
 * Handles the creation of table `{{%status}}`.
 */
class m220209_193702_create_status_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->createTable('{{%status}}', [
            'id' => $this->primaryKey(),
            'name'=>$this->string()->notNull(),
            'created_at'=>$this->integer(),
            'updated_at'=>$this->integer(),
            'created_by'=>$this->integer(),
            'last_updated_by'=>$this->integer(),
            'is_deleted'=>$this->integer()->defaultValue(0)
        ]);
        $this->insert('{{%status}}',['name'=>'تحت التدقيق']);
        $this->insert('{{%status}}',['name'=>'مرفوض']);
        $this->insert('{{%status}}',['name'=>'مقبول']);

    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropTable('{{%status}}');
    }
}
