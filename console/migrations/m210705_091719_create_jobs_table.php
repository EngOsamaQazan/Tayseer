<?php

use yii\db\Migration;

/**
 * Handles the creation of table `{{%jobs}}`.
 */
class m210705_091719_create_jobs_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->createTable('{{%jobs}}', [
            'id' => $this->primaryKey(),
            'name'=>$this->string(),
            'job_type'=>$this->integer()
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropTable('{{%jobs}}');
    }
}
