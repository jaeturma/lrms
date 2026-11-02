# Changelog

All notable changes to this project are documented in this file. Format follows [Keep a Changelog](https://keepachangelog.com/en/1.1.0/).

This is the first entry in this file — there is no prior tagged release to diff against, so `v0.8.0` documents the audited baseline state of the codebase as verified against routes, controllers, models, migrations, services, seeders, tests, and Inertia pages. See [`.ai/07-modules.md`](.ai/07-modules.md) for full per-module detail and [`.ai/08-roadmap.md`](.ai/08-roadmap.md) for forward-looking status.

## [v0.9.0] - 2026-11-02

### Added

- **VALID-LR Monitoring module** (`/app/admin/valid-lr`, sidebar "VALID-LR Monitoring") — replaces the `VALID-LR_Monitoring_Tool.xlsx` workflow (Project VALID-LR, 2026 cycle). See [`.ai/07-modules.md`](.ai/07-modules.md) §22.
  - Imports the tool's source workbooks, scoped to one division: `_reference_data.xlsx` (schools, LR allocations, regional titles/publishers), `LRMIS_ENCODED.xlsx` (LRMIS-encoded totals), `masterlist lte.xlsx` (SME delivery years per key stage), and the monitoring tool itself (SME Central Office/Downloaded items, TVL packages, TVL procurement status). Upload from the module page (system admins) or run `php artisan valid-lr:import {type} {path} [--division=]`.
  - School ID lookup reproduces the tool's ES/HS, SME and TVL sheets, plus LRMS-recorded quantities and active-year enrolment when the school is registered in LRMS.
  - Monitoring visits snapshot the reference data, then record actual counts, distributed quantities, enrolment, MOV, year received, repairs/missing/others remarks, IMs for learners with disabilities, ARAL quantities, the four Yes/No indicators, delivery status, findings and sign-off (draft/completed).
  - Nine new tables (`valid_lr_*`); no existing table, route or permission was changed.
- `App\Support\XlsxStreamReader` — streaming, multi-sheet .xlsx reader (built on PHP's `XMLReader`/`ZipArchive`, no new Composer dependency); reads 546k-row sheets in seconds with flat memory.

### Known Issues

- Elementary allocation columns in `_reference_data.xlsx` hold division-level totals (identical for every school), so the lookup labels them as such rather than as per-school allocations.
- Very large uploads (the monitoring tool is ~46 MB) depend on the web server's request-size limits; use the artisan command if an upload is rejected.

## [v0.8.0] - 2026-07-09

### Added

All 21 modules below are verified present in the codebase (routes + controller + model/migration + at least one feature test, unless noted):

- **Authentication & Account Security** — school login (Fortify, role-restricted), separate admin/staff login, OTP-based password reset, TOTP 2FA with confirmation.
- **User Account Settings** — profile edit/delete, password change, 2FA management, appearance theme.
- **Application Settings** — branding (login/app logo upload) and SMTP configuration, applied at runtime.
- **Content Management** — editable Support and About pages (key/title/body content model).
- **Location Reference Data** — Districts, Municipalities, Barangays: CRUD + CSV import + delete guards.
- **School Years & Grade Levels** — CRUD, single-active-year invariant, delete guards tied to enrollment usage.
- **Learning Resource Types** — CRUD with `Print`/`Non-Print`/`Equipment` categories.
- **School Directory & Profile Management** — admin CRUD, manual activation, credential resend, soft-delete.
- **School Self-Activation Onboarding** — public lookup, OTP-verified activation, auto-generated credentials, SMTP-unavailable fallback to manual approval.
- **School Bulk Import (CSV)** — template download, on-the-fly location creation, duplicate-row skipping.
- **Admin Executive Dashboard** — KPI tiles + 4 charts, scope filters (district, school type, grade level), pending-activation panel.
- **School Dashboard** — same KPI/chart treatment scoped to a single school's own data, with verified data isolation.
- **Division Reports & CSV Exports** — resource adequacy, ICT/Other equipment breakdowns, SME summary, 4 CSV exports.
- **Printed Resource Title Catalog** — CRUD with cover/attachment uploads, CSV import, delete guard.
- **School Resource Encoding & Inventory Ledger** — catalog-backed or manual encoding, 6-state inventory ledger, full movement history.
- **Resource Distribution** — division-to-school delivery lifecycle (pending/received/cancelled) with auto-generated reference codes.
- **Digital Learning Materials Repository** — admin catalog CRUD + CSV import, read-only school-facing browsing.
- **ICT Equipment** — division catalog + per-school CRUD + movement/audit log + admin monitoring.
- **Science & Math Equipment (SME)** — same structure as ICT Equipment, scoped to Science/Mathematics categories.
- **Other Equipment/Materials** — same structure as ICT Equipment, scoped to TVL/ALS/Library/SPED/Sports/Others categories.
- **School Enrollment** — per-grade-level male/female counts per active school year, replace-on-save semantics.

### Changed

- Admin and School dashboards rebuilt with a shared executive visual language: hand-rolled SVG/HTML chart components (`ChartCard`, `GroupedColumnChart`, `HProgressBars`, `DivergingStackedBar`), extracted shared `StatTile` and `EmptyChart` components used by both dashboards.
- Admin Dashboard gained scope filters (district, school type, grade level, "Entire Division" reset) applied consistently across every aggregate query.
- School Dashboard rebuilt to mirror the Admin Dashboard's KPI-tile-and-chart layout, scoped to the school's own data.
- Entry/edit modal dialogs (school equipment/SME registration, learning-resource entry, admin catalog management) widened and re-gridded so fields fit without vertical scrolling on desktop.
- Sidebar navigation icons switched from a filled to a pure outline style.
- Dashboard KPI tiles restyled: each tile now uses a distinct dark background color, the icon chip background was removed, the icon size was doubled, and overall dashboard spacing was tightened to reduce scrolling.

### Fixed

- No prior tagged release exists to diff against, so no fixes are recorded relative to an earlier version in this changelog. Internal lint/style corrections made during this audit period (ESLint import-order and blank-line rules) are cosmetic and not user-facing.

### Known Issues

- **No Policy classes** — authorization is enforced entirely through route-level `role:` middleware and manual `abort_if()` ownership checks inside controllers, not centralized Policy classes.
- **Duplicate logic across ICT / SME / Other Equipment** — the three equipment verticals share an identical model/controller/service shape that is triplicated in code rather than abstracted into a shared base.
- **CSV is the only bulk import/export format** across every module that supports bulk I/O (schools, locations, resource titles, digital materials, all three equipment catalogs, all report exports) — no Excel or PDF support anywhere.
- **`qr_code` / `barcode` columns are unused** — present as plain text fields on all three equipment tables, but no QR/barcode image generation, label printing, or scan-to-lookup feature exists.
- **No notification/alerting system** — no low-stock, warranty-expiry, or aging-pending-activation alerts anywhere; every workflow is pull-based (a user must open a page to notice a problem).
- **Resource Distribution may create duplicate `LearningResource` rows** — receiving two separate distributions of the same title for the same school creates two independent `LearningResource`/inventory-ledger rows instead of accumulating into one, fragmenting the school's on-hand count for that title.
- **Schools can be activated or deleted, but not deactivated back to pending** — there is no admin action to revert an activated school to an unactivated state short of deleting it outright.
- **Reports module lacks chart visualizations** — unlike the Admin and School dashboards (which use `ChartCard`/`GroupedColumnChart`/etc.), the Reports page remains purely tabular with CSV export as the only data-visualization/export mechanism.
- No region/multi-division data model exists — all dashboards and reports are scoped to a single division; "Regional Reports" would require a schema change, not just a new report screen.
- File uploads (branding logos, resource covers/attachments, digital material files) are stored on the local `public` filesystem disk; no cloud object storage integration is wired up despite `AWS_*` variables being present in `.env.example`.

### In Progress

- **Division Reports** — functional (adequacy, equipment breakdowns, CSV exports) but still tabular-only; no chart visualizations, no PDF output, no scheduling.
- **Equipment lifecycle extras** — condition/status tracking and movement history work across all three verticals, but there is no maintenance/warranty-expiry alerting, depreciation reporting, or formal disposal/condemnation workflow.

### Roadmap

Full detail in [`.ai/08-roadmap.md`](.ai/08-roadmap.md).

- **Pending**: Deeper Analytics (drill-down navigation, saved/bookmarked views, year-over-year comparisons), QR Labels (image generation, printing, scan-to-lookup — schema already has the columns), AI Recommendations, Regional Reports (requires a region/multi-division schema, not just a new report), cloud Object Storage (S3-compatible, to replace the local `public` disk).
- **Future**: Mobile App, Offline Sync, OCR, RFID, AI Chat Assistant.
