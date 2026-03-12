<?php

use yii\db\Migration;

/**
 * Handles the creation of table `{{%lawyers}}`.
 */
class m210128_074723_create_lawyers_table extends Migration
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

        $this->createTable('{{%lawyers}}', [
            'id' => $this->primaryKey(),
            'name' => $this->string(255)->notNull(),
            'address' => $this->string(255)->notNull(),
            'phone_number' => $this->string(255)->notNull(),
            'status' => $this->tinyInteger(1)->notNull(),
            'notes' => $this->text()->notNull(),
            'created_at' => $this->integer()->notNull(),
            'updated_at' => $this->integer()->notNull(),
            'created_by' => $this->integer()->notNull(),
            'last_update_by' => $this->integer()->notNull(),
           'is_deleted'=>$this->integer()->notNull()
        ], $tableOptions);

        $this->createIndex('created_by', '{{%lawyers}}', 'created_by');
        $this->createIndex('id', '{{%lawyers}}', 'id');
        $this->addForeignKey('os_lawyers_ibfk_1', '{{%lawyers}}', 'created_by', '{{%user}}', 'id', 'RESTRICT', 'RESTRICT');
   
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropTable('{{%lawyers}}');
    }
}
