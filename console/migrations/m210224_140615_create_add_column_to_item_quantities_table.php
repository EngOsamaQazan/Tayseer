<?php

use yii\db\Migration;

/**
 * Handles the creation of table `{{%add_column_to_item_quantities}}`.
 */
class m210224_140615_create_add_column_to_item_quantities_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {

            $this->addColumn('{{%inventory_item_quantities}}','last_updated_by',$this->integer()->notNull());
            $this->addColumn('{{%inventory_item_quantities}}','updated_at',$this->integer()->notNull());
            $this->addColumn('{{%inventory_item_quantities}}','is_deleted',$this->integer()->notNull()->defaultValue(0));

        }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
    }
}
