<?php

use yii\db\Migration;

/**
 * Handles the creation of table `{{%lawyers_image}}`.
 */
class m230221_113242_create_lawyers_image_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->createTable('{{%lawyers_image}}', [
            'id' => $this->primaryKey(),
            'lawyer_id'=> $this->integer(),
            'image'=>$this->string()
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropTable('{{%lawyers_image}}');
    }
}
