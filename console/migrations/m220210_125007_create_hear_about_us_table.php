<?php

use yii\db\Migration;

/**
 * Handles the creation of table `{{%hear_about_us}}`.
 */
class m220210_125007_create_hear_about_us_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->createTable('{{%hear_about_us}}', [
            'id' => $this->primaryKey(),
            'name'=>$this->string()->notNull(),
            'created_at'=>$this->integer(),
            'updated_at'=>$this->integer(),
            'created_by'=>$this->integer(),
            'last_updated_by'=>$this->integer(),
            'is_deleted'=>$this->integer()->defaultValue(0)
        ]);
        $this->insert('{{%hear_about_us}}',['name'=>'اعلان ممول على فيس بك']);
        $this->insert('{{%hear_about_us}}',['name'=>'انستغرام']);
        $this->insert('{{%hear_about_us}}',['name'=>'فيس بك']);
        $this->insert('{{%hear_about_us}}',['name'=>'ارمات']);
        $this->insert('{{%hear_about_us}}',['name'=> 'رسائل ترويج sms']);
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropTable('{{%hear_about_us}}');
    }
}
