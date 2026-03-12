<?php

use yii\db\Migration;

class m210112_202602_013_create_table_os_leave_types extends Migration
{
    public function up()
    {
        $tableOptions = null;
        if ($this->db->driverName === 'mysql') {
            $tableOptions = 'CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE=InnoDB';
        }

        $this->createTable('{{%leave_types}}', [
            'id' => $this->primaryKey(),
            'title' => $this->string(50)->notNull(),
            'description' => $this->string(250),
            'status' => $this->string()->notNull()->defaultValue('active'),
            'created_by' => $this->integer()->notNull(),
            'created_at' => $this->integer()->notNull(),
            'updated_at' => $this->integer(),
        ], $tableOptions);

        $this->createIndex('created_by', '{{%leave_types}}', 'created_by');
        $this->createIndex('location', '{{%leave_types}}', 'title', true);
    }

    public function down()
    {
        $this->dropTable('{{%leave_types}}');
    }
}
