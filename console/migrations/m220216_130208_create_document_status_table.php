<?php

use yii\db\Migration;

/**
 * Handles the creation of table `{{%Loan_status}}`.
 */
class m220216_130208_create_document_status_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->createTable('{{%document_status}}', [
            'id' => $this->primaryKey(),
            'name'=>$this->string()->notNull(),
            'created_at'=>$this->integer(),
            'updated_at'=>$this->integer(),
            'created_by'=>$this->integer(),
            'last_updated_by'=>$this->integer(),
            'is_deleted'=>$this->integer()->defaultValue(0)
        ]);
        $this->insert('{{%document_status}}',['name'=>'تحت التدقيق']);
        $this->insert('{{%document_status}}',['name'=>'مرفوض']);
        $this->insert('{{%document_status}}',['name'=>'مقبول']);
        $this->insert('{{%document_status}}',['name'=>'جاهز']);
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropTable('{{%document_status}}');
    }
}
