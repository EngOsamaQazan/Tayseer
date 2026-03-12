<?php

use yii\db\Migration;

class m210112_202602_032_create_table_os_location extends Migration
{
    public function up()
    {
        $tableOptions = null;
        if ($this->db->driverName === 'mysql') {
            $tableOptions = 'CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE=InnoDB';
        }

        $this->createTable('{{%location}}', [
            'id' => $this->primaryKey(),
            'location' => $this->string(50)->notNull(),
            'description' => $this->string(250),
            'longitude' => $this->integer()->notNull(),
            'latitude' => $this->integer()->notNull(),
            'radius' => $this->integer()->notNull(),
            'status' => $this->string()->notNull()->defaultValue('active'),
            'created_by' => $this->integer()->notNull(),
            'created_at' => $this->integer()->notNull(),
            'updated_at' => $this->integer(),
        ], $tableOptions);

        $this->createIndex('created_by', '{{%location}}', 'created_by');
        $this->createIndex('location', '{{%location}}', 'location', true);
        $this->addForeignKey('os_location_ibfk_1', '{{%location}}', 'created_by', '{{%user}}', 'id', 'RESTRICT', 'RESTRICT');
    }

    public function down()
    {
        $this->dropTable('{{%location}}');
    }
}
