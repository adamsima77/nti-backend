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
}
