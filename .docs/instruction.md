# PROJECT TITLE

Learning Resources Monitoring System

# PROJECT OVERVIEW

Create a Laravel 13 + React web application for collecting and monitoring defective learning resources from schools.

The system has two user types:

1. Admin
2. School Users

---

## MAIN FEATURES

ADMIN SIDE

* Separate admin login
* CSV import of schools
* Manage school records
* View submitted learning resources
* Dashboard and reports

SCHOOL SIDE

* Initial access using School ID
* School updates profile information
* School enters email address
* System generates password automatically
* Credentials sent/displayed to school
* School can login afterward
* School can encode multiple learning resources dynamically

---

## TECH STACK

Backend:

* Laravel 13
* MySQL

Frontend:

* React
* Tailwind CSS
* Axios

---

## AUTHENTICATION STRUCTURE

ADMIN LOGIN
URL:

* /app/admin/login

Use Laravel authentication.

Admin features:

* dashboard
* csv import
* reports
* manage schools

---

## SCHOOL USER FLOW

INITIAL ACCESS FLOW

1. School opens homepage
2. Inputs School ID
3. If School ID exists:

   * Load school record
4. School updates:

   * School Head
   * Librarian
   * Property Custodian
   * Email Address
5. Upon first save:

   * Generate random password
   * Create school user account
   * Hash password
   * Save email/password
   * Mark school as activated
6. Show generated password once
7. Future access uses:

   * email + password login

---

## DATABASE TABLES

Create these tables:

1. users
2. schools
3. learning_resources
4. municipalities
5. barangays
6. districts

---

## TABLE: users

Columns:

* id
* name
* email
* password
* role (admin, school)
* school_id nullable
* email_verified_at nullable
* remember_token
* created_at
* updated_at

---

## TABLE: districts

Columns:

* id
* name
* created_at
* updated_at

---

## TABLE: municipalities

Columns:

* id
* district_id
* name
* created_at
* updated_at

---

## TABLE: barangays

Columns:

* id
* municipality_id
* name
* created_at
* updated_at

---

## TABLE: schools

Columns:

* id
* district_id
* municipality_id
* barangay_id nullable
* school_id (unique)
* school_name
* school_head nullable
* librarian nullable
* property_custodian nullable
* email nullable
* user_id nullable
* is_activated boolean default false
* created_at
* updated_at

---

## TABLE: learning_resources

Columns:

* id
* school_id (foreign key)
* resource_type
* issue_defect
* quantity
* publisher
* created_at
* updated_at

---

## CSV IMPORT REQUIREMENTS

ADMIN can upload CSV file.

CSV Columns:

* district
* municipality
* barangay
* school_id
* school_name

Import behavior:

1. Create districts if not existing
2. Create municipalities if not existing
3. Create barangays if not existing
4. Create schools
5. Prevent duplicate school_id
6. Show import summary

Use:

* Laravel Excel package

---

## ADMIN FEATURES

Admin dashboard:

* total schools
* activated schools
* pending schools
* total learning resources
* reports by district

Admin can:

* search schools
* filter by district
* view submissions
* export Excel/PDF

---

## SCHOOL FEATURES

PUBLIC ACCESS PAGE
Route:

* /

Fields:

* School ID

Button:

* Continue

---

## FIRST TIME SETUP PAGE

If school is not activated:

Display:

* School Name
* District
* Municipality
* Barangay

Allow editing:

* School Head
* Librarian
* Property Custodian
* Email Address

Upon save:

* Generate random password
* Create school user
* Show credentials once

---

## SCHOOL LOGIN PAGE

Route:

* /login

Use:

* email
* password

---

## SCHOOL DASHBOARD

After login:

* show school information
* show learning resources table

---

## LEARNING RESOURCES TABLE

Dynamic add/remove rows using React state.

Columns:

* Type of Learning Resource
* Issue/Defect
* Quantity
* Printer/Publisher
* Action

Buttons:

* Add Row
* Save Changes

---

## FRONTEND REQUIREMENTS

Use:

* React hooks
* Axios
* Tailwind CSS

UI Requirements:

* Mobile responsive
* Government/professional design
* Clean cards
* Sticky Save button on mobile
* Responsive table

---

## BACKEND REQUIREMENTS

Use:

* API Controllers
* Form Requests
* Resource Responses
* Service classes for import logic

---

## VALIDATION RULES

School activation:

* valid school_id
* unique email
* required email
* required school_head

Learning resources:

* quantity numeric
* resource_type required

---

## PASSWORD GENERATION

Generate secure random password:

* 10 characters minimum

Hash password using Laravel Hash.

Display generated password ONCE after activation.

Optional:

* send credentials via email if mail configured

---

## ROUTES STRUCTURE

PUBLIC

* /
* /school/find
* /login

ADMIN

* /app/admin/*
  Protected via middleware.

SCHOOL USER
Protected authenticated routes.

---

## RECOMMENDED IMPLEMENTATION

Backend:

* Laravel Sanctum or session auth
* Repository/service pattern optional

Frontend:

* React pages/components
* Dynamic row table component

---

## REACT COMPONENTS

Create:

* HomePage
* SchoolActivationPage
* LoginPage
* SchoolDashboard
* LearningResourcesTable
* AdminDashboard
* SchoolImportPage

---

## IMPORTANT NOTES

1. Admin and School users are separate roles
2. School activation happens only once
3. School ID is preloaded via CSV import
4. School email becomes login credential
5. Prepare architecture for future scaling
6. Use proper foreign keys and indexing
7. Prevent duplicate school records

---

## BONUS FEATURES IF POSSIBLE

1. Auto-save draft
2. Activity logs
3. Import validation report
4. Dashboard charts
5. Excel export
6. PDF export
7. Password reset
8. QR code school access
9. Submission status tracker

---

## EXPECTED OUTPUT

A fully working Laravel 13 + React application where:

ADMIN:

* imports schools via CSV
* manages reports and submissions

SCHOOLS:

* activate account using School ID
* receive generated password
* login using email/password
* encode multiple learning resources dynamically
* update records anytime
