<?php

use yii\db\Migration;

/**
 * إنشاء حسابات صناديق النقد الأساسية تحت 1101.
 * هذه الحسابات تمثّل الصناديق الفعلية (كاش) وليست بنوك.
 * كل شركة تحتاج على الأقل صندوق نقد رئيسي.
 *
 * يتكامل مع migration سابق أنشأ حسابات البنوك (110101-110104).
 */
class m260415_300001_seed_cash_fund_accounts extends Migration
{
    private $funds = [
        ['code' => '110105', 'name_ar' => 'صندوق النقد الرئيسي',     'name_en' => 'Main Cash Box'],
        ['code' => '110106', 'name_ar' => 'صندوق نقد المحل - جدل',  'name_en' => 'Store Cash - Jadal'],
        ['code' => '110107', 'name_ar' => 'صندوق نقد المحل - نوران', 'name_en' => 'Store Cash - Noran'],
        ['code' => '110108', 'name_ar' => 'صندوق نقد المحل - اثمار', 'name_en' => 'Store Cash - Athmar'],
        ['code' => '110109', 'name_ar' => 'صندوق السلف والعهد',      'name_en' => 'Petty Cash / Advances'],
    ];

    public function safeUp()
    {
        $db = Yii::$app->db;

        $cashParent = $db->createCommand(
            "SELECT id, level FROM {{%accounts}} WHERE code = '1101' AND is_active = 1"
        )->queryOne();

        if (!$cashParent) {
            echo "  Account 1101 not found — skipping.\n";
            return;
        }

        $parentId   = (int) $cashParent['id'];
        $childLevel = (int) $cashParent['level'] + 1;
        $now        = time();
        $created    = 0;

        foreach ($this->funds as $fund) {
            $exists = $db->createCommand(
                "SELECT 1 FROM {{%accounts}} WHERE code = :code",
                [':code' => $fund['code']]
            )->queryScalar();

            if ($exists) {
                echo "  SKIP {$fund['code']} — already exists.\n";
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

            $created++;
            echo "  CREATED {$fund['code']} — {$fund['name_ar']}\n";
        }

        echo "\n  Total cash fund accounts created: {$created}\n";
    }

    public function safeDown()
    {
        $codes = array_column($this->funds, 'code');
        $this->delete('{{%accounts}}', ['code' => $codes]);
    }
}
