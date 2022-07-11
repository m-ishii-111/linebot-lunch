<?php

namespace App\Providers;

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
        // $this->app->bind('line-bot', function ($app, array $parameters) {
        //     return new LINEBot(
        //       new LINEBot\HTTPClient\CurlHTTPClient(env('LINE_ACCESS_TOKEN')),
        //       ['channelSecret' => env('LINE_CHANNEL_SERCRET')]
        //     );
        // });
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
