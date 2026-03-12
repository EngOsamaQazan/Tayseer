<?php

use yii\db\Migration;

/**
 * Handles the creation of table `{{%add_company_id_to_expenses}}`.
 */
class m210124_084649_create_add_company_id_to_expenses_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->addColumn('{{%expenses}}', 'company_id', $this->integer());
        $this->createIndex('index_company_expenses', '{{%expenses}}', 'company_id');
        $this->addForeignKey('fk_company_expenses', '{{%expenses}}', 'company_id', '{{%companies}}', 'id');
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropForeignKey('fk_company_expenses', '{{%expenses}}');
        $this->dropIndex('index_company_expenses', '{{%expenses}}');
        $this->dropColumn('{{%expenses}}', 'company_id');
    }
}
