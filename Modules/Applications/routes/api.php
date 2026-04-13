<?php

use Illuminate\Support\Facades\Route;

Route::get('/test2', function () {
    return response()->json([
        'message' => 'Applications module is working 🚀'
    ]);
});
