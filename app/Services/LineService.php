<?php

namespace App\Serviecs;

use LINE\LINEBot;
use LINE\LINEBot\HTTPClient\CurlHTTPClient;

class LineService
{
    public static function lineSdk()
    {
        $token  = env('LINE_ACCESS_TOKEN');
        $secret = env('LINE_CHANNEL_SERCRET');

        $httpClient = new CurlHTTPClient($token);
        $bot = new LINEBot($httpClient, ['channelSecret' => $secret]);

        return $bot;
    }
}