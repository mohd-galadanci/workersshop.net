
<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\RequestController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\HealthController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

// Public routes
Route::get('/health', [HealthController::class, 'check']);
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);
Route::post('/face-auth', [AuthController::class, 'faceAuth']);

// Protected routes
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    
    // Food request routes
    Route::apiResource('requests', RequestController::class);
    Route::post('/requests/{id}/approve', [RequestController::class, 'approve']);
    
    // User profile
    Route::get('/user', function (Request $request) {
        return $request->user();
    });
    
    // Dashboard routes
    Route::get('/dashboard/stats', [DashboardController::class, 'stats']);
    Route::get('/dashboard/recent', [DashboardController::class, 'recentActivity']);
    
    // Chatbot routes
    Route::post('/chat', [App\Http\Controllers\ChatbotController::class, 'chat']);
    Route::get('/chat/history', [App\Http\Controllers\ChatbotController::class, 'getHistory']);
    
    // Report routes (admin only)
    Route::middleware('admin')->group(function () {
        Route::get('/reports/generate', [ReportController::class, 'generateReport']);
        Route::get('/reports/export', [ReportController::class, 'exportReport']);
    });
});
