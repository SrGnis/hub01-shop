<?php

namespace App\Providers;

use App\Services\ProjectService;
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
        $this->app->singleton(ProjectService::class);
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
    }
}
