<?php

use yii\db\Migration;

class m210112_202602_005_create_table_os_customers extends Migration
{
    public function up()
    {
        $tableOptions = null;
        if ($this->db->driverName === 'mysql') {
            $tableOptions = 'CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE=InnoDB';
        }

        $this->createTable('{{%customers}}', [
            'id' => $this->primaryKey(),
            'name' => $this->string(250)->notNull(),
            'status' => $this->tinyInteger(1)->notNull()->defaultValue('0'),
            'city' => $this->string(),
            'job_title' => $this->string(),
            'id_number' => $this->string(),
            'birth_date' => $this->date(),
            'job_number' => $this->string(20),
            'email' => $this->string(50),
            'sex' => $this->tinyInteger(1),
            'citizen' => $this->string(50)->notNull(),
            'hear_about_us' => $this->text(),
            'selected_image' => $this->string(),
            'bank_name' => $this->string(),
            'bank_branch' => $this->string(100),
            'account_number' => $this->string(50),
            'is_social_security' => $this->tinyInteger(1)->notNull(),
            'social_security_number' => $this->string(50),
            'do_have_any_property' => $this->tinyInteger(1)->notNull(),
            'property_name' => $this->string(50),
            'property_number' => $this->string(20),
            'notes' => $this->string(500),
            'primary_phone_number' => $this->text(),
            'facebook_account' => $this->text(),
        ], $tableOptions);

    }

    public function down()
    {
        $this->dropTable('{{%customers}}');
    }
}
