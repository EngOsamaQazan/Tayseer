<?php

use yii\db\Migration;

class m210112_202602_025_create_table_os_designation extends Migration
{
    public function up()
    {
        $tableOptions = null;
        if ($this->db->driverName === 'mysql') {
            $tableOptions = 'CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE=InnoDB';
        }

        $this->createTable('{{%designation}}', [
            'id' => $this->primaryKey(),
            'title' => $this->string(50)->notNull(),
            'description' => $this->string(250),
            'status' => $this->string()->notNull()->defaultValue('active'),
            'created_by' => $this->integer()->notNull(),
            'created_at' => $this->integer()->notNull(),
            'updated_at' => $this->integer(),
        ], $tableOptions);

        $this->createIndex('created_by', '{{%designation}}', 'created_by');
        $this->createIndex('id', '{{%designation}}', 'id');
        $this->addForeignKey('os_designation_ibfk_1', '{{%designation}}', 'created_by', '{{%user}}', 'id', 'RESTRICT', 'RESTRICT');
    }

    public function down()
    {
        $this->dropTable('{{%designation}}');
    }
}
