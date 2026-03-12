<?php

use yii\db\Migration;

class m260227_100000_create_contract_adjustments_table extends Migration
{
    public function safeUp()
    {
        $this->createTable('{{%contract_adjustments}}', [
            'id'          => $this->primaryKey(),
            'contract_id' => $this->integer()->notNull(),
            'type'        => "ENUM('discount','write_off','waiver','free_discount') NOT NULL DEFAULT 'discount'",
            'amount'      => $this->decimal(12, 2)->notNull()->defaultValue(0),
            'reason'      => $this->text()->null(),
            'approved_by' => $this->integer()->null(),
            'created_by'  => $this->integer()->null(),
            'created_at'  => $this->timestamp()->defaultExpression('CURRENT_TIMESTAMP'),
            'is_deleted'  => $this->tinyInteger(1)->notNull()->defaultValue(0),
        ], 'ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');

        $this->createIndex('idx-ca-contract', '{{%contract_adjustments}}', 'contract_id');
        $this->createIndex('idx-ca-type', '{{%contract_adjustments}}', 'type');

        $this->addForeignKey(
            'fk-ca-contract',
            '{{%contract_adjustments}}',
            'contract_id',
            '{{%contracts}}',
            'id',
            'CASCADE',
            'CASCADE'
        );
    }

    public function safeDown()
    {
        $this->dropForeignKey('fk-ca-contract', '{{%contract_adjustments}}');
        $this->dropTable('{{%contract_adjustments}}');
    }
}
