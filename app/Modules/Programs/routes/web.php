<?php

use Illuminate\Support\Facades\Route;
use Modules\Programs\Http\Controllers\ProgramsController;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::resource('programs', ProgramsController::class)->names('programs');
});
