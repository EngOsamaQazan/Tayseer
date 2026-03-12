<?php

use yii\db\Migration;

class m210112_202602_027_create_table_os_follow_up extends Migration
{
    public function up()
    {
        $tableOptions = null;
        if ($this->db->driverName === 'mysql') {
            $tableOptions = 'CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE=InnoDB';
        }

        $this->createTable('{{%follow_up}}', [
            'id' => $this->primaryKey(),
            'contract_id' => $this->integer()->notNull(),
            'date_time' => $this->dateTime()->notNull()->defaultExpression('CURRENT_TIMESTAMP'),
            'notes' => $this->text(),
            'feeling' => $this->string(),
            'created_by' => $this->integer()->notNull(),
            'connection_goal' => $this->tinyInteger(),
            'reminder' => $this->date(),
            'promise_to_pay_at' => $this->date(),
        ], $tableOptions);

        $this->addForeignKey('os_follow_up_created_by_ibfk_1', '{{%follow_up}}', 'created_by', '{{%user}}', 'id', 'RESTRICT', 'RESTRICT');
        $this->addForeignKey('os_follow_up_ibfk_1', '{{%follow_up}}', 'contract_id', '{{%contracts}}', 'id', 'RESTRICT', 'RESTRICT');
    }

    public function down()
    {
        $this->dropTable('{{%follow_up}}');
    }
}
