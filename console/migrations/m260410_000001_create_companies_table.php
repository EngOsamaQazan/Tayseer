<?php

use yii\db\Migration;

class m260410_000001_create_companies_table extends Migration
{
    public function safeUp()
    {
        $this->createTable('{{%companies}}', [
            'id'             => $this->primaryKey(),
            'slug'           => $this->string(50)->notNull()->unique(),
            'name_ar'        => $this->string(255)->notNull(),
            'name_en'        => $this->string(255)->null(),
            'domain'         => $this->string(255)->notNull()->unique(),
            'db_name'        => $this->string(100)->notNull(),
            'server_ip'      => $this->string(45)->notNull()->defaultValue('31.220.82.115'),
            'sms_sender'     => $this->string(50)->null(),
            'sms_user'       => $this->string(50)->null(),
            'sms_pass'       => $this->string(100)->null(),
            'og_title'       => $this->string(255)->null(),
            'og_description' => $this->string(500)->null(),
            'og_image'       => $this->string(255)->null(),
            'status'         => "ENUM('pending','dns_ready','provisioned','active','disabled') NOT NULL DEFAULT 'pending'",
            'provision_log'  => $this->text()->null(),
            'provisioned_at' => $this->integer()->null(),
            'created_at'     => $this->integer()->notNull(),
            'updated_at'     => $this->integer()->notNull(),
        ]);

        $this->createIndex('idx-companies-status', '{{%companies}}', 'status');
        $this->createIndex('idx-companies-domain', '{{%companies}}', 'domain');
    }

    public function safeDown()
    {
        $this->dropTable('{{%companies}}');
    }
}
