<<<<<<< HEAD
<?php

use Illuminate\Support\Facades\Route;
    use Modules\Logs\Http\Controllers\AuditLogsController;
    use Modules\Logs\Http\Controllers\LogsController;
    use Modules\Logs\Http\Controllers\SystemLogsController;

    /*
    |--------------------------------------------------------------------------
    | Web Routes
    |--------------------------------------------------------------------------
    |
    | Here is where you can register web routes for your application. These
    | routes are loaded by the RouteServiceProvider within a group which
    | contains the "web" middleware group. Now create something great!
    |
    */

Route::group([], function () {
    Route::name('logs.')->prefix('logs')->group(function () {
        Route::name('audit.')->prefix('audit')->group(function () {
            Route::get('datatables', [AuditLogsController::class, 'datatable'])->name('datatables');
        });
        Route::resource('audit', AuditLogsController::class)->only(['index', 'delete']);

        Route::get('system', [SystemLogsController::class, 'index'])->name('system.index');
        Route::get('datatables', [SystemLogsController::class, 'datatable'])->name('system.datatables');
    });

    Route::resource('logs', LogsController::class)->except('index');
});
=======
<?php

use Illuminate\Support\Facades\Route;
use Modules\Logs\Http\Controllers\AuditLogsController;
use Modules\Logs\Http\Controllers\LogsController;
use Modules\Logs\Http\Controllers\SystemLogsController;

Route::middleware(['auth'])->group(function () {
    Route::name('logs.')->prefix('logs')->group(function () {

        // ---------------------------------------------------------------------
        // Log Dokumen — logs.dokumen
        // Roles: administrator, adminkredit, admindokumen, auditor
        // ---------------------------------------------------------------------
        Route::middleware(['role:administrator|adminkredit|admindokumen|auditor'])
            ->group(function () {
                Route::get('dokumen', [AuditLogsController::class, 'indexAdminKredit'])->name('dokumen.index');

                Route::name('audit.')->prefix('audit')->group(function () {
                    Route::get('datatablesAdminKredit', [AuditLogsController::class, 'datatableAdminKredit'])
                        ->name('datatablesAdminKredit');
                });
            });

        // ---------------------------------------------------------------------
        // Log Audit — logs.audit
        // Roles: administrator
        // ---------------------------------------------------------------------
        Route::middleware(['role:administrator'])->group(function () {
            Route::name('audit.')->prefix('audit')->group(function () {
                Route::get('datatables', [AuditLogsController::class, 'datatable'])->name('datatables');
            });
            Route::resource('audit', AuditLogsController::class)->only(['index', 'delete']);
        });

        // ---------------------------------------------------------------------
        // Log Sistem — logs.system
        // Roles: administrator
        // ---------------------------------------------------------------------
        Route::middleware(['role:administrator'])->group(function () {
            Route::get('system', [SystemLogsController::class, 'index'])->name('system.index');
            Route::get('datatables', [SystemLogsController::class, 'datatable'])->name('system.datatables');
        });
    });

    // -------------------------------------------------------------------------
    // LogsController resource (except index) — administrator only
    // -------------------------------------------------------------------------
    Route::middleware(['role:administrator'])->group(function () {
        Route::resource('logs', LogsController::class)->except('index');
    });
});
>>>>>>> 1742328187f1689f93ade2ab4053da783b9d9363
