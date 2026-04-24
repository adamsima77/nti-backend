<?php

use Illuminate\Support\Facades\Route;
use Modules\Content\Http\Controllers\CategoryController;
use Modules\Content\Http\Controllers\FrequentlyAskedQuestionController;
use Modules\Content\Http\Controllers\HeroBannerController;
use Modules\Content\Http\Controllers\LanguageController;
use Modules\Content\Http\Controllers\MetaTagController;
use Modules\Content\Http\Controllers\NewsController;
use Modules\Content\Http\Controllers\PartnerController;
use Modules\Content\Http\Controllers\PartnerReferenceController;

Route::get('/categories/lang/{lang}', [CategoryController::class, 'fetchByLang']);
Route::get('/hero-banners/lang/{lang}', [HeroBannerController::class, 'fetchByLang']);
Route::get('/news/lang/{lang}', [NewsController::class, 'fetchByLang']);
Route::get('/partners/lang/{lang}', [PartnerController::class, 'fetchByLang']);
Route::get('/partner-references/lang/{lang}', [PartnerReferenceController::class, 'fetchByLang']);
Route::get('/faq/lang/{lang}', [FrequentlyAskedQuestionController::class, 'fetchByLang']);
Route::get('/meta-tags/lang/{lang}', [MetaTagController::class, 'fetchByLang']);

Route::apiResource('categories', CategoryController::class)->only(['index', 'show']);
Route::apiResource('news', NewsController::class)->only(['index', 'show']);
Route::apiResource('hero-banners', HeroBannerController::class)->only(['index', 'show']);
Route::apiResource('partners', PartnerController::class)->only(['index', 'show']);
Route::apiResource('partner-references', PartnerReferenceController::class)->only(['index', 'show']);
Route::apiResource('faq', FrequentlyAskedQuestionController::class)->only(['index', 'show']);
Route::apiResource('meta-tags', MetaTagController::class)->only(['index', 'show']);
Route::middleware(['auth:sanctum'])->group(function () {

    Route::apiResource('languages', LanguageController::class);

    Route::apiResource('categories', CategoryController::class)
        ->except(['index', 'show']);

    Route::apiResource('hero-banners', HeroBannerController::class)
        ->except(['index', 'show']);

    Route::apiResource('news', NewsController::class)
        ->except(['index', 'show']);

    Route::apiResource('partners', PartnerController::class)
        ->except(['index', 'show']);

    Route::apiResource('partner-references', PartnerReferenceController::class)
        ->except(['index', 'show']);

    Route::apiResource('faq', FrequentlyAskedQuestionController::class)
        ->except(['index', 'show']);

    Route::apiResource('meta-tags', MetaTagController::class)
        ->except(['index', 'show']);
});
