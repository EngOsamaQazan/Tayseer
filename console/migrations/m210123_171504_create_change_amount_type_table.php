<?php

use yii\db\Migration;

/**
 * Handles the creation of table `{{%change_amount_type}}`.
 */
class m210123_171504_create_change_amount_type_table extends Migration
{

    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->dropColumn('{{%expenses}}','amount');
        $this->addColumn('{{%expenses}}','amount',$this->double(255)->notNull());

    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropColumn('{{%expenses}}','amount');
    }
}
