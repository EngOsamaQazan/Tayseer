<?php

use yii\db\Migration;

class m210112_202602_012_create_table_os_leave_policy extends Migration
{
    public function up()
    {
        $tableOptions = null;
        if ($this->db->driverName === 'mysql') {
            $tableOptions = 'CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE=InnoDB';
        }

        $this->createTable('{{%leave_policy}}', [
            'id' => $this->primaryKey(),
            'title' => $this->string(50)->notNull(),
            'year' => $this->date()->notNull(),
            'leave_type' => $this->integer()->notNull(),
            'total_days' => $this->integer()->notNull(),
            'description' => $this->string(250),
            'department' => $this->integer()->notNull()->defaultValue('0'),
            'designation' => $this->integer()->notNull()->defaultValue('0'),
            'location' => $this->integer()->notNull()->defaultValue('0'),
            'gender' => $this->string()->notNull(),
            'marital_status' => $this->string()->notNull(),
            'status' => $this->string()->notNull()->defaultValue('active'),
            'created_by' => $this->integer()->notNull(),
            'created_at' => $this->integer()->notNull(),
            'updated_at' => $this->integer(),
        ], $tableOptions);

        $this->createIndex('leave_type', '{{%leave_policy}}', 'leave_type');
        $this->createIndex('designation', '{{%leave_policy}}', 'designation');
        $this->createIndex('department', '{{%leave_policy}}', 'department');
        $this->createIndex('created_by', '{{%leave_policy}}', 'created_by');
    }

    public function down()
    {
        $this->dropTable('{{%leave_policy}}');
    }
}
