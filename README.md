# Tayseer ERP — نظام تيسير

Enterprise Resource Planning system built on **Yii 2 Advanced** (PHP 8.5+).  
Multi-tier architecture with backend admin panel, public frontend, REST API, and console tools.

---

## Directory Structure

```
Tayseer/
│
├── api/                        Yii2 REST API application (v1 module)
├── backend/                    Admin panel (AdminLTE 3 + Bootstrap 5)
├── frontend/                   Public-facing web application
├── common/                     Shared models, config, mail templates
├── console/                    CLI commands & Yii migrations
├── environments/               Environment-specific config overrides
│
├── database/                   Database assets
│   ├── migrations/             Remote migration helper scripts
│   ├── seeds/                  Test data seeders
│   └── sql/                    Raw SQL views & scripts
│
├── docs/                       Project documentation
│   ├── reports/                System reverse-engineering reports
│   ├── specs/                  Feature specifications & design docs
│   ├── diagnostics/            Server diagnostic outputs
│   └── attachments/            Reference documents & files
│
├── scripts/                    Utility & operations scripts (Python)
│   ├── check/                  Health-check & validation scripts
│   ├── debug/                  Debugging & diagnostic scripts
│   ├── deploy/                 Deployment & upload scripts
│   ├── fix/                    Bug-fix & patch scripts
│   ├── server/                 Server management & maintenance
│   ├── verify/                 Verification & post-deploy checks
│   └── misc/                   General-purpose utilities
│
├── deploy/                     Infrastructure & DevOps
│   ├── docker-legacy/          Legacy Docker configuration
│   └── sshfs/                  SSHFS/SFTP setup tools & installers
│
├── landing/                    Static marketing landing page
├── judiciary-v3/               Standalone judiciary app (Node/React)
│
├── .cursor/                    Cursor IDE skills & rules
├── .github/                    GitHub Actions CI/CD workflows
│
├── composer.json               PHP dependencies (Composer)
├── phpstan.neon                PHPStan static analysis config
├── requirements.php            Yii2 server requirements checker
├── init / init.bat             Environment initializer
├── yii.bat                     Console entry point (Windows)
├── .htaccess                   Apache rewrite rules
├── .gitignore                  Git ignore rules
├── LICENSE.md                  License (BSD)
└── README.md                   This file
```

## Quick Start

1. **Clone & install dependencies**
   ```bash
   composer install
   ```

2. **Initialize environment**
   ```bash
   php init            # Linux/Mac
   init.bat            # Windows
   ```

3. **Configure database**  
   Edit `common/config/main-local.php` with your DB credentials.

4. **Run migrations**
   ```bash
   php yii migrate
   ```

5. **Launch dev server**
   ```bash
   php yii serve
   ```

> For detailed setup instructions, see [`docs/setup-guide.md`](docs/setup-guide.md).

## Key Technologies

| Layer | Stack |
|-------|-------|
| Framework | Yii 2.0.54+ |
| PHP | 8.5+ |
| Admin UI | AdminLTE 3, Bootstrap 5, Kartik widgets |
| Frontend Assets | Vite, Tailwind CSS |
| REST API | Yii2 REST module (`api/v1`) |
| Auth | dektrium/yii2-user, mdmsoft/yii2-admin (RBAC) |
| Queue | yii2-queue + php-amqplib |
| Reports | mPDF, PhpSpreadsheet |
| CI/CD | GitHub Actions (SSH deploy) |
| Static Analysis | PHPStan (level 3) |

## UX & Frontend Improvements (April 2026)

Comprehensive UX audit and implementation cycle aligned with **ISO 9241**, **WCAG 2.2 AAA**, and **Nielsen's 10 Heuristics**.

### Accessibility (WCAG 2.2 AAA)
- Skip-to-main-content link and `<main>` landmark with correct `<h1>` hierarchy
- ARIA attributes on sidebar navigation, tabs, modals, and wizard forms
- Minimum 44×44 px touch targets for all interactive elements
- 7:1 contrast ratio for secondary text; distinct `:focus-visible` outline
- `aria-current="step"` and auto-focus management on customer onboarding wizard

### Dashboard
- Clickable KPI cards linking to their respective modules
- Drag & Drop section reordering with `localStorage` persistence
- Lazy-loaded ApexCharts via `IntersectionObserver`
- Server-side caching (split KPI / recent-data TTLs) for faster loads
- Fixed income chart showing future month data
- Arabic-localized dates and auto-translated UI strings

### UI / Design System
- Microinteractions on buttons, cards, and badges (`prefers-reduced-motion` aware)
- Custom toast notification system (`TyToast`) — WCAG-compliant, RTL-ready
- Skeleton loading screens for Pjax-based navigation
- Keyboard shortcuts with on-screen help panel (`?` key)
- Redesigned error page matching the application theme and dark mode
- CSS shim mapping legacy AdminLTE classes to Bootstrap 5 card styles
- Persistent quick-search bar and column-visibility toggle for all GridViews
- Design Tokens reference — [`backend/web/css/DESIGN-TOKENS.md`](backend/web/css/DESIGN-TOKENS.md)
- Component Library reference — [`backend/web/css/COMPONENT-LIBRARY.md`](backend/web/css/COMPONENT-LIBRARY.md)

### Progressive Web App (PWA)
- `manifest.json` with Arabic metadata, standalone display, and maskable icon
- Service Worker (`sw.js`) caching app shell for offline-first experience

### Bug Fixes
- Fixed `bootstrap is not defined` in Vuexy `main.js` (deferred tooltip init behind guard)
- Fixed KPI grid broken layout (premature `</div>` closing tag)
- Fixed `Undefined array key 'contract_id'` in FollowUpSearch model
- Fixed Income index returning 400 error when `customer_id` not provided
- Cleaned unused legacy assets from `AppAsset.php`
- Stable cache-busting via `assetVersion` param instead of `time()`

### Performance
- Dashboard data split into two cache layers (KPI 5 min, recent data 1 min)
- Lazy chart rendering deferred until viewport intersection
- Asset consolidation and cache-busting on all static resources

## Documentation

- **Setup Guide** — [`docs/setup-guide.md`](docs/setup-guide.md)
- **HR Module Spec** — [`docs/HR_MODULE_SPECIFICATION.md`](docs/HR_MODULE_SPECIFICATION.md)
- **Invoice Wizard Flow** — [`docs/invoice-wizard-and-approval-flow.md`](docs/invoice-wizard-and-approval-flow.md)
- **Judiciary Redesign** — [`docs/specs/JUDICIARY_REDESIGN_PROMPT.md`](docs/specs/JUDICIARY_REDESIGN_PROMPT.md)
- **Design Tokens** — [`backend/web/css/DESIGN-TOKENS.md`](backend/web/css/DESIGN-TOKENS.md)
- **Component Library** — [`backend/web/css/COMPONENT-LIBRARY.md`](backend/web/css/COMPONENT-LIBRARY.md)
- **System Analysis** — [`docs/reports/`](docs/reports/)

## License

See [LICENSE.md](LICENSE.md).
