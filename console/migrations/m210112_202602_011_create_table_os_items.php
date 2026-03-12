<?php

use yii\db\Migration;

class m210112_202602_011_create_table_os_items extends Migration
{
    public function up()
    {
        $tableOptions = null;
        if ($this->db->driverName === 'mysql') {
            $tableOptions = 'CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE=InnoDB';
        }

        $this->createTable('{{%items}}', [
            'id' => $this->primaryKey(),
            'name' => $this->string(50)->notNull(),
            'cost' => $this->integer()->notNull(),
            'price' => $this->integer()->notNull(),
            'invoice_number' => $this->integer()->notNull(),
            'notes' => $this->string(250),
            'is_sold' => $this->integer(),
            'sold_to' => $this->integer()->notNull(),
        ], $tableOptions);

        $this->createIndex('sold_to', '{{%items}}', 'sold_to');
        $this->createIndex('is_sold', '{{%items}}', 'is_sold');
    }

    public function down()
    {
        $this->dropTable('{{%items}}');
    }
}
