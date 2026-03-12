<?php

use yii\db\Migration;

class m260227_100001_add_is_legal_department_to_contracts extends Migration
{
    public function safeUp()
    {
        $this->addColumn('{{%contracts}}', 'is_legal_department', $this->tinyInteger(1)->notNull()->defaultValue(0)->after('status'));

        $this->update('{{%contracts}}', ['is_legal_department' => 1], ['status' => 'legal_department']);
    }

    public function safeDown()
    {
        $this->dropColumn('{{%contracts}}', 'is_legal_department');
    }
}
