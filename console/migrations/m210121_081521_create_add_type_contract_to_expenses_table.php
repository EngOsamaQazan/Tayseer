<?php

use yii\db\Migration;

/**
 * Handles the creation of table `{{%add_type_contract_to_expenses}}`.
 */
class m210121_081521_create_add_type_contract_to_expenses_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->addColumn('{{%expenses}}', 'type', $this->integer());
        $this->addColumn('{{%expenses}}', 'income_type', $this->integer());
        $this->addColumn('{{%expenses}}', 'contract_id', $this->integer());

        $this->createIndex('index_contract', '{{%expenses}}', 'contract_id');
        $this->addForeignKey('fk_contract', '{{%expenses}}', 'contract_id', '{{%contracts}}', 'id');
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {

        $this->dropForeignKey('fk_contract', '{{%expenses}}');
        $this->dropIndex('index_contract', '{{%expenses}}');

        $this->dropColumn('{{%expenses}}', 'contract_id');
        $this->dropColumn('{{%expenses}}', 'type');
        $this->dropColumn('{{%expenses}}', 'income_type');

    }
}
