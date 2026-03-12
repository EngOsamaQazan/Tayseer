<?php

use yii\db\Migration;

class m210112_202602_031_create_table_os_leave_request extends Migration
{
    public function up()
    {
        $tableOptions = null;
        if ($this->db->driverName === 'mysql') {
            $tableOptions = 'CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE=InnoDB';
        }

        $this->createTable('{{%leave_request}}', [
            'id' => $this->primaryKey(),
            'reason' => $this->string(),
            'start_at' => $this->date()->notNull(),
            'end_at' => $this->date()->notNull(),
            'attachment' => $this->integer(),
            'leave_policy' => $this->integer()->notNull(),
            'action_by' => $this->integer()->defaultValue('0'),
            'status' => $this->string()->notNull()->defaultValue('under review'),
            'created_by' => $this->integer()->notNull(),
            'created_at' => $this->integer()->notNull(),
            'updated_at' => $this->integer(),
        ], $tableOptions);

        $this->createIndex('approved_by', '{{%leave_request}}', 'action_by');
        $this->createIndex('created_by', '{{%leave_request}}', 'created_by');
        $this->addForeignKey('os_leave_request_ibfk_1', '{{%leave_request}}', 'created_by', '{{%user}}', 'id', 'RESTRICT', 'RESTRICT');
    }

    public function down()
    {
        $this->dropTable('{{%leave_request}}');
    }
}
