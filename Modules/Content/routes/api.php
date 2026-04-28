<?php

use Illuminate\Support\Facades\Route;
use Modules\Content\Http\Controllers\CategoryController;
use Modules\Content\Http\Controllers\ContactSubmissionController;
use Modules\Content\Http\Controllers\FrequentlyAskedQuestionController;
use Modules\Content\Http\Controllers\HeroBannerController;
use Modules\Content\Http\Controllers\LanguageController;
use Modules\Content\Http\Controllers\MetaTagController;
use Modules\Content\Http\Controllers\NewsController;
use Modules\Content\Http\Controllers\PartnerController;
use Modules\Content\Http\Controllers\PartnerReferenceController;
use Modules\Content\Http\Controllers\SiteMemberController;

Route::get('partners/fetch-images', [PartnerController::class, 'fetchImages']);
Route::get('/categories/lang/{lang}', [CategoryController::class, 'fetchByLang']);
Route::get('/hero-banners/lang/{lang}', [HeroBannerController::class, 'fetchByLang']);
Route::get('/news/lang/{lang}', [NewsController::class, 'fetchByLang']);
Route::get('/partners/lang/{lang}', [PartnerController::class, 'fetchByLang']);
Route::get('/partner-references/lang/{lang}', [PartnerReferenceController::class, 'fetchByLang']);
Route::get('/faq/lang/{lang}', [FrequentlyAskedQuestionController::class, 'fetchByLang']);
Route::get('/meta-tags/lang/{lang}', [MetaTagController::class, 'fetchByLang']);
Route::get('/pages/{page}/hero-banner/{lang}', [HeroBannerController::class, 'getByPageAndLang']);
Route::get('/pages/{page}/faq/{lang}', [FrequentlyAskedQuestionController::class, 'getByPageAndLang']);
Route::get('/site-members/lang/{lang}', [SiteMemberController::class, 'fetchByLang']);
Route::get('/news/slug/{slug}/lang/{lang}', [NewsController::class, 'fetchBySlug']);

Route::apiResource('categories', CategoryController::class)->only(['index', 'show']);
Route::apiResource('news', NewsController::class)->only(['index', 'show']);
Route::apiResource('hero-banners', HeroBannerController::class)->only(['index', 'show']);
Route::apiResource('partners', PartnerController::class)->only(['index', 'show']);
Route::apiResource('partner-references', PartnerReferenceController::class)->only(['index', 'show']);
Route::apiResource('faq', FrequentlyAskedQuestionController::class)->only(['index', 'show']);
Route::apiResource('meta-tags', MetaTagController::class)->only(['index', 'show']);
Route::apiResource('site-members', SiteMemberController::class)->only(['index', 'show']);

Route::apiResource('contact', ContactSubmissionController::class)
    ->only(['store'])
    ->middleware(['throttle:contact']);


Route::middleware(['auth:sanctum'])->group(function () {
    Route::apiResource('languages', LanguageController::class);

    Route::apiResource('contact', ContactSubmissionController::class)
        ->only(['index', 'show', 'update', 'destroy']);

    Route::apiResource('categories', CategoryController::class)->only(['store', 'update', 'destroy']);
    Route::apiResource('hero-banners', HeroBannerController::class)->only(['store', 'update', 'destroy']);
    Route::apiResource('news', NewsController::class)->only(['store', 'update', 'destroy']);
    Route::apiResource('partners', PartnerController::class)->only(['store', 'update', 'destroy']);
    Route::apiResource('partner-references', PartnerReferenceController::class)->only(['store', 'update', 'destroy']);
    Route::apiResource('faq', FrequentlyAskedQuestionController::class)->only(['store', 'update', 'destroy']);
    Route::apiResource('meta-tags', MetaTagController::class)->only(['store', 'update', 'destroy']);
    Route::apiResource('site-members', SiteMemberController::class)->only(['store', 'update', 'destroy']);
});
