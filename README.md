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

## Documentation

- **Setup Guide** — [`docs/setup-guide.md`](docs/setup-guide.md)
- **HR Module Spec** — [`docs/HR_MODULE_SPECIFICATION.md`](docs/HR_MODULE_SPECIFICATION.md)
- **Invoice Wizard Flow** — [`docs/invoice-wizard-and-approval-flow.md`](docs/invoice-wizard-and-approval-flow.md)
- **Judiciary Redesign** — [`docs/specs/JUDICIARY_REDESIGN_PROMPT.md`](docs/specs/JUDICIARY_REDESIGN_PROMPT.md)
- **System Analysis** — [`docs/reports/`](docs/reports/)

## License

See [LICENSE.md](LICENSE.md).
