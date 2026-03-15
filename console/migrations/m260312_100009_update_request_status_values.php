<?php

use yii\db\Migration;

class m260312_100009_update_request_status_values extends Migration
{
    public function safeUp()
    {
        $this->update('{{%judiciary_customers_actions}}', ['request_status' => 'submitted'], ['request_status' => 'pending']);
    }

    public function safeDown()
    {
        $this->update('{{%judiciary_customers_actions}}', ['request_status' => 'pending'], ['request_status' => 'submitted']);
    }
}
