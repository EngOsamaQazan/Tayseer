<?php

use yii\db\Migration;

class m260318_100002_add_accounting_rbac_permissions extends Migration
{
    private $permissions = [
        'المحاسبة',
        'المحاسبة: مشاهدة',
        'المحاسبة: إضافة قيود',
        'المحاسبة: تعديل',
        'المحاسبة: حذف',
        'المحاسبة: ترحيل قيود',
        'المحاسبة: عكس قيود',
        'شجرة الحسابات: إدارة',
        'السنة المالية: إدارة',
        'الموازنات: مشاهدة',
        'الموازنات: إدارة',
        'التقارير المالية: مشاهدة',
        'الذمم المدينة: إدارة',
        'الذمم الدائنة: إدارة',
    ];

    public function safeUp()
    {
        $now = time();

        // Insert parent permission
        $this->insert('{{%auth_item}}', [
            'name' => 'المحاسبة',
            'type' => 2, // permission
            'description' => 'المحاسبة',
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        // Insert child permissions and link to parent
        $children = array_slice($this->permissions, 1);
        foreach ($children as $permName) {
            $this->insert('{{%auth_item}}', [
                'name' => $permName,
                'type' => 2,
                'description' => $permName,
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            $this->insert('{{%auth_item_child}}', [
                'parent' => 'المحاسبة',
                'child' => $permName,
            ]);
        }

        // Assign to مدير role if it exists
        $managerExists = (new \yii\db\Query())
            ->from('{{%auth_item}}')
            ->where(['name' => 'مدير'])
            ->exists();

        if ($managerExists) {
            $this->insert('{{%auth_item_child}}', [
                'parent' => 'مدير',
                'child' => 'المحاسبة',
            ]);
        } else {
            // Assign directly to user 1
            $this->insert('{{%auth_assignment}}', [
                'item_name' => 'المحاسبة',
                'user_id' => '1',
                'created_at' => $now,
            ]);
        }
    }

    public function safeDown()
    {
        // Remove assignments
        $this->delete('{{%auth_assignment}}', ['item_name' => $this->permissions]);
        
        // Remove child relationships
        $children = array_slice($this->permissions, 1);
        foreach ($children as $permName) {
            $this->delete('{{%auth_item_child}}', ['child' => $permName]);
        }
        $this->delete('{{%auth_item_child}}', ['child' => 'المحاسبة']);

        // Remove permissions
        foreach ($this->permissions as $permName) {
            $this->delete('{{%auth_item}}', ['name' => $permName]);
        }
    }
}
