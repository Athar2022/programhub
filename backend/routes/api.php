<?php

use App\Http\Controllers\Api\ApplicationController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\OrganizationController;
use App\Http\Controllers\Api\ProgramController;
use App\Http\Controllers\Api\DocumentController;
use App\Http\Controllers\Api\PlanController;
use App\Http\Controllers\Api\SubscriptionController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

Route::get('/programs', [ProgramController::class, 'index']);
Route::get('/programs/{program}', [ProgramController::class, 'show']);

Route::get('/plans', [PlanController::class, 'index']);
Route::get('/plans/{plan}', [PlanController::class, 'show']);

Route::middleware('auth:sanctum')->group(function (): void {
    Route::post('/logout', [AuthController::class, 'logout']);

    Route::get('/user', function (Request $request) {
        return $request->user();
    });

    Route::get('/organizations', [OrganizationController::class, 'index']);
    Route::get('/organizations/{organization}', [OrganizationController::class, 'show']);

    Route::get('/applications', [ApplicationController::class, 'index']);
    Route::get('/applications/{application}', [ApplicationController::class, 'show']);
    Route::post('/programs/{program}/applications', [ApplicationController::class, 'store']);
    Route::patch('/applications/{application}', [ApplicationController::class, 'update']);
    Route::post('/applications/{application}/submit', [ApplicationController::class, 'submit']);
    Route::delete('/applications/{application}', [ApplicationController::class, 'destroy']);

    Route::middleware('role:organization,platform_admin')->group(function (): void {
        Route::post('/organizations', [OrganizationController::class, 'store']);
        Route::patch('/organizations/{organization}', [OrganizationController::class, 'update']);
        Route::delete('/organizations/{organization}', [OrganizationController::class, 'destroy']);

        Route::post('/organizations/{organization}/programs', [ProgramController::class, 'store']);
        Route::patch('/programs/{program}', [ProgramController::class, 'update']);
        Route::delete('/programs/{program}', [ProgramController::class, 'destroy']);

        Route::patch('/applications/{application}/review', [ApplicationController::class, 'review']);
    });

    Route::get('/applications/{application}/documents', [DocumentController::class, 'index']);
    Route::post('/applications/{application}/documents', [DocumentController::class, 'store']);
    Route::get('/documents/{document}', [DocumentController::class, 'show']);
    Route::delete('/documents/{document}', [DocumentController::class, 'destroy']);

    Route::get('/organizations/{organization}/subscriptions', [SubscriptionController::class, 'index']);
    Route::post('/organizations/{organization}/subscriptions', [SubscriptionController::class, 'store']);
    Route::get('/subscriptions/{subscription}', [SubscriptionController::class, 'show']);
    Route::patch('/subscriptions/{subscription}', [SubscriptionController::class, 'update']);

});

Route::middleware('role:platform_admin')->group(function (): void {
    Route::post('/plans', [PlanController::class, 'store']);
    Route::patch('/plans/{plan}', [PlanController::class, 'update']);
});
