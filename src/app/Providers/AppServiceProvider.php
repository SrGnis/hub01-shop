<?php

namespace App\Providers;

use App\Models\User;
use App\Services\PageService;
use App\Services\ProjectQuotaService;
use App\Services\ProjectService;
use App\Services\ProjectVersionService;
use App\Services\UserService;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Facades\App;
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
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Relation::morphMap([
            'project' => 'App\Models\Project',
            'project_type' => 'App\Models\ProjectType',
        ]);

        if($this->app->environment('production')) {
            \URL::forceScheme('https');
        }
    }
}
