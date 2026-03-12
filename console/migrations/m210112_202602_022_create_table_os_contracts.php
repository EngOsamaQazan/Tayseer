<?php

use yii\db\Migration;

class m210112_202602_022_create_table_os_contracts extends Migration
{
    public function up()
    {
        $tableOptions = null;
        if ($this->db->driverName === 'mysql') {
            $tableOptions = 'CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE=InnoDB';
        }

        $this->createTable('{{%contracts}}', [
            'id' => $this->primaryKey(),
            'type' => $this->string()->notNull(),
            'seller_id' => $this->integer(),
            'Date_of_sale' => $this->date(),
            'total_value' => $this->double(),
            'first_installment_value' => $this->double()->comment('value of first intallment amount'),
            'first_installment_date' => $this->date(),
            'monthly_installment_value' => $this->double(),
            'notes' => $this->text(),
            'status' => $this->string()->notNull(),
            'updated_at' => $this->timestamp(),
            'is_deleted' => $this->integer(1)->defaultValue('0'),
            'selected_image' => $this->string(),
            'company_id' => $this->text(),
            'commitment_discount' => $this->double()->notNull(),
            'loss_commitment' => $this->integer(),
            'created_by' => $this->integer()->notNull(),
            'updated_by' => $this->integer()->notNull(),
            'created_at' => $this->timestamp()->notNull()->defaultExpression('CURRENT_TIMESTAMP'),
            'follow_up_lock_by' => $this->integer(),
            'follow_up_lock_at' => $this->timestamp(),
        ], $tableOptions);

        $this->addForeignKey('os_contracts_seller_ibfk_1', '{{%contracts}}', 'seller_id', '{{%user}}', 'id', 'RESTRICT', 'RESTRICT');
    }

    public function down()
    {
        $this->dropTable('{{%contracts}}');
    }
}
