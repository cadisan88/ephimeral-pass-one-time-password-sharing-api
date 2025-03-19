<?php

use Illuminate\Support\Facades\Route;

Route::get('/health-check', function () {
    return response()->json(['status' => 'ok']);
});
