<?php

use Illuminate\Support\Facades\Route;
use Modules\IdentityAccess\Http\Controllers\IdentityAccessController;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::resource('identityaccesses', IdentityAccessController::class)->names('identityaccess');
});
