<?php

namespace App\Providers;

use Illuminate\Mail\Markdown;
use Illuminate\Support\ServiceProvider;

class MailThemeServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        $this->app->singleton('markdown', function ($app) {
            $markdown = new Markdown($app['view'], [
                'theme' => 'hub01',
                'paths' => [
                    resource_path('views/vendor/mail'),
                ],
            ]);

            return $markdown;
        });

        config(['mail.markdown.theme' => 'hub01']);
        config(['mail.markdown.paths' => [resource_path('views/vendor/mail')]]);
    }
}
