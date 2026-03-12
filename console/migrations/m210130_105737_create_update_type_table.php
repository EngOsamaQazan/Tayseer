<?php

use yii\db\Migration;

/**
 * Handles the creation of table `{{%update_type}}`.
 */
class m210130_105737_create_update_type_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
       $this->createIndex('index_type', '{{%judiciary}}','type_id');
       $this->addForeignKey('fk_type_judiciary', '{{%judiciary}}', 'type_id', '{{%judiciary_type}}', 'id');
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropIndex('index_type', '{{%judiciary}}');
        $this->dropForeignKey('fk_type_judiciary','{{%judiciary}}');
    }
}
