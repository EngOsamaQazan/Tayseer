<?php

use yii\db\Migration;

class m210112_202602_019_create_table_os_address extends Migration
{
    public function up()
    {
        $tableOptions = null;
        if ($this->db->driverName === 'mysql') {
            $tableOptions = 'CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE=InnoDB';
        }

        $this->createTable('{{%address}}', [
            'id' => $this->primaryKey(),
            'customers_id' => $this->integer(),
            'address' => $this->string(),
            'created_at' => $this->timestamp(),
            'updated_at' => $this->timestamp(),
            'is_deleted' => $this->integer(1)->defaultValue('0'),
            'address_type' => $this->integer(),
        ], $tableOptions);

        $this->addForeignKey('fk_address_customers_id', '{{%address}}', 'customers_id', '{{%customers}}', 'id', 'RESTRICT', 'RESTRICT');
    }

    public function down()
    {
        $this->dropTable('{{%address}}');
    }
}
