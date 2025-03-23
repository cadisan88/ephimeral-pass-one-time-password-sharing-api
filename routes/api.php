<?php

use App\Http\Controllers\SecretsController;
use Illuminate\Support\Facades\Route;

// API health check route
Route::middleware(['throttle:api'])->get('/up', function () {
    return response()->json(['status' => 'ok']);
});

// Create a new secret
Route::middleware(['throttle:api'])->post('/secrets', [SecretsController::class, 'createSecret']);

// Get a secret
Route::middleware(['throttle:api'])->get('/secrets/{id}', [SecretsController::class, 'getSecret']);
