<?php

use yii\db\Migration;

class m260312_100001_create_judiciary_authorities_with_seed extends Migration
{
    public function safeUp()
    {
        if ($this->db->getTableSchema('{{%judiciary_authorities}}', true) !== null) {
            return;
        }

        $this->createTable('{{%judiciary_authorities}}', [
            'id' => $this->primaryKey(),
            'name' => $this->string(255)->notNull(),
            'authority_type' => $this->string(50)->notNull(),
            'notes' => $this->text()->null(),
            'is_deleted' => $this->tinyInteger()->defaultValue(0),
            'created_at' => $this->integer()->null(),
            'updated_at' => $this->integer()->null(),
            'created_by' => $this->integer()->null(),
            'company_id' => $this->integer()->null(),
        ]);

        $this->createIndex('idx-judiciary_authorities-type', '{{%judiciary_authorities}}', 'authority_type');
        $this->createIndex('idx-judiciary_authorities-company', '{{%judiciary_authorities}}', ['company_id', 'is_deleted']);

        $now = time();
        $this->batchInsert('{{%judiciary_authorities}}', ['name', 'authority_type', 'is_deleted', 'created_at', 'updated_at'], [
            ['دائرة الأراضي والمساحة', 'land', 0, $now, $now],
            ['إدارة ترخيص السواقين والمركبات', 'licensing', 0, $now, $now],
            ['دائرة مراقبة الشركات', 'companies_registry', 0, $now, $now],
            ['وزارة الصناعة والتجارة', 'industry_trade', 0, $now, $now],
            ['الأمن العام', 'security', 0, $now, $now],
            ['المحكمة الشرعية', 'court', 0, $now, $now],
            ['الضمان الاجتماعي', 'social_security', 0, $now, $now],
        ]);
    }

    public function safeDown()
    {
        $this->dropTable('{{%judiciary_authorities}}');
    }
}
