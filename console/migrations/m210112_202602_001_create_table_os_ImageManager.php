<?php

use yii\db\Migration;

class m210112_202602_001_create_table_os_ImageManager extends Migration
{
    public function up()
    {
        $tableOptions = null;
        if ($this->db->driverName === 'mysql') {
            $tableOptions = 'CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE=InnoDB';
        }

        $this->createTable('{{%ImageManager}}', [
            'id' => $this->primaryKey(10)->unsigned(),
            'fileName' => $this->string(128)->notNull(),
            'fileHash' => $this->string(32)->notNull(),
            'contractId' => $this->string(50),
            'groupName' => $this->string(50),
            'created' => $this->dateTime()->notNull(),
            'modified' => $this->dateTime(),
            'createdBy' => $this->integer(10)->unsigned(),
            'modifiedBy' => $this->integer(10)->unsigned(),
        ], $tableOptions);

    }

    public function down()
    {
        $this->dropTable('{{%ImageManager}}');
    }
}
