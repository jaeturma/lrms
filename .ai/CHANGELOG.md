# Changelog

All notable changes to this project are documented in this file. Format follows [Keep a Changelog](https://keepachangelog.com/en/1.1.0/).

This is the first entry in this file — there is no prior tagged release to diff against, so `v0.8.0` documents the audited baseline state of the codebase as verified against routes, controllers, models, migrations, services, seeders, tests, and Inertia pages. See [`07-modules.md`](07-modules.md) for full per-module detail and [`08-roadmap.md`](08-roadmap.md) for forward-looking status.

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

### In Progress

- **Division Reports** — functional (adequacy, equipment breakdowns, CSV exports) but still tabular-only; no chart visualizations, no PDF output, no scheduling.
- **Equipment lifecycle extras** — condition/status tracking and movement history work across all three verticals, but there is no maintenance/warranty-expiry alerting, depreciation reporting, or formal disposal/condemnation workflow.

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
