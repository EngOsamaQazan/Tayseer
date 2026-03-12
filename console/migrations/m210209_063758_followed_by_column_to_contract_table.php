<?php

use yii\db\Migration;

/**
 * Class m210209_063758_followed_by_column_to_contract_table
 */
class m210209_063758_followed_by_column_to_contract_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->addColumn('{{%contracts}}','followed_by',$this->integer()->Null()->defaultValue(1));
        $this->createIndex('index_followed_by', '{{%contracts}}', 'followed_by');
        $this->addForeignKey('fk_followed_by_user', '{{%contracts}}', 'followed_by', '{{%user}}', 'id');
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
       $this->dropColumn('{{%contracts}}','followed_by');
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m210209_063758_followed_by_column_to_contract_table cannot be reverted.\n";

        return false;
    }
    */
}
