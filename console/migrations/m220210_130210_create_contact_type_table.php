<?php

use yii\db\Migration;

/**
 * Handles the creation of table `{{%contact_type}}`.
 */
class m220210_130210_create_contact_type_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->createTable('{{%contact_type}}', [
            'id' => $this->primaryKey(),
            'name'=>$this->string()->notNull(),
            'created_at'=>$this->integer(),
            'updated_at'=>$this->integer(),
            'created_by'=>$this->integer(),
            'last_updated_by'=>$this->integer(),
            'is_deleted'=>$this->integer()->defaultValue(0)
        ]);
        $this->insert('{{%contact_type}}',['name'=>'اتصال هاتفي']);
        $this->insert('{{%contact_type}}',['name'=>'واتس اب']);
        $this->insert('{{%contact_type}}',['name'=>'رسالة نصية']);
        $this->insert('{{%contact_type}}',['name'=>'فيس بوك']);
        $this->insert('{{%contact_type}}',['name'=>'اخر']);
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropTable('{{%contact_type}}');
    }
}
