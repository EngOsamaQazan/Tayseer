<?php

namespace common\services\media;

/**
 * Phase 1 / M1.2 — Single source of truth for `groupName` codes.
 *
 * Before this class existed, the same {code → label} map was duplicated
 * across at least four files:
 *   • backend/web/fahras/api.php             (3-segment $docTypes)
 *   • backend/web/fahras/client-attachments.php
 *   • backend/web/fahras/relations.php
 *   • console/controllers/RecoverOrphanMediaController.php (WIZARD_GROUPS)
 *
 * Drift between those copies caused two production incidents (a label
 * said "هوية" on one screen and "بطاقة" on another, and a docType
 * silently disappeared from one Fahras tab when added to another).
 * From now on every label, every entity-type association, every
 * MIME/size limit lives here and is consumed via:
 *
 *   • Server-side PHP        — {@see self::label()} / {@see self::all()}
 *   • Standalone scripts (Fahras) — HTTP fetch from MediaController::actionLabels
 *   • Front-end JS upload widgets — same HTTP endpoint
 *
 * Adding a new docType is now a one-line patch in {@see self::DEFINITIONS}.
 *
 * Design notes:
 *   • The "_front" / "_back" composites are intentional: they let the
 *     wizard track that the rep already captured the front of the ID
 *     and only needs the back, while still rolling up to a single ID
 *     document when displayed in Fahras.
 *   • Legacy mis-spellings ("coustmers" — yes, the typo shipped) are
 *     KEPT as aliases so historical rows do not become unaddressable;
 *     {@see self::canonicalize()} normalises them at write time so we
 *     stop accumulating new ones.
 */
final class GroupNameRegistry
{
    /**
     * The master table.
     *
     * Each entry:
     *   label_ar    — Arabic display label
     *   label_en    — English label (fallback)
     *   entity      — Entity types this group can be attached to.
     *                 'customer' implies the wizard surface; multiple
     *                 entries mean the same group is reused across
     *                 modules (rare).
     *   wizard      — true ⇢ this code is one of the legitimate wizard
     *                 buckets and is eligible for orphan-recovery
     *                 (mirrors RecoverOrphanMediaController::WIZARD_GROUPS).
     *   max_bytes   — Per-file size cap. 0 ⇢ no cap.
     *   mimes       — Allowed MIME prefixes/exact types. Matched by
     *                 startsWith for prefixes ending in '/' and exact
     *                 otherwise.
     *
     * @var array<string,array<string,mixed>>
     */
    private const DEFINITIONS = [
        // ── Customer-side wizard groups (legacy numeric codes) ──────
        '0' => [
            'label_ar' => 'هوية وطنية',
            'label_en' => 'National ID',
            'entity'   => ['customer'],
            'wizard'   => true,
            'max_bytes'=> 10 * 1024 * 1024,
            'mimes'    => ['image/', 'application/pdf'],
        ],
        '0_front' => [
            'label_ar' => 'هوية وطنية — الوجه',
            'label_en' => 'National ID — front',
            'entity'   => ['customer'],
            'wizard'   => true,
            'max_bytes'=> 10 * 1024 * 1024,
            'mimes'    => ['image/'],
        ],
        '0_back' => [
            'label_ar' => 'هوية وطنية — الظهر',
            'label_en' => 'National ID — back',
            'entity'   => ['customer'],
            'wizard'   => true,
            'max_bytes'=> 10 * 1024 * 1024,
            'mimes'    => ['image/'],
        ],
        '1' => [
            'label_ar' => 'جواز سفر',
            'label_en' => 'Passport',
            'entity'   => ['customer'],
            'wizard'   => true,
            'max_bytes'=> 10 * 1024 * 1024,
            'mimes'    => ['image/', 'application/pdf'],
        ],
        '2' => [
            'label_ar' => 'رخصة قيادة',
            'label_en' => 'Driving licence',
            'entity'   => ['customer'],
            'wizard'   => true,
            'max_bytes'=> 10 * 1024 * 1024,
            'mimes'    => ['image/', 'application/pdf'],
        ],
        '3' => [
            'label_ar' => 'شهادة ميلاد',
            'label_en' => 'Birth certificate',
            'entity'   => ['customer'],
            'wizard'   => false,  // legacy, no longer surfaced in wizard
            'max_bytes'=> 10 * 1024 * 1024,
            'mimes'    => ['image/', 'application/pdf'],
        ],
        '4' => [
            'label_ar' => 'شهادة تعيين',
            'label_en' => 'Employment letter',
            'entity'   => ['customer'],
            'wizard'   => true,
            'max_bytes'=> 10 * 1024 * 1024,
            'mimes'    => ['image/', 'application/pdf'],
        ],
        '4_front' => [
            'label_ar' => 'شهادة تعيين عسكرية — الوجه',
            'label_en' => 'Military employment letter — front',
            'entity'   => ['customer'],
            'wizard'   => true,
            'max_bytes'=> 10 * 1024 * 1024,
            'mimes'    => ['image/'],
        ],
        '4_back' => [
            'label_ar' => 'شهادة تعيين عسكرية — الظهر',
            'label_en' => 'Military employment letter — back',
            'entity'   => ['customer'],
            'wizard'   => true,
            'max_bytes'=> 10 * 1024 * 1024,
            'mimes'    => ['image/'],
        ],
        '5' => [
            'label_ar' => 'كتاب ضمان اجتماعي',
            'label_en' => 'Social security letter',
            'entity'   => ['customer'],
            'wizard'   => true,
            'max_bytes'=> 10 * 1024 * 1024,
            'mimes'    => ['image/', 'application/pdf'],
        ],
        '6' => [
            'label_ar' => 'كشف راتب',
            'label_en' => 'Salary statement',
            'entity'   => ['customer'],
            'wizard'   => true,
            'max_bytes'=> 10 * 1024 * 1024,
            'mimes'    => ['image/', 'application/pdf'],
        ],
        '7' => [
            'label_ar' => 'شهادة تعيين عسكري',
            'label_en' => 'Military employment letter',
            'entity'   => ['customer'],
            'wizard'   => true,
            'max_bytes'=> 10 * 1024 * 1024,
            'mimes'    => ['image/', 'application/pdf'],
        ],
        '8' => [
            'label_ar' => 'صورة شخصية',
            'label_en' => 'Personal photo',
            'entity'   => ['customer'],
            'wizard'   => true,
            'max_bytes'=> 5 * 1024 * 1024,
            'mimes'    => ['image/'],
        ],
        '9' => [
            'label_ar' => 'غير محدد',
            'label_en' => 'Unspecified',
            'entity'   => ['customer'],
            'wizard'   => true,
            'max_bytes'=> 25 * 1024 * 1024,
            'mimes'    => ['image/', 'application/pdf'],
        ],

        // ── Generic owner-document buckets ──────────────────────────
        'customers' => [
            'label_ar' => 'وثيقة عميل',
            'label_en' => 'Customer document',
            'entity'   => ['customer'],
            'wizard'   => false,
            'max_bytes'=> 25 * 1024 * 1024,
            'mimes'    => ['image/', 'application/pdf'],
        ],
        'contracts' => [
            'label_ar' => 'وثيقة عقد',
            'label_en' => 'Contract document',
            'entity'   => ['contract'],
            'wizard'   => false,
            'max_bytes'=> 25 * 1024 * 1024,
            'mimes'    => ['image/', 'application/pdf'],
        ],
        'smart_media' => [
            'label_ar' => 'وسائط ذكية',
            'label_en' => 'Smart media',
            'entity'   => ['customer'],
            'wizard'   => false,
            'max_bytes'=> 25 * 1024 * 1024,
            'mimes'    => ['image/', 'application/pdf'],
        ],

        // ── Lawyer module ───────────────────────────────────────────
        'signature' => [
            'label_ar' => 'توقيع',
            'label_en' => 'Signature',
            'entity'   => ['lawyer', 'customer'],
            'wizard'   => false,
            'max_bytes'=> 1 * 1024 * 1024,
            'mimes'    => ['image/png', 'image/jpeg', 'image/webp'],
        ],
        'lawyer_photo' => [
            'label_ar' => 'صورة المحامي',
            'label_en' => 'Lawyer photo',
            'entity'   => ['lawyer'],
            'wizard'   => false,
            'max_bytes'=> 5 * 1024 * 1024,
            'mimes'    => ['image/'],
        ],

        // ── HR / Employee ───────────────────────────────────────────
        'employee_avatar' => [
            'label_ar' => 'صورة الموظف',
            'label_en' => 'Employee avatar',
            'entity'   => ['employee'],
            'wizard'   => false,
            'max_bytes'=> 5 * 1024 * 1024,
            'mimes'    => ['image/'],
        ],
        'employee_attachment' => [
            'label_ar' => 'مرفق موظف',
            'label_en' => 'Employee attachment',
            'entity'   => ['employee'],
            'wizard'   => false,
            'max_bytes'=> 25 * 1024 * 1024,
            'mimes'    => ['image/', 'application/pdf'],
        ],

        // ── Companies module ────────────────────────────────────────
        'company_logo' => [
            'label_ar' => 'شعار الشركة',
            'label_en' => 'Company logo',
            'entity'   => ['company'],
            'wizard'   => false,
            'max_bytes'=> 2 * 1024 * 1024,
            'mimes'    => ['image/'],
        ],
        'commercial_register' => [
            'label_ar' => 'السجل التجاري',
            'label_en' => 'Commercial register',
            'entity'   => ['company'],
            'wizard'   => false,
            'max_bytes'=> 25 * 1024 * 1024,
            'mimes'    => ['image/', 'application/pdf'],
        ],
        'trade_license' => [
            'label_ar' => 'رخصة المهنة',
            'label_en' => 'Trade licence',
            'entity'   => ['company'],
            'wizard'   => false,
            'max_bytes'=> 25 * 1024 * 1024,
            'mimes'    => ['image/', 'application/pdf'],
        ],

        // ── Judiciary actions ───────────────────────────────────────
        // The legacy code path stores the *attachment* (proof of action)
        // and the *decision document* (court ruling PDF) in two separate
        // directories — keeping them as distinct groupNames preserves
        // that semantic distinction in dashboards / reports without
        // forcing readers to disambiguate by URL prefix.
        'judiciary_action' => [
            'label_ar' => 'مرفق إجراء قضائي',
            'label_en' => 'Judiciary action attachment',
            'entity'   => ['judiciary_action'],
            'wizard'   => false,
            'max_bytes'=> 25 * 1024 * 1024,
            'mimes'    => ['image/', 'application/pdf'],
        ],
        'judiciary_decision' => [
            'label_ar' => 'وثيقة قرار قضائي',
            'label_en' => 'Judiciary decision document',
            'entity'   => ['judiciary_action'],
            'wizard'   => false,
            'max_bytes'=> 25 * 1024 * 1024,
            'mimes'    => ['image/', 'application/pdf'],
        ],

        // ── Movement / receipts ─────────────────────────────────────
        'receipt' => [
            'label_ar' => 'إيصال حركة',
            'label_en' => 'Movement receipt',
            'entity'   => ['movement'],
            'wizard'   => false,
            'max_bytes'=> 10 * 1024 * 1024,
            'mimes'    => ['image/', 'application/pdf'],
        ],

        // ── Contract supplementary docs ─────────────────────────────
        'contract_doc' => [
            'label_ar' => 'مستند عقد',
            'label_en' => 'Contract document',
            'entity'   => ['contract', 'contract_doc'],
            'wizard'   => false,
            'max_bytes'=> 25 * 1024 * 1024,
            'mimes'    => ['image/', 'application/pdf'],
        ],
    ];

    /**
     * Aliases — old/typo'd codes that still exist in the database. We
     * accept them on read but {@see self::canonicalize()} rewrites them
     * to the canonical key on write so the alias set never grows.
     *
     * @var array<string,string>
     */
    private const ALIASES = [
        'coustmers' => 'customers', // shipped typo, ~hundreds of rows
    ];

    /**
     * Look up the human-friendly label for a code.
     *
     * @param string $groupName  raw group code from a DB row or upload
     * @param string $locale     'ar' | 'en' (anything else falls to 'ar')
     * @return string  empty string when the code is unknown
     */
    public static function label(string $groupName, string $locale = 'ar'): string
    {
        $code = self::canonicalize($groupName);
        $def = self::DEFINITIONS[$code] ?? null;
        if ($def === null) {
            return '';
        }
        return $locale === 'en'
            ? ($def['label_en'] ?? $def['label_ar'])
            : ($def['label_ar'] ?? '');
    }

    /**
     * Is this code one of the wizard buckets?
     *
     * Used by the orphan-recovery logic: only wizard groups are
     * eligible for retroactive customer adoption, because non-wizard
     * groups (lawyer signature, employee avatar, …) belong to flows
     * where ownership is set at upload time and an orphan there is a
     * bug, not a normal lifecycle state.
     */
    public static function isWizardGroup(string $groupName): bool
    {
        $def = self::DEFINITIONS[self::canonicalize($groupName)] ?? null;
        return (bool)($def['wizard'] ?? false);
    }

    /**
     * Is the (groupName, entityType) pair a known combination?
     * Used by MediaService::store() to refuse uploads that would
     * create rows no UI can possibly render.
     */
    public static function validate(string $groupName, string $entityType): bool
    {
        $def = self::DEFINITIONS[self::canonicalize($groupName)] ?? null;
        if ($def === null) {
            return false;
        }
        return in_array($entityType, (array)($def['entity'] ?? []), true);
    }

    /**
     * Per-group MIME allow-list — used for the very first server-side
     * gate before we even hash the file.
     *
     * @return array<int,string>  empty when no constraint is defined
     */
    public static function allowedMimes(string $groupName): array
    {
        $def = self::DEFINITIONS[self::canonicalize($groupName)] ?? null;
        return (array)($def['mimes'] ?? []);
    }

    /**
     * Per-group max bytes (0 = no limit).
     */
    public static function maxBytes(string $groupName): int
    {
        $def = self::DEFINITIONS[self::canonicalize($groupName)] ?? null;
        return (int)($def['max_bytes'] ?? 0);
    }

    /**
     * Run a MIME string through the allow-list. Prefixes ending in
     * '/' match by `str_starts_with`; everything else is exact.
     */
    public static function mimeAllowed(string $groupName, string $mime): bool
    {
        $allowed = self::allowedMimes($groupName);
        if (empty($allowed)) {
            return true; // no constraint configured
        }
        foreach ($allowed as $rule) {
            if (str_ends_with($rule, '/')) {
                if (str_starts_with($mime, $rule)) return true;
            } elseif ($mime === $rule) {
                return true;
            }
        }
        return false;
    }

    /**
     * Convert a possibly-aliased code to its canonical form. Safe to
     * call on any value (returns the input unchanged when not aliased).
     */
    public static function canonicalize(string $groupName): string
    {
        return self::ALIASES[$groupName] ?? $groupName;
    }

    /**
     * Full dump for the JSON endpoint that Fahras + the unified
     * uploader consume. Aliases are exposed as separate entries so a
     * standalone script that reads a row with the legacy code still
     * gets the right label without having to re-implement
     * canonicalize().
     *
     * @return array<string,array<string,mixed>>
     */
    public static function all(): array
    {
        $out = [];
        foreach (self::DEFINITIONS as $code => $def) {
            $out[$code] = [
                'label_ar'  => (string)($def['label_ar'] ?? ''),
                'label_en'  => (string)($def['label_en'] ?? ''),
                'entity'    => array_values((array)($def['entity'] ?? [])),
                'wizard'    => (bool)($def['wizard'] ?? false),
                'max_bytes' => (int)($def['max_bytes'] ?? 0),
                'mimes'     => array_values((array)($def['mimes'] ?? [])),
            ];
        }
        // Aliases inherit the canonical entry verbatim, with an extra
        // `alias_of` field so consumers can redirect / log.
        foreach (self::ALIASES as $alias => $canonical) {
            if (isset($out[$canonical])) {
                $out[$alias] = $out[$canonical] + ['alias_of' => $canonical];
            }
        }
        return $out;
    }

    /**
     * All wizard codes in one array — what RecoverOrphanMediaController
     * ::WIZARD_GROUPS used to be. Single source now.
     *
     * @return array<int,string>
     */
    public static function wizardGroups(): array
    {
        $out = [];
        foreach (self::DEFINITIONS as $code => $def) {
            if (!empty($def['wizard'])) {
                $out[] = $code;
            }
        }
        return $out;
    }

    /**
     * All known entity types in the system. Used by Fahras filter UI.
     *
     * @return array<int,string>
     */
    public static function entityTypes(): array
    {
        $out = [];
        foreach (self::DEFINITIONS as $def) {
            foreach ((array)($def['entity'] ?? []) as $e) {
                $out[$e] = true;
            }
        }
        return array_keys($out);
    }
}
