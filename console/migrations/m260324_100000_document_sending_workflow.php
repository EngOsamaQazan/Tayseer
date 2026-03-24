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

        // Documents stay NULL (displayed as "غير مُدخل" in frontend)
        // No update needed — they are already NULL by default
    }

    public function safeDown()
    {
        $this->dropForeignKey('fk_jca_correspondence', '{{%judiciary_customers_actions}}');
        $this->dropColumn('{{%judiciary_customers_actions}}', 'correspondence_id');
        $this->dropColumn('{{%diwan_correspondence}}', 'delivery_method');
    }
}
