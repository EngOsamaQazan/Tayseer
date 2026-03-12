<?php

use yii\db\Migration;

/**
 * Class m220425_220128_create_drop_case_cost_from_judicary_tabel
 */
class m220425_220128_create_drop_case_cost_from_judicary_tabel extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->dropColumn('{{%judiciary}}','case_cost');

    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {

        return false;
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m220425_220128_create_drop_case_cost_from_judicary_tabel cannot be reverted.\n";

        return false;
    }
    */
}
