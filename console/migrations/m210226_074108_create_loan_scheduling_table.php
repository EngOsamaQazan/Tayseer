<?php

use yii\db\Migration;

/**
 * Handles the creation of table `{{%loan_scheduling}}`.
 */
class m210226_074108_create_loan_scheduling_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->createTable('{{%loan_scheduling}}', [
            'id' => $this->primaryKey(),
            'contract_id'=>$this->integer()->notNull(),
            'new_installment_date'=>$this->date()->notNull(),
            'monthly_installment'=>$this->double()->notNull(),
            'new_amount'=>$this->double()->notNull(),
            'first_installment_date'=>$this->date()->notNull(),
            'status'=>$this->integer()->notNull(),
            'status_action_by'=>$this->integer()->notNull(),
            'created_at' => $this->time()->notNull(),
            'updated_at' => $this->time()->notNull(),
            'created_by' => $this->integer()->notNull(),
            'last_update_by' => $this->integer(),
            'is_deleted'=>$this->integer(1)->notNull()->defaultValue(0)
        ]);
        $this->createIndex('index_contract','{{%loan_scheduling}}', 'contract_id');
        $this->createIndex('index_created_by','{{%loan_scheduling}}', 'created_by');
        $this->createIndex('index_status_action_by','{{%loan_scheduling}}', 'status_action_by');
        $this->createIndex('index_last_update_by','{{%loan_scheduling}}', 'last_update_by');

        $this->addForeignKey('fk_loan_scheduling_contract_id','{{%loan_scheduling}}','contract_id','{{%contracts}}','id');
        $this->addForeignKey('fk_loan_scheduling_created_by','{{%loan_scheduling}}','created_by','{{%user}}','id');
        $this->addForeignKey('fk_loan_scheduling_status_action_by','{{%loan_scheduling}}','status_action_by','{{%user}}','id');
        $this->addForeignKey('fk_loan_scheduling_last_update_by','{{%loan_scheduling}}','last_update_by','{{%user}}','id');
    }
    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropTable('{{%loan_scheduling}}');
    }
}
