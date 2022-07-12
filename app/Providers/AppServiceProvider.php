<?php

namespace App\Providers;

use App\Services\LineService;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        // LINE BOT
        $this->app->bind(LineService::class, function () {
            return new LineService(
                config('line.access_token'),
                config('line.channel_secret')
            );
        });
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        //
    }
}
