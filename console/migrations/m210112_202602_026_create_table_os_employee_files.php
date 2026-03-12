<?php

use yii\db\Migration;

class m210112_202602_026_create_table_os_employee_files extends Migration
{
    public function up()
    {
        $tableOptions = null;
        if ($this->db->driverName === 'mysql') {
            $tableOptions = 'CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE=InnoDB';
        }

        $this->createTable('{{%employee_files}}', [
            'id' => $this->primaryKey(),
            'user_id' => $this->integer()->notNull(),
            'type' => $this->tinyInteger(1)->notNull()->comment('0:Avatar | 1:Attachment'),
            'file_name' => $this->string()->notNull(),
            'path' => $this->string()->notNull(),
        ], $tableOptions);

        $this->createIndex('indx_images_user_id', '{{%employee_files}}', 'user_id');
        $this->addForeignKey('fk_images_user_id', '{{%employee_files}}', 'user_id', '{{%user}}', 'id', 'RESTRICT', 'RESTRICT');
    }

    public function down()
    {
        $this->dropTable('{{%employee_files}}');
    }
}
