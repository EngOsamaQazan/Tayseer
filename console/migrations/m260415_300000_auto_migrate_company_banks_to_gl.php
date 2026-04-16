<?php

use yii\db\Migration;

/**
 * هجرة ذكية: سحب كل بنوك الشركات من os_company_banks + os_bancks
 * وإنشاء حسابات فرعية (leaf) تحت 1101 تلقائياً مع ربط account_id.
 *
 * - لا يُكرّر الإنشاء إذا كان account_id مربوطاً مسبقاً
 * - يطبع تقريراً بالحسابات المنشأة في الـ console
 */
class m260415_300000_auto_migrate_company_banks_to_gl extends Migration
{
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

        $highestCode = $db->createCommand(
            "SELECT MAX(code) FROM {{%accounts}} WHERE parent_id = :pid",
            [':pid' => $parentId]
        )->queryScalar();

        $nextSeq = $highestCode ? ((int) substr($highestCode, 4)) + 1 : 1;

        $companyBanks = $db->createCommand("
            SELECT cb.id       AS cb_id,
                   cb.bank_id,
                   cb.bank_number,
                   cb.iban_number,
                   cb.company_id,
                   cb.account_id,
                   b.name      AS bank_name,
                   c.name      AS company_name
            FROM   {{%company_banks}} cb
            LEFT JOIN {{%bancks}}    b ON b.id = cb.bank_id
            LEFT JOIN {{%companies}} c ON c.id = cb.company_id
            WHERE  cb.is_deleted = 0
            ORDER BY cb.company_id, cb.id
        ")->queryAll();

        if (empty($companyBanks)) {
            echo "  No company banks found — nothing to migrate.\n";
            return;
        }

        $created = [];

        foreach ($companyBanks as $row) {
            if (!empty($row['account_id'])) {
                echo "  SKIP cb#{$row['cb_id']} — already linked to account_id={$row['account_id']}\n";
                continue;
            }

            $bankName    = $row['bank_name'] ?: 'بنك';
            $companyName = $row['company_name'] ?: '';
            $nameAr      = $bankName . ($companyName ? ' — ' . $companyName : '');
            $nameEn      = 'Bank #' . $row['bank_number'];
            $code        = '1101' . str_pad($nextSeq, 2, '0', STR_PAD_LEFT);

            $exists = $db->createCommand(
                "SELECT 1 FROM {{%accounts}} WHERE code = :code",
                [':code' => $code]
            )->queryScalar();
            if ($exists) {
                $nextSeq++;
                $code = '1101' . str_pad($nextSeq, 2, '0', STR_PAD_LEFT);
            }

            $description = '';
            if ($row['bank_number']) {
                $description .= 'رقم الحساب: ' . $row['bank_number'];
            }
            if ($row['iban_number']) {
                $description .= ($description ? ' | ' : '') . 'IBAN: ' . $row['iban_number'];
            }

            $this->insert('{{%accounts}}', [
                'code'            => $code,
                'name_ar'         => $nameAr,
                'name_en'         => $nameEn,
                'parent_id'       => $parentId,
                'type'            => 'assets',
                'nature'          => 'debit',
                'level'           => $childLevel,
                'is_parent'       => 0,
                'is_active'       => 1,
                'company_id'      => $row['company_id'],
                'opening_balance' => 0,
                'description'     => $description ?: null,
                'created_at'      => $now,
                'updated_at'      => $now,
            ]);

            $newAccountId = $db->getLastInsertID();

            $this->update(
                '{{%company_banks}}',
                ['account_id' => $newAccountId],
                ['id' => $row['cb_id']]
            );

            $created[] = [
                'code'      => $code,
                'name'      => $nameAr,
                'bank_num'  => $row['bank_number'],
                'company'   => $companyName,
                'cb_id'     => $row['cb_id'],
                'account_id'=> $newAccountId,
            ];

            $nextSeq++;
        }

        echo "\n  ══════════════════════════════════════════\n";
        echo "  تقرير الهجرة — حسابات GL المنشأة تلقائياً\n";
        echo "  ══════════════════════════════════════════\n";
        if (empty($created)) {
            echo "  لم يتم إنشاء أي حسابات (جميعها مربوطة مسبقاً).\n";
        } else {
            echo sprintf("  %-8s %-30s %-20s %-15s\n", 'الكود', 'اسم الحساب', 'الشركة', 'رقم البنك');
            echo "  " . str_repeat('─', 75) . "\n";
            foreach ($created as $r) {
                echo sprintf("  %-8s %-30s %-20s %-15s\n",
                    $r['code'],
                    mb_substr($r['name'], 0, 28),
                    mb_substr($r['company'], 0, 18),
                    $r['bank_num']
                );
            }
            echo "\n  Total: " . count($created) . " account(s) created.\n";
        }
    }

    public function safeDown()
    {
        $db = Yii::$app->db;

        $cashParent = $db->createCommand(
            "SELECT id FROM {{%accounts}} WHERE code = '1101'"
        )->queryOne();

        if (!$cashParent) {
            return;
        }

        $childIds = $db->createCommand(
            "SELECT id FROM {{%accounts}} WHERE parent_id = :pid",
            [':pid' => $cashParent['id']]
        )->queryColumn();

        if (!empty($childIds)) {
            $this->update(
                '{{%company_banks}}',
                ['account_id' => null],
                ['account_id' => $childIds]
            );
            $this->delete('{{%accounts}}', ['id' => $childIds]);
        }
    }
}
