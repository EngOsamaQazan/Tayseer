<?php

use yii\db\Migration;

/**
 * Handles the creation of table `{{%add_total_element_to_inventory_items}}`.
 */
class m210407_081744_create_add_total_element_to_inventory_items_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->addColumn('{{%inventory_items}}', 'total_element', $this->integer()->defaultValue(0));
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
    }
}
