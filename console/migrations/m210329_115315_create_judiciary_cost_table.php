<?php

use yii\db\Migration;

/**
 * Handles the creation of table `{{%judiciary_cost}}`.
 */
class m210329_115315_create_judiciary_cost_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->createTable('{{%judiciary_cost}}', [
            'id' => $this->primaryKey(),
            'lawyer_cost'=>$this->integer(),
            'judiciary_cost'=>$this->integer(),
            'contract_id'=>$this->integer(),
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropTable('{{%judiciary_cost}}');
    }
}
