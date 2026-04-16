<?php

use yii\db\Migration;

/**
 * تحويل حساب 1101 (النقدية والبنوك) من حساب طرفي إلى حساب رئيسي
 * وإنشاء حسابات فرعية (صناديق) تحته حسب المعايير المحاسبية.
 */
class m260415_100000_setup_cash_fund_sub_accounts extends Migration
{
    public function safeUp()
    {
        $db = Yii::$app->db;
        $cashRow = $db->createCommand("SELECT id, level FROM {{%accounts}} WHERE code = '1101'")->queryOne();

        if (!$cashRow) {
            echo "Account 1101 not found — skipping.\n";
            return;
        }

        $parentId = $cashRow['id'];
        $childLevel = (int) $cashRow['level'] + 1;

        $this->update('{{%accounts}}', ['is_parent' => 1], ['id' => $parentId]);

        $now = time();
        $funds = [
            ['code' => '110101', 'name_ar' => 'صندوق النقد الرئيسي',       'name_en' => 'Main Cash Box'],
            ['code' => '110102', 'name_ar' => 'صندوق نقد المحل',           'name_en' => 'Store Cash Box'],
            ['code' => '110103', 'name_ar' => 'صندوق السلف والعهد',        'name_en' => 'Petty Cash / Advances'],
            ['code' => '110104', 'name_ar' => 'حساب بنكي رئيسي',          'name_en' => 'Primary Bank Account'],
        ];

        foreach ($funds as $fund) {
            $exists = $db->createCommand("SELECT 1 FROM {{%accounts}} WHERE code = :code", [':code' => $fund['code']])->queryScalar();
            if ($exists) {
                continue;
            }
            $this->insert('{{%accounts}}', [
                'code'            => $fund['code'],
                'name_ar'         => $fund['name_ar'],
                'name_en'         => $fund['name_en'],
                'parent_id'       => $parentId,
                'type'            => 'assets',
                'nature'          => 'debit',
                'level'           => $childLevel,
                'is_parent'       => 0,
                'is_active'       => 1,
                'company_id'      => null,
                'opening_balance' => 0,
                'created_at'      => $now,
                'updated_at'      => $now,
            ]);
        }
    }

    public function safeDown()
    {
        $this->delete('{{%accounts}}', ['code' => ['110101', '110102', '110103', '110104']]);
        $this->update('{{%accounts}}', ['is_parent' => 0], ['code' => '1101']);
    }
}
