<?php

use yii\db\Migration;

/**
 * Handles the creation of table `{{%update_is_deleted_to_judiciary}}`.
 */
class m210224_171526_create_update_is_deleted_to_judiciary_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->alterColumn('{{%judiciary}}', 'is_deleted', $this->integer(1)->defaultValue(0));

    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
    }
}
