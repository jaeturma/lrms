<?php

use App\Http\Controllers\Admin\AdminAuthController;
use App\Http\Controllers\Admin\AdminDashboardController;
use App\Http\Controllers\Admin\LearningResourceTypeController;
use App\Http\Controllers\Admin\SchoolImportController;
use App\Http\Controllers\Admin\SchoolManagementController;
use App\Http\Controllers\HomePageController;
use App\Http\Controllers\SchoolController;
use App\Http\Controllers\SchoolDashboardController;
use Illuminate\Support\Facades\Route;

Route::get('/', HomePageController::class)->name('home');

Route::post('/school/find', [SchoolController::class, 'find'])->name('school.find');
Route::get('/school/activate/{school}', [SchoolController::class, 'edit'])->name('school.activate.edit');
Route::post('/school/activate/{school}', [SchoolController::class, 'activate'])->name('school.activate.store');
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
        Route::get('/import/schools', [SchoolImportController::class, 'index'])->name('admin.import.index');
        Route::post('/import/schools', [SchoolImportController::class, 'store'])->name('admin.import.store');
        Route::get('/schools', [SchoolManagementController::class, 'index'])->name('admin.schools.index');
        Route::get('/schools/create', [SchoolManagementController::class, 'create'])->name('admin.schools.create');
        Route::post('/schools', [SchoolManagementController::class, 'store'])->name('admin.schools.store');
        Route::get('/schools/{school}/edit', [SchoolManagementController::class, 'edit'])->name('admin.schools.edit');
        Route::put('/schools/{school}', [SchoolManagementController::class, 'update'])->name('admin.schools.update');
        Route::delete('/schools/{school}', [SchoolManagementController::class, 'destroy'])->name('admin.schools.destroy');
        Route::post('/learning-resource-types', [LearningResourceTypeController::class, 'store'])->name('admin.learning-resource-types.store');
        Route::put('/learning-resource-types/{learningResourceType}', [LearningResourceTypeController::class, 'update'])->name('admin.learning-resource-types.update');
        Route::delete('/learning-resource-types/{learningResourceType}', [LearningResourceTypeController::class, 'destroy'])->name('admin.learning-resource-types.destroy');
    });

Route::middleware(['auth', 'role:school'])->group(function (): void {
    Route::get('/dashboard', SchoolDashboardController::class)->name('dashboard');
    Route::put('/school/resources', [SchoolController::class, 'storeLearningResources'])->name('school.resources.store');
});

require __DIR__.'/settings.php';
