<?php

use yii\db\Migration;

/**
 * Persisted, auditable record of every issued "براءة ذمة" (clearance certificate).
 *
 * Each row is an immutable snapshot of the certificate as of the moment it
 * was officially issued. The QR printed on the certificate links back to
 * actionVerifyClearance, which looks up the row by cert_number and validates
 * the HMAC signature stored here — so QR scans work permanently after issuance.
 *
 * Status lifecycle:
 *   - active  : issued, valid unless later expired by new contract movements
 *   - revoked : explicitly cancelled by an admin (unlocks re-issue)
 *
 * "expired" is computed at read-time by comparing issued_at with the latest
 * movement date on the contract (payments/expenses/judiciary/sale date).
 */
class m260421_100000_create_clearance_certificates extends Migration
{
    public function safeUp()
    {
        $tableOptions = null;
        if ($this->db->driverName === 'mysql') {
            $tableOptions = 'CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE=InnoDB';
        }

        $this->createTable('{{%clearance_certificates}}', [
            'id'             => $this->primaryKey(),

            // Human-readable unique serial, e.g. CLR-2026-00001
            'cert_number'    => $this->string(32)->notNull(),

            // Target contract (no FK to avoid cascade surprises with soft-deletes)
            'contract_id'    => $this->integer()->notNull(),

            // Owning company (CompanyChecked convention)
            'company_id'     => $this->integer()->null(),

            // Issuance metadata
            'issued_at'      => $this->dateTime()->notNull(),
            'issued_by'      => $this->integer()->null(),

            // HMAC-SHA256 hex of "contract_id|cert_number|issued_date"
            'signature'      => $this->char(64)->notNull(),

            // Immutable JSON snapshot: company, customers, totals, cases, etc.
            // Raw LONGTEXT (Yii2 has no longText() builder).
            'snapshot_json'  => 'LONGTEXT NOT NULL',

            // active | revoked (expired is computed, not stored)
            'status'         => "ENUM('active','revoked') NOT NULL DEFAULT 'active'",

            // Revocation audit
            'revoked_at'     => $this->dateTime()->null(),
            'revoked_by'     => $this->integer()->null(),

            // Standard timestamps + soft delete (project convention)
            'created_at'     => $this->integer()->notNull(),
            'updated_at'     => $this->integer()->notNull(),
            'is_deleted'     => $this->tinyInteger(1)->notNull()->defaultValue(0),
        ], $tableOptions);

        $this->createIndex(
            'uq-clearance_certificates-cert_number',
            '{{%clearance_certificates}}',
            'cert_number',
            true
        );
        $this->createIndex(
            'idx-clearance_certificates-contract_status',
            '{{%clearance_certificates}}',
            ['contract_id', 'status']
        );
        $this->createIndex(
            'idx-clearance_certificates-issued_at',
            '{{%clearance_certificates}}',
            'issued_at'
        );
    }

    public function safeDown()
    {
        $this->dropTable('{{%clearance_certificates}}');
    }
}
