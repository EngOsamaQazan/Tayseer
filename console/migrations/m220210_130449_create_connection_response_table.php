<?php

use yii\db\Migration;

/**
 * Handles the creation of table `{{%connection_response}}`.
 */
class m220210_130449_create_connection_response_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->createTable('{{%connection_response}}', [
            'id' => $this->primaryKey(),
            'name'=>$this->string()->notNull(),
            'created_at'=>$this->integer(),
            'updated_at'=>$this->integer(),
            'created_by'=>$this->integer(),
            'last_updated_by'=>$this->integer(),
            'is_deleted'=>$this->integer()->defaultValue(0)
        ]);
        $this->insert('{{%connection_response}}',['name'=>'وعد بالدفع']);
        $this->insert('{{%connection_response}}',['name'=>'وعد بإبلاغه']);
        $this->insert('{{%connection_response}}',['name'=>'مغلق']);
        $this->insert('{{%connection_response}}',['name'=>'مفصول']);
        $this->insert('{{%connection_response}}',['name'=>'الرقم غير صحيح']);
        $this->insert('{{%connection_response}}',['name'=>'طلب عدم الاتصال به مرة اخرى']);
        $this->insert('{{%connection_response}}',['name'=>'لم يرد']);
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropTable('{{%connection_response}}');
    }
}
