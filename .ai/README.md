# LRMS — Learning Resources Monitoring System

> This document lives in `.ai/` as machine-generated reference documentation, kept in sync with the actual codebase. See [`07-modules.md`](07-modules.md) for the full per-module audit and [`08-roadmap.md`](08-roadmap.md) for the reconciled roadmap.

## Short Description

LRMS is a Laravel + Inertia/React web application that lets a Schools Division Office track learning resources, ICT/Science-Math/Other equipment, and enrollment data across every school under it — from the division office down to each individual school's own encoding of its stock.

## Purpose

- Give schools a single place to self-activate their account and encode what printed resources, equipment, and enrollment they actually have on hand.
- Give the division office (superintendents, program managers, supply/ICT officers, librarians) a real-time, filterable view of resource adequacy, equipment condition, and delivery status across every school, without waiting for manual paper reports.
- Track the full lifecycle of a resource: division catalog → distribution to a school → school receipt → on-hand inventory ledger → movement history (issued, borrowed, damaged, lost, condemned).

## Technology Stack

| Layer | Technology |
|---|---|
| Backend | Laravel 13 (PHP ^8.3), Laravel Fortify (auth, 2FA) |
| Frontend | React 19 + TypeScript, Inertia.js 3 (server-driven SPA) |
| Styling | Tailwind CSS 4, shadcn-style UI primitives (Radix UI) |
| Build | Vite 8, `@laravel/vite-plugin-wayfinder` |
| Database | SQLite by default (`DB_CONNECTION=sqlite`); MySQL supported via `.env` |
| Queue / Cache / Session | Database driver by default; Redis optional |
| Testing | Pest 4 (`pestphp/pest`, `pest-plugin-laravel`) |
| Code style | Laravel Pint (PHP), ESLint 9 + Prettier (TypeScript/React) |

## Core Modules

Grouped from the full, code-verified inventory in [`07-modules.md`](07-modules.md) (21 modules total):

- **Platform**: Authentication & Account Security, User Account Settings, Application Settings (branding/SMTP), Content Management (Support/About pages)
- **Reference Data**: Locations (District/Municipality/Barangay), School Years & Grade Levels, Learning Resource Types
- **Schools**: School Directory & Profile Management, School Self-Activation Onboarding, School Bulk Import (CSV)
- **Dashboards & Reporting**: Admin Executive Dashboard, School Dashboard, Division Reports & CSV Exports
- **Printed Learning Resources**: Resource Title Catalog, School Resource Encoding & Inventory Ledger, Resource Distribution (division → school)
- **Digital Learning Materials**: Digital Learning Materials Repository
- **Equipment**: ICT Equipment, Science & Math Equipment (SME), Other Equipment/Materials — each with an admin catalog, a per-school inventory, and a movement/audit log
- **Enrollment**: School Enrollment (per grade level, per school year)

## Features

- Role-based admin workspace (10 staff roles) and a separate school workspace (1 role), enforced via route middleware.
- Self-service school activation with email OTP verification and auto-generated credentials.
- Executive dashboards (division-wide and per-school) with KPI tiles and charts, filterable by district, school type, and grade level.
- Two-tier equipment tracking (division catalog + per-school physical units) with full movement/audit history, mirrored across ICT, Science & Math, and Other equipment.
- Delivery lifecycle: admin creates a distribution → school confirms receipt → resource lands directly in the school's inventory ledger.
- CSV import/export across schools, locations, resource titles, digital materials, and all three equipment catalogs.
- Two-Factor Authentication (TOTP) and OTP-based password reset.
- Configurable branding (logos) and SMTP settings from the admin panel, applied at runtime — no `.env` edits required for mail delivery.

## Installation Steps

Prerequisites: PHP ^8.3 with the extensions Laravel 13 requires, Composer, Node.js (for Vite), and either SQLite (default) or MySQL.

```bash
git clone <repository-url> lrms
cd lrms

composer install
npm install

cp .env.example .env
php artisan key:generate

# SQLite is the default; create the file if it doesn't exist yet
type nul > database\database.sqlite   # Windows
# touch database/database.sqlite      # macOS/Linux

php artisan migrate
php artisan db:seed

npm run build
php artisan serve
```

Or use the one-shot Composer script, which does most of the above:

```bash
composer run setup
```

## Environment Setup

Key `.env` values (see `.env.example` for the full list):

| Variable | Notes |
|---|---|
| `APP_NAME`, `APP_URL` | App name/base URL used in emails and links |
| `DB_CONNECTION` | `sqlite` by default; set to `mysql` + `DB_HOST/PORT/DATABASE/USERNAME/PASSWORD` for MySQL |
| `SESSION_DRIVER`, `CACHE_STORE`, `QUEUE_CONNECTION` | Default to `database` — no Redis required to run locally |
| `MAIL_MAILER` | Defaults to `log` (emails written to the log, not sent). Real OTP/credential emails require SMTP to be configured — either here or via **App Settings** in the admin panel at runtime |
| `FILESYSTEM_DISK` | Defaults to `local`; uploaded logos/covers/attachments are served from the `public` disk (`php artisan storage:link` may be needed) |
| `AWS_*` | Present in `.env.example` but not wired to any cloud storage disk in this codebase yet — file uploads use the local `public` disk only |

Run `php artisan storage:link` after install so uploaded files (logos, resource covers, digital material attachments) are web-accessible.

## Database Migration and Seeding

```bash
php artisan migrate          # run all migrations
php artisan migrate:fresh    # drop everything and re-run (destructive)
php artisan db:seed          # populate reference/demo data
```

`DatabaseSeeder` runs, in order: `AdminUserSeeder`, `RoleUserSeeder`, `LearningResourceTypeSeeder`, `DigitalLearningMaterialSeeder`, `IctEquipmentCatalogSeeder`, `OtherEquipmentCatalogSeeder`, `SmeCatalogSeeder`, `GradeLevelSeeder`, `SchoolYearSeeder`, `LocationSeeder`, `SchoolSeeder`.

This creates, among other things, a default admin login:

| Email | Password | Role |
|---|---|---|
| `admin@lrms.com` | `password` | `admin` |

`RoleUserSeeder` additionally seeds one demo account per staff role (`manager@lrms.com`, `librarian@lrms.com`, `supply@lrms.com`, `cidchief@lrms.com`, `asds@lrms.com`, `sds@lrms.com`, `ito@lrms.com`, `sysadmin@lrms.com`, `superadmin@lrms.com`), all with password `Pass1234`.

**Change or remove these credentials before deploying anywhere but local development.**

## Development Commands

```bash
composer run dev   # runs php artisan serve + queue:listen + vite dev, concurrently
# or run them separately:
php artisan serve
php artisan queue:listen --tries=1
npm run dev
```

Code style / linting:

```bash
composer run lint         # Pint, auto-fix
composer run lint:check   # Pint, check only
npm run lint               # ESLint, auto-fix
npm run lint:check         # ESLint, check only
npm run format              # Prettier, auto-fix
npm run format:check        # Prettier, check only
npm run types:check         # tsc --noEmit
```

## Testing Commands

```bash
php artisan test          # full Pest suite
composer run test         # config:clear + lint:check + php artisan test
composer run ci:check     # lint:check + format:check + types:check + test
```

## Folder Structure

```
app/
  Actions/         Fortify actions (user creation, etc.)
  Concerns/        Shared trait-style behavior
  Http/
    Controllers/   Admin/ (division-side) + top-level (school-side) controllers
    Middleware/     EnsureUserRole, HandleAppearance, HandleInertiaRequests
    Requests/       Form Request validation classes (one per write endpoint)
    Resources/      API/Inertia resource transformers (SchoolResource, LearningResourceResource)
  Mail/            Mailable classes (OTP, activation credentials)
  Models/          Eloquent models
  Providers/       FortifyServiceProvider, etc.
  Services/        Business logic (import services, inventory/distribution services)
  Support/         Small framework-agnostic helpers
config/            Laravel + Fortify configuration
database/
  factories/       Model factories for tests/seeding
  migrations/       Schema history
  seeders/          DatabaseSeeder + per-domain seeders
resources/
  css/             Tailwind entry point
  js/
    actions/, routes/, wayfinder/   Generated Laravel Wayfinder route/action helpers
    components/     Shared React components (incl. charts/, ui/)
    hooks/          React hooks
    layouts/        Page layout shells
    lib/            Client-side utilities
    pages/          One Inertia page component per route (Admin*.tsx, School*.tsx)
    types/          Shared TypeScript types
    app.tsx         Inertia/React entry point
routes/
  web.php           Application routes (admin + school + public)
  settings.php      Profile/security/appearance routes
  console.php       Artisan console routes
tests/
  Feature/          Pest feature tests (one file per module/flow)
.ai/                Machine-generated reference docs (this audit, roadmap, changelog)
```

## Roles and Permissions Overview

Enforced by `App\Http\Middleware\EnsureUserRole` and route-level `role:` middleware groups in `routes/web.php`. There are no Laravel Policy classes — authorization is route middleware plus manual ownership checks (`abort_if($resource->school_id !== $school->id, 403)`) inside controllers.

| Role | Scope |
|---|---|
| `school` | One per school. Own-school CRUD only (equipment, printed resources, enrollment, distributions receipt, activation). |
| `manager`, `librarian`, `supply` | Admin workspace + catalog management (resource titles, all three equipment catalogs, digital learning materials). |
| `cidchief`, `asds`, `sds` | Admin workspace, monitoring/reporting only — no catalog or reference-data management, no Settings/Distributions access. |
| `ito` | Admin workspace + catalog management + full system-admin access (reference data, settings, content, distributions, imports). |
| `sysadmin`, `admin`, `superadmin` | Full system-admin access — everything `ito` can do, plus (implicitly) any future admin-only surface. |

Route-level role groups actually used: `adminWorkspaceRoles` (base gate for all 10 staff roles), `catalogRoles` (7 roles — everyone except `cidchief`/`asds`/`sds`), `systemAdminRoles` (4 roles: `admin, superadmin, sysadmin, ito`), and `role:school` for the entire school-side route group.

## Known Limitations

Full detail and per-module completion percentages are in [`07-modules.md`](07-modules.md). Headline items:

- **No Policy classes** — all authorization is route middleware + manual ownership checks.
- **Duplicate logic across the three equipment verticals** (ICT / SME / Other) — same model, controller, and service shape triplicated rather than shared.
- **CSV is the only bulk I/O format** anywhere in the app — no Excel or PDF import/export.
- **`qr_code` / `barcode` columns exist but are unused** — plain text fields only, no label generation or scan-to-lookup.
- **No notification/alerting system** — no low-stock, warranty-expiry, or aging-pending-activation alerts; everything is pull-based.
- **Resource Distribution can create duplicate `LearningResource` rows** — receiving the same title twice for the same school creates a second ledger row instead of accumulating into the existing one.
- **Schools can be activated or deleted, but not deactivated back to pending** — there's no "revert to unactivated" action.
- **The Reports module has no chart visualizations** — unlike the Admin/School dashboards, it is tabular/CSV-only.
- No region/multi-division data model — all reporting and dashboards are scoped to a single division.
- File uploads use the local `public` disk — no cloud object storage is wired up despite `AWS_*` variables existing in `.env.example`.

## Roadmap

See [`08-roadmap.md`](08-roadmap.md) for the full, reconciled roadmap. Summary:

- **Completed**: Authentication, Dashboards, School onboarding/directory/import, Reference data, Printed Resources (catalog/encoding/distribution), Digital Learning Materials, all three Equipment verticals, Enrollment, Settings/Content Management.
- **In Progress / Needs Enhancement**: Division Reports (no charts/PDF yet), Equipment lifecycle extras (no maintenance/warranty alerts, no disposal workflow).
- **Pending**: Deeper Analytics (drill-down, saved views), QR Labels, AI Recommendations, Regional (multi-division) Reports, cloud Object Storage.
- **Future**: Mobile App, Offline Sync, OCR, RFID, AI Chat Assistant.

## Credits

Built on the [Laravel React starter kit](https://github.com/laravel/react-starter-kit) (Laravel + Inertia + React + Tailwind + shadcn-style UI). See `composer.json` and `package.json` for the full list of open-source dependencies this project relies on.
