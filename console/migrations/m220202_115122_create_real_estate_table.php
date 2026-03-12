<?php

use yii\db\Migration;

/**
 * Handles the creation of table `{{%real_estate}}`.
 */
class m220202_115122_create_real_estate_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->createTable('{{%real_estate}}', [
            'id' => $this->primaryKey(),
            'customer_id'=>$this->integer()->notNull(),
            'property_type'=>$this->string()->notNull(),
            'property_number'=>$this->string()->notNull(),
            'created_at'=>$this->integer(),
            'updated_at'=>$this->integer(),
            'created_by'=>$this->integer(),
            'updated_by'=>$this->integer(),
            'is_deleted'=>$this->integer()->defaultValue(0),
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropTable('{{%real_estate}}');
    }
}
