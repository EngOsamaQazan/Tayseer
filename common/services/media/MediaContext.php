<?php

namespace common\services\media;

use Yii;

/**
 * Phase 1 / M1.3 — Immutable upload-context DTO.
 *
 * Carried into MediaService::store() / storeFromBase64() / storeFromPath()
 * by the calling controller. Concentrates everything we need to know
 * about WHO is uploading WHAT to WHERE in a single object so the
 * service signature stays compact and the call-sites read declaratively:
 *
 *     $ctx = MediaContext::forCustomer($customerId, '0_front', 'wizard')
 *         ->withAutoClassify(true);
 *     $result = Yii::$app->media->store($file, $ctx);
 *
 * Three reasons it is its own class instead of an associative array:
 *   1. Type-safety — IDE autocomplete catches typos like 'enityType'.
 *   2. Validation lives in the constructor, not scattered across
 *      every controller (was the source of two production bugs where
 *      a missing entityId silently stored an orphan).
 *   3. The `withXxx()` helpers make tests trivial: build a base
 *      context once, then only override the field under test.
 */
final class MediaContext
{
    public function __construct(
        public readonly string  $entityType,
        public readonly ?int    $entityId,
        public readonly string  $groupName,
        public readonly string  $uploadedVia,
        public readonly ?int    $userId,
        public readonly bool    $autoClassify   = false,
        public readonly ?string $originalName   = null,
        public readonly ?string $contractId     = null,  // legacy, written for back-compat
        public readonly ?int    $customerId     = null,  // legacy, written for back-compat
    ) {
        if ($entityType === '') {
            throw new \InvalidArgumentException('MediaContext: entityType is required');
        }
        if ($groupName === '') {
            throw new \InvalidArgumentException('MediaContext: groupName is required');
        }
        if ($uploadedVia === '') {
            throw new \InvalidArgumentException('MediaContext: uploadedVia is required');
        }
        // entityId being NULL is intentional — wizard uploads happen
        // BEFORE the customer is created. MediaService::adopt() fills
        // it later. We do NOT validate the (entity_type, entity_id)
        // pair here because GroupNameRegistry::validate() handles it
        // inside MediaService::store() with a richer error message.
    }

    // ─── Named constructors ────────────────────────────────────────
    // Keeps controller call-sites short and removes any chance of
    // mixing up positional arguments — there are 9 fields above and
    // they all happen to be strings/ints, which is a recipe for bugs.

    /** Wizard upload before the customer row exists yet. */
    public static function forWizardScan(string $groupName, ?int $userId = null): self
    {
        return new self(
            entityType:  'customer',
            entityId:    null,           // adopted later via MediaService::adoptOrphans()
            groupName:   $groupName,
            uploadedVia: 'wizard',
            userId:      $userId ?? self::currentUserId(),
            autoClassify: true,
        );
    }

    public static function forCustomer(int $customerId, string $groupName, string $via = 'smart_media'): self
    {
        return new self(
            entityType:  'customer',
            entityId:    $customerId,
            groupName:   $groupName,
            uploadedVia: $via,
            userId:      self::currentUserId(),
            customerId:  $customerId,
        );
    }

    public static function forContract(int $contractId, string $groupName, string $via = 'contract_form'): self
    {
        return new self(
            entityType:  'contract',
            entityId:    $contractId,
            groupName:   $groupName,
            uploadedVia: $via,
            userId:      self::currentUserId(),
            contractId:  (string)$contractId,
        );
    }

    public static function forLawyer(int $lawyerId, string $groupName = 'lawyer_photo'): self
    {
        return new self(
            entityType:  'lawyer',
            entityId:    $lawyerId,
            groupName:   $groupName,
            uploadedVia: 'lawyer_form',
            userId:      self::currentUserId(),
        );
    }

    public static function forEmployee(int $employeeId, string $groupName = 'employee_avatar'): self
    {
        return new self(
            entityType:  'employee',
            entityId:    $employeeId,
            groupName:   $groupName,
            uploadedVia: 'employee_form',
            userId:      self::currentUserId(),
        );
    }

    public static function forCompany(int $companyId, string $groupName): self
    {
        return new self(
            entityType:  'company',
            entityId:    $companyId,
            groupName:   $groupName,
            uploadedVia: 'company_form',
            userId:      self::currentUserId(),
        );
    }

    public static function forJudiciaryAction(int $actionId): self
    {
        return new self(
            entityType:  'judiciary_action',
            entityId:    $actionId,
            groupName:   'judiciary_action',
            uploadedVia: 'judiciary_form',
            userId:      self::currentUserId(),
        );
    }

    public static function forMovement(int $movementId): self
    {
        return new self(
            entityType:  'movement',
            entityId:    $movementId,
            groupName:   'receipt',
            uploadedVia: 'movement_form',
            userId:      self::currentUserId(),
        );
    }

    // ─── Fluent overrides ──────────────────────────────────────────
    // Each returns a new instance (the class is immutable) so callers
    // can build variants without surprising shared-state bugs.

    public function withEntityId(int $entityId): self
    {
        return new self(
            $this->entityType, $entityId, $this->groupName,
            $this->uploadedVia, $this->userId, $this->autoClassify,
            $this->originalName, $this->contractId, $this->customerId
        );
    }

    public function withAutoClassify(bool $on): self
    {
        return new self(
            $this->entityType, $this->entityId, $this->groupName,
            $this->uploadedVia, $this->userId, $on,
            $this->originalName, $this->contractId, $this->customerId
        );
    }

    public function withOriginalName(?string $name): self
    {
        return new self(
            $this->entityType, $this->entityId, $this->groupName,
            $this->uploadedVia, $this->userId, $this->autoClassify,
            $name, $this->contractId, $this->customerId
        );
    }

    public function withGroupName(string $groupName): self
    {
        return new self(
            $this->entityType, $this->entityId, $groupName,
            $this->uploadedVia, $this->userId, $this->autoClassify,
            $this->originalName, $this->contractId, $this->customerId
        );
    }

    /** Resolve the current Yii user id, or null in console / tests. */
    private static function currentUserId(): ?int
    {
        try {
            $u = Yii::$app->user ?? null;
            if ($u === null || $u->isGuest) return null;
            return (int)$u->id;
        } catch (\Throwable) {
            return null;
        }
    }
}
