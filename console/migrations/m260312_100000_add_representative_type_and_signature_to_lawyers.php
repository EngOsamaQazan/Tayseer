<?php

use yii\db\Migration;

class m260312_100000_add_representative_type_and_signature_to_lawyers extends Migration
{
    public function safeUp()
    {
        $this->addColumn('{{%lawyers}}', 'representative_type', $this->string(20)->defaultValue('delegate')->after('notes'));
        $this->addColumn('{{%lawyers}}', 'signature_image', $this->string(500)->null()->after('representative_type'));
    }

    public function safeDown()
    {
        $this->dropColumn('{{%lawyers}}', 'signature_image');
        $this->dropColumn('{{%lawyers}}', 'representative_type');
    }
}
