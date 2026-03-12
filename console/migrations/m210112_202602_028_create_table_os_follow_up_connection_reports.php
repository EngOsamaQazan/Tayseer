<?php

use yii\db\Migration;

class m210112_202602_028_create_table_os_follow_up_connection_reports extends Migration
{
    public function up()
    {
        $tableOptions = null;
        if ($this->db->driverName === 'mysql') {
            $tableOptions = 'CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE=InnoDB';
        }

        $this->createTable('{{%follow_up_connection_reports}}', [
            'id' => $this->primaryKey(),
            'os_follow_up_id' => $this->integer()->notNull(),
            'customer_name' => $this->string(),
            'connection_type' => $this->string(),
            'connection_response' => $this->string(),
            'note' => $this->string(),
        ], $tableOptions);

        $this->createIndex('os_follow_up_id', '{{%follow_up_connection_reports}}', 'os_follow_up_id');
        $this->addForeignKey('os_follow_up_connection_reports_ibfk_1', '{{%follow_up_connection_reports}}', 'os_follow_up_id', '{{%follow_up}}', 'id', 'RESTRICT', 'RESTRICT');
    }

    public function down()
    {
        $this->dropTable('{{%follow_up_connection_reports}}');
    }
}
