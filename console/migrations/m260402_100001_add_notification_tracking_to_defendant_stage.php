<?php

use yii\db\Migration;

class m260402_100001_add_notification_tracking_to_defendant_stage extends Migration
{
    public function safeUp()
    {
        $p = $this->db->tablePrefix;
        $table = $p . 'judiciary_defendant_stage';

        if (!$this->db->getTableSchema($table)->getColumn('notification_date')) {
            $this->addColumn($table, 'notification_date', $this->date()->null()->after('stage_updated_at'));
        }
        if (!$this->db->getTableSchema($table)->getColumn('comprehensive_request_date')) {
            $this->addColumn($table, 'comprehensive_request_date', $this->date()->null()->after('notification_date'));
        }

        $this->createIndex('idx_ds_notification', $table, ['judiciary_id', 'notification_date']);
    }

    public function safeDown()
    {
        $p = $this->db->tablePrefix;
        $table = $p . 'judiciary_defendant_stage';

        $this->dropIndex('idx_ds_notification', $table);
        $this->dropColumn($table, 'comprehensive_request_date');
        $this->dropColumn($table, 'notification_date');
    }
}
