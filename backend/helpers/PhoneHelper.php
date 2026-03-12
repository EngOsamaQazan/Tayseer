<?php

namespace backend\helpers;

/**
 * Utility class for standardizing phone number formats.
 *
 * Storage format (DB): E.164  e.g. +962791091504
 * Display format:      local  e.g. 0791091504
 * WhatsApp / API:      intl   e.g. 962791091504
 */
class PhoneHelper
{
    private static $countryRules = [
        '962' => ['len' => 9, 'local_prefix' => '0'],   // Jordan
        '970' => ['len' => 9, 'local_prefix' => '0'],   // Palestine
        '966' => ['len' => 9, 'local_prefix' => '0'],   // Saudi Arabia
        '964' => ['len' => 10, 'local_prefix' => '0'],  // Iraq
        '20'  => ['len' => 10, 'local_prefix' => '0'],  // Egypt
        '963' => ['len' => 9, 'local_prefix' => '0'],   // Syria
        '961' => ['len' => 8, 'local_prefix' => '0'],   // Lebanon
        '971' => ['len' => 9, 'local_prefix' => '0'],   // UAE
    ];

    /**
     * Normalize a raw phone number to E.164 format.
     * Assumes Jordan (+962) when no country code is detected.
     *
     * @param string $raw  The raw phone number input
     * @param string $defaultCode  Default country calling code (without +)
     * @return string  E.164 formatted number (e.g. +962791091504)
     */
    public static function toE164($raw, $defaultCode = '962')
    {
        if (empty($raw)) return '';

        $digits = preg_replace('/[^\d]/', '', $raw);
        if (empty($digits)) return '';

        if (strpos($raw, '+') === 0) {
            return '+' . $digits;
        }

        if (substr($digits, 0, 2) === '00') {
            return '+' . substr($digits, 2);
        }

        foreach (self::$countryRules as $code => $rule) {
            if (strpos($digits, $code) === 0 && strlen($digits) > strlen($code)) {
                $national = substr($digits, strlen($code));
                if (strlen($national) === $rule['len']) {
                    return '+' . $code . $national;
                }
            }
        }

        if (substr($digits, 0, 1) === '0') {
            $national = substr($digits, 1);
            if (isset(self::$countryRules[$defaultCode])) {
                $rule = self::$countryRules[$defaultCode];
                if (strlen($national) === $rule['len'] || strlen($national) === $rule['len'] - 1) {
                    return '+' . $defaultCode . $national;
                }
            }
            return '+' . $defaultCode . $national;
        }

        if (isset(self::$countryRules[$defaultCode])) {
            $rule = self::$countryRules[$defaultCode];
            if (strlen($digits) === $rule['len']) {
                return '+' . $defaultCode . $digits;
            }
        }

        return '+' . $defaultCode . $digits;
    }

    /**
     * Convert an E.164 number to local display format.
     * +962791091504 -> 0791091504
     *
     * @param string $e164
     * @return string
     */
    public static function toLocal($e164)
    {
        if (empty($e164)) return '';

        $digits = preg_replace('/[^\d]/', '', ltrim($e164, '+'));

        foreach (self::$countryRules as $code => $rule) {
            if (strpos($digits, $code) === 0) {
                $national = substr($digits, strlen($code));
                if (!empty($national)) {
                    return $rule['local_prefix'] . $national;
                }
            }
        }

        return $e164;
    }

    /**
     * Convert to international format without '+' (for wa.me links).
     * +962791091504 -> 962791091504
     *
     * @param string $number
     * @return string
     */
    public static function toWhatsApp($number)
    {
        $e164 = self::toE164($number);
        return ltrim($e164, '+');
    }

    /**
     * Convert to full E.164 format for tel: links.
     * Returns +962791091504
     *
     * @param string $number
     * @return string
     */
    public static function toTel($number)
    {
        return self::toE164($number);
    }

    /**
     * Check if a number is already in E.164 format.
     *
     * @param string $number
     * @return bool
     */
    public static function isE164($number)
    {
        return preg_match('/^\+\d{7,15}$/', $number) === 1;
    }
}
