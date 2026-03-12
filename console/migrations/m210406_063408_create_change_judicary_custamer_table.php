<?php

use yii\db\Migration;

/**
 * Handles the creation of table `{{%change_judicary_custamer}}`.
 */
class m210406_063408_create_change_judicary_custamer_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
$this->dropTable('{{%judiciary_customers_actions}}');
        $this->createTable('{{%judiciary_customers_actions}}', [
            'id' => $this->primaryKey(),
            'judiciary_id'=>$this->integer()->notNull(),
            'customers_id'=>$this->integer()->notNull(),
            'judiciary_actions_id'=>$this->integer()->notNull(),
            'note'=>$this->text(),
            'created_at' => $this->integer()->notNull(),
            'updated_at' => $this->integer()->notNull(),
            'created_by' => $this->integer()->notNull(),
            'last_update_by' => $this->integer()->notNull(),
            'is_deleted'=>$this->integer()->notNull()

        ]);
        $this->createIndex('index_judiciary','{{%judiciary_customers_actions}}','judiciary_id');
        $this->createIndex('index_customers','{{%judiciary_customers_actions}}','customers_id');
        $this->createIndex('index_actions','{{%judiciary_customers_actions}}','judiciary_actions_id');

        $this->addForeignKey('fk_judiciary','{{%judiciary_customers_actions}}','judiciary_id','{{%judiciary}}','id');
        $this->addForeignKey('fk_customers','{{%judiciary_customers_actions}}', 'customers_id','{{%customers}}','id');
        $this->addForeignKey('fk_actions','{{%judiciary_customers_actions}}','judiciary_actions_id','{{%judiciary_actions}}','id');
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropTable('{{%change_judicary_custamer}}');
    }
}
