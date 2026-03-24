<?php

use yii\db\Migration;

class m260324_100000_document_sending_workflow extends Migration
{
    public function safeUp()
    {
        $this->addColumn('{{%diwan_correspondence}}', 'delivery_method', $this->string(30)->null()->after('notification_method'));

        $this->addColumn('{{%judiciary_customers_actions}}', 'correspondence_id', $this->integer()->null()->after('request_target'));
        $this->addForeignKey(
            'fk_jca_correspondence',
            '{{%judiciary_customers_actions}}',
            'correspondence_id',
            '{{%diwan_correspondence}}',
            'id',
            'SET NULL',
            'CASCADE'
        );

        $this->execute("
            UPDATE {{%judiciary_customers_actions}} jca
            INNER JOIN {{%judiciary_actions}} ja ON ja.id = jca.judiciary_actions_id
            SET jca.request_status = 'not_sent'
            WHERE ja.action_nature = 'document'
              AND (jca.request_status IS NULL OR jca.request_status = '' OR jca.request_status = 'pending')
        ");
    }

    public function safeDown()
    {
        $this->execute("
            UPDATE {{%judiciary_customers_actions}} jca
            INNER JOIN {{%judiciary_actions}} ja ON ja.id = jca.judiciary_actions_id
            SET jca.request_status = NULL
            WHERE ja.action_nature = 'document'
              AND jca.request_status = 'not_sent'
        ");

        $this->dropForeignKey('fk_jca_correspondence', '{{%judiciary_customers_actions}}');
        $this->dropColumn('{{%judiciary_customers_actions}}', 'correspondence_id');
        $this->dropColumn('{{%diwan_correspondence}}', 'delivery_method');
    }
}
