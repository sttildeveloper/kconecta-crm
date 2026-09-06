<?php

namespace App\Providers;

use App\Services\Orchestration\Contracts\WorkerDriver;
use App\Services\Orchestration\LocalWorkerDriver;
use Illuminate\Support\Facades\URL;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Vite;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(WorkerDriver::class, LocalWorkerDriver::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        RateLimiter::for('content-reports', fn ($request) => Limit::perMinute(5)->by('reports:'.($request->user()?->id ?: $request->ip())));
        RateLimiter::for('user-blocks', fn ($request) => Limit::perMinute(20)->by('blocks:'.($request->user()?->id ?: $request->ip())));
        RateLimiter::for('legal-acceptance', fn ($request) => Limit::perMinute(10)->by('legal:'.($request->user()?->id ?: $request->ip())));
        RateLimiter::for('account-deletion', fn ($request) => Limit::perMinute(5)->by('account-delete:'.($request->user()?->id ?: $request->ip())));

        Vite::prefetch(concurrency: 3);

        $appUrl = rtrim((string) config('app.url'), '/');

        if ($appUrl !== '') {
            URL::forceRootUrl($appUrl);

            if (str_starts_with($appUrl, 'https://')) {
                URL::forceScheme('https');
            }
        }
    }
}
