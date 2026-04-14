<?php

use Illuminate\Support\Facades\Route;
use Modules\Applications\Http\Controllers\ApplicationsController;
use Modules\Applications\Http\Middleware\CheckApplicationOwnership;

Route::get('/test4', function (){
    return response()->json(['message' => 'API is working']);
});
