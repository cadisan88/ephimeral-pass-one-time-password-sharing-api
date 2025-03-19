<?php

use Illuminate\Support\Facades\Route;

// API health check route
Route::middleware(['throttle:api'])->get('/up', function () {
    return response()->json(['status' => 'ok']);
});
