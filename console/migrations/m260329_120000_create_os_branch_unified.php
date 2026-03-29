<?php

use yii\db\Migration;

/**
 * Creates unified os_branch table and migrates data from os_location + os_hr_work_zone.
 * Adds branch_id columns to related tables for gradual migration.
 */
class m260329_120000_create_os_branch_unified extends Migration
{
    public function safeUp()
    {
        $tableOptions = 'ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci';

        // ═══ 1. Create os_branch ═══
        $this->createTable('{{%branch}}', [
            'id'            => $this->primaryKey(),
            'company_id'    => $this->integer()->null(),
            'name'          => $this->string(150)->notNull(),
            'code'          => $this->string(20)->null()->unique(),
            'branch_type'   => "ENUM('hq','branch','warehouse','client_site','field_area') DEFAULT 'branch'",
            'description'   => $this->string(500)->null(),
            'address'       => $this->string(500)->null(),
            'latitude'      => $this->decimal(10, 8)->null(),
            'longitude'     => $this->decimal(11, 8)->null(),
            'radius_meters' => $this->integer()->defaultValue(100),
            'wifi_ssid'     => $this->string(100)->null(),
            'wifi_bssid'    => $this->string(50)->null(),
            'manager_id'    => $this->integer()->null(),
            'phone'         => $this->string(20)->null(),
            'is_active'     => $this->tinyInteger(1)->defaultValue(1),
            'sort_order'    => $this->integer()->defaultValue(0),
            'created_by'    => $this->integer()->null(),
            'created_at'    => $this->dateTime()->defaultExpression('CURRENT_TIMESTAMP'),
            'updated_at'    => $this->dateTime()->null(),
        ], $tableOptions);

        $this->createIndex('idx_branch_company', '{{%branch}}', 'company_id');
        $this->createIndex('idx_branch_type', '{{%branch}}', 'branch_type');
        $this->createIndex('idx_branch_active', '{{%branch}}', 'is_active');

        // ═══ 2. Migrate data from os_location ═══
        $locationRows = (new \yii\db\Query())
            ->from('{{%location}}')
            ->all($this->db);

        $seq = 1;
        $locationMap = []; // old location.id => new branch.id
        foreach ($locationRows as $row) {
            $this->insert('{{%branch}}', [
                'name'          => $row['location'],
                'code'          => 'BR-' . str_pad($seq, 3, '0', STR_PAD_LEFT),
                'branch_type'   => 'branch',
                'description'   => $row['description'] ?? null,
                'latitude'      => $row['latitude'] ?: null,
                'longitude'     => $row['longitude'] ?: null,
                'radius_meters' => $row['radius'] ?: 100,
                'is_active'     => ($row['status'] === 'active') ? 1 : 0,
                'created_by'    => $row['created_by'] ?? null,
                'created_at'    => $row['created_at'] ? date('Y-m-d H:i:s', $row['created_at']) : date('Y-m-d H:i:s'),
            ]);
            $newId = $this->db->getLastInsertID();
            $locationMap[$row['id']] = $newId;
            $seq++;
        }

        // ═══ 3. Migrate data from os_hr_work_zone (avoid duplicates) ═══
        $workZoneRows = (new \yii\db\Query())
            ->from('{{%hr_work_zone}}')
            ->all($this->db);

        $workZoneMap = []; // old zone.id => new branch.id
        foreach ($workZoneRows as $row) {
            $typeMap = [
                'office'      => 'hq',
                'branch'      => 'branch',
                'client_site' => 'client_site',
                'field_area'  => 'field_area',
                'restricted'  => 'field_area',
            ];

            $this->insert('{{%branch}}', [
                'company_id'    => $row['company_id'] ?? null,
                'name'          => $row['name'],
                'code'          => 'WZ-' . str_pad($seq, 3, '0', STR_PAD_LEFT),
                'branch_type'   => $typeMap[$row['zone_type']] ?? 'branch',
                'address'       => $row['address'] ?? null,
                'latitude'      => $row['latitude'] ?: null,
                'longitude'     => $row['longitude'] ?: null,
                'radius_meters' => $row['radius_meters'] ?: 100,
                'wifi_ssid'     => $row['wifi_ssid'] ?? null,
                'wifi_bssid'    => $row['wifi_bssid'] ?? null,
                'is_active'     => $row['is_active'] ?? 1,
                'created_by'    => $row['created_by'] ?? null,
                'created_at'    => $row['created_at'] ?? date('Y-m-d H:i:s'),
            ]);
            $newId = $this->db->getLastInsertID();
            $workZoneMap[$row['id']] = $newId;
            $seq++;
        }

        // ═══ 4. Add branch_id to os_inventory_stock_locations ═══
        $schema = $this->db->getTableSchema('{{%inventory_stock_locations}}', true);
        if ($schema && !$schema->getColumn('branch_id')) {
            $this->addColumn('{{%inventory_stock_locations}}', 'branch_id', $this->integer()->null()->after('company_id'));
            $this->createIndex('idx_stock_loc_branch', '{{%inventory_stock_locations}}', 'branch_id');
        }

        // ═══ 5. Add new_branch_id to os_user (keeps old 'location' column) ═══
        $userSchema = $this->db->getTableSchema('{{%user}}', true);
        if ($userSchema && !$userSchema->getColumn('branch_id')) {
            $this->addColumn('{{%user}}', 'branch_id', $this->integer()->null()->after('location'));
        }

        // Populate user.branch_id from user.location using map
        foreach ($locationMap as $oldId => $newId) {
            $this->update('{{%user}}', ['branch_id' => $newId], ['location' => $oldId]);
        }

        // ═══ 6. Update hr_employee_extended.branch_id values (old -> new) ═══
        $empSchema = $this->db->getTableSchema('{{%hr_employee_extended}}', true);
        if ($empSchema && $empSchema->getColumn('branch_id')) {
            foreach ($locationMap as $oldId => $newId) {
                $this->update('{{%hr_employee_extended}}', ['branch_id' => $newId], ['branch_id' => $oldId]);
            }
        }

        // Update work_zone_id references -> add new_branch_id column for zone mapping
        if ($empSchema && !$empSchema->getColumn('unified_branch_id')) {
            $this->addColumn('{{%hr_employee_extended}}', 'unified_branch_id', $this->integer()->null()->after('branch_id'));
        }
        // Copy branch_id (already remapped) to unified_branch_id
        $this->execute("UPDATE {{%hr_employee_extended}} SET unified_branch_id = branch_id WHERE branch_id IS NOT NULL");
        // For those with work_zone_id but no branch_id, map from work zones
        foreach ($workZoneMap as $oldZoneId => $newBranchId) {
            $this->update('{{%hr_employee_extended}}',
                ['unified_branch_id' => $newBranchId],
                ['AND', ['work_zone_id' => $oldZoneId], ['unified_branch_id' => null]]
            );
        }

        // ═══ 7. Add branch_id to hr_attendance_log ═══
        $attLogSchema = $this->db->getTableSchema('{{%hr_attendance_log}}', true);
        if ($attLogSchema && !$attLogSchema->getColumn('branch_id')) {
            $this->addColumn('{{%hr_attendance_log}}', 'branch_id', $this->integer()->null()->after('company_id'));
            // Map clock_in_zone_id to branch_id
            foreach ($workZoneMap as $oldZoneId => $newBranchId) {
                $this->update('{{%hr_attendance_log}}', ['branch_id' => $newBranchId], ['clock_in_zone_id' => $oldZoneId]);
            }
        }

        // ═══ 8. Add branch_id to hr_geofence_event ═══
        $geoSchema = $this->db->getTableSchema('{{%hr_geofence_event}}', true);
        if ($geoSchema && !$geoSchema->getColumn('branch_id')) {
            $this->addColumn('{{%hr_geofence_event}}', 'branch_id', $this->integer()->null()->after('company_id'));
            foreach ($workZoneMap as $oldZoneId => $newBranchId) {
                $this->update('{{%hr_geofence_event}}', ['branch_id' => $newBranchId], ['zone_id' => $oldZoneId]);
            }
        }

        // ═══ 9. Add branch_id to hr_payroll_run ═══
        // hr_payroll_run.branch_id already exists but pointed to os_location
        // Remap its values
        $payrollSchema = $this->db->getTableSchema('{{%hr_payroll_run}}', true);
        if ($payrollSchema && $payrollSchema->getColumn('branch_id')) {
            foreach ($locationMap as $oldId => $newId) {
                $this->update('{{%hr_payroll_run}}', ['branch_id' => $newId], ['branch_id' => $oldId]);
            }
        }
    }

    public function safeDown()
    {
        // Remove added columns
        $tables = [
            '{{%hr_geofence_event}}' => 'branch_id',
            '{{%hr_attendance_log}}' => 'branch_id',
            '{{%hr_employee_extended}}' => 'unified_branch_id',
            '{{%user}}' => 'branch_id',
            '{{%inventory_stock_locations}}' => 'branch_id',
        ];

        foreach ($tables as $table => $col) {
            $schema = $this->db->getTableSchema($table, true);
            if ($schema && $schema->getColumn($col)) {
                $this->dropColumn($table, $col);
            }
        }

        $this->dropTable('{{%branch}}');
    }
}
