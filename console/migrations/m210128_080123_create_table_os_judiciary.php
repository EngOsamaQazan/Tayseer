<?php

use yii\db\Migration;

class m210128_080123_create_table_os_judiciary extends Migration
{
    public function up()
    {
        $tableOptions = null;
        if ($this->db->driverName === 'mysql') {
            $tableOptions = 'CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE=InnoDB';
        }

        $this->createTable('{{%judiciary}}', [
            'id' => $this->primaryKey(),
            'court_id' => $this->integer()->notNull(),
            'type_id' => $this->integer()->notNull(),
            'case_cost' => $this->double()->notNull(),
            'lawyer_cost' => $this->double()->notNull(),
            'lawyer_id' => $this->integer()->notNull(),
            'created_at' => $this->integer()->notNull(),
            'updated_at' => $this->integer()->notNull(),
            'created_by' => $this->integer()->notNull(),
            'last_update_by' => $this->integer()->notNull(),
        ], $tableOptions);

        $this->createIndex('lawyer_id', '{{%judiciary}}', 'lawyer_id');
        $this->createIndex('created_by', '{{%judiciary}}', 'created_by');
        $this->createIndex('id', '{{%judiciary}}', 'id');
        $this->createIndex('court_id', '{{%judiciary}}', 'court_id');
        $this->addForeignKey('os_judiciary_ibfk_1', '{{%judiciary}}', 'lawyer_id', '{{%lawyers}}', 'id', 'RESTRICT', 'RESTRICT');
        $this->addForeignKey('os_judiciary_ibfk_2', '{{%judiciary}}', 'court_id', '{{%court}}', 'id', 'RESTRICT', 'RESTRICT');
        $this->addForeignKey('os_judiciary_ibfk_3', '{{%judiciary}}', 'created_by', '{{%user}}', 'id', 'RESTRICT', 'RESTRICT');
    }

    public function down()
    {
        $this->dropTable('{{%judiciary}}');
    }
}
