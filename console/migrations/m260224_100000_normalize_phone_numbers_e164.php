<?php

use yii\db\Migration;

/**
 * Normalizes all phone numbers in the database to E.164 format (+962XXXXXXXXX).
 * Handles: customers.primary_phone_number, phone_numbers.phone_number
 */
class m260224_100000_normalize_phone_numbers_e164 extends Migration
{
    public function safeUp()
    {
        // 1) Normalize customers.primary_phone_number
        $rows = (new \yii\db\Query())
            ->select(['id', 'primary_phone_number'])
            ->from('{{%customers}}')
            ->where(['not', ['primary_phone_number' => null]])
            ->andWhere(['not', ['primary_phone_number' => '']])
            ->all();

        $updated = 0;
        foreach ($rows as $row) {
            $original = $row['primary_phone_number'];
            $e164 = $this->convertToE164($original);
            if ($e164 !== $original && !empty($e164)) {
                $this->db->createCommand()->update('{{%customers}}', [
                    'primary_phone_number' => $e164,
                ], ['id' => $row['id']])->execute();
                $updated++;
            }
        }
        echo "    > Normalized $updated / " . count($rows) . " customer primary phone numbers.\n";

        // 2) Normalize phone_numbers.phone_number
        $rows2 = (new \yii\db\Query())
            ->select(['id', 'phone_number'])
            ->from('{{%phone_numbers}}')
            ->where(['not', ['phone_number' => null]])
            ->andWhere(['not', ['phone_number' => '']])
            ->all();

        $updated2 = 0;
        foreach ($rows2 as $row) {
            $original = $row['phone_number'];
            $e164 = $this->convertToE164($original);
            if ($e164 !== $original && !empty($e164)) {
                $this->db->createCommand()->update('{{%phone_numbers}}', [
                    'phone_number' => $e164,
                ], ['id' => $row['id']])->execute();
                $updated2++;
            }
        }
        echo "    > Normalized $updated2 / " . count($rows2) . " additional phone numbers.\n";
    }

    public function safeDown()
    {
        echo "    > This migration cannot be reverted (data transformation).\n";
        return false;
    }

    private function convertToE164($raw)
    {
        if (empty($raw)) return '';

        $digits = preg_replace('/[^\d]/', '', $raw);
        if (empty($digits)) return $raw;

        // Already E.164
        if (strpos($raw, '+') === 0 && strlen($digits) >= 7) {
            return '+' . $digits;
        }

        // International with 00 prefix
        if (substr($digits, 0, 2) === '00') {
            return '+' . substr($digits, 2);
        }

        // Already has country code (962XXXXXXXXX)
        if (substr($digits, 0, 3) === '962' && strlen($digits) >= 12) {
            return '+' . $digits;
        }

        // Local Jordanian format 07XXXXXXXX
        if (substr($digits, 0, 2) === '07' && strlen($digits) === 10) {
            return '+962' . substr($digits, 1);
        }

        // Local without leading zero (7XXXXXXXX)
        if (substr($digits, 0, 1) === '7' && strlen($digits) === 9) {
            return '+962' . $digits;
        }

        // Other known country codes
        $countryCodes = ['970', '966', '964', '963', '961', '971'];
        foreach ($countryCodes as $code) {
            if (strpos($digits, $code) === 0 && strlen($digits) > strlen($code) + 5) {
                return '+' . $digits;
            }
        }
        if (substr($digits, 0, 2) === '20' && strlen($digits) >= 12) {
            return '+' . $digits;
        }

        // Fallback: assume Jordanian
        if (strlen($digits) === 10 && substr($digits, 0, 1) === '0') {
            return '+962' . substr($digits, 1);
        }

        return $raw;
    }
}
