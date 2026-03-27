<?php

use yii\db\Migration;

class m260326_300002_add_wizard_drafts_multi_slot extends Migration
{
    public function safeUp()
    {
        $this->dropIndex('idx-wizard_drafts-user_key', '{{%wizard_drafts}}');

        $this->addColumn('{{%wizard_drafts}}', 'is_auto', $this->tinyInteger()->notNull()->defaultValue(0)->after('draft_key'));
        $this->addColumn('{{%wizard_drafts}}', 'draft_label', $this->string(150)->null()->after('is_auto'));
        $this->addColumn('{{%wizard_drafts}}', 'items_summary', $this->string(255)->null()->after('draft_label'));

        $this->createIndex('idx-wizard_drafts-user_key_auto', '{{%wizard_drafts}}', ['user_id', 'draft_key', 'is_auto']);
    }

    public function safeDown()
    {
        $this->dropIndex('idx-wizard_drafts-user_key_auto', '{{%wizard_drafts}}');
        $this->dropColumn('{{%wizard_drafts}}', 'items_summary');
        $this->dropColumn('{{%wizard_drafts}}', 'draft_label');
        $this->dropColumn('{{%wizard_drafts}}', 'is_auto');
        $this->createIndex('idx-wizard_drafts-user_key', '{{%wizard_drafts}}', ['user_id', 'draft_key'], true);
    }
}
