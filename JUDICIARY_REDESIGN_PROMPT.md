# Judiciary Module Redesign — Full Technical Prompt

> **IMPORTANT INSTRUCTIONS:**
> - Do NOT use any stored skills, custom rules, or cached design patterns. Start from scratch with your own creative approach.
> - The UI must be fully in **Arabic (RTL)** — all labels, buttons, messages, tooltips, and placeholders in Arabic.
> - All your responses and explanations to me must be in **Arabic**.
> - This is a **new standalone screen** within an existing Yii2/PHP project. It must **NOT modify, override, or affect** the current judiciary module at `backend/modules/judiciary/`. The new implementation should live in its own isolated directory (e.g., `judiciary-v3/` or `judiciary-node/` at the project root) with its own routing, assets, and entry point — completely independent from the existing PHP backend and views.

---

## Project: Rebuild the Judiciary (Legal Department) Module — Full-Stack Node.js

### Overview

I need you to design and build a **complete legal/judiciary case management system** as a standalone Node.js application embedded within an existing project. This system manages court cases, tracks legal actions per party, monitors deadlines, handles seized assets, manages correspondence (Diwan), and provides persistence/follow-up reports. The application must connect to an **existing MySQL database** (schema detailed below) and provide a modern, highly usable web interface.

**You have full creative freedom on:**
- Frontend framework/library (React, Vue, Svelte, Solid, vanilla, etc.)
- UI component library and design system
- CSS approach (Tailwind, CSS Modules, styled-components, etc.)
- State management
- Backend framework (Express, Fastify, Hono, Nest, etc.)
- Any architecture pattern you prefer

**Constraints:**
- Must use **Node.js** for the backend
- Must connect to the **existing MySQL database** (tables described below)
- The interface must be **fully Arabic (RTL)**
- All existing functionality described below must be preserved and fully functional
- Must be **completely isolated** from the existing PHP/Yii2 codebase — no shared controllers, views, or assets

---

### Database Schema (MySQL — table prefix: `os_`)

#### 1. `os_judiciary` (Main cases table)

| Column | Type | Notes |
|--------|------|-------|
| `id` | INT PK AUTO_INCREMENT | |
| `court_id` | INT NOT NULL | FK → `os_court.id` |
| `type_id` | INT NOT NULL | FK → `os_judiciary_type.id` |
| `case_cost` | INT | Court fees |
| `lawyer_cost` | DECIMAL NOT NULL | Attorney fees |
| `lawyer_id` | INT NOT NULL | FK → `os_lawyers.id` |
| `company_id` | INT | Optional company filter |
| `contract_id` | INT NOT NULL | FK → `os_contracts.id` |
| `income_date` | DATE | Case filing/receipt date |
| `judiciary_number` | INT | Case number |
| `year` | VARCHAR | Case year |
| `judiciary_inform_address_id` | INT NOT NULL | FK → `os_judiciary_inform_address.id` |
| `case_status` | VARCHAR | open/closed/suspended/archived |
| `last_check_date` | VARCHAR | Last review date |
| `furthest_stage` | VARCHAR(30) DEFAULT 'case_preparation' | Furthest pipeline stage reached |
| `bottleneck_stage` | VARCHAR(30) DEFAULT 'case_preparation' | Stage where progress is stuck |
| `is_deleted` | TINYINT DEFAULT 0 | Soft delete |
| `created_at`, `updated_at` | INT | Unix timestamps |
| `created_by`, `last_update_by` | INT | FK → `os_user.id` |

**Stage Pipeline (ordered):** `case_preparation` → `fee_payment` → `case_registration` → `notification` → `procedural_requests` → `correspondence` → `follow_up` → `payment_settlement` → `case_closure` (plus `general` for unclassified)

**Stage Labels (Arabic):**

| Key | Arabic Label |
|-----|-------------|
| case_preparation | إعداد الملف |
| fee_payment | دفع الرسوم |
| case_registration | تسجيل الدعوى |
| notification | التبليغ |
| procedural_requests | الطلبات الإجرائية |
| correspondence | المراسلات |
| follow_up | المتابعة |
| payment_settlement | التسوية والسداد |
| case_closure | إغلاق القضية |
| general | عام |

#### 2. `os_judiciary_type` (Case types)

| Column | Type |
|--------|------|
| `id` | INT PK |
| `name` | VARCHAR(255) |

#### 3. `os_court` (Courts)

| Column | Type | Notes |
|--------|------|-------|
| `id` | INT PK | |
| `name` | VARCHAR | |
| `city` | INT | Governorate index |
| `adress` | VARCHAR | |
| `phone_number` | VARCHAR | |
| `is_deleted` | TINYINT DEFAULT 0 | |
| `created_at`, `updated_at` | INT | |
| `created_by`, `last_updated_by` | INT | |

#### 4. `os_lawyers` (Attorneys)

| Column | Type | Notes |
|--------|------|-------|
| `id` | INT PK | |
| `name` | VARCHAR | |
| `address`, `phone_number` | VARCHAR | |
| `status` | INT | 0=active, 1=inactive |
| `type` | INT | |
| `representative_type` | VARCHAR | delegate / lawyer |
| `signature_image`, `image` | VARCHAR | |
| `notes` | TEXT | |
| `is_deleted` | TINYINT DEFAULT 0 | |

#### 5. `os_judiciary_actions` (Action catalog)

| Column | Type | Notes |
|--------|------|-------|
| `id` | INT PK | |
| `name` | VARCHAR(255) | Action name in Arabic |
| `action_type` | VARCHAR | Category: case_preparation, fee_registration, notification, procedural_requests, correspondence, follow_up, settlement_closure, appeal, general |
| `action_nature` | VARCHAR | One of: request, document, doc_status, process |
| `allowed_documents` | VARCHAR | Comma-separated action IDs |
| `allowed_statuses` | VARCHAR | Comma-separated action IDs |
| `parent_request_ids` | VARCHAR | Comma-separated prerequisite action IDs |
| `is_deleted` | TINYINT DEFAULT 0 | |

#### 6. `os_judiciary_customers_actions` (Party actions — core transaction table)

| Column | Type | Notes |
|--------|------|-------|
| `id` | INT PK | |
| `judiciary_id` | INT | FK → os_judiciary |
| `customers_id` | INT | FK → os_customers (nullable) |
| `judiciary_actions_id` | INT | FK → os_judiciary_actions |
| `contract_id` | INT | |
| `action_date` | DATE/DATETIME | |
| `note` | TEXT | |
| `image` | VARCHAR | Attached file/image |
| `parent_id` | INT | FK → self (prerequisite action) |
| `request_status` | VARCHAR | printed / submitted / pending / approved / rejected |
| `decision_text` | TEXT | |
| `decision_file` | VARCHAR | |
| `is_current` | TINYINT | |
| `amount` | DECIMAL | |
| `request_target` | VARCHAR | judge / accounting / other |
| `is_deleted` | TINYINT DEFAULT 0 | |
| `created_at`, `updated_at` | INT | |
| `created_by`, `last_update_by` | INT | |

#### 7. `os_judiciary_defendant_stage` (Per-defendant stage tracking)

| Column | Type | Notes |
|--------|------|-------|
| `id` | INT PK | |
| `judiciary_id` | INT | FK → os_judiciary |
| `customer_id` | INT | FK → os_customers |
| `current_stage` | VARCHAR(30) DEFAULT 'case_preparation' | |
| `stage_updated_at` | DATETIME | |
| `notes` | TEXT | |
| UNIQUE (`judiciary_id`, `customer_id`) | | |

#### 8. `os_judiciary_deadlines` (Deadline tracking)

| Column | Type | Notes |
|--------|------|-------|
| `id` | INT PK | |
| `judiciary_id` | INT | FK → os_judiciary |
| `customer_id` | INT | |
| `deadline_type` | VARCHAR | registration_3wd, notification_check, notification_16cd, request_decision, correspondence_10wd, property_7cd, salary_3m, custom |
| `day_type` | VARCHAR | working / calendar |
| `label` | VARCHAR | |
| `start_date` | DATE | |
| `deadline_date` | DATE | |
| `status` | VARCHAR DEFAULT 'pending' | pending / approaching / expired / completed |
| `related_communication_id` | INT | FK → os_diwan_correspondence (SET NULL) |
| `related_customer_action_id` | INT | FK → os_judiciary_customers_actions (SET NULL) |
| `notes` | TEXT | |
| `is_deleted` | TINYINT DEFAULT 0 | |
| `created_at`, `updated_at` | INT | |
| `created_by` | INT | |

#### 9. `os_judiciary_seized_assets` (Seized property)

| Column | Type | Notes |
|--------|------|-------|
| `id` | INT PK | |
| `judiciary_id` | INT | FK → os_judiciary |
| `customer_id` | INT | |
| `asset_type` | VARCHAR | vehicle, real_estate, bank_account, salary, shares, e_payment |
| `status` | VARCHAR DEFAULT 'seizure_requested' | seizure_requested / seized / valued / auction_requested / auctioned / released |
| `authority_id` | INT | FK → os_judiciary_authorities |
| `correspondence_id` | INT | FK → os_diwan_correspondence |
| `description` | TEXT | |
| `amount` | DECIMAL | |
| `notes` | TEXT | |
| `is_deleted` | TINYINT DEFAULT 0 | |
| `created_at`, `updated_at` | INT | |
| `created_by` | INT | |

#### 10. `os_diwan_correspondence` (Legal correspondence)

| Column | Type | Notes |
|--------|------|-------|
| `id` | INT PK | |
| `communication_type` | VARCHAR | |
| `related_module` | VARCHAR DEFAULT 'judiciary' | |
| `related_record_id` | INT | judiciary_id when module=judiciary |
| `customer_id` | INT | |
| `direction` | VARCHAR | incoming / outgoing |
| `recipient_type` | VARCHAR | |
| Various recipient FKs | INT | authority_id, bank_id, job_id |
| Notification fields | | notification_method, notification_result, notification_date |
| `reference_number`, `purpose` | VARCHAR | |
| `content_summary` | TEXT | |
| `image` | VARCHAR | Attachment |
| `follow_up_date` | DATE | |
| `status` | VARCHAR | |
| `notes` | TEXT | |
| `company_id` | INT | |
| `is_deleted` | TINYINT DEFAULT 0 | |
| `created_at`, `updated_at` | INT | |
| `created_by`, `last_update_by` | INT | |

#### 11. `os_judiciary_inform_address` (Service addresses)

| Column | Type |
|--------|------|
| `id` | INT PK |
| `address` | VARCHAR |
| `is_deleted` | INT DEFAULT 0 |
| `created_at`, `updated_at` | INT |
| `created_by` | INT |

#### 12. `os_judiciary_authorities` (External authorities)

| Column | Type | Notes |
|--------|------|-------|
| `id` | INT PK | |
| `name` | VARCHAR | |
| `authority_type` | VARCHAR | land, licensing, companies_registry, industry_trade, security, court, social_security |
| `is_deleted` | TINYINT DEFAULT 0 | |

#### 13. `os_judiciary_request_templates` (Document templates)

| Column | Type |
|--------|------|
| `id` | INT PK |
| `name` | VARCHAR |
| `template_type` | VARCHAR |
| `template_content` | LONGTEXT |
| `is_combinable` | TINYINT |
| `sort_order` | INT |
| `is_deleted` | TINYINT DEFAULT 0 |

#### 14. `os_contracts` (Referenced — not managed here)

Key fields used: `id`, `status` (including 'judiciary'), contract financial data. Cases link via `contract_id`.

#### 15. `os_customers` (Referenced — not managed here)

Key fields used: `id`, `name`, `id_number`, `primary_phone_number`, `city`, banking info (`bank_name`, `bank_branch`, `account_number`), employment info (`job_title`, `total_salary`, social security fields), property info. Linked through `os_contracts_customers` junction table.

#### 16. `os_contracts_customers` (Junction table)

Links contracts to customers with `type` field (client vs guarantor/كفيل).

#### 17. `os_collection` (Payment collection)

| Column | Type |
|--------|------|
| `id` | INT PK |
| `contract_id` | INT |
| `date` | DATE |
| `amount` | DECIMAL |
| `notes` | TEXT |
| `judiciary_id` | INT (optional) |
| `created_by` | INT |

#### 18. `tbl_persistence_cache` (Persistence/follow-up cache)

Pre-computed table (refreshed via stored procedure `sp_refresh_persistence_cache`) containing case follow-up status with fields for case info, last action date, persistence indicator (red/orange/green), lawyer, job info.

---

### Required Functionality (5 Main Tabs + Supporting Features)

#### TAB 1: Cases (القضايا) — Primary Tab

**Grid/List displaying all cases with columns:**

- Case ID (#)
- Contract number (clickable link)
- Parties (all customers + guarantors from the contract, showing name, ID number, job title)
- Court name
- Lawyer name
- Case number + year
- Last action per party (from `os_judiciary_customers_actions` — show action name, nature color-coded by: request=blue, document=green, doc_status=orange, process=purple, with customer name and date)
- Pipeline stage (furthest_stage with visual indicator + bottleneck warning)
- Action buttons per row

**Filters (search form):**

- `judiciary_number` — text search (exact or LIKE)
- `contract_id` — numeric
- `party_name` — text (splits on whitespace, AND-matches each word against customer names)
- `court_id` — dropdown from os_court
- `type_id` — dropdown from os_judiciary_type
- `lawyer_id` — dropdown from os_lawyers
- `year` — dropdown (2010 to current year)
- `from_income_date` / `to_income_date` — date range
- `last_party_action` — dropdown from os_judiciary_actions (filters cases where the latest action for any party matches)
- `status` — Available (contract not canceled/finished) / Unavailable
- `pending_requests` — toggle to show only cases with pending request_status actions
- `furthest_stage` — dropdown from stage list

**Row Actions (per case):**

- View case details
- Edit case
- Add party action (modal/dialog)
- Print case (opens in new tab)
- Delete case (soft delete with confirmation)
- Timeline/follow-up view (side panel or modal showing chronological history)
- Pin/bookmark case

**Toolbar Buttons:**

- Create new case
- Batch entry (إدخال مجمّع) — wizard to paste case numbers, parse & match, assign actions in bulk
- Add action (modal)
- Refresh
- Export to Excel
- Export to PDF
- Pending requests queue link (shows count badge)

**Pending Requests Mode:**

When `pending_requests` is active, each row shows inline approve/reject controls for pending actions with `request_status = 'pending'`.

---

#### TAB 2: Actions (الإجراءات)

**Grid showing all party actions from `os_judiciary_customers_actions`:**

- Case (link to case)
- Defendant/customer name
- Action name (from judiciary_actions)
- Notes
- Creator username
- Lawyer (from related case)
- Court (from related case)
- Contract number (link)
- Action date

**Filters:**

- `judiciary_number`
- `customers_id` — async search by customer name
- `judiciary_actions_id` — multi-select from actions catalog
- `year`
- `contract_id`
- `court_name` — dropdown
- `lawyer_name` — dropdown
- `created_by` — dropdown of users
- `from_action_date` / `to_action_date` — date range

**Row Actions:** View, Edit, Delete (all via modal/dialog)

**Toolbar:** Add action (modal), Refresh, Export Excel, Export PDF

---

#### TAB 3: Persistence/Follow-up (المثابرة)

**Dashboard cards at top:**

- Total cases count
- Urgent attention needed (red) count
- Approaching deadline (orange) count
- Good standing (green) count

**Table columns (14):**

#, Case number, Year, Court, Contract number, Customer name, Last action, Last action date, Persistence indicator (color-coded badge: red=urgent, orange=approaching, green=good), Last contract follow-up, Last job check, Lawyer, Job, Job type

**Filters:**

- Text search (searches across name, court, case number, contract)
- Color chip filters: All / Urgent (red) / Approaching (orange) / Good (green)

**Actions:**

- Refresh cache (calls stored procedure `sp_refresh_persistence_cache`)
- Export to Excel
- Print report (new window)
- Pagination with "show all" toggle

---

#### TAB 4: Legal Department (الشؤون القانونية)

This tab displays contracts that are in legal/judiciary status. Show contract-related data: contract ID, customer name, sale date, total amount, paid amount, remaining amount, contract status. Allow selecting contracts for batch case creation.

---

#### TAB 5: Collection (التحصيل)

**Summary cards:** Number of collection cases, Available to collect amount

**Grid columns:**

- Contract number
- Date
- Amount (formatted decimal)
- Notes
- Employee name (creator)
- Available to collect (computed: months since date × amount minus financial transactions with income_type=11)

**Row Actions:** View, Edit, Delete (permission-gated)

**Toolbar:** Refresh, Toggle data display, Export Excel, Export PDF

---

### Case View Page (ملف القضية)

A detailed single-case page showing:

**Header:** Case number, year, status chip (open/closed/suspended/archived)

**Info Cards:**

- Case info: case number, type, income date, last procedural request date
- Court & lawyer: court name, lawyer name, lawyer cost, case cost
- Contract info: contract number, type, value, status

**Parties Section:** List of customers (client) and guarantors (كفيل) with names

**Stage Pipeline Visualization:**

- Visual progress through the 9 stages
- Highlight furthest stage and bottleneck
- Overall status indicator
- Per-defendant stage tracking table

**Active Deadlines:** List of active deadlines with type label, status styling, date, days remaining, notes

**Seized Assets:** List with asset type, description, amount, status badge

**Correspondence:** Filterable list (all / notifications / outgoing letters / incoming replies) with purpose, status, date, recipient type. Link to full correspondence view.

**Party Actions History:** Paginated list of all actions per party with action name, nature badge, request status badge, customer, date, creator, notes. Edit/delete per action.

**Action Buttons:**

- Edit case
- Create procedural request (from templates in `os_judiciary_request_templates`)
- Deadline dashboard link
- Timeline view (chronological merged view of actions + correspondence + deadlines)

---

### Case Timeline

A chronological view (newest first) merging:

- Party actions (from `os_judiciary_customers_actions`)
- Correspondence (from `os_diwan_correspondence` where `related_module='judiciary'`)
- Deadlines (from `os_judiciary_deadlines`)

Each entry shows: action name, nature/type color, customer name, date, notes, creator, status badge. Filterable by party.

---

### Deadline Dashboard (لوحة المواعيد)

Three sections:

1. **Overdue (متأخرة):** Deadlines past their `deadline_date`
2. **Approaching (تقترب):** Deadlines coming soon
3. **Pending (قائمة):** Future deadlines

Each card shows: deadline type label, case number (linked), calendar date, days remaining/overdue text, label, notes.

Summary counts at the top for each category.

---

### Create/Edit Case Form

**Fields:**

- Court (searchable dropdown from `os_court`)
- Case type (dropdown from `os_judiciary_type`)
- Company (optional dropdown)
- Lawyer (searchable dropdown from `os_lawyers`)
- Lawyer cost (number)
- Case cost (number)
- Case number (number)
- Year (dropdown 2010–current)
- Income/filing date (date picker)
- Input method (manual / percentage-based)
- Service address (searchable dropdown from `os_judiciary_inform_address`)

**On update:** Also show the list of party actions with inline approve/reject for pending requests.

**After save:** Option to print execution documents.

---

### Batch Case Creation (إنشاء مجمّع)

Left panel: shared fields (court, lawyer, type, service address, company, year, lawyer percentage).

Right panel: table of selected contracts with: contract ID, customer name, sale date, total, paid, remaining, computed lawyer cost. Ability to remove rows.

Summary: contract count, total remaining, total fees.

Submit creates multiple cases at once.

---

### Batch Action Entry (إدخال مجمّع للإجراءات)

A 3-step wizard:

1. **Paste:** Textarea to paste case numbers
2. **Review:** Parsed results in a table with: checkbox, input text, matched case number, year, court, contract, party (with debtor/guarantor type), action dropdown (grouped by nature), date picker, notes field, status indicator. Bulk action toolbar to apply same action/date/note to selected rows. Handle prerequisite actions (show warning, allow auto-creation).
3. **Execute:** Progress bar, summary (saved/cases/errors), detail log.

---

### Exports

The system must support exporting to:

- **Excel** — for cases list (filtered), actions list (filtered), persistence report
- **PDF** — for cases list, actions list, persistence report, individual case print

---

### Request Workflow

Actions with `request_status`:

- Flow: `printed` → `submitted` → `pending` → `approved` / `rejected`
- Approve/reject controls accessible from: cases grid (pending mode), case view, case edit
- Each status change recorded with the current user

---

### Stats/Counts (Dashboard Strip)

Display counts across all tabs:

- Total cases
- Total actions
- Persistence cache rows
- Legal department contracts count
- Collection cases count
- Pending requests count
- Persistence breakdown: red/orange/green counts
- Collection available amount

---

### Non-Functional Requirements

1. **Isolation:** This is a new standalone app within the project. It must NOT touch or modify any existing PHP files, views, controllers, or assets. It should live in its own directory (e.g., `judiciary-v3/`).
2. **Authentication:** Assume a session-based auth system exists; the current user ID is available for `created_by` / audit fields.
3. **Soft delete:** All delete operations set `is_deleted = 1`, never physically delete rows. All queries must filter `is_deleted = 0`.
4. **Pagination:** Default 10 rows per page with configurable page size.
5. **Sorting:** Default sort by `id DESC`.
6. **Real-time feel:** Use AJAX/fetch for tab switching, modal operations, and data updates without full page reloads.
7. **Responsive:** Must work on desktop and tablet screens.
8. **RTL:** Full right-to-left layout with Arabic typography.
9. **Date formatting:** Display dates in a user-friendly Arabic format.

---

### What to Build

1. **Node.js backend** with RESTful API endpoints covering all CRUD operations, search/filter, exports, batch operations, timeline, deadlines, and stats.
2. **Frontend application** with the complete UI described above — all 5 tabs, case view, forms, batch wizards, timeline, deadline dashboard, modals, and export functionality.
3. **Database connection** configuration for the existing MySQL database (provide `.env` template).
4. Clear **project setup instructions** in the README.

---

*Remember: Respond to me in Arabic. Build the interface entirely in Arabic (RTL). You have complete creative freedom on the tech stack and design — make it modern, intuitive, and beautiful.*
