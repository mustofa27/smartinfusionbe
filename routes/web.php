<?php

use App\Http\Controllers\Web\Admin\AlertRuleCrudController;
use App\Http\Controllers\Web\Admin\BedCrudController;
use App\Http\Controllers\Web\Admin\AuthController;
use App\Http\Controllers\Web\Admin\DashboardController;
use App\Http\Controllers\Web\Admin\DeviceCrudController;
use App\Http\Controllers\Web\Admin\MonitoringController;
use App\Http\Controllers\Web\Admin\TestFcmController;
use App\Http\Controllers\Web\Admin\OrganizationCrudController;
use App\Http\Controllers\Web\Admin\PatientCrudController;
use App\Http\Controllers\Web\Admin\RoomCrudController;
use App\Http\Controllers\Web\Admin\UserCrudController;
use App\Http\Controllers\Web\Admin\WardCrudController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('admin.login');
});

Route::prefix('admin')->group(function (): void {
    Route::middleware('guest')->group(function (): void {
        Route::get('/login', [AuthController::class, 'showLogin'])->name('admin.login');
        Route::post('/login', [AuthController::class, 'login'])->name('admin.login.submit');
    });

    Route::middleware(['auth', 'admin'])->group(function (): void {
        Route::post('/logout', [AuthController::class, 'logout'])->name('admin.logout');
        Route::get('/dashboard', DashboardController::class)->name('admin.dashboard');

        Route::get('/patients', [PatientCrudController::class, 'index'])->name('admin.patients.index');
        Route::post('/patients', [PatientCrudController::class, 'store'])->name('admin.patients.store');
        Route::put('/patients/{patient}', [PatientCrudController::class, 'update'])->name('admin.patients.update');
        Route::delete('/patients/{patient}', [PatientCrudController::class, 'destroy'])->name('admin.patients.destroy');

        Route::middleware('super-admin')->group(function (): void {
            Route::get('/devices', [DeviceCrudController::class, 'index'])->name('admin.devices.index');
            Route::post('/devices', [DeviceCrudController::class, 'store'])->name('admin.devices.store');
            Route::put('/devices/{device}', [DeviceCrudController::class, 'update'])->name('admin.devices.update');
            Route::delete('/devices/{device}', [DeviceCrudController::class, 'destroy'])->name('admin.devices.destroy');
            Route::get('/devices/qr-codes/print', [DeviceCrudController::class, 'printAllQrCodes'])->name('admin.devices.qr-codes.print');
            Route::get('/devices/{device}/qr-code', [DeviceCrudController::class, 'showQrCode'])->name('admin.devices.qr-code');

            Route::get('/test-fcm', [TestFcmController::class, 'index'])->name('admin.test-fcm.index');
            Route::post('/test-fcm', [TestFcmController::class, 'send'])->name('admin.test-fcm.send');

            Route::get('/organizations', [OrganizationCrudController::class, 'index'])->name('admin.organizations.index');
            Route::post('/organizations', [OrganizationCrudController::class, 'store'])->name('admin.organizations.store');
            Route::put('/organizations/{organization}', [OrganizationCrudController::class, 'update'])->name('admin.organizations.update');
            Route::delete('/organizations/{organization}', [OrganizationCrudController::class, 'destroy'])->name('admin.organizations.destroy');

            Route::get('/users', [UserCrudController::class, 'index'])->name('admin.users.index');
            Route::post('/users', [UserCrudController::class, 'store'])->name('admin.users.store');
            Route::put('/users/{user}', [UserCrudController::class, 'update'])->name('admin.users.update');
            Route::delete('/users/{user}', [UserCrudController::class, 'destroy'])->name('admin.users.destroy');
        });

        Route::get('/alert-rules', [AlertRuleCrudController::class, 'index'])->name('admin.alert-rules.index');
        Route::post('/alert-rules', [AlertRuleCrudController::class, 'store'])->name('admin.alert-rules.store');
        Route::put('/alert-rules/{alertRule}', [AlertRuleCrudController::class, 'update'])->name('admin.alert-rules.update');
        Route::delete('/alert-rules/{alertRule}', [AlertRuleCrudController::class, 'destroy'])->name('admin.alert-rules.destroy');

        Route::get('/wards', [WardCrudController::class, 'index'])->name('admin.wards.index');
        Route::post('/wards', [WardCrudController::class, 'store'])->name('admin.wards.store');
        Route::put('/wards/{ward}', [WardCrudController::class, 'update'])->name('admin.wards.update');
        Route::delete('/wards/{ward}', [WardCrudController::class, 'destroy'])->name('admin.wards.destroy');

        Route::get('/rooms', [RoomCrudController::class, 'index'])->name('admin.rooms.index');
        Route::post('/rooms', [RoomCrudController::class, 'store'])->name('admin.rooms.store');
        Route::put('/rooms/{room}', [RoomCrudController::class, 'update'])->name('admin.rooms.update');
        Route::delete('/rooms/{room}', [RoomCrudController::class, 'destroy'])->name('admin.rooms.destroy');

        Route::get('/beds', [BedCrudController::class, 'index'])->name('admin.beds.index');
        Route::post('/beds', [BedCrudController::class, 'store'])->name('admin.beds.store');
        Route::put('/beds/{bed}', [BedCrudController::class, 'update'])->name('admin.beds.update');
        Route::delete('/beds/{bed}', [BedCrudController::class, 'destroy'])->name('admin.beds.destroy');

        Route::get('/monitoring', [MonitoringController::class, 'index'])->name('admin.monitoring.index');
        Route::get('/monitoring/{session}', [MonitoringController::class, 'show'])->name('admin.monitoring.show');
        Route::post('/monitoring/alerts/{alert}/acknowledge', [MonitoringController::class, 'acknowledge'])->name('admin.monitoring.alerts.acknowledge');
        Route::post('/monitoring/alerts/{alert}/resolve', [MonitoringController::class, 'resolve'])->name('admin.monitoring.alerts.resolve');

    });
});
