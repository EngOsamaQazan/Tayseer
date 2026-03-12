<?php

use yii\db\Migration;

class m210112_202602_036_create_table_os_attendance extends Migration
{
    public function up()
    {
        $tableOptions = null;
        if ($this->db->driverName === 'mysql') {
            $tableOptions = 'CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE=InnoDB';
        }

        $this->createTable('{{%attendance}}', [
            'id' => $this->primaryKey(),
            'user_id' => $this->integer()->notNull(),
            'location_id' => $this->integer()->notNull(),
            'check_in_time' => $this->timestamp()->notNull()->defaultExpression('CURRENT_TIMESTAMP'),
            'check_out_time' => $this->timestamp()->notNull()->defaultValue('0000-00-00 00:00:00'),
            'manual_checked_in_by' => $this->integer()->notNull()->defaultValue('0'),
            'manual_checked_out_by' => $this->integer()->notNull()->defaultValue('0'),
            'is_manual_actions' => $this->string()->notNull()->defaultValue('no'),
        ], $tableOptions);

        $this->createIndex('location_id', '{{%attendance}}', 'location_id');
        $this->createIndex('user_id', '{{%attendance}}', 'user_id');
        $this->addForeignKey('os_attendance_ibfk_1', '{{%attendance}}', 'user_id', '{{%user}}', 'id', 'RESTRICT', 'RESTRICT');
        $this->addForeignKey('os_attendance_ibfk_2', '{{%attendance}}', 'location_id', '{{%location}}', 'id', 'RESTRICT', 'RESTRICT');
    }

    public function down()
    {
        $this->dropTable('{{%attendance}}');
    }
}
