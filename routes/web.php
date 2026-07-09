<?php

use App\Http\Controllers\Admin\AdminAuthController;
use App\Http\Controllers\Admin\AdminDashboardController;
use App\Http\Controllers\Admin\AdminLocationController;
use App\Http\Controllers\Admin\AppSettingsController;
use App\Http\Controllers\Admin\ContentManagementController;
use App\Http\Controllers\Admin\DigitalLearningMaterialController;
use App\Http\Controllers\Admin\DigitalLearningMaterialImportController;
use App\Http\Controllers\Admin\DistributionController;
use App\Http\Controllers\Admin\GradeLevelController;
use App\Http\Controllers\Admin\IctEquipmentCatalogController;
use App\Http\Controllers\Admin\IctEquipmentController;
use App\Http\Controllers\Admin\IctEquipmentImportController;
use App\Http\Controllers\Admin\LearningMaterialsController;
use App\Http\Controllers\Admin\LearningResourceImportController;
use App\Http\Controllers\Admin\LearningResourceTypeController;
use App\Http\Controllers\Admin\OtherEquipmentCatalogController;
use App\Http\Controllers\Admin\OtherEquipmentController;
use App\Http\Controllers\Admin\OtherEquipmentImportController;
use App\Http\Controllers\Admin\ReportsController;
use App\Http\Controllers\Admin\ResourceTitleController;
use App\Http\Controllers\Admin\SchoolImportController;
use App\Http\Controllers\Admin\SchoolManagementController;
use App\Http\Controllers\Admin\SchoolYearController;
use App\Http\Controllers\Admin\SmeCatalogController;
use App\Http\Controllers\Admin\SmeController;
use App\Http\Controllers\Admin\SmeImportController;
use App\Http\Controllers\ContentPageController;
use App\Http\Controllers\HomePageController;
use App\Http\Controllers\PasswordResetOtpController;
use App\Http\Controllers\SchoolController;
use App\Http\Controllers\SchoolDashboardController;
use App\Http\Controllers\SchoolDigitalLearningMaterialController;
use App\Http\Controllers\SchoolDistributionController;
use App\Http\Controllers\SchoolEnrollmentController;
use App\Http\Controllers\SchoolIctEquipmentController;
use App\Http\Controllers\SchoolInventoryController;
use App\Http\Controllers\SchoolOtherEquipmentController;
use App\Http\Controllers\SchoolSmeController;
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

    Route::get('/forgot-password', [PasswordResetOtpController::class, 'create'])->name('password.request');
    Route::post('/forgot-password', [PasswordResetOtpController::class, 'store'])->middleware('throttle:6,1')->name('password.otp.send');
    Route::get('/forgot-password/verify', [PasswordResetOtpController::class, 'showVerify'])->name('password.otp.verify');
    Route::post('/forgot-password/verify', [PasswordResetOtpController::class, 'update'])->middleware('throttle:6,1')->name('password.otp.update');
});

Route::post('/app/admin/logout', [AdminAuthController::class, 'destroy'])
    ->middleware('auth')
    ->name('admin.logout');

$adminWorkspaceRoles = 'admin,superadmin,sysadmin,ito,manager,librarian,supply,cidchief,asds,sds';
$systemAdminRoles = 'admin,superadmin,sysadmin,ito';
$catalogRoles = 'admin,superadmin,sysadmin,ito,manager,librarian,supply';
$coreMonitoringRoles = 'admin,superadmin,sysadmin,ito,manager,librarian,supply,cidchief,asds,sds';

Route::prefix('/app/admin')
    ->middleware(['auth', 'role:'.$adminWorkspaceRoles])
    ->group(function () use ($catalogRoles, $coreMonitoringRoles, $systemAdminRoles): void {
        Route::get('/dashboard', AdminDashboardController::class)->name('admin.dashboard');

        Route::middleware('role:'.$coreMonitoringRoles)->group(function (): void {
            Route::get('/learning-materials', [LearningMaterialsController::class, 'index'])->name('admin.learning-materials.index');
            Route::get('/digital-learning-materials', [DigitalLearningMaterialController::class, 'index'])->name('admin.digital-learning-materials.index');
            Route::get('/ict-equipment', [IctEquipmentController::class, 'index'])->name('admin.ict-equipment.index');
            Route::get('/other-equipment', [OtherEquipmentController::class, 'index'])->name('admin.other-equipment.index');
            Route::get('/sme', [SmeController::class, 'index'])->name('admin.sme.index');
            Route::get('/reports', [ReportsController::class, 'index'])->name('admin.reports.index');
            Route::get('/reports/learning-resources/export', [ReportsController::class, 'exportLearningResources'])->name('admin.reports.learning-resources.export');
            Route::get('/reports/ict-equipment/export', [ReportsController::class, 'exportIctEquipment'])->name('admin.reports.ict-equipment.export');
            Route::get('/reports/other-equipment/export', [ReportsController::class, 'exportOtherEquipment'])->name('admin.reports.other-equipment.export');
            Route::get('/reports/sme/export', [ReportsController::class, 'exportSme'])->name('admin.reports.sme.export');
            Route::get('/schools', [SchoolManagementController::class, 'index'])->name('admin.schools.index');
            Route::get('/schools/create', [SchoolManagementController::class, 'create'])->name('admin.schools.create');
            Route::post('/schools', [SchoolManagementController::class, 'store'])->name('admin.schools.store');
            Route::get('/schools/{school}', [SchoolManagementController::class, 'show'])->name('admin.schools.show');
            Route::get('/schools/{school}/edit', [SchoolManagementController::class, 'edit'])->name('admin.schools.edit');
            Route::put('/schools/{school}', [SchoolManagementController::class, 'update'])->name('admin.schools.update');
            Route::post('/schools/{school}/manual-activate', [SchoolManagementController::class, 'manuallyActivate'])->name('admin.schools.manual-activate');
            Route::post('/schools/{school}/send-credentials', [SchoolManagementController::class, 'sendCredentials'])->name('admin.schools.send-credentials');
            Route::delete('/schools/{school}', [SchoolManagementController::class, 'destroy'])->name('admin.schools.destroy');
        });

        Route::middleware('role:'.$catalogRoles)->group(function (): void {
            Route::get('/resource-titles', [ResourceTitleController::class, 'index'])->name('admin.resource-titles.index');
            Route::get('/resource-titles/import/template', [LearningResourceImportController::class, 'downloadTemplate'])->name('admin.resource-titles.import.template');
            Route::post('/resource-titles/import', [LearningResourceImportController::class, 'store'])->name('admin.resource-titles.import.store');
            Route::post('/resource-titles', [ResourceTitleController::class, 'store'])->name('admin.resource-titles.store');
            Route::put('/resource-titles/{resourceTitle}', [ResourceTitleController::class, 'update'])->name('admin.resource-titles.update');
            Route::delete('/resource-titles/{resourceTitle}', [ResourceTitleController::class, 'destroy'])->name('admin.resource-titles.destroy');
            Route::get('/digital-learning-materials/import/template', [DigitalLearningMaterialImportController::class, 'downloadTemplate'])->name('admin.digital-learning-materials.import.template');
            Route::post('/digital-learning-materials/import', [DigitalLearningMaterialImportController::class, 'store'])->name('admin.digital-learning-materials.import.store');
            Route::post('/digital-learning-materials', [DigitalLearningMaterialController::class, 'store'])->name('admin.digital-learning-materials.store');
            Route::put('/digital-learning-materials/{digitalLearningMaterial}', [DigitalLearningMaterialController::class, 'update'])->name('admin.digital-learning-materials.update');
            Route::delete('/digital-learning-materials/{digitalLearningMaterial}', [DigitalLearningMaterialController::class, 'destroy'])->name('admin.digital-learning-materials.destroy');
            Route::get('/ict-equipment-catalog', [IctEquipmentCatalogController::class, 'index'])->name('admin.ict-equipment-catalog.index');
            Route::get('/ict-equipment-catalog/import/template', [IctEquipmentImportController::class, 'downloadTemplate'])->name('admin.ict-equipment-catalog.import.template');
            Route::post('/ict-equipment-catalog/import', [IctEquipmentImportController::class, 'store'])->name('admin.ict-equipment-catalog.import.store');
            Route::post('/ict-equipment-catalog', [IctEquipmentCatalogController::class, 'store'])->name('admin.ict-equipment-catalog.store');
            Route::put('/ict-equipment-catalog/{ictEquipmentCatalogItem}', [IctEquipmentCatalogController::class, 'update'])->name('admin.ict-equipment-catalog.update');
            Route::delete('/ict-equipment-catalog/{ictEquipmentCatalogItem}', [IctEquipmentCatalogController::class, 'destroy'])->name('admin.ict-equipment-catalog.destroy');
            Route::get('/other-equipment-catalog', [OtherEquipmentCatalogController::class, 'index'])->name('admin.other-equipment-catalog.index');
            Route::get('/other-equipment-catalog/import/template', [OtherEquipmentImportController::class, 'downloadTemplate'])->name('admin.other-equipment-catalog.import.template');
            Route::post('/other-equipment-catalog/import', [OtherEquipmentImportController::class, 'store'])->name('admin.other-equipment-catalog.import.store');
            Route::post('/other-equipment-catalog', [OtherEquipmentCatalogController::class, 'store'])->name('admin.other-equipment-catalog.store');
            Route::put('/other-equipment-catalog/{otherEquipmentCatalogItem}', [OtherEquipmentCatalogController::class, 'update'])->name('admin.other-equipment-catalog.update');
            Route::delete('/other-equipment-catalog/{otherEquipmentCatalogItem}', [OtherEquipmentCatalogController::class, 'destroy'])->name('admin.other-equipment-catalog.destroy');
            Route::get('/sme-catalog', [SmeCatalogController::class, 'index'])->name('admin.sme-catalog.index');
            Route::get('/sme-catalog/import/template', [SmeImportController::class, 'downloadTemplate'])->name('admin.sme-catalog.import.template');
            Route::post('/sme-catalog/import', [SmeImportController::class, 'store'])->name('admin.sme-catalog.import.store');
            Route::post('/sme-catalog', [SmeCatalogController::class, 'store'])->name('admin.sme-catalog.store');
            Route::put('/sme-catalog/{smeCatalogItem}', [SmeCatalogController::class, 'update'])->name('admin.sme-catalog.update');
            Route::delete('/sme-catalog/{smeCatalogItem}', [SmeCatalogController::class, 'destroy'])->name('admin.sme-catalog.destroy');
        });

        Route::middleware('role:'.$systemAdminRoles)->group(function (): void {
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
            Route::post('/learning-resource-types', [LearningResourceTypeController::class, 'store'])->name('admin.learning-resource-types.store');
            Route::put('/learning-resource-types/{learningResourceType}', [LearningResourceTypeController::class, 'update'])->name('admin.learning-resource-types.update');
            Route::delete('/learning-resource-types/{learningResourceType}', [LearningResourceTypeController::class, 'destroy'])->name('admin.learning-resource-types.destroy');
            Route::get('/settings', [AppSettingsController::class, 'edit'])->name('admin.settings.edit');
            Route::put('/settings', [AppSettingsController::class, 'update'])->name('admin.settings.update');
            Route::get('/import/schools', [SchoolImportController::class, 'index'])->name('admin.import.index');
            Route::get('/import/schools/template', [SchoolImportController::class, 'downloadTemplate'])->name('admin.import.template');
            Route::post('/import/schools', [SchoolImportController::class, 'store'])->name('admin.import.store');
            Route::get('/content/support', [ContentManagementController::class, 'editSupport'])->name('admin.content.edit-support');
            Route::post('/content/support', [ContentManagementController::class, 'updateSupport'])->name('admin.content.update-support');
            Route::get('/content/about', [ContentManagementController::class, 'editAbout'])->name('admin.content.edit-about');
            Route::post('/content/about', [ContentManagementController::class, 'updateAbout'])->name('admin.content.update-about');
            Route::get('/distributions', [DistributionController::class, 'index'])->name('admin.distributions.index');
            Route::post('/distributions', [DistributionController::class, 'store'])->name('admin.distributions.store');
            Route::post('/distributions/{distribution}/cancel', [DistributionController::class, 'cancel'])->name('admin.distributions.cancel');
        });
    });

Route::middleware(['auth', 'role:school'])->group(function (): void {
    Route::get('/dashboard', SchoolDashboardController::class)->name('dashboard');
    Route::get('/school/learning-resources', [SchoolDashboardController::class, 'learningResources'])->name('school.resources.index');
    Route::put('/school/resources', [SchoolController::class, 'storeLearningResources'])->name('school.resources.store');
    Route::get('/school/digital-learning-materials', [SchoolDigitalLearningMaterialController::class, 'index'])->name('school.digital-learning-materials.index');
    Route::get('/school/enrollment', [SchoolEnrollmentController::class, 'index'])->name('school.enrollment.index');
    Route::put('/school/enrollment', [SchoolEnrollmentController::class, 'store'])->name('school.enrollment.store');
    Route::get('/school/inventory', [SchoolInventoryController::class, 'index'])->name('school.inventory.index');
    Route::post('/school/inventory/{learningResource}/movements', [SchoolInventoryController::class, 'storeMovement'])->name('school.inventory.movements.store');
    Route::get('/school/distributions', [SchoolDistributionController::class, 'index'])->name('school.distributions.index');
    Route::post('/school/distributions/{distribution}/receive', [SchoolDistributionController::class, 'receive'])->name('school.distributions.receive');
    Route::get('/school/ict-equipment', [SchoolIctEquipmentController::class, 'index'])->name('school.ict-equipment.index');
    Route::post('/school/ict-equipment', [SchoolIctEquipmentController::class, 'store'])->name('school.ict-equipment.store');
    Route::put('/school/ict-equipment/{ictEquipment}', [SchoolIctEquipmentController::class, 'update'])->name('school.ict-equipment.update');
    Route::delete('/school/ict-equipment/{ictEquipment}', [SchoolIctEquipmentController::class, 'destroy'])->name('school.ict-equipment.destroy');
    Route::get('/school/other-equipment', [SchoolOtherEquipmentController::class, 'index'])->name('school.other-equipment.index');
    Route::post('/school/other-equipment', [SchoolOtherEquipmentController::class, 'store'])->name('school.other-equipment.store');
    Route::put('/school/other-equipment/{otherEquipment}', [SchoolOtherEquipmentController::class, 'update'])->name('school.other-equipment.update');
    Route::delete('/school/other-equipment/{otherEquipment}', [SchoolOtherEquipmentController::class, 'destroy'])->name('school.other-equipment.destroy');
    Route::get('/school/sme', [SchoolSmeController::class, 'index'])->name('school.sme.index');
    Route::post('/school/sme', [SchoolSmeController::class, 'store'])->name('school.sme.store');
    Route::put('/school/sme/{sme}', [SchoolSmeController::class, 'update'])->name('school.sme.update');
    Route::delete('/school/sme/{sme}', [SchoolSmeController::class, 'destroy'])->name('school.sme.destroy');
});

require __DIR__.'/settings.php';
