<?php

use yii\db\Migration;

/**
 * Handles the creation of table `{{%movment}}`.
 */
class m210116_073026_create_movment_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->createTable('{{%movment}}', [
            'id' => $this->primaryKey(),
            'user_id'=>$this->integer(),
            'movement_number'=>$this->integer()->notNull(),
            'bank_receipt_number'=>$this->integer()->notNull(),
            'financial_value'=>$this->integer()->notNull(),
            'receipt_image'=>$this->string(255)->notNull(),
        ]);
        $this->createIndex('index_user_id','{{%movment}}','user_id');
        $this->addForeignKey('os_fk_user_id','{{%movment}}','user_id','{{%user}}','id');
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropIndex('index_user_id');
        $this->dropForeignKey('os_fk_user_id');
        $this->dropTable('{{%movment}}');

    }
}
