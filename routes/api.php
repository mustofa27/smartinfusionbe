<?php

use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\Admin\AlertRuleController;
use App\Http\Controllers\Api\V1\Admin\BedController;
use App\Http\Controllers\Api\V1\Admin\DeviceController;
use App\Http\Controllers\Api\V1\Admin\PatientController;
use App\Http\Controllers\Api\V1\Admin\RoomController;
use App\Http\Controllers\Api\V1\Admin\WardController;
use App\Http\Controllers\Api\V1\DeviceAssignmentController;
use App\Http\Controllers\Api\V1\NurseMonitoringController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function (): void {
    Route::get('/health', function (): array {
        return ['status' => 'ok'];
    });

    Route::post('/auth/login', [AuthController::class, 'login']);

    Route::middleware('auth:sanctum')->group(function (): void {
        Route::post('/auth/logout', [AuthController::class, 'logout']);

        Route::prefix('nurse')->group(function (): void {
            Route::post('/monitor/by-device-code', [NurseMonitoringController::class, 'monitorByDeviceCode']);
            Route::post('/infusion-sessions/start', [NurseMonitoringController::class, 'startInfusionSession']);
            Route::get('/infusion-sessions/active', [NurseMonitoringController::class, 'activeSessions']);
            Route::post('/infusion-sessions/{sessionId}/pause', [NurseMonitoringController::class, 'pauseInfusionSession']);
            Route::post('/infusion-sessions/{sessionId}/complete', [NurseMonitoringController::class, 'completeInfusionSession']);
            Route::post('/infusion-sessions/{sessionId}/interrupt', [NurseMonitoringController::class, 'interruptInfusionSession']);
            Route::get('/alerts', [NurseMonitoringController::class, 'alerts']);
            Route::post('/alerts/{alertId}/acknowledge', [NurseMonitoringController::class, 'acknowledgeAlert']);
            Route::post('/fcm-tokens', [NurseMonitoringController::class, 'registerFcmToken']);
            Route::get('/patients', [NurseMonitoringController::class, 'patients']);
            Route::get('/beds', [NurseMonitoringController::class, 'beds']);
        });

        Route::middleware('admin-or-nurse')->group(function (): void {
            Route::get('/device-assignments', [DeviceAssignmentController::class, 'index']);
            Route::post('/device-assignments', [DeviceAssignmentController::class, 'store']);
            Route::post('/device-assignments/{assignment}/unmount', [DeviceAssignmentController::class, 'unmount']);
        });

        Route::middleware('admin')->prefix('admin')->group(function (): void {
            Route::apiResource('wards', WardController::class);
            Route::apiResource('rooms', RoomController::class);
            Route::apiResource('beds', BedController::class);
            Route::apiResource('patients', PatientController::class);
            Route::apiResource('alert-rules', AlertRuleController::class);

            Route::middleware('super-admin')->group(function (): void {
                Route::apiResource('devices', DeviceController::class);
            });
        });
    });
});