# LRMS — Learning Resources Monitoring System

A Laravel + Inertia/React web application that lets a Schools Division Office track learning resources, ICT / Science-Math / Other equipment, and enrollment data across every school under it — from the division office down to each individual school's own encoding of its stock.

## Project Overview

LRMS gives two audiences a shared, real-time system instead of paper reporting:

- **Schools** get a single place to self-activate their account and encode what printed resources, equipment, and enrollment they actually have on hand.
- **The division office** (superintendents, program managers, supply/ICT officers, librarians) gets a filterable, real-time view of resource adequacy, equipment condition, and delivery status across every school.

The system also tracks the full lifecycle of a physical resource: division catalog → distribution to a school → school receipt → on-hand inventory ledger → movement history (issued, borrowed, damaged, lost, condemned).

This is a server-driven SPA: Laravel owns routing, validation, and data; React renders every page via Inertia, with no separate REST/JSON API to maintain.

## Features

- Role-based **admin workspace** (10 staff roles) and a separate **school workspace** (1 role), enforced entirely via route middleware.
- Self-service school activation with email OTP verification and auto-generated credentials.
- Executive dashboards (division-wide and per-school) with KPI tiles and charts, filterable by district, school type, and grade level.
- Two-tier equipment tracking (division catalog + per-school physical units) with full movement/audit history, mirrored across ICT, Science & Math, and Other equipment.
- Delivery lifecycle: admin creates a distribution → school confirms receipt → resource lands directly in the school's inventory ledger.
- CSV import/export across schools, locations, resource titles, digital materials, and all three equipment catalogs.
- Two-Factor Authentication (TOTP) and OTP-based password reset.
- Configurable branding (logos) and SMTP settings from the admin panel, applied at runtime — no `.env` edits required for mail delivery.

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

## Architecture

- **Server-driven SPA, not a REST API.** Every page is an Inertia response (`Inertia::render()`) rendering a React component in `resources/js/pages`; there is no separate JSON API layer for the frontend to consume.
- **Layered backend.** Controllers stay thin (validate via Form Requests, authorize, call a Service, return a response). Business logic — imports, inventory ledger math, distribution lifecycle, equipment movement logging — lives in `app/Services`.
- **No Policy classes.** Authorization is enforced entirely through route-level `role:` middleware (`App\Http\Middleware\EnsureUserRole`) plus manual `abort_if($resource->school_id !== $school->id, 403)` ownership checks inside controllers.
- **Two workspaces, one codebase.** The admin/division workspace (`/app/admin/*`, 10 staff roles) and the school workspace (single `school` role, one user per school) have separate login flows (`AdminAuthController` vs. Fortify) and separate route groups, but share the same models, migrations, and UI component library.
- **Typed routing via Laravel Wayfinder.** TypeScript functions for controllers/routes are generated into `resources/js/actions` and `resources/js/routes`, so the React side calls Laravel endpoints with compile-time-checked helpers instead of hardcoded URL strings.
- **Three near-identical equipment verticals.** ICT Equipment, Science & Math Equipment (SME), and Other Equipment each follow the same pattern: an admin-maintained catalog table, a per-school physical-unit table, and a movement/audit log table — currently implemented as parallel, triplicated code rather than a shared abstraction.
- **Shared dashboard chart components.** `ChartCard`, `GroupedColumnChart`, `HProgressBars`, `DivergingStackedBar`, and `StatTile` are reused by both the Admin and School dashboards to keep the executive visual language consistent.

## Installation

Prerequisites: PHP ^8.3 with the extensions Laravel 13 requires, Composer, Node.js 22+ (for Vite), and either SQLite (default) or MySQL.

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

Run `php artisan storage:link` after install so uploaded files (logos, resource covers, digital material attachments) are web-accessible.

## Configuration

Key `.env` values (see `.env.example` for the full list):

| Variable | Notes |
|---|---|
| `APP_NAME`, `APP_URL` | App name/base URL used in emails and links |
| `DB_CONNECTION` | `sqlite` by default; set to `mysql` + `DB_HOST/PORT/DATABASE/USERNAME/PASSWORD` for MySQL |
| `SESSION_DRIVER`, `CACHE_STORE`, `QUEUE_CONNECTION` | Default to `database` — no Redis required to run locally |
| `MAIL_MAILER` | Defaults to `log` (emails written to the log, not sent). Real OTP/credential emails require SMTP to be configured — either here or via **App Settings** in the admin panel at runtime |
| `FILESYSTEM_DISK` | Defaults to `local`; uploaded logos/covers/attachments are served from the `public` disk (`php artisan storage:link` may be needed) |
| `AWS_*` | Present in `.env.example` but not wired to any cloud storage disk in this codebase yet — file uploads use the local `public` disk only |

SMTP and branding can also be configured **at runtime**, without touching `.env`, from **Admin → Settings** (`admin.settings.edit`) — see [Application Settings](#existing-modules) below. This is backed by the `app_settings` key/value table and applied to the mailer per-request via `AppSettingsService::applyToMailer()`.

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
.ai/                Machine-generated reference docs (module audit, roadmap, changelog history)
.github/workflows/  CI: tests.yml (Pest matrix), lint.yml (Pint + ESLint + Prettier)
```

## Existing Modules

21 modules, grouped by area (full per-module completion % and feature/gap detail in [`.ai/07-modules.md`](.ai/07-modules.md)):

- **Platform**: Authentication & Account Security, User Account Settings, Application Settings (branding/SMTP), Content Management (Support/About pages)
- **Reference Data**: Locations (District/Municipality/Barangay), School Years & Grade Levels, Learning Resource Types
- **Schools**: School Directory & Profile Management, School Self-Activation Onboarding, School Bulk Import (CSV)
- **Dashboards & Reporting**: Admin Executive Dashboard, School Dashboard, Division Reports & CSV Exports
- **Printed Learning Resources**: Resource Title Catalog, School Resource Encoding & Inventory Ledger, Resource Distribution (division → school)
- **Digital Learning Materials**: Digital Learning Materials Repository
- **Equipment**: ICT Equipment, Science & Math Equipment (SME), Other Equipment/Materials — each with an admin catalog, a per-school inventory, and a movement/audit log
- **Enrollment**: School Enrollment (per grade level, per school year)

## User Roles

Enforced by `App\Http\Middleware\EnsureUserRole` and route-level `role:` middleware groups in `routes/web.php`. There are no Laravel Policy classes — authorization is route middleware plus manual ownership checks (`abort_if($resource->school_id !== $school->id, 403)`) inside controllers.

| Role | Scope |
|---|---|
| `school` | One per school. Own-school CRUD only (equipment, printed resources, enrollment, distributions receipt, activation). |
| `manager`, `librarian`, `supply` | Admin workspace + catalog management (resource titles, all three equipment catalogs, digital learning materials). |
| `cidchief`, `asds`, `sds` | Admin workspace, monitoring/reporting only — no catalog or reference-data management, no Settings/Distributions access. |
| `ito` | Admin workspace + catalog management + full system-admin access (reference data, settings, content, distributions, imports). |
| `sysadmin`, `admin`, `superadmin` | Full system-admin access — everything `ito` can do, plus (implicitly) any future admin-only surface. |

Route-level role groups actually used: `adminWorkspaceRoles` (base gate for all 10 staff roles), `catalogRoles` (7 roles — everyone except `cidchief`/`asds`/`sds`), `systemAdminRoles` (4 roles: `admin, superadmin, sysadmin, ito`), and `role:school` for the entire school-side route group.

## Development Workflow

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

If a frontend change doesn't show up in the browser, run `npm run build` (production) or make sure `npm run dev` / `composer run dev` is running (development, hot-reloaded via Vite).

## Deployment

There is no deployment pipeline (Docker, Forge, Envoyer, etc.) checked into this repository — `.github/workflows/` only contains CI for tests and linting (see [Testing](#testing)), not a deploy job. Deploying is standard Laravel:

1. `composer install --no-dev --optimize-autoloader`
2. `npm ci && npm run build`
3. Set production `.env` (`APP_ENV=production`, `APP_DEBUG=false`, real `DB_*`/`MAIL_*` credentials or configure SMTP via Admin → Settings post-deploy)
4. `php artisan migrate --force`
5. `php artisan storage:link`
6. `php artisan config:cache && php artisan route:cache && php artisan view:cache`
7. Point the web server at `public/`

No Mailable or Notification in the codebase implements `ShouldQueue`, so mail (OTP codes, activation credentials) currently sends synchronously — a queue worker is not required for correctness, only for the `queue:listen` process used in local dev via `composer run dev`.

## Testing

```bash
php artisan test          # full Pest suite
composer run test         # config:clear + lint:check + php artisan test
composer run ci:check     # lint:check + format:check + types:check + test
```

As of `v0.8.0`, the full Pest suite passes: **207 tests, 1,476 assertions**. CI (`.github/workflows/tests.yml`) runs the suite on PHP 8.3, 8.4, and 8.5 against Node 22 on every push/PR to `develop`, `main`, `master`, and `workos`. A separate workflow (`.github/workflows/lint.yml`) runs Pint, ESLint, and Prettier checks.

## Roadmap

See [`.ai/08-roadmap.md`](.ai/08-roadmap.md) for the full, reconciled roadmap. Summary:

- **Completed**: Authentication, Dashboards, School onboarding/directory/import, Reference data, Printed Resources (catalog/encoding/distribution), Digital Learning Materials, all three Equipment verticals, Enrollment, Settings/Content Management.
- **In Progress / Needs Enhancement**: Division Reports (no charts/PDF yet), Equipment lifecycle extras (no maintenance/warranty alerts, no disposal workflow).
- **Pending**: Deeper Analytics (drill-down, saved views), QR Labels, AI Recommendations, Regional (multi-division) Reports, cloud Object Storage.
- **Future**: Mobile App, Offline Sync, OCR, RFID, AI Chat Assistant.

## Credits

Built on the [Laravel React starter kit](https://github.com/laravel/react-starter-kit) (Laravel + Inertia + React + Tailwind + shadcn-style UI). See `composer.json` and `package.json` for the full list of open-source dependencies this project relies on.
