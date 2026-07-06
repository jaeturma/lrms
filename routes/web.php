<?php

use App\Http\Controllers\Admin\AdminAuthController;
use App\Http\Controllers\Admin\AdminDashboardController;
use App\Http\Controllers\Admin\AdminLocationController;
use App\Http\Controllers\Admin\AppSettingsController;
use App\Http\Controllers\Admin\ContentManagementController;
use App\Http\Controllers\Admin\GradeLevelController;
use App\Http\Controllers\Admin\LearningMaterialsController;
use App\Http\Controllers\Admin\LearningResourceTypeController;
use App\Http\Controllers\Admin\SchoolImportController;
use App\Http\Controllers\Admin\SchoolManagementController;
use App\Http\Controllers\Admin\SchoolYearController;
use App\Http\Controllers\ContentPageController;
use App\Http\Controllers\HomePageController;
use App\Http\Controllers\SchoolController;
use App\Http\Controllers\SchoolDashboardController;
use App\Http\Controllers\SchoolEnrollmentController;
use Illuminate\Support\Facades\Route;

Route::get('/', HomePageController::class)->name('home');
Route::get('/support', [ContentPageController::class, 'support'])->name('support');
Route::get('/about', [ContentPageController::class, 'about'])->name('about');

Route::post('/school/find', [SchoolController::class, 'find'])->middleware('throttle:10,1')->name('school.find');
Route::get('/school/activate/{school}', [SchoolController::class, 'edit'])->name('school.activate.edit');
Route::post('/school/activate/{school}', [SchoolController::class, 'activate'])->middleware('throttle:6,1')->name('school.activate.store');
Route::post('/school/activate/{school}/verify-otp', [SchoolController::class, 'verifyActivationOtp'])->middleware('throttle:6,1')->name('school.activate.verify-otp');
Route::get('/school/activate/{school}/credentials', [SchoolController::class, 'credentials'])->name('school.activate.credentials');

Route::middleware('guest')->group(function (): void {
    Route::get('/app/admin/login', [AdminAuthController::class, 'create'])->name('admin.login');
    Route::post('/app/admin/login', [AdminAuthController::class, 'store'])->name('admin.login.store');
});

Route::post('/app/admin/logout', [AdminAuthController::class, 'destroy'])
    ->middleware('auth')
    ->name('admin.logout');

Route::prefix('/app/admin')
    ->middleware(['auth', 'role:admin'])
    ->group(function (): void {
        Route::get('/dashboard', AdminDashboardController::class)->name('admin.dashboard');
        Route::get('/locations', [AdminLocationController::class, 'index'])->name('admin.locations.index');
        Route::get('/districts', [AdminLocationController::class, 'districts'])->name('admin.districts.index');
        Route::get('/municipalities', [AdminLocationController::class, 'municipalities'])->name('admin.municipalities.index');
        Route::get('/barangays', [AdminLocationController::class, 'barangays'])->name('admin.barangays.index');
        Route::post('/locations/import', [AdminLocationController::class, 'import'])->name('admin.locations.import');
        Route::get('/locations/template', [AdminLocationController::class, 'downloadTemplate'])->name('admin.locations.template');
        Route::post('/locations/{type}', [AdminLocationController::class, 'store'])->name('admin.locations.store');
        Route::put('/locations/{type}/{id}', [AdminLocationController::class, 'update'])->name('admin.locations.update');
        Route::delete('/locations/{type}/{id}', [AdminLocationController::class, 'destroy'])->name('admin.locations.destroy');
        Route::get('/school-years', [SchoolYearController::class, 'index'])->name('admin.school-years.index');
        Route::post('/school-years', [SchoolYearController::class, 'store'])->name('admin.school-years.store');
        Route::put('/school-years/{schoolYear}', [SchoolYearController::class, 'update'])->name('admin.school-years.update');
        Route::post('/school-years/{schoolYear}/activate', [SchoolYearController::class, 'activate'])->name('admin.school-years.activate');
        Route::delete('/school-years/{schoolYear}', [SchoolYearController::class, 'destroy'])->name('admin.school-years.destroy');
        Route::get('/grade-levels', [GradeLevelController::class, 'index'])->name('admin.grade-levels.index');
        Route::post('/grade-levels', [GradeLevelController::class, 'store'])->name('admin.grade-levels.store');
        Route::put('/grade-levels/{gradeLevel}', [GradeLevelController::class, 'update'])->name('admin.grade-levels.update');
        Route::delete('/grade-levels/{gradeLevel}', [GradeLevelController::class, 'destroy'])->name('admin.grade-levels.destroy');
        Route::get('/learning-resource-types', [LearningResourceTypeController::class, 'index'])->name('admin.learning-resource-types.index');
        Route::get('/learning-materials', [LearningMaterialsController::class, 'index'])->name('admin.learning-materials.index');
        Route::get('/settings', [AppSettingsController::class, 'edit'])->name('admin.settings.edit');
        Route::put('/settings', [AppSettingsController::class, 'update'])->name('admin.settings.update');
        Route::get('/import/schools', [SchoolImportController::class, 'index'])->name('admin.import.index');
        Route::get('/import/schools/template', [SchoolImportController::class, 'downloadTemplate'])->name('admin.import.template');
        Route::post('/import/schools', [SchoolImportController::class, 'store'])->name('admin.import.store');
        Route::get('/schools', [SchoolManagementController::class, 'index'])->name('admin.schools.index');
        Route::get('/schools/create', [SchoolManagementController::class, 'create'])->name('admin.schools.create');
        Route::post('/schools', [SchoolManagementController::class, 'store'])->name('admin.schools.store');
        Route::get('/schools/{school}', [SchoolManagementController::class, 'show'])->name('admin.schools.show');
        Route::get('/schools/{school}/edit', [SchoolManagementController::class, 'edit'])->name('admin.schools.edit');
        Route::put('/schools/{school}', [SchoolManagementController::class, 'update'])->name('admin.schools.update');
        Route::post('/schools/{school}/manual-activate', [SchoolManagementController::class, 'manuallyActivate'])->name('admin.schools.manual-activate');
        Route::delete('/schools/{school}', [SchoolManagementController::class, 'destroy'])->name('admin.schools.destroy');
        Route::post('/learning-resource-types', [LearningResourceTypeController::class, 'store'])->name('admin.learning-resource-types.store');
        Route::put('/learning-resource-types/{learningResourceType}', [LearningResourceTypeController::class, 'update'])->name('admin.learning-resource-types.update');
        Route::delete('/learning-resource-types/{learningResourceType}', [LearningResourceTypeController::class, 'destroy'])->name('admin.learning-resource-types.destroy');
        Route::get('/content/support', [ContentManagementController::class, 'editSupport'])->name('admin.content.edit-support');
        Route::post('/content/support', [ContentManagementController::class, 'updateSupport'])->name('admin.content.update-support');
        Route::get('/content/about', [ContentManagementController::class, 'editAbout'])->name('admin.content.edit-about');
        Route::post('/content/about', [ContentManagementController::class, 'updateAbout'])->name('admin.content.update-about');
    });

Route::middleware(['auth', 'role:school'])->group(function (): void {
    Route::get('/dashboard', SchoolDashboardController::class)->name('dashboard');
    Route::get('/school/learning-resources', [SchoolDashboardController::class, 'learningResources'])->name('school.resources.index');
    Route::put('/school/resources', [SchoolController::class, 'storeLearningResources'])->name('school.resources.store');
    Route::get('/school/enrollment', [SchoolEnrollmentController::class, 'index'])->name('school.enrollment.index');
    Route::put('/school/enrollment', [SchoolEnrollmentController::class, 'store'])->name('school.enrollment.store');
});

require __DIR__.'/settings.php';
