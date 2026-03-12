<?php

use yii\db\Migration;

class m210112_202602_014_create_table_os_phone_numbers extends Migration
{
    public function up()
    {
        $tableOptions = null;
        if ($this->db->driverName === 'mysql') {
            $tableOptions = 'CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE=InnoDB';
        }

        $this->createTable('{{%phone_numbers}}', [
            'id' => $this->primaryKey(),
            'customers_id' => $this->integer(),
            'phone_number' => $this->string(),
            'fb_account' => $this->string(250),
            'created_at' => $this->timestamp(),
            'updated_at' => $this->timestamp(),
            'is_deleted' => $this->integer(1)->defaultValue('0'),
            'phone_number_owner' => $this->string(100),
            'owner_name' => $this->string(100)->notNull(),
        ], $tableOptions);

        $this->addForeignKey('fk_phone_numbers_customers_id', '{{%phone_numbers}}', 'customers_id', '{{%customers}}', 'id', 'RESTRICT', 'RESTRICT');
    }

    public function down()
    {
        $this->dropTable('{{%phone_numbers}}');
    }
}
