<?php

use yii\db\Migration;

class m210112_202602_039_create_foreign_keys extends Migration
{
    public function up()
    {
        $this->addForeignKey('os_profile_ibfk_5', '{{%user}}', 'job_title', '{{%designation}}', 'id', 'RESTRICT', 'RESTRICT');
        $this->addForeignKey('os_profile_ibfk_6', '{{%user}}', 'reporting_to', '{{%user}}', 'id', 'RESTRICT', 'RESTRICT');
        $this->addForeignKey('os_profile_ibfk_7', '{{%user}}', 'department', '{{%department}}', 'id', 'RESTRICT', 'RESTRICT');
        $this->addForeignKey('os_profile_ibfk_8', '{{%user}}', 'location', '{{%location}}', 'id', 'RESTRICT', 'RESTRICT');
        $this->addForeignKey('os_user_leave_policy_ibfk_2', '{{%user_leave_policy}}', 'user_id', '{{%user}}', 'id', 'RESTRICT', 'RESTRICT');
        $this->addForeignKey('os_work_shift_ibfk_1', '{{%work_shift}}', 'created_by', '{{%user}}', 'id', 'RESTRICT', 'RESTRICT');
    }

    public function down()
    {
        echo "m210112_202602_039_create_foreign_keys cannot be reverted.\n";
        return false;
    }
}
