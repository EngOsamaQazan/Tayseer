<?php

namespace backend\services;

use Yii;
use backend\modules\diwan\models\DiwanCorrespondence;
use backend\models\JudiciaryDeadline;
use backend\models\JudiciarySeizedAsset;

/**
 * All correspondence creation goes through this service.
 * Enforces business rules and triggers side effects (deadlines, assets).
 */
class DiwanCorrespondenceService
{
    /** @var JudiciaryDeadlineService */
    private $deadlineService;

    public function __construct()
    {
        $this->deadlineService = new JudiciaryDeadlineService();
    }

    /**
     * Create a notification record for a defendant.
     */
    public function createNotification(int $judiciaryId, int $customerId, array $data): DiwanCorrespondence
    {
        $model = new DiwanCorrespondence();
        $model->communication_type = DiwanCorrespondence::TYPE_NOTIFICATION;
        $model->related_module = 'judiciary';
        $model->related_record_id = $judiciaryId;
        $model->customer_id = $customerId;
        $model->direction = 'outgoing';
        $model->recipient_type = DiwanCorrespondence::RECIPIENT_DEFENDANT;
        $model->attributes = $data;
        $model->company_id = $data['company_id'] ?? (Yii::$app->user->identity->company_id ?? null);

        if (!$model->save()) {
            throw new \yii\base\UserException(implode(', ', $model->getFirstErrors()));
        }

        if ($model->notification_result === 'delivered' && $model->delivery_date) {
            $this->deadlineService->createNotificationPeriodDeadline($model->id);
        }

        return $model;
    }

    /**
     * Create an outgoing letter to an authority/bank/employer.
     */
    public function createOutgoingLetter(int $judiciaryId, string $recipientType, int $recipientId, array $data): DiwanCorrespondence
    {
        $model = new DiwanCorrespondence();
        $model->communication_type = DiwanCorrespondence::TYPE_OUTGOING_LETTER;
        $model->related_module = 'judiciary';
        $model->related_record_id = $judiciaryId;
        $model->direction = 'outgoing';
        $model->recipient_type = $recipientType;
        $model->attributes = $data;
        $model->company_id = $data['company_id'] ?? (Yii::$app->user->identity->company_id ?? null);

        switch ($recipientType) {
            case DiwanCorrespondence::RECIPIENT_BANK:
                $model->bank_id = $recipientId;
                break;
            case DiwanCorrespondence::RECIPIENT_EMPLOYER:
                $model->job_id = $recipientId;
                break;
            case DiwanCorrespondence::RECIPIENT_ADMINISTRATIVE:
                $model->authority_id = $recipientId;
                break;
        }

        if (!$model->save()) {
            throw new \yii\base\UserException(implode(', ', $model->getFirstErrors()));
        }

        $this->deadlineService->createCorrespondenceResponseDeadline($model->id);

        return $model;
    }

    /**
     * Record an incoming response to an existing outgoing letter.
     */
    public function recordIncomingResponse(int $parentLetterId, array $data): DiwanCorrespondence
    {
        $parent = DiwanCorrespondence::findOne($parentLetterId);
        if (!$parent || $parent->communication_type !== DiwanCorrespondence::TYPE_OUTGOING_LETTER) {
            throw new \yii\base\UserException('الكتاب الأصلي غير موجود أو ليس كتاباً صادراً');
        }

        $model = new DiwanCorrespondence();
        $model->communication_type = DiwanCorrespondence::TYPE_INCOMING_RESPONSE;
        $model->related_module = $parent->related_module;
        $model->related_record_id = $parent->related_record_id;
        $model->customer_id = $parent->customer_id;
        $model->direction = 'incoming';
        $model->parent_id = $parentLetterId;
        $model->recipient_type = $parent->recipient_type;
        $model->bank_id = $parent->bank_id;
        $model->job_id = $parent->job_id;
        $model->authority_id = $parent->authority_id;
        $model->attributes = $data;
        $model->company_id = $parent->company_id;

        if (!$model->save()) {
            throw new \yii\base\UserException(implode(', ', $model->getFirstErrors()));
        }

        $parent->status = DiwanCorrespondence::STATUS_RESPONDED;
        $parent->save(false, ['status']);

        $this->handleResponseSideEffects($model, $parent);

        return $model;
    }

    /**
     * Handle side effects based on response result (auto-create assets, deadlines).
     */
    private function handleResponseSideEffects(DiwanCorrespondence $response, DiwanCorrespondence $parent)
    {
        if (!$parent->related_record_id) {
            return;
        }

        $judiciaryId = $parent->related_record_id;
        $customerId = $parent->customer_id;

        if ($response->response_result === 'seized' && $customerId) {
            $assetType = $this->mapPurposeToAssetType($parent->purpose);
            if ($assetType) {
                $asset = new JudiciarySeizedAsset([
                    'judiciary_id' => $judiciaryId,
                    'customer_id' => $customerId,
                    'asset_type' => $assetType,
                    'status' => JudiciarySeizedAsset::STATUS_SEIZED,
                    'authority_id' => $parent->authority_id,
                    'correspondence_id' => $response->id,
                    'amount' => $response->response_amount,
                ]);
                $asset->save(false);
            }
        }

        if ($response->response_result === 'insufficient') {
            $this->deadlineService->createSalaryRetryDeadline($response->id);
        }
    }

    private function mapPurposeToAssetType(?string $purpose): ?string
    {
        $map = [
            'vehicle_seizure'  => JudiciarySeizedAsset::ASSET_VEHICLE,
            'property_seizure' => JudiciarySeizedAsset::ASSET_REAL_ESTATE,
            'account_freeze'   => JudiciarySeizedAsset::ASSET_BANK_ACCOUNT,
            'salary_deduction' => JudiciarySeizedAsset::ASSET_SALARY,
            'shares_freeze'    => JudiciarySeizedAsset::ASSET_SHARES,
            'e_payment_freeze' => JudiciarySeizedAsset::ASSET_E_PAYMENT,
            'vehicle_arrest'   => JudiciarySeizedAsset::ASSET_VEHICLE,
        ];
        return $map[$purpose] ?? null;
    }

    /**
     * Get suggested next actions based on a response result.
     */
    public static function getSuggestedActions(string $responseResult): array
    {
        $map = [
            'seized'       => ['ضبط مركبة', 'تخمين', 'بيع بالمزاد العلني'],
            'not_found'    => ['متابعة حجوزات أخرى'],
            'insufficient' => ['انتظار 3 أشهر', 'إعادة إصدار كتاب'],
            'unemployed'   => ['انتظار', 'متابعة حجوزات أخرى'],
            'transferred'  => ['تحويل أرصدة لحساب الدعوى'],
            'no_response'  => ['إعادة إصدار كتاب'],
        ];
        return $map[$responseResult] ?? [];
    }
}
