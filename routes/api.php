<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\GroupController;
use App\Http\Controllers\Api\InvitationController;
use App\Http\Controllers\Api\ExpenseController;
use App\Http\Controllers\Api\PaymentController;
use App\Http\Controllers\Api\NotificationController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\ReportController;

/**
 * RUTAS PÚBLICAS (sin auth)
 * - OJO: la verificación de invitación es pública y con prefijo "token/"
 *   para no chocar con apiResource('invitations')
 */
// Route::get('invitations/token/{token}', [InvitationController::class, 'verifyToken']);

Route::get('invitations/token/{token}', [InvitationController::class, 'verifyToken'])
    ->name('invitations.verify');

Route::prefix('auth')->group(function () {
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);
});

/**
 * RUTAS PROTEGIDAS (con auth)
 */
Route::middleware('auth:sanctum')->group(function () {

    // Auth
    Route::post('/auth/logout', [AuthController::class, 'logout']);

    // Usuarios
    Route::prefix('users')->group(function () {
        Route::get('/me', [UserController::class, 'me']);
        Route::put('/me', [UserController::class, 'update']);
        Route::put('/me/password', [UserController::class, 'updatePassword']);
        Route::delete('/me', [UserController::class, 'destroy']);
    });

    // Grupos
    Route::apiResource('groups', GroupController::class);
    Route::post('groups/{group}/members', [GroupController::class, 'addMember']);
    Route::put('groups/{group}/members/{user}', [GroupController::class, 'updateMemberRole']);
    Route::delete('groups/{group}/members/{user}', [GroupController::class, 'removeMember']);

    // Invitaciones (REST) + aceptar (SIN la vieja GET invitations/{token})
    Route::apiResource('invitations', InvitationController::class)->only(['index','store','show','destroy']);
    Route::post('invitations/accept', [InvitationController::class, 'accept']);

    // Gastos
    Route::apiResource('expenses', ExpenseController::class);
    Route::post('expenses/{expense}/approve', [ExpenseController::class, 'approve']);

    // Pagos
    Route::get('payments/due', [PaymentController::class, 'due']);
    Route::apiResource('payments', PaymentController::class)->only(['index','store','show','update']);
    Route::post('payments/{payment}/approve', [PaymentController::class, 'approve']);
    Route::post('payments/{payment}/reject', [PaymentController::class, 'reject']);


    // Notificaciones
    Route::post('notifications/register-device', [NotificationController::class, 'registerDevice']);

    // Dashboard & Reportes
    Route::get('dashboard/summary', [DashboardController::class, 'summary']);
    Route::get('reports', [ReportController::class, 'index']);
});
