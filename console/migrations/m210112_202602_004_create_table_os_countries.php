<?php

use yii\db\Migration;

class m210112_202602_004_create_table_os_countries extends Migration
{
    public function up()
    {
        $tableOptions = null;
        if ($this->db->driverName === 'mysql') {
            $tableOptions = 'CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE=InnoDB';
        }

        $this->createTable('{{%countries}}', [
            'id' => $this->primaryKey(),
            'country_code' => $this->string(2)->notNull()->defaultValue(''),
            'country_name' => $this->string(100)->notNull()->defaultValue(''),
        ], $tableOptions);

    }

    public function down()
    {
        $this->dropTable('{{%countries}}');
    }
}
