<?php
namespace Modules\Content\Providers;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Modules\Content\Models\Category;
use Modules\Content\Models\ContactSubmission;
use Modules\Content\Models\FrequentlyAskedQuestion;
use Modules\Content\Models\HeroBanner;
use Modules\Content\Models\Language;
use Modules\Content\Models\MetaTag;
use Modules\Content\Models\News;
use Modules\Content\Models\Partner;
use Modules\Content\Models\PartnerReference;
use Modules\Content\Models\SiteMember;
use Modules\Content\Policies\CategoryPolicy;
use Modules\Content\Policies\ContactSubmissionPolicy;
use Modules\Content\Policies\FrequentlyAskedQuestionPolicy;
use Modules\Content\Policies\HeroBannerPolicy;
use Modules\Content\Policies\LanguagePolicy;
use Modules\Content\Policies\MetaTagPolicy;
use Modules\Content\Policies\NewsPolicy;
use Modules\Content\Policies\PartnerPolicy;
use Modules\Content\Policies\PartnerReferencePolicy;
use Modules\Content\Policies\SiteMemberPolicy;

class AuthServiceProvider extends ServiceProvider {
    protected $policies = [
      Category::class => CategoryPolicy::class,
        FrequentlyAskedQuestion::class => FrequentlyAskedQuestionPolicy::class,
        HeroBanner::class => HeroBannerPolicy::class,
        Language::class => LanguagePolicy::class,
        MetaTag::class => MetaTagPolicy::class,
        News::class  => NewsPolicy::class,
        Partner::class => PartnerPolicy::class,
        PartnerReference::class => PartnerReferencePolicy::class,
        SiteMember::class => SiteMemberPolicy::class,
        ContactSubmission::class => ContactSubmissionPolicy::class
    ];

     public function boot(): void
     {
         $this->registerPolicies();
     }
}
