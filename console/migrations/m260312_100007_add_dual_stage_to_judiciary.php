<?php

use yii\db\Migration;

class m260312_100007_add_dual_stage_to_judiciary extends Migration
{
    public function safeUp()
    {
        $this->addColumn('{{%judiciary}}', 'furthest_stage', $this->string(30)->defaultValue('case_preparation')->after('case_status'));
        $this->addColumn('{{%judiciary}}', 'bottleneck_stage', $this->string(30)->defaultValue('case_preparation')->after('furthest_stage'));

        $this->createIndex('idx-judiciary-furthest_stage', '{{%judiciary}}', 'furthest_stage');
        $this->createIndex('idx-judiciary-bottleneck_stage', '{{%judiciary}}', 'bottleneck_stage');
    }

    public function safeDown()
    {
        $this->dropIndex('idx-judiciary-bottleneck_stage', '{{%judiciary}}');
        $this->dropIndex('idx-judiciary-furthest_stage', '{{%judiciary}}');
        $this->dropColumn('{{%judiciary}}', 'bottleneck_stage');
        $this->dropColumn('{{%judiciary}}', 'furthest_stage');
    }
}
