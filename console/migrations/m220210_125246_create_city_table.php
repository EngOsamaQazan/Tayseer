<?php

use yii\db\Migration;

/**
 * Handles the creation of table `{{%city}}`.
 */
class m220210_125246_create_city_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->createTable('{{%city}}', [
            'id' => $this->primaryKey(),
            'name'=>$this->string()->notNull(),
            'created_at'=>$this->integer(),
            'updated_at'=>$this->integer(),
            'created_by'=>$this->integer(),
            'last_updated_by'=>$this->integer(),
            'is_deleted'=>$this->integer()->defaultValue(0)
        ]);
        $this->insert('{{%city}}',['name'=>'المفرق']);
        $this->insert('{{%city}}',['name'=>'عمان']);
        $this->insert('{{%city}}',['name'=>'عجلون']);
        $this->insert('{{%city}}',['name'=>'العقية']);
        $this->insert('{{%city}}',['name'=>'اربد']);
        $this->insert('{{%city}}',['name'=>'البلقاء']);
        $this->insert('{{%city}}',['name'=>'مادبا']);
        $this->insert('{{%city}}',['name'=>'الكرك']);
        $this->insert('{{%city}}',['name'=>'الطفيلة']);
        $this->insert('{{%city}}',['name'=> 'جرش']);
        $this->insert('{{%city}}',['name'=> 'الزرقاء']);
        $this->insert('{{%city}}',['name'=>'معان']);
        $this->insert('{{%city}}',['name'=>'الأغوار الشمالية']);
        $this->insert('{{%city}}',['name'=>'الرصيفه']);
        $this->insert('{{%city}}',['name'=>'الرمثا']);
        $this->insert('{{%city}}',['name'=>'السلط']);
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropTable('{{%city}}');
    }
}
