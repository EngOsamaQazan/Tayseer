<?php

use yii\db\Migration;

/**
 * Class m220226_103507_add_last_updated_by
 */
class m220226_103507_add_last_updated_by extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
$this->addColumn('{{%companies}}','last_updated_by',$this->integer());
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        echo "m220226_103507_add_last_updated_by cannot be reverted.\n";

        return false;
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m220226_103507_add_last_updated_by cannot be reverted.\n";

        return false;
    }
    */
}
