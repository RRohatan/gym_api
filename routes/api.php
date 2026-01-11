<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;

// Controladores
use App\Http\Controllers\GimnasioController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DailyCashboxController;
use App\Http\Controllers\GastoController;
use App\Http\Controllers\MemberController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\SupplementProductController;
use App\Http\Controllers\SupplementSaleController;
use App\Http\Controllers\MembershipController;
use App\Http\Controllers\MembershipPlanController;
use App\Http\Controllers\MembershipTypeController;
use App\Http\Controllers\PaymentMethodController;
use App\Http\Controllers\AccesController;
use App\Http\Controllers\PublicRegistrationController;

/*
|--------------------------------------------------------------------------
| RUTAS PÚBLICAS (No requieren Login)
|--------------------------------------------------------------------------
*/

Route::get('/test', function () {
    return response()->json(['ok' => true]);
});

// Autenticación
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

// Registro Público (QR)
Route::get('/public/plans/{gimnasio_id}', [PublicRegistrationController::class, 'getPlans']);
Route::post('/public/register/{gimnasio_id}', [PublicRegistrationController::class, 'store']);

// Acceso (Kiosco / Huella) - Si estos dispositivos no envían token, déjalos aquí.
// Si el control de acceso lo hace el admin desde el sistema, muévelos al grupo de abajo.
Route::post('/access/identification', [AccesController::class, 'accessByIdentification']);
Route::post('/access/fingerprint', [AccesController::class, 'accessByFingerprint']);

/*
|--------------------------------------------------------------------------
| RUTAS PROTEGIDAS (Requieren Token / Login)
|--------------------------------------------------------------------------
*/
Route::middleware('auth:sanctum')->group(function () {

    // Usuario y Logout
    Route::get('/user', function (Request $request) { return $request->user(); });
    Route::post('/logout', [AuthController::class, 'logout']);

    // Gimnasios
    Route::get('/gimnasios', [GimnasioController::class, 'show']);

    // ----------------------------------------------------
    // 🚨 CORRECCIÓN IMPORTANTE AQUÍ 🚨
    // 1. Primero las rutas específicas (stats)
    Route::get('/memberships/stats', [MembershipController::class, 'getStats']);
    Route::get('/memberships/by-member/{memberId}', [MembershipController::class, 'getByMemberId']);

    // 2. Luego el recurso general (apiResource)
    Route::apiResource('/memberships', MembershipController::class);
    // ----------------------------------------------------

    Route::apiResource('/members', MemberController::class);

    // Configuración de Membresías
    Route::apiResource('/membershipPlan', MembershipPlanController::class);
    Route::apiResource('/membershipType', MembershipTypeController::class);

    // ----------------------------------------------------
    // 🚨 CORRECCIÓN IMPORTANTE AQUÍ TAMBIÉN 🚨
    // 1. Primero el historial y total
    Route::get('/payments/history', [PaymentController::class, 'getHistory']);
    Route::get('/paymentsToday', [PaymentController::class, 'totalToday']);

    // 2. Luego el recurso general
    Route::apiResource('/payments', PaymentController::class);
    // ----------------------------------------------------

    Route::apiResource('/payment_methods', PaymentMethodController::class);

    // Tesorería
    Route::apiResource('/gastos', GastoController::class);
    Route::apiResource('/dailyCashbox', DailyCashboxController::class);

    // Productos y POS
    Route::apiResource('/supplementProduct', SupplementProductController::class);
    Route::post('/supplement-sales/bulk', [SupplementSaleController::class, 'storeBulk']);
    Route::apiResource('/supplementSale', SupplementSaleController::class);
});
