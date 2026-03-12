<?php

use yii\db\Migration;

/**
 * Handles adding columns to table `{{%lawyers}}`.
 */
class m230221_130243_add_columns_to_lawyers_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->addColumn('{{%lawyers}}', 'type', $this->string());
       
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
    }
}
