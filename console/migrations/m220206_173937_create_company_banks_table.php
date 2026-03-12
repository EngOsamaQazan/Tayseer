<?php

use yii\db\Migration;

/**
 * Handles the creation of table `{{%company_banks}}`.
 */
class m220206_173937_create_company_banks_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->createTable('{{%company_banks}}', [
            'id' => $this->primaryKey(),
            'company_id'=>$this->integer(),
            'bank_name'=>$this->string()->notNull(),
            'bank_number'=>$this->string()->notNull(),
            'created_at'=>$this->integer(),
            'updated_at'=>$this->integer(),
            'created_by'=>$this->integer(),
            'last_updated_by'=>$this->integer(),
            'is_deleted'=>$this->integer()->defaultValue(0)
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropTable('{{%company_banks}}');
    }
}
