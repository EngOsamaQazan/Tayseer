<?php

use yii\db\Migration;

/**
 * Class m220302_174852_add_images_to_os_customers_document
 */
class m220302_174852_add_images_to_os_customers_document extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {

        $this->addColumn('{{%customers_document}}', 'images', $this->string());
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropColumn('{{%customers_document}}', 'images');
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m220302_174852_add_images_to_os_customers_document cannot be reverted.\n";

        return false;
    }
    */
}
