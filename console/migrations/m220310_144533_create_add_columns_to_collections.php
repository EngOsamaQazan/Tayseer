<?php

use yii\db\Migration;

/**
 * Class m220310_144533_create_add_columns_to_collections
 */
class m220310_144533_create_add_columns_to_collections extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
$this->addColumn('{{%collection}}','custamers_id',$this->integer());
$this->addColumn('{{%collection}}','judiciary_id',$this->integer());
$this->addColumn('{{%collection}}','total_amount',$this->double());

    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        echo "m220310_144533_create_add_columns_to_collections cannot be reverted.\n";

        return false;
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m220310_144533_create_add_columns_to_collections cannot be reverted.\n";

        return false;
    }
    */
}
