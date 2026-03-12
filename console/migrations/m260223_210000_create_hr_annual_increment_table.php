<?php

use yii\db\Migration;

class m260223_210000_create_hr_annual_increment_table extends Migration
{
    public function safeUp()
    {
        if ($this->db->getTableSchema('{{%hr_annual_increment}}') !== null) {
            return;
        }

        $tableOptions = null;
        if ($this->db->driverName === 'mysql') {
            $tableOptions = 'CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE=InnoDB';
        }

        $this->createTable('{{%hr_annual_increment}}', [
            'id'                => $this->primaryKey(),
            'user_id'           => $this->integer()->notNull(),
            'service_year'      => $this->integer(),
            'increment_year'    => $this->integer(),
            'increment_type'    => "ENUM('fixed','percentage') NOT NULL DEFAULT 'fixed'",
            'amount'            => $this->decimal(12, 2)->notNull()->defaultValue(0),
            'calculated_amount' => $this->decimal(12, 2),
            'previous_salary'   => $this->decimal(12, 2),
            'new_salary'        => $this->decimal(12, 2),
            'effective_date'    => $this->date()->notNull(),
            'status'            => "ENUM('pending','approved','applied','cancelled') NOT NULL DEFAULT 'pending'",
            'approved_by'       => $this->integer(),
            'approved_at'       => $this->integer(),
            'applied_at'        => $this->integer(),
            'notes'             => $this->text(),
            'is_deleted'        => $this->tinyInteger(1)->notNull()->defaultValue(0),
            'created_at'        => $this->integer(),
            'created_by'        => $this->integer(),
            'updated_at'        => $this->integer(),
            'updated_by'        => $this->integer(),
        ], $tableOptions);

        $this->createIndex('idx_inc_user', '{{%hr_annual_increment}}', 'user_id');
        $this->createIndex('idx_inc_status', '{{%hr_annual_increment}}', 'status');
        $this->createIndex('idx_inc_effective', '{{%hr_annual_increment}}', 'effective_date');
    }

    public function safeDown()
    {
        $this->dropTable('{{%hr_annual_increment}}');
    }
}
