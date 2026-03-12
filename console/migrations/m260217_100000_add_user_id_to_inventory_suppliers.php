<?php

use yii\db\Migration;

/**
 * Adds user_id to os_inventory_suppliers to link system users (vendors) with supplier records.
 * This unifies the supplier data model — all suppliers (external and system users) live in one table.
 */
class m260217_100000_add_user_id_to_inventory_suppliers extends Migration
{
    public function safeUp()
    {
        $table = '{{%inventory_suppliers}}';
        $schema = $this->db->getTableSchema($table, true);

        if ($schema && !$schema->getColumn('user_id')) {
            $this->addColumn($table, 'user_id', $this->integer()->null()->defaultValue(null)->comment('Linked system user ID'));
            $this->createIndex('idx_inventory_suppliers_user_id', $table, 'user_id', true);
        }

        // Sync existing vendor users: create supplier records for users categorized as vendors
        // who don't already have a matching supplier record
        $vendorUsers = (new \yii\db\Query())
            ->select(['u.id AS user_id', 'p.name', 'u.email', 'u.username'])
            ->from('{{%user}} u')
            ->innerJoin('{{%user_category_map}} ucm', 'ucm.user_id = u.id')
            ->innerJoin('{{%user_categories}} uc', 'uc.id = ucm.category_id')
            ->leftJoin('{{%profile}} p', 'p.user_id = u.id')
            ->where(['uc.slug' => 'vendor', 'uc.is_active' => 1])
            ->all();

        foreach ($vendorUsers as $vu) {
            $userId = (int) $vu['user_id'];
            // Check if a supplier record already exists for this user
            $exists = (new \yii\db\Query())
                ->from($table)
                ->where(['user_id' => $userId])
                ->exists();
            if ($exists) continue;

            $displayName = !empty($vu['name']) ? $vu['name'] : $vu['username'];
            $email = $vu['email'] ?: '';

            // Check if a supplier with the same name already exists — link it
            $existingByName = (new \yii\db\Query())
                ->from($table)
                ->where(['name' => $displayName])
                ->andWhere(['user_id' => null])
                ->one();

            if ($existingByName) {
                $this->update($table, ['user_id' => $userId], ['id' => $existingByName['id']]);
            } else {
                $this->insert($table, [
                    'name' => $displayName,
                    'phone_number' => $email,
                    'adress' => '',
                    'company_id' => 0,
                    'user_id' => $userId,
                    'is_deleted' => 0,
                    'created_at' => time(),
                    'updated_at' => time(),
                    'created_by' => 1,
                    'last_update_by' => 1,
                ]);
            }
        }
    }

    public function safeDown()
    {
        $table = '{{%inventory_suppliers}}';
        $schema = $this->db->getTableSchema($table, true);
        if ($schema && $schema->getColumn('user_id')) {
            $this->dropIndex('idx_inventory_suppliers_user_id', $table);
            $this->dropColumn($table, 'user_id');
        }
    }
}
