<?php

use yii\db\Migration;

/**
 * Class m210224_061333_add_default_value_to_is_deleted_in_court_table
 */
class m210224_061333_add_default_value_to_is_deleted_in_court_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->alterColumn('{{%court}}', 'is_deleted', $this->integer(1)->defaultValue(0));
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        echo "m210224_061333_add_default_value_to_is_deleted_in_court_table cannot be reverted.\n";

        return false;
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m210224_061333_add_default_value_to_is_deleted_in_court_table cannot be reverted.\n";

        return false;
    }
    */
}
