<?php

use yii\db\Migration;

class m260312_100007_add_dual_stage_to_judiciary extends Migration
{
    public function safeUp()
    {
        $table = $this->db->getTableSchema('{{%judiciary}}', true);
        if ($table && !isset($table->columns['furthest_stage'])) {
            $this->addColumn('{{%judiciary}}', 'furthest_stage', $this->string(30)->defaultValue('case_preparation')->after('case_status'));
            try { $this->createIndex('idx-judiciary-furthest_stage', '{{%judiciary}}', 'furthest_stage'); } catch (\Exception $e) {}
        }
        if ($table && !isset($table->columns['bottleneck_stage'])) {
            $this->addColumn('{{%judiciary}}', 'bottleneck_stage', $this->string(30)->defaultValue('case_preparation')->after('furthest_stage'));
            try { $this->createIndex('idx-judiciary-bottleneck_stage', '{{%judiciary}}', 'bottleneck_stage'); } catch (\Exception $e) {}
        }
    }

    public function safeDown()
    {
        $this->dropIndex('idx-judiciary-bottleneck_stage', '{{%judiciary}}');
        $this->dropIndex('idx-judiciary-furthest_stage', '{{%judiciary}}');
        $this->dropColumn('{{%judiciary}}', 'bottleneck_stage');
        $this->dropColumn('{{%judiciary}}', 'furthest_stage');
    }
}
