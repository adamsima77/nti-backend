<?php

use Illuminate\Support\Facades\Route;
use Modules\Content\Http\Controllers\CategoryController;
use Modules\Content\Http\Controllers\CmsStatsController;
use Modules\Content\Http\Controllers\CmsStatusController;
use Modules\Content\Http\Controllers\ContactSubmissionController;
use Modules\Content\Http\Controllers\FrequentlyAskedQuestionController;
use Modules\Content\Http\Controllers\HeroBannerController;
use Modules\Content\Http\Controllers\LanguageController;
use Modules\Content\Http\Controllers\MetaTagController;
use Modules\Content\Http\Controllers\NewsController;
use Modules\Content\Http\Controllers\PageController;
use Modules\Content\Http\Controllers\PartnerController;
use Modules\Content\Http\Controllers\PartnerReferenceController;
use Modules\Content\Http\Controllers\SiteMemberController;
use Modules\Content\Models\CmsStatus;

Route::get('/partner-references/cms/{id}', [PartnerReferenceController::class, 'showCms']);
Route::get('/public/partner-references/lang/{lang}', [PartnerReferenceController::class, 'fetchByLangPublic']);
Route::get('/meta-tags/cms/{id}', [MetaTagController::class, 'showCms']);
Route::get('/site-members/cms/{id}', [SiteMemberController::class, 'showCms']);
Route::get('/public/site-members/lang/{lang}', [SiteMemberController::class, 'fetchByLangPublic']);
Route::get('/hero-banners/cms/{id}', [HeroBannerController::class, 'showCms']);
Route::get('/public/pages/{page}/hero-banner/{lang}', [HeroBannerController::class, 'fetchByPageLangPublic']);
Route::get('/public/pages/{page}/faq/{lang}', [FrequentlyAskedQuestionController::class, 'fetchByPageLangPublic']);
Route::apiResource('pages', PageController::class)->only(['index']);
Route::get('/partners/cms/{id}', [PartnerController::class, 'showCms']);
Route::get('/faq/cms/{id}', [FrequentlyAskedQuestionController::class, 'showCms']);
Route::get('/public/partners/lang/{lang}', [PartnerController::class, 'fetchByLangPublic']);
Route::get('/public/news/lang/{lang}', [NewsController::class, 'fetchByLangPublic']);
Route::get('/pages/{page}/meta-tags/{lang}', [MetaTagController::class, 'getByPageAndLang']);
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
Route::get('/news/cms/{id}', [NewsController::class, 'showCms']);
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

Route::get('/cms-statuses', fn() => response()->json(
    CmsStatus::select('id', 'name')->get()
));
Route::get('/content-overview', [CmsStatsController::class, 'contentOverview']);
Route::middleware(['auth:sanctum'])->group(function () {
    Route::get('/publicated-articles', [CmsStatsController::class, 'publicatedArticles']);
    Route::get('/partner-count', [CmsStatsController::class, 'partnerCount']);
    Route::get('/faq-count', [CmsStatsController::class, 'faqCount']);
    Route::get('/concept-count', [CmsStatsController::class, 'conceptCount']);

    Route::apiResource('languages', LanguageController::class);
    Route::get('/last-updated', [CmsStatsController::class, 'lastUpdated']);

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
