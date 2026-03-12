<?php

use yii\db\Migration;

/**
 * Class m220321_141424_add_to_sms
 */
class m220321_141424_add_to_sms extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->addColumn('{{%sms}}', 'massage', $this->text());
        $this->addColumn('{{%sms}}', 'created_at', $this->integer());
        $this->addColumn('{{%sms}}', 'updated_at', $this->integer());
        $this->addColumn('{{%sms}}', 'created_by', $this->integer());
        $this->addColumn('{{%sms}}', 'last_updated_by', $this->integer());
        $this->addColumn('{{%sms}}', 'is_deleted', $this->integer());
        $this->addColumn('{{%sms}}', 'type', $this->integer());
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
       $this->dropColumn('{{%sms}}', 'massage');
       $this->dropColumn('{{%sms}}', 'created_at');
       $this->dropColumn('{{%sms}}', 'updated_at');
       $this->dropColumn('{{%sms}}', 'created_by');
       $this->dropColumn('{{%sms}}', 'last_updated_by');
       $this->dropColumn('{{%sms}}', 'is_deleted');
       $this->dropColumn('{{%sms}}', 'type');
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m220321_141424_add_to_sms cannot be reverted.\n";

        return false;
    }
    */
}
