<?php

use yii\db\Migration;

class m210112_202602_033_create_table_os_profile extends Migration
{
    public function up()
    {
        $tableOptions = null;
        if ($this->db->driverName === 'mysql') {
            $tableOptions = 'CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE=InnoDB';
        }

        $this->createTable('{{%profile}}', [
            'user_id' => $this->primaryKey(),
            'name' => $this->string(),
            'public_email' => $this->string(),
            'gravatar_email' => $this->string(),
            'gravatar_id' => $this->string(32),
            'location' => $this->integer(),
            'website' => $this->string(),
            'bio' => $this->text(),
            'timezone' => $this->string(40),
            'middle_name' => $this->string(),
            'last_name' => $this->string(),
            'employee_type' => $this->string()->notNull(),
            'employee_status' => $this->string()->notNull(),
            'date_of_hire' => $this->date(),
            'department' => $this->integer(),
            'job_title' => $this->integer(),
            'reporting_to' => $this->integer(),
            'mobile' => $this->string(),
            'nationality' => $this->integer(),
            'gender' => $this->string()->notNull(),
            'marital_status' => $this->string()->notNull(),
        ], $tableOptions);

        $this->createIndex('job_title', '{{%profile}}', 'job_title');
        $this->createIndex('reporting_to', '{{%profile}}', 'reporting_to');
        $this->createIndex('location', '{{%profile}}', 'location');
        $this->addForeignKey('os_fk_user_profile', '{{%profile}}', 'user_id', '{{%user}}', 'id', 'CASCADE', 'RESTRICT');
        $this->addForeignKey('os_profile_ibfk_1', '{{%profile}}', 'job_title', '{{%designation}}', 'id', 'RESTRICT', 'RESTRICT');
        $this->addForeignKey('os_profile_ibfk_2', '{{%profile}}', 'reporting_to', '{{%user}}', 'id', 'RESTRICT', 'RESTRICT');
        $this->addForeignKey('os_profile_ibfk_3', '{{%profile}}', 'department', '{{%department}}', 'id', 'RESTRICT', 'RESTRICT');
        $this->addForeignKey('os_profile_ibfk_4', '{{%profile}}', 'location', '{{%location}}', 'id', 'RESTRICT', 'RESTRICT');
    }

    public function down()
    {
        $this->dropTable('{{%profile}}');
    }
}
