<?php

namespace App\Providers;

use App\Services\LineService;
use App\Services\HotpepperService;
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

        // HOT PEPPER API
        $this->app->bind(HotpepperService::class, function () {
            return new HotpepperService(
                config('hotpepper.api_key'),
                config('hotpepper.base_url')
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
