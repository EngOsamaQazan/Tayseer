<?php

use yii\db\Migration;

/**
 * Handles the creation of table `{{%court}}`.
 */
class m210128_080122_create_court_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $tableOptions = null;
        if ($this->db->driverName === 'mysql') {
            $tableOptions = 'CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE=InnoDB';
        }

        $this->createTable('{{%court}}', [
            'id' => $this->primaryKey(),
            'name' => $this->string(255)->notNull(),
            'city' => $this->integer()->notNull(),
            'adress' => $this->string(255)->notNull(),
            'phone_number' => $this->string(255)->notNull(),
            'created_by' => $this->integer()->notNull(),
            'last_update_by' => $this->integer()->notNull(),
            'created_at' => $this->integer()->notNull(),
            'updates_at' => $this->integer()->notNull(),
            'is_deleted'=>$this->integer()->notNull()
        ], $tableOptions);

        $this->createIndex('created_by', '{{%court}}', 'created_by');
        $this->addForeignKey('os_court_ibfk_1', '{{%court}}', 'created_by', '{{%user}}', 'id', 'RESTRICT', 'RESTRICT');
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropTable('{{%court}}');
    }
}
