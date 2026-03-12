<?php

use yii\db\Migration;

class m210112_202602_003_create_table_os_companies extends Migration
{
    public function up()
    {
        $tableOptions = null;
        if ($this->db->driverName === 'mysql') {
            $tableOptions = 'CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE=InnoDB';
        }

        $this->createTable('{{%companies}}', [
            'id' => $this->primaryKey(),
            'name' => $this->string(50)->notNull(),
            'phone_number' => $this->string(50)->notNull(),
            'bank_info' => $this->string(50)->notNull(),
            'logo' => $this->string(50)->notNull(),
            'created_by' => $this->integer()->notNull(),
            'created_at' => $this->integer()->notNull(),
            'updated_at' => $this->integer(),
        ], $tableOptions);

    }

    public function down()
    {
        $this->dropTable('{{%companies}}');
    }
}
