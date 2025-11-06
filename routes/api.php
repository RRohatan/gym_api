<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\GimnasioController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DailyCashboxController;
use App\Http\Controllers\GastoController;
use Illuminate\Http\Request;
use App\Http\Controllers\MemberController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\SupplementProductController;
use App\Http\Controllers\SupplementSaleController;
use App\Http\Controllers\MembershipController;
use App\Http\Controllers\MembershipPlanController;
use App\Http\Controllers\MembershipTypeController;
use App\Http\Controllers\PaymentMethodController;
use App\Http\Controllers\AccesController;
use App\Http\Controllers\PublicRegistrationController; // <-- 1. IMPORTAR EL NUEVO CONTROLADOR

Route::get('/test', function () {
    return response()->json(['ok' => true]);
});


Route::post('/register', [AuthController::class, 'register']);

Route::post('/login', [AuthController::class, 'login']);

// --- 2. AÑADIR RUTAS PÚBLICAS (QR) ---
// Estas rutas no requieren autenticación (van fuera del auth group)
Route::get('/public/plans/{gimnasio_id}', [PublicRegistrationController::class, 'getPlans']);
Route::post('/public/register/{gimnasio_id}', [PublicRegistrationController::class, 'store']);
// --- FIN RUTAS PÚBLICAS ---


Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::middleware('auth:sanctum')->group(function () {
    Route::apiResource('/payment_methods',PaymentMethodController::class);
});

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/gimnasios', [GimnasioController::class, 'show']);
});

Route::middleware('auth:sanctum')->post('/logout', [AuthController::class, 'logout']);

Route::middleware('auth:sanctum')->group(function () {
    Route::apiResource('/members', MemberController::class);
});

Route::middleware('auth:sanctum')->group(function () {
    // --- 3. AÑADIR RUTA DE HISTORIAL (DASHBOARD) ---
    Route::get('/payments/history', [PaymentController::class, 'getHistory']);
    // --- FIN RUTA ---
    Route::apiResource('/payments', PaymentController::class);
});

Route::middleware('auth:sanctum')->group(function () {
    Route::apiResource('/supplementSale', SupplementSaleController::class);
});

Route::middleware('auth:sanctum')->group(function () {
    Route::apiResource('/supplementProduct', SupplementProductController::class);
});

Route::middleware('auth:sanctum')->group(function () {
    // --- 4. AÑADIR RUTA DE ESTADÍSTICAS (DASHBOARD) ---
    Route::get('/memberships/stats', [MembershipController::class, 'getStats']);
    // --- FIN RUTA ---
    Route::apiResource('/memberships', MembershipController::class);
});

Route::middleware('auth:sanctum')->group(function(){
    Route::apiResource('/membershipPlan', MembershipPlanController::class);
});

Route::middleware('auth:sanctum')->group(function(){
    Route::apiResource('/membershipType', MembershipTypeController::class);
});

Route::middleware('auth:Sanctum')->group(function(){
    Route::apiResource('/gasto', GastoController::class);
});

Route::middleware('auth:sanctum')->group(function(){
    Route::apiResource('/dailyCashbox', DailyCashboxController::class);
});


Route::get('/paymentsToday', [PaymentController::class, 'totalToday']);


Route::get('/memberships/by-member/{memberId}', [MembershipController::class, 'getByMemberId']);


Route::post('/access/identification', [AccesController::class, 'accessByIdentification']);
Route::post('/access/fingerprint', [AccesController::class, 'accessByFingerprint']);
