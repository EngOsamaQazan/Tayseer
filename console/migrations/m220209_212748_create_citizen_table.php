<?php

use yii\db\Migration;

/**
 * Handles the creation of table `{{%citizen}}`.
 */
class m220209_212748_create_citizen_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->createTable('{{%citizen}}', [
            'id' => $this->primaryKey(),
            'name'=>$this->string()->notNull(),
            'created_at'=>$this->integer(),
            'updated_at'=>$this->integer(),
            'created_by'=>$this->integer(),
            'last_updated_by'=>$this->integer(),
            'is_deleted'=>$this->integer()->defaultValue(0)
        ]);

        $this->insert('{{%citizen}}',['name'=>'اردني']);
        $this->insert('{{%citizen}}',['name'=>'مصري']);
        $this->insert('{{%citizen}}',['name'=>'سوري']);
        $this->insert('{{%citizen}}',['name'=>'ليبي']);
        $this->insert('{{%citizen}}',['name'=>'عراقي']);
        $this->insert('{{%citizen}}',['name'=>'قطاع غزة']);
        $this->insert('{{%citizen}}',['name'=>'فلسطيني']);
        $this->insert('{{%citizen}}',['name'=>'فلسطيني ٤٨']);
        $this->insert('{{%citizen}}',['name'=>' أبناء الاردنيات']);
        $this->insert('{{%citizen}}',['name'=>' اردني غير مكتمل البيانات']);
        $this->insert('{{%citizen}}',['name'=>' الكيان الصهيوني']);
        $this->insert('{{%citizen}}',['name'=>'  غير ذلك']);

    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropTable('{{%citizen}}');
    }
}
