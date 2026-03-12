<?php

use yii\db\Migration;

/**
 * Class m220305_171436_update_default_value_to_image_in_custamer_document
 */
class m220305_171436_update_default_value_to_image_in_custamer_document extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->alterColumn('{{%customers_document}}', 'images', $this->string()->defaultValue(0));

    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        echo "m220305_171436_update_default_value_to_image_in_custamer_document cannot be reverted.\n";

        return false;
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m220305_171436_update_default_value_to_image_in_custamer_document cannot be reverted.\n";

        return false;
    }
    */
}
