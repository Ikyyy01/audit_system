<?php

use App\Http\Controllers\AuditFormController;
use App\Http\Controllers\AuditFormResponseController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ClientController;
use App\Http\Controllers\EngagementController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function (): void {
    Route::post('login', [AuthController::class, 'login']);

    Route::middleware('auth:sanctum')->group(function (): void {
        Route::get('me', [AuthController::class, 'me']);
        Route::post('logout', [AuthController::class, 'logout']);

        Route::apiResource('clients', ClientController::class);
        Route::apiResource('engagements', EngagementController::class);
        Route::apiResource('audit-forms', AuditFormController::class);
        Route::apiResource('audit-form-responses', AuditFormResponseController::class);

        Route::post('audit-form-responses/{auditFormResponse}/answers', [AuditFormResponseController::class, 'saveAnswers']);
        Route::post('audit-form-responses/{auditFormResponse}/worksheet-rows', [AuditFormResponseController::class, 'saveWorksheetRows']);
        Route::post('audit-form-responses/{auditFormResponse}/import-excel', [AuditFormResponseController::class, 'importExcel']);
        Route::get('audit-form-responses/{auditFormResponse}/export', [AuditFormResponseController::class, 'export']);
        Route::post('audit-form-responses/{auditFormResponse}/partner-notes', [AuditFormResponseController::class, 'savePartnerNotes']);

        Route::middleware('role:Junior,Senior,Manager')
            ->post('audit-form-responses/{auditFormResponse}/submit', [AuditFormResponseController::class, 'submit']);

        Route::middleware('role:Manager')
            ->post('audit-form-responses/{auditFormResponse}/review', [AuditFormResponseController::class, 'review']);

        Route::middleware('role:Partner')
            ->post('audit-form-responses/{auditFormResponse}/approve', [AuditFormResponseController::class, 'approve']);

        Route::middleware('role:Partner')
            ->post('audit-form-responses/{auditFormResponse}/signature', [AuditFormResponseController::class, 'uploadSignature']);
    });
});
