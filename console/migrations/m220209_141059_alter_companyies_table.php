<?php

use yii\db\Migration;

/**
 * Class m220209_141059_alter_companyies_table
 */
class m220209_141059_alter_companyies_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
$this->alterColumn('{{%companies}}','logo',$this->text());
$this->dropColumn('{{%companies}}','bank_info');
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        echo "m220209_141059_alter_companyies_table cannot be reverted.\n";

        return false;
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m220209_141059_alter_companyies_table cannot be reverted.\n";

        return false;
    }
    */
}
