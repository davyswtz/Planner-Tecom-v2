<?php

namespace App\Providers;

use App\Models\OpTask;
use App\Observers\OpTaskObserver;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        OpTask::observe(OpTaskObserver::class);

        if (! config('services.http_verify_ssl', true)) {
            Http::globalOptions(['verify' => false]);
        }
    }
}
