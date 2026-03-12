<?php

use yii\db\Migration;

/**
 * Handles the creation of table `{{%collection}}`.
 */
class m210717_092947_create_collection_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->createTable('{{%collection}}', [
            'id' => $this->primaryKey(),
            'contract_id'=>$this->integer(),
            'date'=>$this->date(),
            'amount'=>$this->double(),
            'notes'=>$this->text(),
            'created_at'=>$this->integer(),
            'updated_at'=>$this->integer(),
            'created_by'=>$this->integer(),
            'last_updated_by'=>$this->integer(),
            'is_deleted'=>$this->integer()->defaultValue(0)
        ]);
        $this->createIndex('index_created_by','{{%collection}}','created_by');
        $this->createIndex('index_contract_id','{{%collection}}','contract_id');
        $this->createIndex('index_last_updated_by','{{%collection}}','last_updated_by');

        $this->addForeignKey('fk_last_updated_by','{{%collection}}','last_updated_by','{{%user}}','id');
        $this->addForeignKey('fk_created_by','{{%collection}}','created_by','{{%user}}','id');
        $this->addForeignKey('fk_contract_id','{{%collection}}','contract_id','{{%contracts}}','id');
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropIndex('index_created_by','{{%collection}}');
        $this->dropIndex('index_contract_id','{{%collection}}');
        $this->dropIndex('index_last_updated_by','{{%collection}}');

        $this->dropForeignKey('fk_last_updated_by','{{%collection}}');
        $this->dropForeignKey('fk_created_by','{{%collection}}');
        $this->dropForeignKey('fk_contract_id','{{%collection}}');

        $this->dropTable('{{%collection}}');
    }
}
