<?php

use yii\db\Migration;

class m210112_202602_024_create_table_os_department extends Migration
{
    public function up()
    {
        $tableOptions = null;
        if ($this->db->driverName === 'mysql') {
            $tableOptions = 'CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE=InnoDB';
        }

        $this->createTable('{{%department}}', [
            'id' => $this->primaryKey(),
            'title' => $this->string(50)->notNull(),
            'description' => $this->string(250),
            'lead_by' => $this->integer(),
            'status' => $this->string()->notNull()->defaultValue('active'),
            'created_by' => $this->integer()->notNull(),
            'created_at' => $this->integer()->notNull(),
            'updated_at' => $this->integer(),
        ], $tableOptions);

        $this->createIndex('id', '{{%department}}', 'id');
        $this->createIndex('created_by', '{{%department}}', 'created_by');
        $this->createIndex('lead_by', '{{%department}}', 'lead_by');
        $this->addForeignKey('os_department_ibfk_1', '{{%department}}', 'created_by', '{{%user}}', 'id', 'RESTRICT', 'RESTRICT');
        $this->addForeignKey('os_department_ibfk_2', '{{%department}}', 'lead_by', '{{%user}}', 'id', 'RESTRICT', 'RESTRICT');
    }

    public function down()
    {
        $this->dropTable('{{%department}}');
    }
}
