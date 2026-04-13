<?php

use yii\db\Migration;

/**
 * Adds columns that are referenced by application code but were never formally migrated.
 * Each column-add is guarded by a schema check so the migration is idempotent.
 */
class m260412_100000_add_missing_judiciary_columns extends Migration
{
    public function safeUp()
    {
        // ── os_judiciary ──
        $t = $this->db->getTableSchema('{{%judiciary}}', true);
        if ($t) {
            if (!isset($t->columns['case_status'])) {
                $this->addColumn('{{%judiciary}}', 'case_status', $this->string(20)->defaultValue('open')->after('is_deleted'));
            }
            if (!isset($t->columns['last_check_date'])) {
                $this->addColumn('{{%judiciary}}', 'last_check_date', $this->date()->null()->after('case_status'));
            }
            if (!isset($t->columns['company_id'])) {
                $this->addColumn('{{%judiciary}}', 'company_id', $this->integer()->null()->after('lawyer_id'));
            }
        }

        // ── os_judiciary_actions ──
        $t = $this->db->getTableSchema('{{%judiciary_actions}}', true);
        if ($t) {
            if (!isset($t->columns['action_type'])) {
                $this->addColumn('{{%judiciary_actions}}', 'action_type', $this->string(30)->null()->after('name'));
            }
            if (!isset($t->columns['action_nature'])) {
                $this->addColumn('{{%judiciary_actions}}', 'action_nature', $this->string(20)->defaultValue('process')->after('action_type'));
            }
        }

        // ── os_judiciary_customers_actions ──
        $t = $this->db->getTableSchema('{{%judiciary_customers_actions}}', true);
        if ($t) {
            if (!isset($t->columns['parent_id'])) {
                $this->addColumn('{{%judiciary_customers_actions}}', 'parent_id', $this->integer()->null()->after('is_deleted'));
            }
            if (!isset($t->columns['request_status'])) {
                $this->addColumn('{{%judiciary_customers_actions}}', 'request_status', $this->string(20)->null()->after('parent_id'));
            }
            if (!isset($t->columns['decision_text'])) {
                $this->addColumn('{{%judiciary_customers_actions}}', 'decision_text', $this->text()->null()->after('request_status'));
            }
            if (!isset($t->columns['decision_file'])) {
                $this->addColumn('{{%judiciary_customers_actions}}', 'decision_file', $this->string(255)->null()->after('decision_text'));
            }
            if (!isset($t->columns['is_current'])) {
                $this->addColumn('{{%judiciary_customers_actions}}', 'is_current', $this->tinyInteger()->defaultValue(1)->after('decision_file'));
            }
            if (!isset($t->columns['amount'])) {
                $this->addColumn('{{%judiciary_customers_actions}}', 'amount', $this->decimal(12, 2)->null()->after('is_current'));
            }
            if (!isset($t->columns['request_target'])) {
                $this->addColumn('{{%judiciary_customers_actions}}', 'request_target', $this->string(20)->null()->after('amount'));
            }
        }

        // ── os_judiciary_defendant_stage ──
        $t = $this->db->getTableSchema('{{%judiciary_defendant_stage}}', true);
        if ($t) {
            if (!isset($t->columns['notification_date'])) {
                $this->addColumn('{{%judiciary_defendant_stage}}', 'notification_date', $this->date()->null()->after('stage_updated_at'));
            }
            if (!isset($t->columns['comprehensive_request_date'])) {
                $this->addColumn('{{%judiciary_defendant_stage}}', 'comprehensive_request_date', $this->date()->null()->after('notification_date'));
            }
        }
    }

    public function safeDown()
    {
        // Reverse in order
        $t = $this->db->getTableSchema('{{%judiciary_defendant_stage}}', true);
        if ($t) {
            if (isset($t->columns['comprehensive_request_date'])) $this->dropColumn('{{%judiciary_defendant_stage}}', 'comprehensive_request_date');
            if (isset($t->columns['notification_date'])) $this->dropColumn('{{%judiciary_defendant_stage}}', 'notification_date');
        }

        $t = $this->db->getTableSchema('{{%judiciary_customers_actions}}', true);
        if ($t) {
            if (isset($t->columns['request_target'])) $this->dropColumn('{{%judiciary_customers_actions}}', 'request_target');
            if (isset($t->columns['amount'])) $this->dropColumn('{{%judiciary_customers_actions}}', 'amount');
            if (isset($t->columns['is_current'])) $this->dropColumn('{{%judiciary_customers_actions}}', 'is_current');
            if (isset($t->columns['decision_file'])) $this->dropColumn('{{%judiciary_customers_actions}}', 'decision_file');
            if (isset($t->columns['decision_text'])) $this->dropColumn('{{%judiciary_customers_actions}}', 'decision_text');
            if (isset($t->columns['request_status'])) $this->dropColumn('{{%judiciary_customers_actions}}', 'request_status');
            if (isset($t->columns['parent_id'])) $this->dropColumn('{{%judiciary_customers_actions}}', 'parent_id');
        }

        $t = $this->db->getTableSchema('{{%judiciary_actions}}', true);
        if ($t) {
            if (isset($t->columns['action_nature'])) $this->dropColumn('{{%judiciary_actions}}', 'action_nature');
            if (isset($t->columns['action_type'])) $this->dropColumn('{{%judiciary_actions}}', 'action_type');
        }

        $t = $this->db->getTableSchema('{{%judiciary}}', true);
        if ($t) {
            if (isset($t->columns['company_id'])) $this->dropColumn('{{%judiciary}}', 'company_id');
            if (isset($t->columns['last_check_date'])) $this->dropColumn('{{%judiciary}}', 'last_check_date');
            if (isset($t->columns['case_status'])) $this->dropColumn('{{%judiciary}}', 'case_status');
        }
    }
}
