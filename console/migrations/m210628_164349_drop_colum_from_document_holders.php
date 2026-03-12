<?php

use yii\db\Migration;

/**
 * Class m210628_164349_drop_colum_from_document_holders
 */
class m210628_164349_drop_colum_from_document_holders extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
$this->dropColumn('{{%document_holder}}','image');
$this->dropColumn('{{%document_holder}}','ready');

    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {

    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m210628_164349_drop_colum_from_document_holders cannot be reverted.\n";

        return false;
    }
    */
}
