<?php

use yii\db\Migration;

class m260223_120000_add_postal_pluscode_to_jobs extends Migration
{
    public function safeUp()
    {
        $this->addColumn('{{%jobs}}', 'postal_code', $this->string(20)->null()->after('address_building'));
        $this->addColumn('{{%jobs}}', 'plus_code', $this->string(20)->null()->after('postal_code'));
    }

    public function safeDown()
    {
        $this->dropColumn('{{%jobs}}', 'plus_code');
        $this->dropColumn('{{%jobs}}', 'postal_code');
    }
}
