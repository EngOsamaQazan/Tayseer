<?php

use yii\db\Migration;

class m260216_180000_add_documents_to_companies_table extends Migration
{
    public function safeUp()
    {
        $this->addColumn('{{%companies}}', 'commercial_register', $this->text()->null());
        $this->addColumn('{{%companies}}', 'trade_license', $this->text()->null());
    }

    public function safeDown()
    {
        $this->dropColumn('{{%companies}}', 'commercial_register');
        $this->dropColumn('{{%companies}}', 'trade_license');
    }
}
