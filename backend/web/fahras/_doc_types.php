<?php
/**
 * Phase 4 / M4.1 — Single source for `groupName` labels in Fahras.
 *
 * Before this file existed, three Fahras scripts (api.php,
 * client-attachments.php, relations.php) each carried their own copy
 * of `$docTypes`. Drift caused at least two production incidents
 * where the same image surfaced with different labels depending on
 * which screen the rep was looking at.
 *
 * Now all three include this helper, which delegates to the canonical
 * `common\services\media\GroupNameRegistry` (the same class
 * MediaController::actionLabels exposes as JSON). That class is pure
 * PHP with no Yii runtime requirement, so we can `require_once` it
 * here without bootstrapping the framework.
 *
 * Exports:
 *   $docTypes        — code → Arabic label (back-compat shape used by
 *                       the existing Fahras templates)
 *   $docTypesFull    — code → full registry record (for future use)
 *   fahras_doc_label($code)  — explicit lookup that returns 'أخرى'
 *                              when the code is unknown
 *   fahras_canonical_group($code) — collapses aliases ('coustmers' →
 *                                   'customers') so writes use one
 *                                   canonical key
 */

if (!class_exists(\common\services\media\GroupNameRegistry::class, false)) {
    require_once __DIR__ . '/../../../common/services/media/GroupNameRegistry.php';
}

use common\services\media\GroupNameRegistry;

/** @var array<string,array<string,mixed>> */
$docTypesFull = GroupNameRegistry::all();

/** @var array<string,string> */
$docTypes = [];
foreach ($docTypesFull as $code => $def) {
    $docTypes[$code] = (string)($def['label_ar'] ?? '');
}

if (!function_exists('fahras_doc_label')) {
    function fahras_doc_label(string $code, string $locale = 'ar'): string
    {
        $label = GroupNameRegistry::label($code, $locale);
        return $label !== '' ? $label : 'أخرى';
    }
}

if (!function_exists('fahras_canonical_group')) {
    function fahras_canonical_group(string $code): string
    {
        return GroupNameRegistry::canonicalize($code);
    }
}
