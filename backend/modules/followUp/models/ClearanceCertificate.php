<?php

namespace backend\modules\followUp\models;

use Yii;
use yii\db\ActiveRecord;
use yii\behaviors\TimestampBehavior;
use yii\behaviors\BlameableBehavior;

/**
 * Issued clearance certificate ("براءة ذمة") — immutable per row.
 *
 * @property int    $id
 * @property string $cert_number
 * @property int    $contract_id
 * @property int|null $company_id
 * @property string $issued_at
 * @property int|null $issued_by
 * @property string $signature
 * @property string $snapshot_json
 * @property string $status active|revoked
 * @property string|null $revoked_at
 * @property int|null $revoked_by
 * @property int    $created_at
 * @property int    $updated_at
 * @property int    $is_deleted
 */
class ClearanceCertificate extends ActiveRecord
{
    const STATUS_ACTIVE  = 'active';
    const STATUS_REVOKED = 'revoked';

    public static function tableName()
    {
        return '{{%clearance_certificates}}';
    }

    public function behaviors()
    {
        return [
            [
                'class' => TimestampBehavior::class,
                'attributes' => [
                    ActiveRecord::EVENT_BEFORE_INSERT => ['created_at', 'updated_at'],
                    ActiveRecord::EVENT_BEFORE_UPDATE => ['updated_at'],
                ],
            ],
            [
                'class' => BlameableBehavior::class,
                'attributes' => [
                    ActiveRecord::EVENT_BEFORE_INSERT => 'issued_by',
                ],
            ],
        ];
    }

    public function rules()
    {
        return [
            [['cert_number', 'contract_id', 'issued_at', 'signature', 'snapshot_json'], 'required'],
            [['contract_id', 'company_id', 'issued_by', 'revoked_by', 'created_at', 'updated_at', 'is_deleted'], 'integer'],
            [['issued_at', 'revoked_at'], 'safe'],
            [['snapshot_json'], 'string'],
            [['cert_number'], 'string', 'max' => 32],
            [['signature'], 'string', 'max' => 64],
            [['status'], 'in', 'range' => [self::STATUS_ACTIVE, self::STATUS_REVOKED]],
            [['cert_number'], 'unique'],
        ];
    }

    /**
     * Latest non-deleted certificate for a contract — regardless of active/revoked.
     * Used by the dispatcher to decide preview vs issued view.
     */
    public static function getLatestForContract($contractId)
    {
        return static::find()
            ->where(['contract_id' => (int) $contractId, 'is_deleted' => 0])
            ->orderBy(['id' => SORT_DESC])
            ->one();
    }

    /**
     * Latest still-valid (status=active) certificate — used to block re-issue.
     */
    public static function getLatestActiveForContract($contractId)
    {
        return static::find()
            ->where([
                'contract_id' => (int) $contractId,
                'status'      => self::STATUS_ACTIVE,
                'is_deleted'  => 0,
            ])
            ->orderBy(['id' => SORT_DESC])
            ->one();
    }

    /**
     * Compute HMAC-SHA256 over "contract_id|cert_number|issued_date".
     * Shared helper so controller and model never diverge.
     */
    public static function buildSignature($contractId, $certNumber, $issuedDate)
    {
        $secret = Yii::$app->params['statementVerifySecret']
            ?? 'tayseer-statement-verify-default';
        $payload = $contractId . '|' . $certNumber . '|' . $issuedDate;
        return hash_hmac('sha256', $payload, $secret);
    }

    /**
     * Verify a supplied signature against this row.
     */
    public function isSignatureValid($providedSignature)
    {
        $expected = self::buildSignature(
            (int) $this->contract_id,
            (string) $this->cert_number,
            substr((string) $this->issued_at, 0, 10)
        );
        return hash_equals($expected, (string) $providedSignature);
    }

    /**
     * Allocate the next certificate number in the form CLR-YYYY-NNNNN.
     * Numbering is yearly: the serial resets each Gregorian year.
     */
    public static function generateNextNumber()
    {
        $year   = (int) date('Y');
        $prefix = 'CLR-' . $year . '-';

        $latest = (new \yii\db\Query())
            ->select('cert_number')
            ->from(static::tableName())
            ->where(['like', 'cert_number', $prefix . '%', false])
            ->orderBy(['id' => SORT_DESC])
            ->limit(1)
            ->scalar();

        $next = 1;
        if ($latest) {
            $tail = (int) substr($latest, strlen($prefix));
            if ($tail > 0) {
                $next = $tail + 1;
            }
        }

        return $prefix . str_pad((string) $next, 5, '0', STR_PAD_LEFT);
    }

    /**
     * Decoded snapshot array, or an empty array on parse failure.
     */
    public function getSnapshot()
    {
        $data = json_decode((string) $this->snapshot_json, true);
        return is_array($data) ? $data : [];
    }

    public function isRevoked()
    {
        return $this->status === self::STATUS_REVOKED;
    }

    public function isActive()
    {
        return $this->status === self::STATUS_ACTIVE;
    }

    /**
     * Date portion of issued_at (YYYY-MM-DD) used for expiry comparison.
     */
    public function getIssuedDate()
    {
        return substr((string) $this->issued_at, 0, 10);
    }
}
