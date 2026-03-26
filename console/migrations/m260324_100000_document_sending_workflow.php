<?php

use yii\db\Migration;

class m260324_100000_document_sending_workflow extends Migration
{
    public function safeUp()
    {
        $diwanTable = $this->db->getTableSchema('{{%diwan_correspondence}}', true);
        if ($diwanTable && !isset($diwanTable->columns['delivery_method'])) {
            $this->addColumn('{{%diwan_correspondence}}', 'delivery_method', $this->string(30)->null()->after('notification_method'));
        }

        $jcaTable = $this->db->getTableSchema('{{%judiciary_customers_actions}}', true);
        if ($jcaTable && !isset($jcaTable->columns['correspondence_id'])) {
            $this->addColumn('{{%judiciary_customers_actions}}', 'correspondence_id', $this->integer()->null()->after('request_target'));
            try {
                $this->addForeignKey(
                    'fk_jca_correspondence',
                    '{{%judiciary_customers_actions}}',
                    'correspondence_id',
                    '{{%diwan_correspondence}}',
                    'id',
                    'SET NULL',
                    'CASCADE'
                );
            } catch (\Exception $e) {}
        }
    }

    public function safeDown()
    {
        $this->dropForeignKey('fk_jca_correspondence', '{{%judiciary_customers_actions}}');
        $this->dropColumn('{{%judiciary_customers_actions}}', 'correspondence_id');
        $this->dropColumn('{{%diwan_correspondence}}', 'delivery_method');
    }
}
