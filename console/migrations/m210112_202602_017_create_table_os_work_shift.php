<?php

use yii\db\Migration;

class m210112_202602_017_create_table_os_work_shift extends Migration
{
    public function up()
    {
        $tableOptions = null;
        if ($this->db->driverName === 'mysql') {
            $tableOptions = 'CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE=InnoDB';
        }

        $this->createTable('{{%work_shift}}', [
            'id' => $this->primaryKey(),
            'title' => $this->string(50)->notNull(),
            'start_at' => $this->time()->notNull(),
            'end_at' => $this->time()->notNull(),
            'created_by' => $this->integer()->notNull(),
            'created_at' => $this->integer()->notNull(),
            'updated_at' => $this->integer(),
        ], $tableOptions);

        $this->createIndex('created_by', '{{%work_shift}}', 'created_by');
    }

    public function down()
    {
        $this->dropTable('{{%work_shift}}');
    }
}
