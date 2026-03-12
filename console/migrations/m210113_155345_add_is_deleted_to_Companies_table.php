<?php

use yii\db\Migration;

/**
 * Class m210113_155345_add_is_deleted_to_Companies_table
 */
class m210113_155345_add_is_deleted_to_Companies_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->addColumn('{{%companies}}', 'is_deleted', $this->integer()->notNull()->defaultValue(0));
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
          $this->dropColumn('{{%os_companies}}', 'is_deleted');
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m210113_155345_add_is_deleted_to_Companies_table cannot be reverted.\n";

        return false;
    }
    */
}
