<?php

use yii\db\Migration;

class m260314_200000_clear_invalid_google_maps_key extends Migration
{
    public function safeUp()
    {
        $row = $this->db->createCommand(
            "SELECT id, setting_value, is_encrypted FROM {{%system_settings}} WHERE setting_group = 'google_maps' AND setting_key = 'api_key' LIMIT 1"
        )->queryOne();

        if ($row && $row['is_encrypted'] && !str_starts_with($row['setting_value'], 'AIza')) {
            $this->update('{{%system_settings}}', [
                'setting_value' => '',
                'is_encrypted' => 0,
            ], ['id' => $row['id']]);
        }
    }

    public function safeDown()
    {
        return true;
    }
}
