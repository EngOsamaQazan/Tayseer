<?php

use yii\db\Migration;

/**
 * Handles the creation of table `{{%contract_id_in_judiciary}}`.
 */
class m210131_060652_create_contract_id_in_judiciary_table extends Migration {

    /**
     * {@inheritdoc}
     */
    public function safeUp() {
        $this->addColumn('{{%judiciary}}', 'contract_id', $this->integer()->notNull());
        $this->createIndex('index_contract_id', '{{%judiciary}}', 'contract_id');
        $this->addForeignKey('fk_contract_id', '{{%judiciary}}', 'contract_id', '{{%contracts}}', 'id');
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown() {
        
    }

}
