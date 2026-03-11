<?php

use Illuminate\Support\Facades\Route;
use Modules\Logs\Http\Controllers\AuditLogsController;
use Modules\Logs\Http\Controllers\LogsController;
use Modules\Logs\Http\Controllers\SystemLogsController;

Route::group([], function () {
    Route::name('logs.')->prefix('logs')->group(function () {
        Route::get('dokumen', [AuditLogsController::class, 'indexAdminKredit'])->name('dokumen.index');

        Route::name('audit.')->prefix('audit')->group(function () {
            Route::get('datatables', [AuditLogsController::class, 'datatable'])->name('datatables');
            Route::get('datatablesAdminKredit', [AuditLogsController::class, 'datatableAdminKredit'])->name('datatablesAdminKredit');
        });
        Route::resource('audit', AuditLogsController::class)->only(['index', 'delete']);

        Route::get('system', [SystemLogsController::class, 'index'])->name('system.index');
        Route::get('datatables', [SystemLogsController::class, 'datatable'])->name('system.datatables');
    });

    Route::resource('logs', LogsController::class)->except('index');
});
