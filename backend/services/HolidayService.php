<?php

namespace backend\services;

use Yii;
use backend\models\Holiday;

class HolidayService
{
    const API_URL = 'https://tallyfy.com/national-holidays/api/JO/{year}.json';

    /**
     * Sync holidays from Tallyfy API for a given year.
     * Existing API-sourced entries for that year are replaced; manual entries are kept.
     */
    public function syncFromApi(int $year): array
    {
        $url = str_replace('{year}', $year, self::API_URL);
        $json = @file_get_contents($url);

        if ($json === false) {
            return ['success' => false, 'message' => 'فشل الاتصال بالـ API'];
        }

        $data = json_decode($json, true);
        if (!is_array($data)) {
            return ['success' => false, 'message' => 'بيانات غير صالحة من API'];
        }

        $holidays = $data['holidays'] ?? $data;
        if (!is_array($holidays)) {
            return ['success' => false, 'message' => 'تنسيق بيانات غير متوقع'];
        }

        Holiday::deleteAll(['year' => $year, 'source' => Holiday::SOURCE_API]);

        $count = 0;
        $now = time();
        foreach ($holidays as $item) {
            $date = $item['date'] ?? null;
            $name = $item['name'] ?? ($item['localName'] ?? '');

            if (!$date || !$name) {
                continue;
            }

            $dateFormatted = date('Y-m-d', strtotime($date));
            if (!$dateFormatted || $dateFormatted === '1970-01-01') {
                continue;
            }

            $exists = Holiday::find()->where(['holiday_date' => $dateFormatted])->exists();
            if ($exists) {
                continue;
            }

            $holiday = new Holiday([
                'holiday_date' => $dateFormatted,
                'name' => $name,
                'year' => $year,
                'source' => Holiday::SOURCE_API,
                'created_at' => $now,
            ]);
            if ($holiday->save()) {
                $count++;
            }
        }

        return ['success' => true, 'message' => "تم استيراد {$count} عطلة لسنة {$year}", 'count' => $count];
    }

    /**
     * Check if a date is a holiday (Friday, Saturday, or in os_holidays).
     */
    public function isHoliday($date): bool
    {
        $timestamp = is_string($date) ? strtotime($date) : $date;
        $dayOfWeek = (int)date('w', $timestamp);

        // Friday = 5, Saturday = 6
        if ($dayOfWeek === 5 || $dayOfWeek === 6) {
            return true;
        }

        $dateStr = date('Y-m-d', $timestamp);
        return Holiday::find()->where(['holiday_date' => $dateStr])->exists();
    }

    /**
     * Get the next working day on or after the given date.
     */
    public function getNextWorkingDay($date): string
    {
        $timestamp = is_string($date) ? strtotime($date) : $date;

        while ($this->isHoliday($timestamp)) {
            $timestamp = strtotime('+1 day', $timestamp);
        }

        return date('Y-m-d', $timestamp);
    }

    /**
     * Ensure holidays exist for the given year, syncing from API if needed.
     */
    public function ensureYearLoaded(int $year): void
    {
        $exists = Holiday::find()->where(['year' => $year])->exists();
        if (!$exists) {
            $this->syncFromApi($year);
        }
    }
}
