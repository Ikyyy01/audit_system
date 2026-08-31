<?php

use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function (): void {
    Route::post('login', [\App\Http\Controllers\AuthController::class, 'login']);

    Route::middleware('auth:sanctum')->group(function (): void {
        Route::get('me', [\App\Http\Controllers\AuthController::class, 'me']);
        Route::post('logout', [\App\Http\Controllers\AuthController::class, 'logout']);

        Route::apiResource('clients', \App\Http\Controllers\ClientController::class);
        Route::apiResource('engagements', \App\Http\Controllers\EngagementController::class);
        Route::apiResource('audit-forms', \App\Http\Controllers\AuditFormController::class);
        Route::apiResource('audit-form-responses', \App\Http\Controllers\AuditFormResponseController::class);

        Route::post('audit-form-responses/{auditFormResponse}/answers', [\App\Http\Controllers\AuditFormResponseController::class, 'saveAnswers']);

        Route::middleware('role:Junior,Senior,Manager')
            ->post('audit-form-responses/{auditFormResponse}/submit', [\App\Http\Controllers\AuditFormResponseController::class, 'submit']);

        Route::middleware('role:Manager')
            ->post('audit-form-responses/{auditFormResponse}/review', [\App\Http\Controllers\AuditFormResponseController::class, 'review']);

        Route::middleware('role:Partner')
            ->post('audit-form-responses/{auditFormResponse}/approve', [\App\Http\Controllers\AuditFormResponseController::class, 'approve']);

        Route::get('audit-form-responses/{auditFormResponse}/export', [\App\Http\Controllers\AuditFormExportController::class, 'export']);
    });
});
