<?php

use yii\db\Migration;

class m260410_000003_add_company_management_permission extends Migration
{
    public function safeUp()
    {
        $now = time();
        $permName = 'إدارة المنشآت';

        $exists = (new \yii\db\Query())
            ->from('{{%auth_item}}')
            ->where(['name' => $permName])
            ->exists();

        if (!$exists) {
            $this->insert('{{%auth_item}}', [
                'name'        => $permName,
                'type'        => 2, // permission
                'description' => 'إدارة وتجهيز المنشآت الجديدة',
                'rule_name'   => null,
                'data'        => null,
                'created_at'  => $now,
                'updated_at'  => $now,
            ]);
        }

        // Assign to "مدير النظام" role if it exists
        $roleExists = (new \yii\db\Query())
            ->from('{{%auth_item}}')
            ->where(['name' => 'مدير النظام', 'type' => 1])
            ->exists();

        if ($roleExists) {
            $childExists = (new \yii\db\Query())
                ->from('{{%auth_item_child}}')
                ->where(['parent' => 'مدير النظام', 'child' => $permName])
                ->exists();

            if (!$childExists) {
                $this->insert('{{%auth_item_child}}', [
                    'parent' => 'مدير النظام',
                    'child'  => $permName,
                ]);
            }
        }
    }

    public function safeDown()
    {
        $this->delete('{{%auth_assignment}}', ['item_name' => 'إدارة المنشآت']);
        $this->delete('{{%auth_item_child}}', ['child' => 'إدارة المنشآت']);
        $this->delete('{{%auth_item}}', ['name' => 'إدارة المنشآت']);
    }
}
