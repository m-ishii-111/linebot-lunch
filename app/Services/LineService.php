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

    // TextMessage
    public function MessageAction($event)
    {
        $text = $event->getText();
        $message = '';
        switch ($text) {
            case 'おはよう':
                $message = 'おはようございます!';
                break;
            case 'こんにちは':
                $message = 'こんにちは！';
                break;
            case 'おやすみ':
                $message = 'おやすみなさい...zzZ';
                break;
            default:
                $message = $text;
        }
        return $message;
    }

    // LocationMessage
    public function LocationAction($event)
    {
        return $event->getAddress() ?? '位置情報がありません。';
    }
}