<?php

use yii\db\Migration;

/**
 * Handles the creation of table `{{%payment_type}}`.
 */
class m220210_125702_create_payment_type_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->createTable('{{%payment_type}}', [
            'id' => $this->primaryKey(),
            'name'=>$this->string()->notNull(),
            'created_at'=>$this->integer(),
            'updated_at'=>$this->integer(),
            'created_by'=>$this->integer(),
            'last_updated_by'=>$this->integer(),
            'is_deleted'=>$this->integer()->defaultValue(0)
        ]);
        $this->insert('{{%payment_type}}',['name'=>'نقداً']);
        $this->insert('{{%payment_type}}',['name'=>'وصل بنكي']);
        $this->insert('{{%payment_type}}',['name'=>'اي فواتيركم']);
        $this->insert('{{%payment_type}}',['name'=>'شيك']);

    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropTable('{{%payment_type}}');
    }
}
