<?php

use yii\db\Migration;

/**
 * Handles the creation of table `{{%cousins}}`.
 */
class m220209_213524_create_cousins_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->createTable('{{%cousins}}', [
            'id' => $this->primaryKey(),
            'name'=>$this->string()->notNull(),
            'created_at'=>$this->integer(),
            'updated_at'=>$this->integer(),
            'created_by'=>$this->integer(),
            'last_updated_by'=>$this->integer(),
            'is_deleted'=>$this->integer()->defaultValue(0)
        ]);

        $this->insert('{{%cousins}}',['name'=>'اخت']);
        $this->insert('{{%cousins}}',['name'=>'اخ']);
        $this->insert('{{%cousins}}',['name'=>'اب']);
        $this->insert('{{%cousins}}',['name'=>'ام']);
        $this->insert('{{%cousins}}',['name'=>'صديق']);
        $this->insert('{{%cousins}}',['name'=>'خال ']);
        $this->insert('{{%cousins}}',['name'=>'عم']);
        $this->insert('{{%cousins}}',['name'=>' زوج']);
        $this->insert('{{%cousins}}',['name'=>' زوجة ']);
        $this->insert('{{%cousins}}',['name'=>' غير ذلك   ']);
        $this->insert('{{%cousins}}',['name'=>' ابنة ']);
        $this->insert('{{%cousins}}',['name'=>'   ابن']);
        $this->insert('{{%cousins}}',['name'=>'  جدة ']);
        $this->insert('{{%cousins}}',['name'=>' جد  ']);
        $this->insert('{{%cousins}}',['name'=>' حفيدة  ']);
        $this->insert('{{%cousins}}',['name'=>' حفيد ']);
        $this->insert('{{%cousins}}',['name'=>' ابنة اخت  ']);
        $this->insert('{{%cousins}}',['name'=>' ابنة اخ  ']);
        $this->insert('{{%cousins}}',['name'=>' ابنة عمة  ']);
        $this->insert('{{%cousins}}',['name'=>'   ابنة خال']);
        $this->insert('{{%cousins}}',['name'=>'  ابنة خالة ']);
        $this->insert('{{%cousins}}',['name'=>'  ابنة عم ']);
        $this->insert('{{%cousins}}',['name'=>' ابن خالة  ']);
        $this->insert('{{%cousins}}',['name'=>'  ابن عمة ']);
        $this->insert('{{%cousins}}',['name'=>' ابن خال  ']);
        $this->insert('{{%cousins}}',['name'=>' ابن عم  ']);
        $this->insert('{{%cousins}}',['name'=>'   ابن اخت']);
        $this->insert('{{%cousins}}',['name'=>'  ابن اخ ']);
        $this->insert('{{%cousins}}',['name'=>'  نسيب ']);
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropTable('{{%cousins}}');
    }
}
