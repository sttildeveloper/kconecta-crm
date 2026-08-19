<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CadastralController;
use App\Http\Controllers\Api\Internal\OrchestratorController;
use App\Http\Controllers\Api\PropertyApiController;
use App\Http\Controllers\Api\ProviderServiceApiController;
use App\Http\Controllers\ApiController;
use Illuminate\Support\Facades\Route;

Route::middleware('throttle:60,1')->group(function () {
    Route::get('/properties', [ApiController::class, 'searchProperties']);
    Route::get('/services', [ApiController::class, 'searchServices']);
    Route::get('/services/{id}', [ApiController::class, 'publicServiceDetail']);
    Route::get('/public/services/{id}', [ApiController::class, 'publicServiceDetail']);
    Route::get('/providers', [ApiController::class, 'publicProvidersDiscovery']);
    Route::get('/public/providers', [ApiController::class, 'publicProvidersDiscovery']);
    Route::get('/providers/{providerUserId}', [ApiController::class, 'publicProviderDetail']);
    Route::get('/public/providers/{providerUserId}', [ApiController::class, 'publicProviderDetail']);
    Route::get('/service-types', [ApiController::class, 'publicServiceTypes']);
    Route::get('/public/service-types', [ApiController::class, 'publicServiceTypes']);
    Route::get('/properties_for_map', [ApiController::class, 'dataPropertiesForMap']);
    Route::get('/services_for_map', [ApiController::class, 'dataServicesForMap']);
    Route::get('/delete_more_image', fn () => response()->json([
        'success' => false,
        'data' => null,
        'meta' => null,
        'message' => 'Endpoint legacy retirado. Usa DELETE /api/agent/property-images/{imageId}.',
        'errors' => null,
    ], 410));
    Route::post('/visitor/save', [ApiController::class, 'visitorRegister']);
    Route::post('/visitor/contacted', [ApiController::class, 'visitorContactedUpdate']);
    Route::post('/google/user/verify_token_google', [ApiController::class, 'verifyTokenGoogleFloat']);
    Route::post('/send/message/email_to_provider', [ApiController::class, 'sendEmailContactUser']);
    Route::get('/send/message/email_share', [ApiController::class, 'sendEmailShare']);
    Route::post('/property_stats/register', [ApiController::class, 'propertyStatsConfig']);
    Route::post('/service_stats/register_visit', [ApiController::class, 'serviceStatsRegisterVisit']);
    Route::post('/service_stats/register_contact_click', [ApiController::class, 'serviceStatsRegisterContactClick']);
    Route::get('/service-ratings/provider/{providerUserId}', [ApiController::class, 'providerServiceRatingSummary']);

    Route::get('/cadastral/estimate', [CadastralController::class, 'estimate']);
    Route::post('/cadastral/advanced-estimate', [CadastralController::class, 'advancedEstimate']);
    Route::get('/test-now', fn () => response()->json(['message' => 'API is working']));
});

// Mobile app auth + agent endpoints
Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:10,1');
Route::post('/mobile/register-provider', [\App\Http\Controllers\Api\RegisterApiController::class, 'registerProvider'])->middleware('throttle:10,1');
Route::post('/mobile/register-client', [\App\Http\Controllers\Api\RegisterApiController::class, 'registerClient'])->middleware('throttle:10,1');
Route::post('/forgot-password', [AuthController::class, 'forgotPassword'])->middleware('throttle:5,1');
Route::post('/reset-password', [AuthController::class, 'resetPassword'])->middleware('throttle:5,1');

Route::middleware(['orchestrator.key', 'throttle:120,1'])->prefix('orchestrate')->group(function () {
    Route::post('/plan', [OrchestratorController::class, 'plan']);
    Route::post('/run', [OrchestratorController::class, 'run']);
    Route::post('/merge', [OrchestratorController::class, 'merge']);
});

Route::middleware(['auth:sanctum', 'throttle:120,1'])->group(function () {
    Route::get('/me', [AuthController::class, 'me']);
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::delete('/me', [AuthController::class, 'deleteAccount'])->middleware('throttle:5,1');
    Route::post('/account/delete', [AuthController::class, 'deleteAccount'])->middleware('throttle:5,1');
    Route::get('/agent/property-types', [PropertyApiController::class, 'propertyTypes']);
    Route::get('/agent/property-form-catalogs', [PropertyApiController::class, 'propertyFormCatalogs']);
    Route::get('/agent/service-types', [ProviderServiceApiController::class, 'serviceTypes']);
    Route::get('/agent/provider-profile', [ProviderServiceApiController::class, 'profile']);
    Route::patch('/agent/provider-profile', [ProviderServiceApiController::class, 'updateProfile']);
    Route::get('/agent/services/profile', [ProviderServiceApiController::class, 'profile']);
    Route::patch('/agent/services/profile', [ProviderServiceApiController::class, 'updateProfile']);
    Route::get('/agent/services/work-codes', [ProviderServiceApiController::class, 'workCodes']);
    Route::post('/agent/services/work-codes', [ProviderServiceApiController::class, 'createWorkCode']);
    Route::delete('/agent/property-images/{imageId}', [PropertyApiController::class, 'destroyMoreImage']);
    Route::apiResource('/agent/properties', PropertyApiController::class);
    Route::apiResource('/agent/services', ProviderServiceApiController::class);
    Route::post('/service-ratings/work-codes', [ApiController::class, 'createServiceWorkCode']);
    Route::post('/service-ratings', [ApiController::class, 'storeServiceRating']);
    Route::post('/service-ratings/by-code', [ApiController::class, 'storeServiceRatingByCode']);
    Route::get('/service-ratings/my-dashboard', [ApiController::class, 'myServiceRatingsDashboard']);

    // Módulo de Tickets / Soporte API
    Route::get('/agent/tickets', [\App\Http\Controllers\Api\TicketApiController::class, 'index']);
    Route::post('/agent/tickets', [\App\Http\Controllers\Api\TicketApiController::class, 'store']);
    Route::get('/agent/tickets/{id}', [\App\Http\Controllers\Api\TicketApiController::class, 'show']);
    Route::post('/agent/tickets/{id}/reply', [\App\Http\Controllers\Api\TicketApiController::class, 'reply']);
    Route::post('/agent/tickets/{id}/close', [\App\Http\Controllers\Api\TicketApiController::class, 'close']);
});
