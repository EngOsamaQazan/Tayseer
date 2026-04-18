<?php

namespace backend\modules\customers\models;

use Yii;
use yii\db\Expression;

/**
 * ActiveRecord for `os_customer_ss_statements`.
 *
 * One row per Social Security statement uploaded for a customer. Children
 * (subscription periods + yearly salaries) are stored in dedicated tables
 * and cascade on delete.
 *
 * @property int         $id
 * @property int         $customer_id
 * @property int|null    $media_id
 * @property string|null $statement_date              YYYY-MM-DD
 * @property string|null $social_security_number
 * @property string|null $national_id_number
 * @property string|null $join_date
 * @property string|null $subjection_salary
 * @property int|null    $current_employer_id
 * @property string|null $current_employer_no
 * @property string|null $current_employer_name
 * @property string|null $subjection_employer_name
 * @property int|null    $latest_salary_year
 * @property string|null $latest_monthly_salary
 * @property int|null    $total_subscription_months
 * @property bool        $active_subscription
 * @property bool        $is_current
 * @property string|null $extracted_payload
 * @property int|null    $created_by
 * @property string      $created_at
 * @property string      $updated_at
 *
 * @property CustomerSsSubscription[] $subscriptions
 * @property CustomerSsSalary[]       $salaries
 */
class CustomerSsStatement extends \yii\db\ActiveRecord
{
    public static function tableName()
    {
        return '{{%customer_ss_statements}}';
    }

    public function rules()
    {
        return [
            [['customer_id'], 'required'],
            [['customer_id', 'media_id', 'current_employer_id', 'latest_salary_year',
              'total_subscription_months', 'created_by'], 'integer'],
            [['statement_date', 'join_date'], 'date', 'format' => 'php:Y-m-d'],
            [['subjection_salary', 'latest_monthly_salary'], 'number'],
            [['active_subscription', 'is_current'], 'boolean'],
            [['social_security_number', 'national_id_number'], 'string', 'max' => 32],
            [['current_employer_no'], 'string', 'max' => 64],
            [['current_employer_name', 'subjection_employer_name'], 'string', 'max' => 255],
            [['extracted_payload'], 'string'],
        ];
    }

    public function getSubscriptions()
    {
        return $this->hasMany(CustomerSsSubscription::class, ['statement_id' => 'id'])
            ->orderBy(['sort_order' => SORT_ASC, 'from_date' => SORT_DESC]);
    }

    public function getSalaries()
    {
        return $this->hasMany(CustomerSsSalary::class, ['statement_id' => 'id'])
            ->orderBy(['year' => SORT_DESC]);
    }

    /**
     * Decode the stored extracted payload (JSON). Returns [] on any error.
     */
    public function getExtractedArray(): array
    {
        if (empty($this->extracted_payload)) {
            return [];
        }
        $data = json_decode($this->extracted_payload, true);
        return is_array($data) ? $data : [];
    }

    // ───────────────────────── Public API ─────────────────────────

    /**
     * Persist a freshly-extracted SS statement (typically from the wizard).
     *
     * Behaviour:
     *   • If a statement with the same `statement_date` already exists for
     *     this customer, it is updated in place (subscriptions + salaries
     *     are wiped and re-inserted from the new payload). This makes the
     *     operation idempotent — the user can safely re-upload the same
     *     statement.
     *   • Otherwise, a new statement row is inserted.
     *   • After insert/update, `is_current` is recomputed for the customer:
     *     only the row with the most recent `statement_date` is flagged.
     *   • If the statement carries no `statement_date`, the upload time is
     *     used as a fallback (so the row still gets a meaningful ordering
     *     value).
     *
     * @param int        $customerId  os_customers.id
     * @param int|null   $mediaId     os_ImageManager.id of the uploaded file
     * @param array      $extracted   normalised array from VisionService
     * @param int|null   $createdBy   user id (defaults to current user)
     * @return self|null              the saved/updated statement, or null on
     *                                fatal error (errors are logged).
     */
    public static function saveExtracted(
        int $customerId,
        ?int $mediaId,
        array $extracted,
        ?int $createdBy = null
    ): ?self {
        if ($customerId <= 0) {
            return null;
        }
        if (empty($extracted)) {
            return null;
        }

        $statementDate = self::sanitizeDate($extracted['statement_date'] ?? null)
            ?? date('Y-m-d');

        $createdBy = $createdBy ?? (Yii::$app->has('user') ? (int)Yii::$app->user->id : null);

        $db = Yii::$app->db;
        $tx = $db->beginTransaction();

        try {
            $stmt = self::findOne([
                'customer_id'    => $customerId,
                'statement_date' => $statementDate,
            ]) ?? new self([
                'customer_id'    => $customerId,
                'statement_date' => $statementDate,
                'created_by'     => $createdBy,
            ]);

            $stmt->setAttributes(self::pluckHeader($extracted, $mediaId), false);

            // Always store the raw normalised payload — useful for audit and
            // for re-rendering the original UI summary if needed.
            $stmt->extracted_payload = json_encode($extracted, JSON_UNESCAPED_UNICODE);

            if (!$stmt->save()) {
                Yii::error('CustomerSsStatement save failed: '
                    . print_r($stmt->getErrors(), true), __METHOD__);
                $tx->rollBack();
                return null;
            }

            // ── Children: wipe + reinsert (clean + simple). ──
            CustomerSsSubscription::deleteAll(['statement_id' => $stmt->id]);
            CustomerSsSalary::deleteAll(['statement_id' => $stmt->id]);

            self::insertSubscriptions($db, $stmt, $extracted['subscription_periods'] ?? []);
            self::insertSalaries($db, $stmt, $extracted['salary_history'] ?? []);

            // ── Recompute `is_current` for the customer. ──
            self::recomputeCurrent($db, $customerId);

            $tx->commit();
            return self::findOne($stmt->id);
        } catch (\Throwable $e) {
            $tx->rollBack();
            Yii::error('CustomerSsStatement::saveExtracted threw: ' . $e->getMessage()
                . "\n" . $e->getTraceAsString(), __METHOD__);
            return null;
        }
    }

    /**
     * Find the most recent statement for a customer (the "current" snapshot).
     */
    public static function findCurrentForCustomer(int $customerId): ?self
    {
        if ($customerId <= 0) return null;

        return self::find()
            ->where(['customer_id' => $customerId])
            ->orderBy(['is_current' => SORT_DESC, 'statement_date' => SORT_DESC, 'id' => SORT_DESC])
            ->limit(1)
            ->one();
    }

    /**
     * Count all statements stored for a customer.
     */
    public static function countForCustomer(int $customerId): int
    {
        if ($customerId <= 0) return 0;
        return (int)self::find()->where(['customer_id' => $customerId])->count();
    }

    // ───────────────────────── Internals ─────────────────────────

    /**
     * Map the extracted payload's header fields → AR attributes.
     */
    private static function pluckHeader(array $extracted, ?int $mediaId): array
    {
        return [
            'media_id'                  => $mediaId ?: null,
            'social_security_number'    => self::str($extracted['social_security_number'] ?? null, 32),
            'national_id_number'        => self::str($extracted['id_number'] ?? null, 32),
            'join_date'                 => self::sanitizeDate($extracted['join_date'] ?? null),
            'subjection_salary'         => self::num($extracted['subjection_salary'] ?? null),
            'current_employer_no'       => self::str($extracted['current_employer_number'] ?? null, 64),
            'current_employer_name'     => self::str($extracted['current_employer'] ?? null, 255),
            'subjection_employer_name'  => self::str($extracted['subjection_employer'] ?? null, 255),
            'latest_salary_year'        => self::int($extracted['latest_salary_year'] ?? null),
            'latest_monthly_salary'     => self::num($extracted['latest_monthly_salary'] ?? null),
            'total_subscription_months' => self::int($extracted['total_subscription_months'] ?? null),
            'active_subscription'       => !empty($extracted['active_subscription']),
        ];
    }

    private static function insertSubscriptions(\yii\db\Connection $db, self $stmt, array $rows): void
    {
        if (empty($rows)) return;

        $batch  = [];
        $cols   = ['statement_id', 'customer_id', 'from_date', 'to_date', 'salary',
                   'reason', 'establishment_no', 'establishment_name', 'months', 'sort_order'];

        foreach (array_values($rows) as $i => $row) {
            if (!is_array($row)) continue;
            $batch[] = [
                $stmt->id,
                $stmt->customer_id,
                self::sanitizeDate($row['from'] ?? null),
                self::sanitizeDate($row['to']   ?? null),
                self::num($row['salary'] ?? null),
                self::str($row['reason'] ?? null, 64),
                self::str($row['establishment_no'] ?? null, 64),
                self::str($row['establishment_name'] ?? null, 255),
                self::int($row['months'] ?? null),
                $i,
            ];
        }

        if ($batch) {
            $db->createCommand()->batchInsert(
                CustomerSsSubscription::tableName(),
                $cols,
                $batch
            )->execute();
        }
    }

    private static function insertSalaries(\yii\db\Connection $db, self $stmt, array $rows): void
    {
        if (empty($rows)) return;

        $batch = [];
        $cols  = ['statement_id', 'customer_id', 'year', 'salary',
                  'establishment_no', 'establishment_name'];

        foreach ($rows as $row) {
            if (!is_array($row)) continue;
            $year = self::int($row['year'] ?? null);
            if ($year === null) continue; // year is required
            $batch[] = [
                $stmt->id,
                $stmt->customer_id,
                $year,
                self::num($row['salary'] ?? null),
                self::str($row['establishment_no'] ?? null, 64),
                self::str($row['establishment_name'] ?? null, 255),
            ];
        }

        if ($batch) {
            $db->createCommand()->batchInsert(
                CustomerSsSalary::tableName(),
                $cols,
                $batch
            )->execute();
        }
    }

    /**
     * For a given customer: flip the is_current flag so only the row with
     * the most recent statement_date carries it. Tie-break by `id` (last
     * inserted wins).
     */
    private static function recomputeCurrent(\yii\db\Connection $db, int $customerId): void
    {
        $tableQuoted = $db->quoteTableName(self::tableName());

        // Reset all to 0.
        $db->createCommand()->update(
            self::tableName(),
            ['is_current' => 0],
            ['customer_id' => $customerId]
        )->execute();

        // Find newest by (statement_date DESC, id DESC).
        $newest = self::find()
            ->where(['customer_id' => $customerId])
            ->orderBy(['statement_date' => SORT_DESC, 'id' => SORT_DESC])
            ->limit(1)
            ->one();

        if ($newest !== null) {
            $db->createCommand()->update(
                self::tableName(),
                ['is_current' => 1],
                ['id' => $newest->id]
            )->execute();
        }
    }

    // ── tiny coercion helpers ──

    private static function str($v, int $max): ?string
    {
        if ($v === null || $v === '') return null;
        $s = trim((string)$v);
        if ($s === '') return null;
        return mb_substr($s, 0, $max);
    }

    private static function int($v): ?int
    {
        if ($v === null || $v === '') return null;
        if (is_numeric($v)) return (int)$v;
        return null;
    }

    private static function num($v)
    {
        if ($v === null || $v === '') return null;
        if (is_numeric($v)) return $v;
        return null;
    }

    private static function sanitizeDate($v): ?string
    {
        if ($v === null || $v === '') return null;
        $s = trim((string)$v);
        if ($s === '' || strtolower($s) === 'null') return null;
        // Accept already-formatted YYYY-MM-DD; otherwise try to parse.
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $s)) {
            return $s;
        }
        $ts = strtotime($s);
        if ($ts === false) return null;
        return date('Y-m-d', $ts);
    }
}
