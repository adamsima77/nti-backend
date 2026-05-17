<?php

namespace Modules\Content\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Content\Models\FrequentlyAskedQuestion;
use Modules\Content\Models\HeroBanner;
use Modules\Content\Models\MetaTag;
use Modules\Content\Models\News;use Illuminate\Http\Response;
use Modules\Content\Models\Partner;
use Modules\Content\Models\PartnerReference;
use Modules\Content\Models\SiteMember;

class CmsStatsController extends Controller
{
    public function publicatedArticles(){
        $numberOfPublicated = News::where('status_id', 1)->count();
        return response()->json(['count' => $numberOfPublicated], Response::HTTP_OK);
    }

    public function conceptCount(){
        $count = News::where('status_id', 2)->count()
            + Partner::where('status_id', 2)->count()
            + FrequentlyAskedQuestion::where('status_id', 2)->count()
            + HeroBanner::where('status_id', 2)->count()
            + SiteMember::where('status_id', 2)->count()
            + MetaTag::where('status_id', 2)->count()
            + PartnerReference::where('status_id', 2)->count();

        return response()->json(['count' => $count], Response::HTTP_OK);
    }

    public function partnerCount(){
        $numberOfPartners = Partner::count();
        return response()->json(['count' => $numberOfPartners], Response::HTTP_OK);
    }

    public function faqCount(){
        $numberOfFaqs = FrequentlyAskedQuestion::count();
        return response()->json(['count' => $numberOfFaqs], Response::HTTP_OK);
    }

    public function lastUpdated(){
        $article = News::with(['newsTranslations'])->orderByDesc('updated_at')->limit(1)->get();
        $partner = Partner::with(['partnerTranslations'])->orderByDesc('updated_at')->limit(1)->get();
        $metaTag = MetaTag::with(['metaTagTranslations'])->orderByDesc('updated_at')->limit(1)->get();
        $faq = FrequentlyAskedQuestion::with(['frequentlyAskedQuestionTranslations'])->orderByDesc('updated_at')->limit(1)->get();
        return response()->json(['article' => $article,
            'partner' => $partner, 'meta_tag' => $metaTag,
            'faq' => $faq], Response::HTTP_OK);
    }

    public function contentOverview()
    {
        $publishedArticles = News::where('status_id', 1)->count();
        $articlesConcept = News::where('status_id', 2)->count();
        $lastUpdatedArticle = News::with(['newsTranslations'])->orderByDesc('updated_at')->first();

        $faqPublished = FrequentlyAskedQuestion::where('status_id', 1)->count();
        $faqConcepts = FrequentlyAskedQuestion::where('status_id', 2)->count();
        $faqLastUpdated = FrequentlyAskedQuestion::with(['frequentlyAskedQuestionTranslations'])->orderByDesc('updated_at')->first();

        $heroBannersPublished = HeroBanner::where('status_id', 1)->count();
        $heroBannerConcepts = HeroBanner::where('status_id', 2)->count();
        $heroBannerLastUpdated = HeroBanner::with(['heroBannerTranslations'])->orderByDesc('updated_at')->first();

        $metaTagPublished = MetaTag::where('status_id', 1)->count();
        $metaTagConcepts = MetaTag::where('status_id', 2)->count();
        $metaTagLastUpdated = MetaTag::with(['metaTagTranslations'])->orderByDesc('updated_at')->first();

        $partnersPublished = Partner::where('status_id', 1)->count();
        $partnersConcept = Partner::where('status_id', 2)->count();
        $partnersLastUpdated = Partner::with(['partnerTranslations'])->orderByDesc('updated_at')->first();

        $partnerReferencePublished = PartnerReference::where('status_id', 1)->count();
        $partnerReferenceConcepts = PartnerReference::where('status_id', 2)->count();
        $partnerReferenceLastUpdated = PartnerReference::with(['partnerReferenceTranslations'])->orderByDesc('updated_at')->first();

        return response()->json([
            'news' => [
                'published' => $publishedArticles,
                'concepts' => $articlesConcept,
                'last_updated' => $lastUpdatedArticle,
            ],

            'faq' => [
                'published' => $faqPublished,
                'concepts' => $faqConcepts,
                'last_updated' => $faqLastUpdated,
            ],

            'hero_banners' => [
                'published' => $heroBannersPublished,
                'concepts' => $heroBannerConcepts,
                'last_updated' => $heroBannerLastUpdated,
            ],

            'meta_tags' => [
                'published' => $metaTagPublished,
                'concepts' => $metaTagConcepts,
                'last_updated' => $metaTagLastUpdated,
            ],

            'partners' => [
                'published' => $partnersPublished,
                'concepts' => $partnersConcept,
                'last_updated' => $partnersLastUpdated,
            ],

            'partner_references' => [
                'published' => $partnerReferencePublished,
                'concepts' => $partnerReferenceConcepts,
                'last_updated' => $partnerReferenceLastUpdated,
            ],
        ], Response::HTTP_OK);
    }

}
