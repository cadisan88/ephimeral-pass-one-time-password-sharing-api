<?php

use Illuminate\Support\Facades\Route;

// API health check route
Route::get('/up', function () {
    return response()->json(['status' => 'ok']);
});
