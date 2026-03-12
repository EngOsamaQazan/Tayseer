<?php

use yii\db\Migration;

/**
 * Expands the jobs system with address, location, working hours, phones, and ratings.
 */
class m260213_120000_expand_jobs_system extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        // 1) Expand os_jobs table
        $this->addColumn('{{%jobs}}', 'address_city', $this->string(255)->null()->comment('المدينة')->after('job_type'));
        $this->addColumn('{{%jobs}}', 'address_area', $this->string(255)->null()->comment('المنطقة/الحي')->after('address_city'));
        $this->addColumn('{{%jobs}}', 'address_street', $this->string(500)->null()->comment('الشارع/العنوان التفصيلي')->after('address_area'));
        $this->addColumn('{{%jobs}}', 'address_building', $this->string(255)->null()->comment('المبنى/الطابق/الرقم')->after('address_street'));
        $this->addColumn('{{%jobs}}', 'latitude', $this->decimal(10, 8)->null()->comment('خط العرض GPS')->after('address_building'));
        $this->addColumn('{{%jobs}}', 'longitude', $this->decimal(11, 8)->null()->comment('خط الطول GPS')->after('latitude'));
        $this->addColumn('{{%jobs}}', 'email', $this->string(255)->null()->comment('البريد الإلكتروني')->after('longitude'));
        $this->addColumn('{{%jobs}}', 'website', $this->string(255)->null()->comment('الموقع الإلكتروني')->after('email'));
        $this->addColumn('{{%jobs}}', 'notes', $this->text()->null()->comment('ملاحظات عامة')->after('website'));
        $this->addColumn('{{%jobs}}', 'status', $this->tinyInteger()->notNull()->defaultValue(1)->comment('1=فعال، 0=غير فعال')->after('notes'));
        $this->addColumn('{{%jobs}}', 'created_at', $this->integer()->null()->after('status'));
        $this->addColumn('{{%jobs}}', 'updated_at', $this->integer()->null()->after('created_at'));
        $this->addColumn('{{%jobs}}', 'created_by', $this->integer()->null()->after('updated_at'));
        $this->addColumn('{{%jobs}}', 'updated_by', $this->integer()->null()->after('created_by'));
        $this->addColumn('{{%jobs}}', 'is_deleted', $this->tinyInteger()->notNull()->defaultValue(0)->after('updated_by'));

        $this->createIndex('idx_jobs_type', '{{%jobs}}', 'job_type');
        $this->createIndex('idx_jobs_status', '{{%jobs}}', 'status');
        $this->createIndex('idx_jobs_deleted', '{{%jobs}}', 'is_deleted');

        // 2) Create os_jobs_phones table
        $this->createTable('{{%jobs_phones}}', [
            'id' => $this->primaryKey(),
            'job_id' => $this->integer()->notNull()->comment('جهة العمل'),
            'phone_number' => $this->string(20)->notNull()->comment('رقم الهاتف'),
            'phone_type' => "ENUM('office','mobile','fax','whatsapp') NOT NULL DEFAULT 'office' COMMENT 'نوع الرقم'",
            'employee_name' => $this->string(255)->null()->comment('اسم الموظف المسؤول'),
            'employee_position' => $this->string(255)->null()->comment('منصب الموظف'),
            'department' => $this->string(255)->null()->comment('القسم الذي يديره'),
            'is_primary' => $this->tinyInteger()->notNull()->defaultValue(0)->comment('رقم أساسي؟'),
            'notes' => $this->string(500)->null(),
            'created_at' => $this->integer()->null(),
            'updated_at' => $this->integer()->null(),
            'created_by' => $this->integer()->null(),
            'is_deleted' => $this->tinyInteger()->notNull()->defaultValue(0),
        ], 'ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');

        $this->createIndex('idx_jobs_phones_job_id', '{{%jobs_phones}}', 'job_id');
        $this->createIndex('idx_jobs_phones_phone', '{{%jobs_phones}}', 'phone_number');
        $this->addForeignKey('fk_jobs_phones_job', '{{%jobs_phones}}', 'job_id', '{{%jobs}}', 'id', 'RESTRICT', 'CASCADE');

        // 3) Create os_jobs_working_hours table
        $this->createTable('{{%jobs_working_hours}}', [
            'id' => $this->primaryKey(),
            'job_id' => $this->integer()->notNull()->comment('جهة العمل'),
            'day_of_week' => $this->tinyInteger()->notNull()->comment('0=أحد، 1=اثنين... 6=سبت'),
            'opening_time' => $this->time()->null()->comment('وقت بداية الدوام'),
            'closing_time' => $this->time()->null()->comment('وقت نهاية الدوام'),
            'is_closed' => $this->tinyInteger()->notNull()->defaultValue(0)->comment('مغلق هذا اليوم؟'),
            'notes' => $this->string(255)->null(),
        ], 'ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');

        $this->createIndex('idx_jobs_hours_job_id', '{{%jobs_working_hours}}', 'job_id');
        $this->createIndex('uk_job_day', '{{%jobs_working_hours}}', ['job_id', 'day_of_week'], true);
        $this->addForeignKey('fk_jobs_hours_job', '{{%jobs_working_hours}}', 'job_id', '{{%jobs}}', 'id', 'RESTRICT', 'CASCADE');

        // 4) Create os_jobs_ratings table
        $this->createTable('{{%jobs_ratings}}', [
            'id' => $this->primaryKey(),
            'job_id' => $this->integer()->notNull()->comment('جهة العمل'),
            'rating_type' => "ENUM('judicial_response','cooperation','speed','overall') NOT NULL COMMENT 'نوع التقييم'",
            'rating_value' => $this->tinyInteger()->notNull()->comment('التقييم من 1 إلى 5'),
            'contract_id' => $this->integer()->null()->comment('العقد المرتبط'),
            'judiciary_id' => $this->integer()->null()->comment('القضية المرتبطة'),
            'review_text' => $this->text()->null()->comment('تفاصيل وملاحظات التقييم'),
            'rated_by' => $this->integer()->notNull()->comment('المستخدم المُقيّم'),
            'rated_at' => $this->dateTime()->notNull()->defaultExpression('CURRENT_TIMESTAMP')->comment('تاريخ التقييم'),
            'created_at' => $this->integer()->null(),
            'updated_at' => $this->integer()->null(),
            'is_deleted' => $this->tinyInteger()->notNull()->defaultValue(0),
        ], 'ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');

        $this->createIndex('idx_jobs_ratings_job_id', '{{%jobs_ratings}}', 'job_id');
        $this->createIndex('idx_jobs_ratings_type', '{{%jobs_ratings}}', 'rating_type');
        $this->createIndex('idx_jobs_ratings_contract', '{{%jobs_ratings}}', 'contract_id');
        $this->createIndex('idx_jobs_ratings_judiciary', '{{%jobs_ratings}}', 'judiciary_id');
        $this->addForeignKey('fk_jobs_ratings_job', '{{%jobs_ratings}}', 'job_id', '{{%jobs}}', 'id', 'RESTRICT', 'CASCADE');
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        // Drop tables in reverse order
        $this->dropForeignKey('fk_jobs_ratings_job', '{{%jobs_ratings}}');
        $this->dropTable('{{%jobs_ratings}}');

        $this->dropForeignKey('fk_jobs_hours_job', '{{%jobs_working_hours}}');
        $this->dropTable('{{%jobs_working_hours}}');

        $this->dropForeignKey('fk_jobs_phones_job', '{{%jobs_phones}}');
        $this->dropTable('{{%jobs_phones}}');

        // Remove indexes from os_jobs
        $this->dropIndex('idx_jobs_deleted', '{{%jobs}}');
        $this->dropIndex('idx_jobs_status', '{{%jobs}}');
        $this->dropIndex('idx_jobs_type', '{{%jobs}}');

        // Remove added columns from os_jobs
        $this->dropColumn('{{%jobs}}', 'is_deleted');
        $this->dropColumn('{{%jobs}}', 'updated_by');
        $this->dropColumn('{{%jobs}}', 'created_by');
        $this->dropColumn('{{%jobs}}', 'updated_at');
        $this->dropColumn('{{%jobs}}', 'created_at');
        $this->dropColumn('{{%jobs}}', 'status');
        $this->dropColumn('{{%jobs}}', 'notes');
        $this->dropColumn('{{%jobs}}', 'website');
        $this->dropColumn('{{%jobs}}', 'email');
        $this->dropColumn('{{%jobs}}', 'longitude');
        $this->dropColumn('{{%jobs}}', 'latitude');
        $this->dropColumn('{{%jobs}}', 'address_building');
        $this->dropColumn('{{%jobs}}', 'address_street');
        $this->dropColumn('{{%jobs}}', 'address_area');
        $this->dropColumn('{{%jobs}}', 'address_city');
    }
}
