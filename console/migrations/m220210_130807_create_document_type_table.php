<?php

use yii\db\Migration;

/**
 * Handles the creation of table `{{%document_type}}`.
 */
class m220210_130807_create_document_type_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->createTable('{{%document_type}}', [
            'id' => $this->primaryKey(),
            'name'=>$this->string()->notNull(),
            'created_at'=>$this->integer(),
            'updated_at'=>$this->integer(),
            'created_by'=>$this->integer(),
            'last_updated_by'=>$this->integer(),
            'is_deleted'=>$this->integer()->defaultValue(0)
        ]);
        $this->insert('{{%document_type}}',['name'=>'بنتظار موافقة المدير']);
        $this->insert('{{%document_type}}',['name'=>'بنتظار تسليم الموظف']);
        $this->insert('{{%document_type}}',['name'=>' تم استلامه قبل الموظف']);
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropTable('{{%document_type}}');
    }
}
