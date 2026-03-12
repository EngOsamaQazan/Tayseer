<?php

use yii\db\Migration;

/**
 * Handles the creation of table `{{%jobs_type}}`.
 */
class m210705_091538_create_jobs_type_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->createTable('{{%jobs_type}}', [
            'id' => $this->primaryKey(),
            'name'=>$this->string()
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropTable('{{%jobs_type}}');
    }
}
