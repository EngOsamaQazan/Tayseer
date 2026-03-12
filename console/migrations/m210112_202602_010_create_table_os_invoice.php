<?php

use yii\db\Migration;

class m210112_202602_010_create_table_os_invoice extends Migration
{
    public function up()
    {
        $tableOptions = null;
        if ($this->db->driverName === 'mysql') {
            $tableOptions = 'CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE=InnoDB';
        }

        $this->createTable('{{%invoice}}', [
            'id' => $this->primaryKey(),
            'title' => $this->string(250)->notNull(),
            'number' => $this->integer()->notNull(),
            'date' => $this->date(),
            'total' => $this->integer()->notNull(),
        ], $tableOptions);

    }

    public function down()
    {
        $this->dropTable('{{%invoice}}');
    }
}
