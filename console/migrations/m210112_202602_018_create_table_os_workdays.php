<?php

use yii\db\Migration;

class m210112_202602_018_create_table_os_workdays extends Migration
{
    public function up()
    {
        $tableOptions = null;
        if ($this->db->driverName === 'mysql') {
            $tableOptions = 'CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE=InnoDB';
        }

        $this->createTable('{{%workdays}}', [
            'id' => $this->primaryKey(),
            'day_name' => $this->string()->notNull(),
            'start_at' => $this->time()->notNull(),
            'end_at' => $this->time()->notNull(),
            'status' => $this->string()->notNull()->defaultValue('working_day'),
            'created_by' => $this->integer()->notNull(),
            'created_at' => $this->integer()->notNull(),
            'updated_at' => $this->integer(),
        ], $tableOptions);

        $this->createIndex('created_by', '{{%workdays}}', 'created_by');
        $this->createIndex('day_name', '{{%workdays}}', 'day_name');
        $this->createIndex('location', '{{%workdays}}', 'day_name', true);
    }

    public function down()
    {
        $this->dropTable('{{%workdays}}');
    }
}
