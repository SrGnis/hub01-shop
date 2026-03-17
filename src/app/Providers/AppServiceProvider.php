<?php

namespace App\Providers;

use App\Models\AbuseReport;
use App\Models\Collection;
use App\Models\Membership;
use App\Models\Project;
use App\Policies\AbuseReportPolicy;
use App\Policies\CollectionPolicy;
use App\Policies\MembershipPolicy;
use App\Policies\ProjectPolicy;
use App\Services\CollectionService;
use App\Services\PageService;
use App\Services\ProjectQuotaService;
use App\Services\ProjectService;
use App\Services\ProjectVersionService;
use App\Services\UserService;
use Dedoc\Scramble\Scramble;
use Illuminate\Support\Facades\Gate;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Register services for dependency injection

        $this->app->singleton(UserService::class);
        $this->app->singleton(ProjectService::class);
        $this->app->singleton(ProjectVersionService::class);
        $this->app->singleton(ProjectQuotaService::class);
        $this->app->singleton(PageService::class);
        $this->app->singleton(CollectionService::class);

        // Ignore default routes for Scramble
        Scramble::ignoreDefaultRoutes();
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Gate::policy(Project::class, ProjectPolicy::class);
        Gate::policy(Membership::class, MembershipPolicy::class);
        Gate::policy(AbuseReport::class, AbuseReportPolicy::class);
        Gate::policy(Collection::class, CollectionPolicy::class);

        Gate::define('collections.view.hidden-token', [CollectionPolicy::class, 'viewHiddenByToken']);
        Gate::define('collections.manage.entries', [CollectionPolicy::class, 'manageEntries']);

        Relation::morphMap([
            'project' => 'App\Models\Project',
            'project_type' => 'App\Models\ProjectType',
        ]);

        if($this->app->environment('production')) {
            \URL::forceScheme('https');
        }
    }
}
