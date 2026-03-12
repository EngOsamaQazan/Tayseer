<?php

use yii\db\Migration;

class m210112_202602_021_create_table_os_auth_item_child extends Migration
{
    public function up()
    {
        $tableOptions = null;
        if ($this->db->driverName === 'mysql') {
            $tableOptions = 'CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE=InnoDB';
        }

        $this->createTable('{{%auth_item_child}}', [
            'parent' => $this->string(64)->notNull(),
            'child' => $this->string(64)->notNull(),
        ], $tableOptions);

        $this->addPrimaryKey('PRIMARYKEY', '{{%auth_item_child}}', ['parent', 'child']);
        $this->createIndex('child', '{{%auth_item_child}}', 'child');
        $this->addForeignKey('os_auth_item_child_ibfk_1', '{{%auth_item_child}}', 'parent', '{{%auth_item}}', 'name', 'CASCADE', 'CASCADE');
        $this->addForeignKey('os_auth_item_child_ibfk_2', '{{%auth_item_child}}', 'child', '{{%auth_item}}', 'name', 'CASCADE', 'CASCADE');
    }

    public function down()
    {
        $this->dropTable('{{%auth_item_child}}');
    }
}
