<?php

namespace App\Services;

use LINE\LINEBot;
use LINE\LINEBot\HTTPClient\CurlHTTPClient;

class LineService
{
    private $accessToken;
    private $channelSecret;
    private $httpClient;
    private $bot;

    public function __construct($accessToken, $channelSecret)
    {
        $this->accessToken = $accessToken;
        $this->channelSecret = $channelSecret;
        $this->httpClient = new CurlHTTPClient($this->accessToken);
        $this->bot = new LINEBot($this->httpClient, ['channelSecret' => $this->channelSecret]);
    }

    public function getBot()
    {
        return $this->bot;
    }

    public function SendReplyMessage($replyToken, string $text): \LINE\LINEBot\Response
    {
        $textMessageBuilder = new \LINE\LINEBot\MessageBuilder\TextMessageBuilder($text);
        return $this->bot->replyMessage($replyToken, $textMessageBuilder);
    }
}