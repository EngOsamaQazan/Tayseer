<?php

use yii\db\Migration;

class m210112_202602_007_create_table_os_holidays extends Migration
{
    public function up()
    {
        $tableOptions = null;
        if ($this->db->driverName === 'mysql') {
            $tableOptions = 'CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE=InnoDB';
        }

        $this->createTable('{{%holidays}}', [
            'id' => $this->primaryKey(),
            'title' => $this->string(50)->notNull(),
            'start_at' => $this->date()->notNull(),
            'end_at' => $this->date()->notNull(),
            'created_by' => $this->integer()->notNull(),
            'created_at' => $this->integer()->notNull(),
            'updated_at' => $this->integer(),
        ], $tableOptions);

        $this->createIndex('created_by', '{{%holidays}}', 'created_by');
    }

    public function down()
    {
        $this->dropTable('{{%holidays}}');
    }
}
