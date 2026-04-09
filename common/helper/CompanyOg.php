<?php

namespace common\helper;

use Yii;

/**
 * Resolves Open Graph metadata for the current hostname
 * by querying the os_companies table.
 * Falls back to generic defaults if no match is found.
 */
class CompanyOg
{
    private static $_data;

    public static function get(): array
    {
        if (self::$_data !== null) {
            return self::$_data;
        }

        $serverName = Yii::$app->request->serverName ?? '';
        $hostInfo   = Yii::$app->request->hostInfo ?? '';
        $baseUrl    = Yii::$app->request->baseUrl ?? '';

        $defaults = [
            'title' => 'نظام تيسير',
            'desc'  => 'نظام إدارة التقسيط والأعمال المتكامل',
            'image' => $hostInfo . $baseUrl . '/img/og-jadal.png',
        ];

        try {
            $row = (new \yii\db\Query())
                ->select(['og_title', 'og_description', 'og_image'])
                ->from('{{%company_registry}}')
                ->where(['status' => 'active'])
                ->andWhere(['like', 'domain', $serverName])
                ->one();

            if ($row) {
                $defaults['title'] = $row['og_title'] ?: $defaults['title'];
                $defaults['desc']  = $row['og_description'] ?: $defaults['desc'];
                if (!empty($row['og_image'])) {
                    $defaults['image'] = $hostInfo . $baseUrl . $row['og_image'];
                }
            }
        } catch (\Exception $e) {
            // Table may not exist yet — use defaults
        }

        self::$_data = $defaults;
        return self::$_data;
    }
}
