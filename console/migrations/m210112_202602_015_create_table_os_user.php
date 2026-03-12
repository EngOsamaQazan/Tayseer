<?php

use yii\db\Migration;

class m210112_202602_015_create_table_os_user extends Migration
{
    public function up()
    {
        $tableOptions = null;
        if ($this->db->driverName === 'mysql') {
            $tableOptions = 'CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE=InnoDB';
        }

        $this->createTable('{{%user}}', [
            'id' => $this->primaryKey(),
            'username' => $this->string()->notNull(),
            'email' => $this->string()->notNull(),
            'password_hash' => $this->string(60)->notNull(),
            'auth_key' => $this->string(32)->notNull(),
            'confirmed_at' => $this->integer(),
            'unconfirmed_email' => $this->string(),
            'blocked_at' => $this->integer(),
            'registration_ip' => $this->string(45),
            'created_at' => $this->integer()->notNull(),
            'updated_at' => $this->integer()->notNull(),
            'flags' => $this->integer()->notNull()->defaultValue('0'),
            'last_login_at' => $this->integer(),
            'verification_token' => $this->string(),
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
            'created_by' => $this->integer()->notNull(),
            'avatar' => $this->string(500),
        ], $tableOptions);

        $this->createIndex('os_profile_ibfk_6', '{{%user}}', 'reporting_to');
        $this->createIndex('os_profile_ibfk_8', '{{%user}}', 'location');
        $this->createIndex('os_user_unique_email', '{{%user}}', 'email', true);
        $this->createIndex('os_profile_ibfk_5', '{{%user}}', 'job_title');
        $this->createIndex('os_profile_ibfk_7', '{{%user}}', 'department');
        $this->createIndex('os_user_unique_username', '{{%user}}', 'username', true);
    }

    public function down()
    {
        $this->dropTable('{{%user}}');
    }
}
