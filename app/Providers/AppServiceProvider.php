<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Services\LineService;

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
                env('LINE_ACCESS_TOKEN'),
                env('LINE_CHANNEL_SECRET')
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
