# Tayseer ERP — HR & Field Operations Module
## Complete Technical Specification v1.0

---

## Executive Overview

This document specifies a **production-grade HR module** for the Tayseer cloud ERP, designed for instalment/financing companies operating in Jordan and the region. The module extends the existing Yii2 codebase with:

- **Employee lifecycle management** (hire → manage → offboard)
- **Attendance, leave, and payroll** with multi-branch support
- **Field staff tracking** for court messengers and collectors with privacy-first design
- **Integration** with existing Contracts, Judiciary, Tasks, and Accounting modules
- **RBAC-based permissions** aligned with the existing `yii\rbac\DbManager`
- **Multi-tenant isolation** via `company_id` on every table

**Existing tables preserved**: `os_user`, `os_department`, `os_designation`, `os_attendance`, `os_leave_request`, `os_leave_types`, `os_leave_policy`, `os_holidays`, `os_workdays`, `os_employee_files`. New tables extend — never replace — existing schema.

---

## 1. SCOPE & FEATURE SPECIFICATION

### 1.1 User Stories & Acceptance Criteria

| ID | Story | Acceptance Criteria |
|----|-------|-------------------|
| HR-01 | As HR Admin, I can manage employee profiles | CRUD with photo, documents, bank info, emergency contacts |
| HR-02 | As HR Admin, I can define org structure | Departments, positions, grades, shift templates per company |
| HR-03 | As Employee, I can clock in/out | Web/mobile, optional geofence, records lat/lng |
| HR-04 | As Manager, I can approve leave requests | Workflow with delegation, balance check, audit trail |
| HR-05 | As HR Admin, I can run payroll | Monthly run with preview, lock, payslips, journal entries |
| HR-06 | As HR Admin, I can track employee documents | Versioned docs with expiry alerts |
| HR-07 | As Field Manager, I can assign field tasks | Linked to case/customer/court with location tracking |
| HR-08 | As Court Messenger, I can submit field events | Arrival, document served, photo proof, offline support |
| HR-09 | As Location Supervisor, I can view field map | Live map, route playback, privacy indicators |
| HR-10 | As Employee, I can view own payslips | Self-service portal with limited data |
| HR-11 | As HR Admin, I can onboard new employees | Checklist, asset assignment, document collection |
| HR-12 | As HR Admin, I can manage KPIs and evaluations | Templates, scoring, improvement plans |
| HR-13 | As HR Admin, I can issue warnings | Disciplinary actions with documents and approvals |
| HR-14 | As Employee, I must consent to location tracking | Digital consent with timestamp and version |

---

## 2. DATABASE SCHEMA

### 2.1 Design Principles
- **Prefix**: `os_hr_` for all new HR tables
- **Multi-tenant**: Every table has `company_id INT NOT NULL`
- **Audit**: `created_at INT`, `updated_at INT`, `created_by INT`, `updated_by INT`
- **Soft delete**: `is_deleted TINYINT(1) DEFAULT 0`
- **Indexes**: Composite `(company_id, ...)` on every query path
- **Encoding**: `utf8mb4` for Arabic text support

### 2.2 Core HR Tables

#### 2.2.1 `os_hr_employee_extended` — Extended Employee Profile
```sql
CREATE TABLE os_hr_employee_extended (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    company_id      INT NOT NULL,
    user_id         INT NOT NULL,                    -- FK → os_user.id
    employee_code   VARCHAR(20),                     -- e.g. EMP-001
    national_id     VARCHAR(20),
    date_of_birth   DATE,
    blood_type      ENUM('A+','A-','B+','B-','AB+','AB-','O+','O-'),
    address_text    TEXT,
    city_id         INT,                             -- FK → os_city.id
    bank_id         INT,                             -- FK → os_bancks.id
    bank_account_no VARCHAR(30),
    iban            VARCHAR(34),
    social_security_no VARCHAR(20),
    tax_number      VARCHAR(20),
    employment_type ENUM('full_time','part_time','contract','probation','intern') DEFAULT 'full_time',
    grade_id        INT,                             -- FK → os_hr_grade.id
    branch_id       INT,                             -- FK → os_hr_branch.id
    shift_id        INT,                             -- FK → os_hr_shift.id
    start_date      DATE NOT NULL,
    end_date        DATE,                            -- NULL = ongoing
    probation_end   DATE,
    termination_reason TEXT,
    is_field_staff  TINYINT(1) DEFAULT 0,           -- court messenger / collector
    field_role      ENUM('court_messenger','collector','inspector','other'),
    commission_eligible TINYINT(1) DEFAULT 0,
    notes           TEXT,
    -- Audit
    created_at      INT NOT NULL,
    updated_at      INT NOT NULL,
    created_by      INT NOT NULL,
    updated_by      INT NOT NULL,
    is_deleted      TINYINT(1) DEFAULT 0,
    INDEX idx_company (company_id),
    INDEX idx_user (user_id),
    INDEX idx_company_user (company_id, user_id),
    INDEX idx_company_branch (company_id, branch_id),
    INDEX idx_field_staff (company_id, is_field_staff),
    UNIQUE KEY uk_company_user (company_id, user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

#### 2.2.2 `os_hr_emergency_contact`
```sql
CREATE TABLE os_hr_emergency_contact (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    company_id      INT NOT NULL,
    user_id         INT NOT NULL,
    contact_name    VARCHAR(100) NOT NULL,
    relationship    VARCHAR(50),
    phone           VARCHAR(20) NOT NULL,
    phone2          VARCHAR(20),
    address         TEXT,
    is_primary      TINYINT(1) DEFAULT 1,
    created_at      INT NOT NULL,
    updated_at      INT NOT NULL,
    created_by      INT NOT NULL,
    updated_by      INT NOT NULL,
    is_deleted      TINYINT(1) DEFAULT 0,
    INDEX idx_company_user (company_id, user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

#### 2.2.3 `os_hr_employee_document`
```sql
CREATE TABLE os_hr_employee_document (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    company_id      INT NOT NULL,
    user_id         INT NOT NULL,
    doc_type        ENUM('national_id','passport','driving_license','birth_cert',
                         'employment_contract','certificate','medical','clearance',
                         'social_security','other') NOT NULL,
    doc_name        VARCHAR(150) NOT NULL,
    file_path       VARCHAR(500) NOT NULL,
    file_size       INT,
    mime_type       VARCHAR(100),
    version         INT DEFAULT 1,
    issue_date      DATE,
    expiry_date     DATE,
    is_verified     TINYINT(1) DEFAULT 0,
    verified_by     INT,
    verified_at     INT,
    notes           TEXT,
    created_at      INT NOT NULL,
    updated_at      INT NOT NULL,
    created_by      INT NOT NULL,
    updated_by      INT NOT NULL,
    is_deleted      TINYINT(1) DEFAULT 0,
    INDEX idx_company_user (company_id, user_id),
    INDEX idx_expiry (company_id, expiry_date),
    INDEX idx_type (company_id, doc_type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

#### 2.2.4 `os_hr_employee_skill`
```sql
CREATE TABLE os_hr_employee_skill (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    company_id      INT NOT NULL,
    user_id         INT NOT NULL,
    skill_name      VARCHAR(100) NOT NULL,
    proficiency     ENUM('beginner','intermediate','advanced','expert') DEFAULT 'intermediate',
    notes           TEXT,
    created_at      INT NOT NULL,
    updated_at      INT NOT NULL,
    created_by      INT NOT NULL,
    updated_by      INT NOT NULL,
    is_deleted      TINYINT(1) DEFAULT 0,
    INDEX idx_company_user (company_id, user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

#### 2.2.5 `os_hr_transfer_history`
```sql
CREATE TABLE os_hr_transfer_history (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    company_id      INT NOT NULL,
    user_id         INT NOT NULL,
    from_branch_id  INT,
    to_branch_id    INT NOT NULL,
    from_department_id INT,
    to_department_id INT,
    from_designation_id INT,
    to_designation_id INT,
    transfer_date   DATE NOT NULL,
    effective_date  DATE NOT NULL,
    reason          TEXT,
    approved_by     INT,
    approved_at     INT,
    status          ENUM('pending','approved','rejected','cancelled') DEFAULT 'pending',
    created_at      INT NOT NULL,
    updated_at      INT NOT NULL,
    created_by      INT NOT NULL,
    updated_by      INT NOT NULL,
    is_deleted      TINYINT(1) DEFAULT 0,
    INDEX idx_company_user (company_id, user_id),
    INDEX idx_status (company_id, status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

### 2.3 Organization Setup Tables

#### 2.3.1 `os_hr_branch`
```sql
CREATE TABLE os_hr_branch (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    company_id      INT NOT NULL,
    name            VARCHAR(100) NOT NULL,
    code            VARCHAR(20),
    address         TEXT,
    city_id         INT,
    latitude        DECIMAL(10,7),
    longitude       DECIMAL(10,7),
    geofence_radius INT DEFAULT 200,              -- meters
    phone           VARCHAR(20),
    manager_id      INT,                          -- FK → os_user.id
    status          ENUM('active','inactive') DEFAULT 'active',
    created_at      INT NOT NULL,
    updated_at      INT NOT NULL,
    created_by      INT NOT NULL,
    updated_by      INT NOT NULL,
    is_deleted      TINYINT(1) DEFAULT 0,
    INDEX idx_company (company_id),
    INDEX idx_status (company_id, status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

#### 2.3.2 `os_hr_grade`
```sql
CREATE TABLE os_hr_grade (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    company_id      INT NOT NULL,
    name            VARCHAR(100) NOT NULL,
    level           INT DEFAULT 1,
    min_salary      DECIMAL(10,2),
    max_salary      DECIMAL(10,2),
    description     TEXT,
    status          ENUM('active','inactive') DEFAULT 'active',
    created_at      INT NOT NULL,
    updated_at      INT NOT NULL,
    created_by      INT NOT NULL,
    updated_by      INT NOT NULL,
    is_deleted      TINYINT(1) DEFAULT 0,
    INDEX idx_company (company_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

#### 2.3.3 `os_hr_shift`
```sql
CREATE TABLE os_hr_shift (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    company_id      INT NOT NULL,
    name            VARCHAR(100) NOT NULL,
    start_time      TIME NOT NULL,
    end_time        TIME NOT NULL,
    break_start     TIME,
    break_end       TIME,
    break_duration  INT DEFAULT 60,               -- minutes
    late_threshold  INT DEFAULT 15,               -- minutes grace period
    early_leave_threshold INT DEFAULT 15,
    overtime_after  INT DEFAULT 0,                -- minutes after shift end
    is_flexible     TINYINT(1) DEFAULT 0,
    flex_window     INT DEFAULT 60,               -- minutes flexibility
    status          ENUM('active','inactive') DEFAULT 'active',
    created_at      INT NOT NULL,
    updated_at      INT NOT NULL,
    created_by      INT NOT NULL,
    updated_by      INT NOT NULL,
    is_deleted      TINYINT(1) DEFAULT 0,
    INDEX idx_company (company_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

#### 2.3.4 `os_hr_work_calendar`
```sql
CREATE TABLE os_hr_work_calendar (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    company_id      INT NOT NULL,
    branch_id       INT,                          -- NULL = company-wide
    calendar_date   DATE NOT NULL,
    day_type        ENUM('working','weekend','holiday','custom_off') NOT NULL,
    holiday_id      INT,                          -- FK → os_holidays.id
    shift_id        INT,                          -- override shift for this day
    notes           VARCHAR(255),
    created_at      INT NOT NULL,
    updated_at      INT NOT NULL,
    created_by      INT NOT NULL,
    updated_by      INT NOT NULL,
    INDEX idx_company_date (company_id, calendar_date),
    INDEX idx_branch_date (company_id, branch_id, calendar_date),
    UNIQUE KEY uk_company_branch_date (company_id, branch_id, calendar_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

### 2.4 Attendance Tables (extending existing `os_attendance`)

#### 2.4.1 `os_hr_attendance` — New comprehensive attendance
```sql
CREATE TABLE os_hr_attendance (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    company_id      INT NOT NULL,
    user_id         INT NOT NULL,
    attendance_date DATE NOT NULL,
    shift_id        INT,
    -- Check-in
    check_in_time   DATETIME,
    check_in_method ENUM('web','mobile','biometric','qr','manual') DEFAULT 'web',
    check_in_lat    DECIMAL(10,7),
    check_in_lng    DECIMAL(10,7),
    check_in_within_geofence TINYINT(1),
    check_in_device_hash VARCHAR(64),
    -- Check-out
    check_out_time  DATETIME,
    check_out_method ENUM('web','mobile','biometric','qr','manual') DEFAULT 'web',
    check_out_lat   DECIMAL(10,7),
    check_out_lng   DECIMAL(10,7),
    check_out_within_geofence TINYINT(1),
    check_out_device_hash VARCHAR(64),
    -- Computed
    total_hours     DECIMAL(5,2),
    overtime_hours  DECIMAL(5,2) DEFAULT 0,
    late_minutes    INT DEFAULT 0,
    early_leave_minutes INT DEFAULT 0,
    break_minutes   INT DEFAULT 0,
    -- Status
    status          ENUM('present','absent','late','half_day','on_leave','holiday','weekend','field_duty') DEFAULT 'present',
    -- Manual adjustment
    is_adjusted     TINYINT(1) DEFAULT 0,
    adjusted_by     INT,
    adjusted_at     INT,
    adjustment_reason TEXT,
    adjustment_approved_by INT,
    adjustment_approved_at INT,
    -- Notes
    notes           TEXT,
    -- Audit
    created_at      INT NOT NULL,
    updated_at      INT NOT NULL,
    created_by      INT NOT NULL,
    updated_by      INT NOT NULL,
    is_deleted      TINYINT(1) DEFAULT 0,
    INDEX idx_company_user_date (company_id, user_id, attendance_date),
    INDEX idx_company_date (company_id, attendance_date),
    INDEX idx_status (company_id, status, attendance_date),
    UNIQUE KEY uk_company_user_date (company_id, user_id, attendance_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

### 2.5 Leave Tables (extending existing)

#### 2.5.1 `os_hr_leave_balance`
```sql
CREATE TABLE os_hr_leave_balance (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    company_id      INT NOT NULL,
    user_id         INT NOT NULL,
    leave_type_id   INT NOT NULL,                 -- FK → os_leave_types.id
    year            YEAR NOT NULL,
    entitled_days   DECIMAL(5,1) NOT NULL,
    used_days       DECIMAL(5,1) DEFAULT 0,
    carried_over    DECIMAL(5,1) DEFAULT 0,
    adjustment_days DECIMAL(5,1) DEFAULT 0,
    pending_days    DECIMAL(5,1) DEFAULT 0,       -- awaiting approval
    remaining_days  DECIMAL(5,1) GENERATED ALWAYS AS
        (entitled_days + carried_over + adjustment_days - used_days - pending_days) STORED,
    created_at      INT NOT NULL,
    updated_at      INT NOT NULL,
    created_by      INT NOT NULL,
    updated_by      INT NOT NULL,
    INDEX idx_company_user_year (company_id, user_id, year),
    UNIQUE KEY uk_company_user_type_year (company_id, user_id, leave_type_id, year)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

#### 2.5.2 `os_hr_leave_delegation`
```sql
CREATE TABLE os_hr_leave_delegation (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    company_id      INT NOT NULL,
    delegator_id    INT NOT NULL,                 -- original approver
    delegate_id     INT NOT NULL,                 -- temporary approver
    start_date      DATE NOT NULL,
    end_date        DATE NOT NULL,
    reason          VARCHAR(255),
    status          ENUM('active','expired','cancelled') DEFAULT 'active',
    created_at      INT NOT NULL,
    updated_at      INT NOT NULL,
    created_by      INT NOT NULL,
    updated_by      INT NOT NULL,
    is_deleted      TINYINT(1) DEFAULT 0,
    INDEX idx_company_delegator (company_id, delegator_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

### 2.6 Payroll Tables

#### 2.6.1 `os_hr_salary_component`
```sql
CREATE TABLE os_hr_salary_component (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    company_id      INT NOT NULL,
    code            VARCHAR(20) NOT NULL,         -- e.g. BASE, TRANSPORT, HOUSING
    name            VARCHAR(100) NOT NULL,
    name_en         VARCHAR(100),
    component_type  ENUM('earning','deduction') NOT NULL,
    calculation     ENUM('fixed','percentage','formula') DEFAULT 'fixed',
    percentage_of   INT,                          -- FK → self.id (e.g., 10% of BASE)
    default_amount  DECIMAL(12,2) DEFAULT 0,
    is_taxable      TINYINT(1) DEFAULT 1,
    is_mandatory    TINYINT(1) DEFAULT 0,
    sort_order      INT DEFAULT 0,
    status          ENUM('active','inactive') DEFAULT 'active',
    created_at      INT NOT NULL,
    updated_at      INT NOT NULL,
    created_by      INT NOT NULL,
    updated_by      INT NOT NULL,
    is_deleted      TINYINT(1) DEFAULT 0,
    INDEX idx_company (company_id),
    UNIQUE KEY uk_company_code (company_id, code)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

#### 2.6.2 `os_hr_employee_salary`
```sql
CREATE TABLE os_hr_employee_salary (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    company_id      INT NOT NULL,
    user_id         INT NOT NULL,
    component_id    INT NOT NULL,                 -- FK → os_hr_salary_component.id
    amount          DECIMAL(12,2) NOT NULL,
    currency        CHAR(3) DEFAULT 'JOD',
    effective_from  DATE NOT NULL,
    effective_to    DATE,                         -- NULL = ongoing
    notes           TEXT,
    created_at      INT NOT NULL,
    updated_at      INT NOT NULL,
    created_by      INT NOT NULL,
    updated_by      INT NOT NULL,
    is_deleted      TINYINT(1) DEFAULT 0,
    INDEX idx_company_user (company_id, user_id),
    INDEX idx_effective (company_id, user_id, effective_from)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

#### 2.6.3 `os_hr_payroll_run`
```sql
CREATE TABLE os_hr_payroll_run (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    company_id      INT NOT NULL,
    run_code        VARCHAR(20) NOT NULL,         -- e.g. PAY-2025-01
    period_month    INT NOT NULL,                 -- 1-12
    period_year     INT NOT NULL,
    branch_id       INT,                          -- NULL = all branches
    total_employees INT DEFAULT 0,
    total_gross     DECIMAL(14,2) DEFAULT 0,
    total_deductions DECIMAL(14,2) DEFAULT 0,
    total_net       DECIMAL(14,2) DEFAULT 0,
    status          ENUM('draft','preview','approved','locked','posted','cancelled') DEFAULT 'draft',
    approved_by     INT,
    approved_at     INT,
    locked_by       INT,
    locked_at       INT,
    posted_to_accounting TINYINT(1) DEFAULT 0,
    journal_entry_id INT,                         -- FK → accounting journal
    notes           TEXT,
    created_at      INT NOT NULL,
    updated_at      INT NOT NULL,
    created_by      INT NOT NULL,
    updated_by      INT NOT NULL,
    is_deleted      TINYINT(1) DEFAULT 0,
    INDEX idx_company_period (company_id, period_year, period_month),
    UNIQUE KEY uk_company_period_branch (company_id, period_year, period_month, branch_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

#### 2.6.4 `os_hr_payslip`
```sql
CREATE TABLE os_hr_payslip (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    company_id      INT NOT NULL,
    payroll_run_id  INT NOT NULL,
    user_id         INT NOT NULL,
    basic_salary    DECIMAL(12,2) NOT NULL,
    total_earnings  DECIMAL(12,2) DEFAULT 0,
    total_deductions DECIMAL(12,2) DEFAULT 0,
    net_salary      DECIMAL(12,2) NOT NULL,
    currency        CHAR(3) DEFAULT 'JOD',
    working_days    DECIMAL(5,1),
    present_days    DECIMAL(5,1),
    absent_days     DECIMAL(5,1),
    leave_days      DECIMAL(5,1),
    overtime_hours  DECIMAL(5,1),
    late_deduction  DECIMAL(10,2) DEFAULT 0,
    status          ENUM('draft','finalized') DEFAULT 'draft',
    created_at      INT NOT NULL,
    updated_at      INT NOT NULL,
    created_by      INT NOT NULL,
    updated_by      INT NOT NULL,
    is_deleted      TINYINT(1) DEFAULT 0,
    INDEX idx_company_run (company_id, payroll_run_id),
    INDEX idx_company_user (company_id, user_id),
    UNIQUE KEY uk_run_user (payroll_run_id, user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

#### 2.6.5 `os_hr_payslip_line`
```sql
CREATE TABLE os_hr_payslip_line (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    payslip_id      INT NOT NULL,
    component_id    INT NOT NULL,
    component_type  ENUM('earning','deduction') NOT NULL,
    description     VARCHAR(255),
    amount          DECIMAL(12,2) NOT NULL,
    sort_order      INT DEFAULT 0,
    INDEX idx_payslip (payslip_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

#### 2.6.6 `os_hr_loan`
```sql
CREATE TABLE os_hr_loan (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    company_id      INT NOT NULL,
    user_id         INT NOT NULL,
    loan_type       ENUM('advance','loan') DEFAULT 'loan',
    amount          DECIMAL(12,2) NOT NULL,
    repaid          DECIMAL(12,2) DEFAULT 0,
    monthly_deduction DECIMAL(12,2) NOT NULL,
    installments    INT NOT NULL,
    remaining_installments INT,
    start_date      DATE NOT NULL,
    status          ENUM('active','completed','cancelled') DEFAULT 'active',
    approved_by     INT,
    approved_at     INT,
    notes           TEXT,
    created_at      INT NOT NULL,
    updated_at      INT NOT NULL,
    created_by      INT NOT NULL,
    updated_by      INT NOT NULL,
    is_deleted      TINYINT(1) DEFAULT 0,
    INDEX idx_company_user (company_id, user_id),
    INDEX idx_status (company_id, status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

### 2.7 Performance & Discipline

#### 2.7.1 `os_hr_kpi_template`
```sql
CREATE TABLE os_hr_kpi_template (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    company_id      INT NOT NULL,
    name            VARCHAR(150) NOT NULL,
    description     TEXT,
    applicable_to   ENUM('all','department','designation','individual') DEFAULT 'all',
    department_id   INT,
    designation_id  INT,
    weight_total    INT DEFAULT 100,
    status          ENUM('active','inactive') DEFAULT 'active',
    created_at      INT NOT NULL,
    updated_at      INT NOT NULL,
    created_by      INT NOT NULL,
    updated_by      INT NOT NULL,
    is_deleted      TINYINT(1) DEFAULT 0,
    INDEX idx_company (company_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

#### 2.7.2 `os_hr_kpi_item`
```sql
CREATE TABLE os_hr_kpi_item (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    template_id     INT NOT NULL,
    name            VARCHAR(150) NOT NULL,
    description     TEXT,
    weight          INT DEFAULT 10,
    target_value    VARCHAR(100),
    unit            VARCHAR(50),                  -- e.g., %, count, JOD
    sort_order      INT DEFAULT 0,
    INDEX idx_template (template_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

#### 2.7.3 `os_hr_evaluation`
```sql
CREATE TABLE os_hr_evaluation (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    company_id      INT NOT NULL,
    user_id         INT NOT NULL,
    evaluator_id    INT NOT NULL,
    template_id     INT NOT NULL,
    period_start    DATE NOT NULL,
    period_end      DATE NOT NULL,
    total_score     DECIMAL(5,2),
    grade           VARCHAR(20),                  -- A, B, C, D, F
    summary         TEXT,
    status          ENUM('draft','submitted','reviewed','finalized') DEFAULT 'draft',
    reviewed_by     INT,
    reviewed_at     INT,
    created_at      INT NOT NULL,
    updated_at      INT NOT NULL,
    created_by      INT NOT NULL,
    updated_by      INT NOT NULL,
    is_deleted      TINYINT(1) DEFAULT 0,
    INDEX idx_company_user (company_id, user_id),
    INDEX idx_period (company_id, period_start, period_end)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

#### 2.7.4 `os_hr_evaluation_score`
```sql
CREATE TABLE os_hr_evaluation_score (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    evaluation_id   INT NOT NULL,
    kpi_item_id     INT NOT NULL,
    score           DECIMAL(5,2),
    actual_value    VARCHAR(100),
    comment         TEXT,
    INDEX idx_evaluation (evaluation_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

#### 2.7.5 `os_hr_disciplinary`
```sql
CREATE TABLE os_hr_disciplinary (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    company_id      INT NOT NULL,
    user_id         INT NOT NULL,
    action_type     ENUM('verbal_warning','written_warning','final_warning','suspension','termination') NOT NULL,
    reason          TEXT NOT NULL,
    incident_date   DATE,
    action_date     DATE NOT NULL,
    duration_days   INT,                          -- for suspension
    document_path   VARCHAR(500),
    response        TEXT,                         -- employee response
    issued_by       INT NOT NULL,
    approved_by     INT,
    approved_at     INT,
    status          ENUM('draft','issued','acknowledged','appealed','resolved') DEFAULT 'draft',
    created_at      INT NOT NULL,
    updated_at      INT NOT NULL,
    created_by      INT NOT NULL,
    updated_by      INT NOT NULL,
    is_deleted      TINYINT(1) DEFAULT 0,
    INDEX idx_company_user (company_id, user_id),
    INDEX idx_type (company_id, action_type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

### 2.8 Recruitment & Onboarding

#### 2.8.1 `os_hr_applicant`
```sql
CREATE TABLE os_hr_applicant (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    company_id      INT NOT NULL,
    full_name       VARCHAR(150) NOT NULL,
    email           VARCHAR(255),
    phone           VARCHAR(20),
    position_applied INT,                         -- FK → os_designation.id
    department_id   INT,
    source          ENUM('website','referral','agency','walk_in','other') DEFAULT 'other',
    resume_path     VARCHAR(500),
    stage           ENUM('applied','screening','interview','offer','hired','rejected','withdrawn') DEFAULT 'applied',
    rating          INT,                          -- 1-5
    interviewer_id  INT,
    interview_date  DATETIME,
    interview_notes TEXT,
    offer_salary    DECIMAL(12,2),
    offer_date      DATE,
    hire_date       DATE,
    hired_user_id   INT,                          -- FK → os_user.id after hired
    rejection_reason TEXT,
    notes           TEXT,
    created_at      INT NOT NULL,
    updated_at      INT NOT NULL,
    created_by      INT NOT NULL,
    updated_by      INT NOT NULL,
    is_deleted      TINYINT(1) DEFAULT 0,
    INDEX idx_company (company_id),
    INDEX idx_stage (company_id, stage)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

#### 2.8.2 `os_hr_onboarding_checklist`
```sql
CREATE TABLE os_hr_onboarding_checklist (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    company_id      INT NOT NULL,
    user_id         INT NOT NULL,
    item_name       VARCHAR(200) NOT NULL,
    category        ENUM('documents','it_setup','training','asset','access','other') DEFAULT 'other',
    assigned_to     INT,
    due_date        DATE,
    completed       TINYINT(1) DEFAULT 0,
    completed_at    INT,
    completed_by    INT,
    notes           TEXT,
    sort_order      INT DEFAULT 0,
    created_at      INT NOT NULL,
    updated_at      INT NOT NULL,
    created_by      INT NOT NULL,
    updated_by      INT NOT NULL,
    is_deleted      TINYINT(1) DEFAULT 0,
    INDEX idx_company_user (company_id, user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

### 2.9 Assets & Equipment

#### 2.9.1 `os_hr_asset_assignment`
```sql
CREATE TABLE os_hr_asset_assignment (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    company_id      INT NOT NULL,
    user_id         INT NOT NULL,
    asset_type      ENUM('laptop','phone','tablet','badge','vehicle','key','uniform','other') NOT NULL,
    asset_name      VARCHAR(150) NOT NULL,
    serial_number   VARCHAR(100),
    inventory_item_id INT,                        -- FK → os_inventory_items.id (optional)
    assigned_date   DATE NOT NULL,
    expected_return DATE,
    returned_date   DATE,
    condition_out   ENUM('new','good','fair','poor') DEFAULT 'good',
    condition_in    ENUM('new','good','fair','poor'),
    notes           TEXT,
    status          ENUM('assigned','returned','lost','damaged') DEFAULT 'assigned',
    created_at      INT NOT NULL,
    updated_at      INT NOT NULL,
    created_by      INT NOT NULL,
    updated_by      INT NOT NULL,
    is_deleted      TINYINT(1) DEFAULT 0,
    INDEX idx_company_user (company_id, user_id),
    INDEX idx_status (company_id, status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

### 2.10 Field Operations Tables

#### 2.10.1 `os_hr_field_config` — Per-tenant field tracking config
```sql
CREATE TABLE os_hr_field_config (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    company_id      INT NOT NULL,
    tracking_mode   ENUM('off','on_duty','always') DEFAULT 'on_duty',
    location_interval_seconds INT DEFAULT 120,    -- how often to capture
    min_accuracy_meters INT DEFAULT 50,           -- reject if accuracy worse
    retention_days  INT DEFAULT 90,               -- raw data retention
    require_consent TINYINT(1) DEFAULT 1,
    allow_offline   TINYINT(1) DEFAULT 1,
    geofence_enabled TINYINT(1) DEFAULT 1,
    photo_required_on_arrival TINYINT(1) DEFAULT 0,
    spoofing_detection TINYINT(1) DEFAULT 1,
    created_at      INT NOT NULL,
    updated_at      INT NOT NULL,
    created_by      INT NOT NULL,
    updated_by      INT NOT NULL,
    UNIQUE KEY uk_company (company_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

#### 2.10.2 `os_hr_field_consent` — Employee consent records
```sql
CREATE TABLE os_hr_field_consent (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    company_id      INT NOT NULL,
    user_id         INT NOT NULL,
    consent_version VARCHAR(20) NOT NULL,         -- e.g., v1.0
    policy_text_hash VARCHAR(64),                 -- SHA-256 of policy text
    consented       TINYINT(1) NOT NULL,
    consented_at    INT NOT NULL,
    ip_address      VARCHAR(45),
    device_info     VARCHAR(255),
    revoked_at      INT,
    revoke_reason   TEXT,
    created_at      INT NOT NULL,
    INDEX idx_company_user (company_id, user_id),
    INDEX idx_version (company_id, consent_version)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

#### 2.10.3 `os_hr_field_session` — On-duty sessions
```sql
CREATE TABLE os_hr_field_session (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    company_id      INT NOT NULL,
    user_id         INT NOT NULL,
    started_at      DATETIME NOT NULL,
    ended_at        DATETIME,
    start_lat       DECIMAL(10,7),
    start_lng       DECIMAL(10,7),
    end_lat         DECIMAL(10,7),
    end_lng         DECIMAL(10,7),
    total_distance_km DECIMAL(8,2),
    total_visits    INT DEFAULT 0,
    status          ENUM('active','completed','force_ended') DEFAULT 'active',
    device_hash     VARCHAR(64),
    created_at      INT NOT NULL,
    updated_at      INT NOT NULL,
    INDEX idx_company_user (company_id, user_id),
    INDEX idx_active (company_id, status, started_at),
    INDEX idx_date (company_id, started_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

#### 2.10.4 `os_hr_location_point` — Raw GPS points (HIGH VOLUME, encrypted)
```sql
CREATE TABLE os_hr_location_point (
    id              BIGINT AUTO_INCREMENT PRIMARY KEY,
    company_id      INT NOT NULL,
    user_id         INT NOT NULL,
    session_id      INT NOT NULL,                 -- FK → os_hr_field_session.id
    captured_at     DATETIME NOT NULL,
    latitude        DECIMAL(10,7) NOT NULL,
    longitude       DECIMAL(10,7) NOT NULL,
    accuracy        DECIMAL(7,2),                 -- meters
    altitude        DECIMAL(8,2),
    speed           DECIMAL(6,2),                 -- m/s
    bearing         DECIMAL(5,2),
    source          ENUM('gps','network','fused','manual') DEFAULT 'gps',
    battery_level   TINYINT,                      -- 0-100
    device_hash     VARCHAR(64),
    is_mock         TINYINT(1) DEFAULT 0,         -- detected mock location
    synced_at       DATETIME,                     -- when synced from offline
    created_at      INT NOT NULL,
    INDEX idx_company_user_time (company_id, user_id, captured_at),
    INDEX idx_session (session_id, captured_at),
    INDEX idx_retention (company_id, captured_at)  -- for cleanup jobs
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
  PARTITION BY RANGE (YEAR(captured_at) * 100 + MONTH(captured_at)) (
    PARTITION p202501 VALUES LESS THAN (202502),
    PARTITION p202502 VALUES LESS THAN (202503),
    PARTITION p202503 VALUES LESS THAN (202504),
    PARTITION p202504 VALUES LESS THAN (202505),
    PARTITION p202505 VALUES LESS THAN (202506),
    PARTITION p202506 VALUES LESS THAN (202507),
    PARTITION p_future VALUES LESS THAN MAXVALUE
);
```

#### 2.10.5 `os_hr_field_task` — Field assignments
```sql
CREATE TABLE os_hr_field_task (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    company_id      INT NOT NULL,
    assigned_to     INT NOT NULL,                 -- FK → os_user.id
    task_type       ENUM('court_filing','document_serve','collection_visit',
                         'inspection','customer_visit','other') NOT NULL,
    -- Linkage
    case_id         INT,                          -- FK → os_judiciary.id
    customer_id     INT,                          -- FK → os_customers.id
    contract_id     INT,                          -- FK → os_contracts.id
    court_id        INT,                          -- FK → os_court.id
    institution_id  INT,                          -- FK → os_hr_institution.id
    -- Details
    title           VARCHAR(255) NOT NULL,
    description     TEXT,
    priority        ENUM('low','medium','high','urgent') DEFAULT 'medium',
    due_date        DATETIME,
    -- Target location
    target_lat      DECIMAL(10,7),
    target_lng      DECIMAL(10,7),
    target_address  TEXT,
    geofence_radius INT DEFAULT 100,              -- meters
    -- Status
    status          ENUM('assigned','accepted','en_route','arrived','in_progress',
                         'completed','failed','cancelled') DEFAULT 'assigned',
    accepted_at     DATETIME,
    en_route_at     DATETIME,
    arrived_at      DATETIME,
    completed_at    DATETIME,
    -- Completion
    result          ENUM('success','partial','failed','rescheduled'),
    failure_reason  TEXT,
    -- Audit
    notes           TEXT,
    created_at      INT NOT NULL,
    updated_at      INT NOT NULL,
    created_by      INT NOT NULL,
    updated_by      INT NOT NULL,
    is_deleted      TINYINT(1) DEFAULT 0,
    INDEX idx_company_assigned (company_id, assigned_to),
    INDEX idx_status (company_id, status),
    INDEX idx_case (company_id, case_id),
    INDEX idx_customer (company_id, customer_id),
    INDEX idx_due (company_id, due_date),
    INDEX idx_date (company_id, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

#### 2.10.6 `os_hr_field_event` — Field check-in events with evidence
```sql
CREATE TABLE os_hr_field_event (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    company_id      INT NOT NULL,
    user_id         INT NOT NULL,
    session_id      INT,
    task_id         INT,                          -- FK → os_hr_field_task.id
    event_type      ENUM('arrival','departure','document_served','case_filed',
                         'payment_collected','customer_contacted','photo_evidence',
                         'note','checkpoint','sos') NOT NULL,
    latitude        DECIMAL(10,7),
    longitude       DECIMAL(10,7),
    accuracy        DECIMAL(7,2),
    captured_at     DATETIME NOT NULL,
    -- Evidence
    photo_path      VARCHAR(500),
    document_path   VARCHAR(500),
    note            TEXT,
    amount_collected DECIMAL(12,2),               -- for payment collection
    -- Metadata
    device_hash     VARCHAR(64),
    is_offline_sync TINYINT(1) DEFAULT 0,
    offline_uuid    VARCHAR(36),                  -- for dedup
    synced_at       DATETIME,
    -- Audit
    created_at      INT NOT NULL,
    updated_at      INT NOT NULL,
    created_by      INT NOT NULL,
    updated_by      INT NOT NULL,
    is_deleted      TINYINT(1) DEFAULT 0,
    INDEX idx_company_user (company_id, user_id),
    INDEX idx_task (task_id),
    INDEX idx_session (session_id),
    INDEX idx_time (company_id, captured_at),
    INDEX idx_offline_uuid (offline_uuid)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

#### 2.10.7 `os_hr_institution` — Courts/institutions with geofences
```sql
CREATE TABLE os_hr_institution (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    company_id      INT NOT NULL,
    name            VARCHAR(200) NOT NULL,
    institution_type ENUM('court','police','municipality','bank','government','other') NOT NULL,
    address         TEXT,
    city_id         INT,
    latitude        DECIMAL(10,7),
    longitude       DECIMAL(10,7),
    geofence_radius INT DEFAULT 150,              -- meters
    phone           VARCHAR(20),
    contact_person  VARCHAR(100),
    notes           TEXT,
    status          ENUM('active','inactive') DEFAULT 'active',
    created_at      INT NOT NULL,
    updated_at      INT NOT NULL,
    created_by      INT NOT NULL,
    updated_by      INT NOT NULL,
    is_deleted      TINYINT(1) DEFAULT 0,
    INDEX idx_company (company_id),
    INDEX idx_type (company_id, institution_type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

### 2.11 Audit & Notifications

#### 2.11.1 `os_hr_audit_log` — Tamper-evident audit
```sql
CREATE TABLE os_hr_audit_log (
    id              BIGINT AUTO_INCREMENT PRIMARY KEY,
    company_id      INT NOT NULL,
    user_id         INT NOT NULL,                 -- who did the action
    action          ENUM('create','update','delete','view','export','approve','reject',
                         'login','clock_in','clock_out','field_event','consent','payroll_run',
                         'location_view','bulk_action') NOT NULL,
    entity_type     VARCHAR(50) NOT NULL,         -- e.g., 'employee', 'payslip', 'location_point'
    entity_id       INT,
    old_values      JSON,                         -- before change (encrypted for sensitive)
    new_values      JSON,                         -- after change
    ip_address      VARCHAR(45),
    user_agent      VARCHAR(500),
    description     VARCHAR(500),
    checksum        VARCHAR(64),                  -- SHA-256(prev_checksum + data) for tamper evidence
    created_at      INT NOT NULL,
    INDEX idx_company_time (company_id, created_at),
    INDEX idx_entity (company_id, entity_type, entity_id),
    INDEX idx_user (company_id, user_id, created_at),
    INDEX idx_action (company_id, action, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

---

## 3. API DESIGN (REST)

### 3.1 Base URL Pattern
```
/hr/api/v1/{resource}
```

### 3.2 Endpoints

#### Employee
| Method | Endpoint | Description | Permission |
|--------|----------|-------------|------------|
| GET | /hr/employee | List employees (filtered by company) | hr.employee.view |
| GET | /hr/employee/{id} | Employee detail | hr.employee.view |
| POST | /hr/employee | Create employee | hr.employee.create |
| PUT | /hr/employee/{id} | Update employee | hr.employee.edit |
| DELETE | /hr/employee/{id} | Soft-delete | hr.employee.delete |
| GET | /hr/employee/{id}/documents | Employee documents | hr.employee.view |
| POST | /hr/employee/{id}/documents | Upload document | hr.employee.edit |
| POST | /hr/employee/{id}/transfer | Transfer employee | hr.employee.transfer |
| GET | /hr/employee/export | Export CSV/Excel | hr.employee.export |
| POST | /hr/employee/import | Import from CSV | hr.employee.import |

#### Attendance
| Method | Endpoint | Description | Permission |
|--------|----------|-------------|------------|
| POST | /hr/attendance/clock-in | Clock in (with location) | self |
| POST | /hr/attendance/clock-out | Clock out | self |
| GET | /hr/attendance | List (date range, user) | hr.attendance.view |
| POST | /hr/attendance/adjust | Manual adjustment | hr.attendance.adjust |
| PUT | /hr/attendance/adjust/{id}/approve | Approve adjustment | hr.attendance.approve |
| GET | /hr/attendance/summary | Monthly summary | hr.attendance.view |

#### Leave
| Method | Endpoint | Description | Permission |
|--------|----------|-------------|------------|
| GET | /hr/leave/balance/{userId} | Leave balances | self OR hr.leave.view |
| POST | /hr/leave/request | Submit leave request | self |
| PUT | /hr/leave/request/{id}/approve | Approve request | hr.leave.approve |
| PUT | /hr/leave/request/{id}/reject | Reject request | hr.leave.approve |
| GET | /hr/leave/calendar | Team leave calendar | hr.leave.view |

#### Payroll
| Method | Endpoint | Description | Permission |
|--------|----------|-------------|------------|
| POST | /hr/payroll/run | Start payroll run | hr.payroll.run |
| GET | /hr/payroll/run/{id}/preview | Preview payslips | hr.payroll.view |
| PUT | /hr/payroll/run/{id}/lock | Lock payroll | hr.payroll.lock |
| POST | /hr/payroll/run/{id}/post | Post to accounting | hr.payroll.post |
| GET | /hr/payslip/{id} | View payslip | self OR hr.payroll.view |
| GET | /hr/payslip/{id}/pdf | Download PDF | self OR hr.payroll.view |

#### Field Operations
| Method | Endpoint | Description | Permission |
|--------|----------|-------------|------------|
| POST | /hr/field/session/start | Start field session | field.session |
| POST | /hr/field/session/end | End field session | field.session |
| POST | /hr/field/location/batch | Submit location batch | field.session |
| POST | /hr/field/event | Submit field event | field.event |
| GET | /hr/field/task | My field tasks | field.task.view |
| PUT | /hr/field/task/{id}/status | Update task status | field.task.update |
| GET | /hr/field/map/live | Live map data | hr.field.map |
| GET | /hr/field/route/{userId}/{date} | Route playback | hr.field.route |
| POST | /hr/field/consent | Submit consent | self |

### 3.3 Validation Rules

```php
// Clock-in validation
[
    'latitude'  => 'required|numeric|between:-90,90',
    'longitude' => 'required|numeric|between:-180,180',
    'accuracy'  => 'required|numeric|max:500',
    'device_hash' => 'required|string|size:64',
    'timestamp' => 'required|date|before:+5minutes|after:-5minutes', // clock skew guard
]

// Field event validation
[
    'event_type' => 'required|in:arrival,departure,...',
    'latitude'   => 'required|numeric',
    'longitude'  => 'required|numeric',
    'task_id'    => 'nullable|exists:os_hr_field_task,id',
    'photo'      => 'nullable|file|mimes:jpg,png,pdf|max:10240',
    'offline_uuid' => 'nullable|uuid|unique:os_hr_field_event,offline_uuid',
]

// Location batch validation (anti-spoofing)
[
    'points'          => 'required|array|max:100',
    'points.*.lat'    => 'required|numeric',
    'points.*.lng'    => 'required|numeric',
    'points.*.ts'     => 'required|integer',
    'points.*.acc'    => 'required|numeric|max:500',
    // Server-side: check speed between consecutive points (>300km/h = reject)
    // Server-side: check is_mock flag from device
]
```

### 3.4 Rate Limits
| Endpoint Group | Limit | Window |
|---------------|-------|--------|
| Authentication | 5 | 1 min |
| Location batch | 30 | 1 min |
| Field events | 60 | 1 min |
| General API | 120 | 1 min |
| Export | 5 | 5 min |
| Payroll run | 3 | 10 min |

---

## 4. PERMISSION MODEL (RBAC)

### 4.1 Roles

| Role | Arabic | Scope |
|------|--------|-------|
| hr_admin | مسؤول الموارد البشرية | Full HR access (tenant-level) |
| hr_manager | مدير الموارد البشرية | HR access for assigned branch |
| location_supervisor | مشرف الميدان | View location for assigned teams |
| direct_manager | المدير المباشر | View team attendance/leave, NOT location |
| payroll_officer | مسؤول الرواتب | Payroll run, view, lock |
| field_staff | موظف ميداني | Own tasks, own location, field events |
| employee | موظف | Self-service (own profile, attendance, payslips) |
| audit_viewer | مراجع | View audit logs, no edit |

### 4.2 Permission Matrix

| Action | hr_admin | hr_manager | location_sup | direct_mgr | payroll | field_staff | employee | audit |
|--------|----------|------------|-------------|------------|---------|-------------|----------|-------|
| View all employees | ✅ | Branch | Team | Team | ❌ | ❌ | ❌ | ❌ |
| Edit employee | ✅ | Branch | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ |
| View attendance | ✅ | Branch | Team | Team | ❌ | Own | Own | ✅(R) |
| Approve leave | ✅ | Branch | ❌ | Team | ❌ | ❌ | ❌ | ❌ |
| Run payroll | ✅ | ❌ | ❌ | ❌ | ✅ | ❌ | ❌ | ❌ |
| View payslips (all) | ✅ | ❌ | ❌ | ❌ | ✅ | ❌ | ❌ | ❌ |
| View own payslip | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ❌ |
| View live map | ✅* | ❌ | ✅ | ❌ | ❌ | ❌ | ❌ | ❌ |
| View precise location | ❌ | ❌ | ✅ | ❌ | ❌ | Own | ❌ | ❌ |
| View route playback | ❌ | ❌ | ✅ | ❌ | ❌ | Own(7d) | ❌ | ❌ |
| View "on duty" status | ✅ | Branch | ✅ | Team | ❌ | ❌ | ❌ | ❌ |
| Assign field tasks | ✅ | Branch | ✅ | Team | ❌ | ❌ | ❌ | ❌ |
| Submit field events | ❌ | ❌ | ❌ | ❌ | ❌ | ✅ | ❌ | ❌ |
| View audit logs | ✅ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ✅ |
| Export data | ✅ | Branch | ❌ | ❌ | ✅ | ❌ | ❌ | ❌ |

*HR Admin can view map only if ALSO assigned `location_supervisor` role.

### 4.3 Location Privacy Rules
1. **Precise GPS** (lat/lng): Only `location_supervisor` for assigned teams + employee for own data
2. **"On duty" status** (boolean): Visible to managers
3. **Route playback**: `location_supervisor` only; employee can see own last 7 days
4. **Last known location**: `location_supervisor` only, with privacy indicator on UI
5. **Location export**: Requires `hr_admin` + `location_supervisor` combined, logged
6. **Retention**: Raw points auto-deleted after `retention_days`; aggregated summary kept

---

## 5. UI/UX SCREENS

### 5.1 Screen List

| # | Screen | Route | Primary Users |
|---|--------|-------|--------------|
| 1 | HR Dashboard | /hr/dashboard | HR Admin, Manager |
| 2 | Employee Directory | /hr/employees | HR Admin, Manager |
| 3 | Employee Profile | /hr/employee/{id} | HR Admin, Employee(self) |
| 4 | Organization Setup | /hr/organization | HR Admin |
| 5 | Attendance Board | /hr/attendance | HR Admin, Manager |
| 6 | Attendance Detail | /hr/attendance/{id} | HR Admin |
| 7 | Leave Management | /hr/leave | HR Admin, Manager |
| 8 | Leave Calendar | /hr/leave/calendar | All |
| 9 | Payroll Dashboard | /hr/payroll | Payroll Officer |
| 10 | Payroll Run Wizard | /hr/payroll/run/{id} | Payroll Officer |
| 11 | Payslip View | /hr/payslip/{id} | Employee(self), Payroll |
| 12 | Field Ops Map | /hr/field/map | Location Supervisor |
| 13 | Field Task Board | /hr/field/tasks | Location Supervisor, Manager |
| 14 | Field Task Detail | /hr/field/task/{id} | Supervisor, Field Staff |
| 15 | Mobile: Field Duty | /m/field | Field Staff |
| 16 | Recruitment Pipeline | /hr/recruitment | HR Admin |
| 17 | Performance Reviews | /hr/performance | HR Admin, Manager |
| 18 | HR Settings | /hr/settings | HR Admin |

### 5.2 Key Screen Descriptions

#### HR Dashboard
- **Top Row**: 4 stat cards — Total Employees, Present Today, On Leave, Pending Approvals
- **Row 2**: Attendance chart (line, 30 days) + Department headcount (donut)
- **Row 3**: Pending items — Leave requests, Adjustment approvals, Expiring documents
- **Row 4**: Field ops summary — On duty now, Tasks today, Completed rate
- **Sidebar Quick Actions**: Add Employee, Run Payroll, View Map

#### Employee Profile (Tabbed)
- **Header**: Photo, name, badge, department, status pill, quick actions
- **Tabs**:
  - Overview: Personal info, job info, manager, branch
  - Documents: Grid with expiry indicators, upload button
  - Attendance: Monthly calendar heat map + daily table
  - Leave: Balance cards + request history
  - Payroll: Salary structure + payslip history
  - Assets: Assigned equipment table
  - Performance: KPI scores, evaluations, disciplinary
  - Field: (if field staff) Session history, route map, task completion rate

#### Field Ops Map
- **Full-width map** (Leaflet/OpenStreetMap)
- **Left sidebar**: Filter by team, branch, task status
- **Map markers**: Field staff (color by status: green=active, orange=en_route, blue=idle)
- **Click marker** → mini popup: name, current task, last update, "View route" link
- **Privacy indicator**: Lock icon on markers with consent status
- **Right panel**: Task timeline for selected employee
- **Bottom bar**: Stats — Active sessions, Tasks in progress, Completed today

#### Mobile: Field Duty
- **Start Duty button** (large, centered) → confirms consent if first time
- **Active duty screen**:
  - Current task card (swipeable for next)
  - Navigation button (opens maps app)
  - "I've Arrived" button (captures location + auto photo)
  - Evidence capture: camera + note
  - Submit button (with offline indicator)
  - End Duty button
- **Offline mode**: Queue indicator with pending count, auto-sync on connectivity

---

## 6. WORKFLOWS

### 6.1 Employee Onboarding
```
Applicant Hired
  → Create User Account (os_user)
  → Create Extended Profile (os_hr_employee_extended)
  → Generate Employee Code
  → Create Onboarding Checklist (template-based)
  → Assign Branch + Department + Shift
  → Assign Assets
  → Create Salary Structure
  → Calculate Initial Leave Balances
  → If field staff: Request Location Consent
  → Notify Manager + IT
  → Event: employee_onboarded
```

### 6.2 Attendance Flow
```
Employee opens app/web
  → Clicks "Clock In"
  → System captures: time, lat/lng, device_hash
  → Validate: within geofence? within shift window?
  → Create os_hr_attendance record
  → If late: flag late_minutes, notify manager (configurable)
  → ...working...
  → Clicks "Clock Out"
  → Calculate: total_hours, overtime, late, early_leave
  → Update record
  → Event: attendance_recorded
```

### 6.3 Payroll Flow
```
HR Admin starts payroll run (month/year)
  → System creates os_hr_payroll_run (status=draft)
  → For each active employee in scope:
    → Fetch salary components (os_hr_employee_salary)
    → Calculate attendance days (os_hr_attendance)
    → Calculate leave days (os_leave_request)
    → Calculate overtime hours
    → Calculate deductions: late penalty, loans, advances
    → Calculate commissions (if eligible, from collection data)
    → Generate payslip + lines
  → Status → preview
  → HR Admin reviews, makes adjustments
  → HR Admin approves → status=approved
  → Finance locks → status=locked
  → Post to accounting → create journal entries
  → Status → posted
  → Payslips available to employees
  → Event: payroll_posted
```

### 6.4 Field Task Flow
```
Manager creates field task (linked to case/customer/court)
  → Assigned to field staff
  → Event: field_task_assigned
  → Field staff sees task in mobile
  → Accepts task → status=accepted
  → Starts navigation → status=en_route, captures location
  → Arrives at destination → status=arrived
    → System checks: within geofence of target?
    → Captures photo evidence
  → Completes task → status=completed
    → Submits: note, photo, collected amount (if collection)
    → Event: field_event_created
    → Auto-creates case timeline entry (if linked to case)
  → OR fails → status=failed, reason required
  → Event: field_task_completed / field_task_failed
```

### 6.5 Location Tracking Flow
```
Field staff starts duty session
  → Check consent (os_hr_field_consent) — must be valid
  → Create os_hr_field_session
  → Start background location capture (every N seconds)
  → Each point → queue locally
  → Batch upload every 2 minutes (or on connectivity)
    → Server validates: accuracy, speed check, mock check
    → Insert to os_hr_location_point
  → On task arrival: create os_hr_field_event
  → On duty end:
    → Final location batch
    → Close session (calculate distance, visits)
    → Stop background tracking
```

---

## 7. DATA PRIVACY & SECURITY

### 7.1 Encryption
| Data | At Rest | In Transit |
|------|---------|-----------|
| Passwords | bcrypt (Yii2 default) | HTTPS |
| Bank account / IBAN | AES-256-GCM (app-level) | HTTPS |
| National ID | AES-256-GCM (app-level) | HTTPS |
| Location points | Standard DB encryption | HTTPS |
| Payroll data | Standard DB encryption | HTTPS |
| Device hash | SHA-256 (one-way) | HTTPS |

### 7.2 Retention Policy
| Data Type | Retention | After Expiry |
|-----------|-----------|-------------|
| Raw location points | Configurable (30/60/90 days) | Aggregate daily summary, delete raw |
| Field events | 2 years | Archive then delete |
| Attendance records | 5 years | Archive |
| Payroll records | 7 years (legal requirement) | Archive |
| Audit logs | 7 years | Archive |
| Employee documents | Employment + 3 years | Archive |
| Consent records | Permanent | Never delete |

### 7.3 Consent Model
1. First time field staff opens app → consent screen
2. Must accept before location tracking activates
3. Consent stored with: timestamp, policy version, policy hash, IP, device
4. Employee can revoke → stops tracking, notifies HR
5. Policy changes → re-consent required (new version)

### 7.4 Anti-Spoofing (Basic)
1. **Mock location detection**: Android `isFromMockProvider()` flag sent with each point
2. **Impossible speed check**: >300 km/h between consecutive points → flag as suspicious
3. **Low accuracy rejection**: Points with accuracy > `min_accuracy_meters` config → reject
4. **Device consistency**: Same session should have same `device_hash`
5. **Timestamp validation**: Point timestamp must be within ±5 minutes of server time

### 7.5 Tamper-Evident Audit
- Each audit log row includes `checksum = SHA256(previous_checksum + row_data)`
- Chain can be verified to detect deletions or modifications
- Audit log table: no UPDATE or DELETE allowed (append-only)

---

## 8. MIGRATION PLAN

### Phase 1 — Foundation (Week 1-2)
1. Run DDL migrations for all new `os_hr_*` tables
2. Add `company_id` to existing HR tables that lack it (`os_department`, `os_designation`, `os_attendance`, etc.)
3. Backfill `company_id` from primary company for existing records
4. Create HR RBAC permissions and roles
5. No disruption to existing functionality

### Phase 2 — Core HR (Week 3-4)
1. Deploy Employee Extended module
2. Deploy enhanced Attendance module
3. Deploy Leave Balance system
4. Migrate existing data from old tables to new structure
5. Keep old tables as read-only fallback

### Phase 3 — Payroll (Week 5-6)
1. Deploy Salary Components
2. Deploy Payroll Run engine
3. Test with sample data
4. Integration with Accounting module

### Phase 4 — Field Operations (Week 7-8)
1. Deploy Field Config + Consent
2. Deploy Location Tracking + Field Tasks
3. Deploy Field Events
4. Deploy Map UI
5. Mobile field duty flow

### Phase 5 — Polish (Week 9-10)
1. Performance & KPI
2. Recruitment pipeline
3. Reports & Analytics
4. Performance tuning
5. Security audit

### Migration Safety
- All migrations use `IF NOT EXISTS`
- All new columns use `DEFAULT` values
- No existing column modifications (only additions)
- Feature flags per tenant (enable gradually)
- Rollback scripts provided for each phase

---

## 9. TESTING PLAN

### 9.1 Unit Tests
| Area | Tests |
|------|-------|
| Payroll calculation | Base + allowances + deductions = correct net |
| Leave balance | Accrual, usage, carryover, negative prevention |
| Attendance rules | Late detection, overtime, geofence validation |
| Location validation | Speed check, accuracy filter, mock detection |
| Permission checks | Each role can/cannot access correct resources |
| Encryption | Encrypt/decrypt roundtrip for sensitive fields |

### 9.2 Integration Tests
| Scenario | Expected |
|----------|----------|
| Clock-in outside geofence | Warning + record created with flag |
| Leave request when zero balance | Rejected |
| Payroll run with mid-month transfer | Pro-rated salary for both branches |
| Field event with case linkage | Case timeline updated |
| Offline sync with duplicates | Dedup by offline_uuid |
| Late attendance adjustment after payroll lock | Error: payroll is locked |

### 9.3 Edge Cases
| Edge Case | Handling |
|-----------|---------|
| Employee transfers mid-month | Pro-rate salary by working days in each branch |
| Payroll recalculation after late adjustment | Only if payroll status is draft/preview |
| Device change during session | New device_hash → log warning but continue |
| Location spoofing (impossible speed) | Flag point, alert supervisor, don't reject silently |
| Offline submissions arriving out of order | Sort by captured_at, merge into timeline |
| Concurrent clock-in attempts | DB unique constraint prevents duplicate |
| Multi-tenant data leak | Every query MUST include company_id in WHERE |
| Consent revocation mid-session | End session immediately, stop tracking |
| Holiday during leave period | Don't count holiday as leave day |
| Employee with no salary structure | Payroll skips with warning in run log |

### 9.4 E2E Tests (Selenium/Cypress)
1. HR Admin creates employee → assign branch → assign shift → verify profile
2. Employee clocks in via web → verify attendance record → clock out → verify hours
3. Employee submits leave → manager approves → balance decremented
4. Payroll officer runs payroll → preview → lock → post → verify journal entry
5. Field staff starts duty → submits event → supervisor sees on map
6. Location supervisor views route playback → data matches submitted points
7. Employee views own payslip → PDF download works
8. Audit viewer sees all actions logged correctly

---

## 10. EVENT-DRIVEN HOOKS

| Event | Trigger | Subscribers |
|-------|---------|------------|
| `employee_created` | Employee onboarding | Notification, Checklist |
| `employee_transferred` | Branch transfer | Attendance, Payroll |
| `attendance_recorded` | Clock in/out | Dashboard, Notifications |
| `attendance_anomaly` | Late/absent detected | Manager notification |
| `leave_requested` | Leave submission | Approver notification |
| `leave_approved` | Leave approval | Balance update, Calendar |
| `leave_rejected` | Leave rejection | Employee notification |
| `payroll_run_created` | Payroll initiated | Dashboard |
| `payroll_posted` | Posted to accounting | Accounting module |
| `payslip_generated` | Individual payslip ready | Employee notification |
| `field_session_started` | Duty starts | Map, Dashboard |
| `field_session_ended` | Duty ends | Map, Attendance |
| `field_task_assigned` | Task created | Mobile push, SMS |
| `field_task_completed` | Task done | Case timeline, Dashboard |
| `field_event_created` | Evidence submitted | Case module, Audit |
| `document_expiring` | 30/15/7 days before | Employee + HR notification |
| `consent_revoked` | Employee revokes consent | HR Admin, Session end |
| `disciplinary_issued` | Warning/action | Employee, HR, Audit |

---

## 11. INTEGRATION POINTS

### With Existing Modules

| Module | Integration |
|--------|------------|
| **Contracts** | Commission calculation from collection performance |
| **Judiciary** | Field task linked to case_id; auto-timeline on field events |
| **Tasks/Kanban** | Field tasks can appear in follow-up task board |
| **Accounting** | Payroll journal entries; expense claims |
| **Customers** | Field task linked to customer_id; visit history |
| **Courts** | Institution geofences; court filing events |
| **SMS/Notification** | Leave approvals, task assignments, payslip ready |
| **Inventory** | Asset assignment tracking; equipment return on offboarding |

---

*End of Specification — v1.0*
*Generated for Tayseer ERP — HR & Field Operations Module*
