<?php

use yii\db\Migration;

class m210112_202602_016_create_table_os_user_leave_policy extends Migration
{
    public function up()
    {
        $tableOptions = null;
        if ($this->db->driverName === 'mysql') {
            $tableOptions = 'CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE=InnoDB';
        }

        $this->createTable('{{%user_leave_policy}}', [
            'id' => $this->primaryKey(),
            'user_id' => $this->integer()->notNull(),
            'leave_policy_id' => $this->integer()->notNull(),
        ], $tableOptions);

        $this->createIndex('user_id', '{{%user_leave_policy}}', 'user_id');
        $this->createIndex('leave_policy_id', '{{%user_leave_policy}}', 'leave_policy_id');
        $this->addForeignKey('os_user_leave_policy_ibfk_1', '{{%user_leave_policy}}', 'leave_policy_id', '{{%leave_policy}}', 'id', 'RESTRICT', 'RESTRICT');
    }

    public function down()
    {
        $this->dropTable('{{%user_leave_policy}}');
    }
}
