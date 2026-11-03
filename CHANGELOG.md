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
- **VALID-LR Monitoring Report** (`/app/admin/valid-lr/report`, "Monitoring Report" button on the module page) — coverage per district, answers to the four indicators, LR/SME/TVL delivery status, and every visit with items counted and count discrepancies (actual vs LRMIS for LRs, vs package quantity for SME/TVL/LWD). Filters by status, district and visit date; CSV export; printable A4 version.
- **Printable monitoring visit** (`/app/admin/valid-lr/visits/{id}/print`, Print button on the visit, school page and report) — the full VALID-LR form on A4 landscape: school profile, indicators with Yes/No boxes, LR/SME/TVL/LWD/ARAL tables, delivery status, findings and signature lines. A draft prints with blank count fields for the team to fill in on site; a completed visit prints as the signed record.

- **School self-assessment before monitoring** (`/school/valid-lr`, school sidebar "VALID-LR Self-Assessment") — the school opens its VALID-LR visit, enters its own count of every LR, SME and TVL item (plus MOV, year received, remarks, LWD and ARAL quantities) and submits. Submission requires a complete school profile (School Head, LR Designate, ICT/Math/Science Coordinators, Property Custodian, email), current-school-year enrolment, and a count for every LR/SME/TVL item. The school can keep correcting its counts after submitting, until the team completes the visit.
- **Validation gate and team leader** — schedulers (system admins and LR manager) schedule monitoring and assign a team leader from staff accounts; the team cannot save a visit until the school submits, unless a system admin overrides with a recorded reason. The team sees the school's count beside the validated count (live, refreshed every 20 s), with a one-click "Confirm" to accept it. Visit stages: Waiting for school → Ready for validation → Validation ongoing → Completed.
- **VALID-LR Live Board** (`/app/admin/valid-lr/board`) — read-only view for every VALID-LR role (LR manager, CID chief, ASDS/SDS, team leaders, etc.): counts per stage, and per school the profile/enrolment readiness, school-count and validated-count progress, count differences, team leader, schedule and last activity; refreshes every 30 s; filters by stage, district, team leader and school.
- **School personnel** — new school profile fields LR Designate, ICT Coordinator, Math Coordinator and Science Coordinator (alongside School Head and Property Custodian), editable in school activation/"Update School Details" and admin school create/edit, and shown on the school dashboard, admin school profile, VALID-LR school and visit pages, the printed visit, the Monitoring Report (screen, print) and its CSV. Completing a visit stores a snapshot of the names.

- **LRMIS count correction** — superadmin, sysadmin, LR manager (`manager`) and ITO can correct a visit's LRMIS-encoded counts ("Update LRMIS counts" on the visit's Learning Resources tab) to match the school's LRMIS records, even when they are not the visit's editor. The imported value, who changed it and when are kept on the item; rows covering a single grade also update the school's reference LRMIS count.
- **App Settings** (system admins): system color theme (DepEd Blue, Ocean, Forest, Sunset, Rose) applied to every user who hasn't picked a personal theme under Settings → Appearance (which gains "Use the system theme"); website (landing page) logo alongside the login and app logos; **Google reCAPTCHA v2** with enable/disable, site key and encrypted secret key — when enabled, the school login, admin login and forgot-password forms require the "I'm not a robot" check (verified server-side via `App\Services\RecaptchaService`); **Send Test Email** for the saved SMTP settings.
- **School logo** — schools upload or remove their logo from the dashboard (`school.logo.store|destroy`, `schools.logo_path`); shown on the school dashboard, the admin school profile and the printed VALID-LR visit.
- **LR Monitoring on the main dashboard** — the admin dashboard gains an LR Monitoring section: schools not started and visits per stage, validation coverage by district, items counted and how many differ from LRMIS or the package quantity, and the validations ongoing now (linking to each visit), plus links to the Live Board and Report.

### Changed

- **iLRMS branding** — the default app title is now "iLRMS - Integrated Learning Resource Management System" (`AppSettingsService::DEFAULT_APP_TITLE`; a migration replaces a blank, "Laravel" or "LRMS" stored title, keeping any title an admin chose); the sidebar shows "iLRMS" with the full name beneath it. `APP_NAME` defaults to `iLRMS`.
- Admin dashboard renamed from "Division Dashboard" to "iLRMS Dashboard"; its header and Scope bar are no longer boxed cards.
- "VALID-LR Monitoring" is now "LR Monitoring" in the sidebar (moved directly under Dashboard) and on the module and report pages; the printed form keeps its official "Project VALID-LR" name.
- Seeded admin and staff accounts (`AdminUserSeeder`, `RoleUserSeeder`) now use the default password `Pass1234!` (was `password` / `Pass1234`). Existing accounts are unchanged until the seeders are re-run.
- Landing page background is a much lighter wash of the theme color (`.landing-surface`); the app-wide gradient is unchanged.
- Monitoring visits can now be edited only by the assigned team leader and system admins (`admin`, `superadmin`, `sysadmin`, `ito`); other VALID-LR roles see them read-only. Only schedulers (system admins and `manager`) can start/schedule a visit, and only system admins can delete one or override the school-submission gate. Visits recorded before this change count as already under validation.
- Team and school saves send only the rows each side changed, so a school correcting counts and a team validating at the same time don't overwrite each other.
- Monitoring visits: marking a visit **Completed** now requires the visit date/time, monitoring team, school representative and "prepared by" (drafts still save with blanks). The visit page asks before leaving with unsaved changes (in-app links and closing the tab), shows "All changes saved · last saved …" after a save, and no longer keeps showing "unsaved changes" after a successful save.
- Pages whose Inertia component name ends in `Print` render without the app layout (`resources/js/app.tsx`).
- Application timezone changed from `UTC` to `Asia/Manila` (`config/app.php`), so saved/visited times display in Philippine time. Timestamps written before this change were stored as UTC and now read 8 hours early; they are not converted.

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
