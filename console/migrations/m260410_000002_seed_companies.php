<?php

use yii\db\Migration;

class m260410_000002_seed_companies extends Migration
{
    public function safeUp()
    {
        $now = time();
        $companies = [
            [
                'slug'           => 'jadal',
                'name_ar'        => 'شركة جدل للتقسيط',
                'name_en'        => 'Jadal',
                'domain'         => 'jadal.aqssat.co',
                'db_name'        => 'namaa_jadal',
                'server_ip'      => '31.220.82.115',
                'sms_sender'     => 'JADAL',
                'sms_user'       => 'jadalSMS',
                'og_title'       => 'نظام تيسير — جدل',
                'og_description' => 'نظام إدارة التقسيط والأعمال المتكامل — شركة جدل للتقسيط',
                'og_image'       => '/img/og-jadal.png',
                'status'         => 'active',
                'provisioned_at' => $now,
                'created_at'     => $now,
                'updated_at'     => $now,
            ],
            [
                'slug'           => 'namaa',
                'name_ar'        => 'شركة نماء للتقسيط',
                'name_en'        => 'Namaa',
                'domain'         => 'namaa.aqssat.co',
                'db_name'        => 'namaa_erp',
                'server_ip'      => '31.220.82.115',
                'sms_sender'     => 'NAMAA',
                'sms_user'       => 'namaaSMS',
                'og_title'       => 'نظام تيسير — نماء',
                'og_description' => 'نظام إدارة التقسيط والأعمال المتكامل — شركة نماء للتقسيط',
                'og_image'       => '/img/og-namaa.png',
                'status'         => 'active',
                'provisioned_at' => $now,
                'created_at'     => $now,
                'updated_at'     => $now,
            ],
            [
                'slug'           => 'watar',
                'name_ar'        => 'شركة وتر للتقسيط',
                'name_en'        => 'Watar',
                'domain'         => 'watar.aqssat.co',
                'db_name'        => 'tayseer_watar',
                'server_ip'      => '31.220.82.115',
                'sms_sender'     => 'WATAR',
                'sms_user'       => 'watarSMS',
                'og_title'       => 'نظام تيسير — وتر',
                'og_description' => 'نظام إدارة التقسيط والأعمال المتكامل — شركة وتر للتقسيط',
                'og_image'       => '/img/og-jadal.png',
                'status'         => 'active',
                'provisioned_at' => $now,
                'created_at'     => $now,
                'updated_at'     => $now,
            ],
            [
                'slug'           => 'majd',
                'name_ar'        => 'عالم المجد للتقسيط',
                'name_en'        => 'Majd',
                'domain'         => 'majd.aqssat.co',
                'db_name'        => 'tayseer_majd',
                'server_ip'      => '31.220.82.115',
                'sms_sender'     => 'MAJD',
                'sms_user'       => 'majdSMS',
                'og_title'       => 'نظام تيسير — عالم المجد',
                'og_description' => 'نظام إدارة التقسيط والأعمال المتكامل — عالم المجد للتقسيط',
                'og_image'       => '/img/og-majd.png',
                'status'         => 'active',
                'provisioned_at' => $now,
                'created_at'     => $now,
                'updated_at'     => $now,
            ],
        ];

        foreach ($companies as $company) {
            $exists = (new \yii\db\Query())
                ->from('{{%companies}}')
                ->where(['slug' => $company['slug']])
                ->exists();

            if (!$exists) {
                $this->insert('{{%companies}}', $company);
            }
        }
    }

    public function safeDown()
    {
        $this->delete('{{%companies}}', ['slug' => ['jadal', 'namaa', 'watar', 'majd']]);
    }
}
