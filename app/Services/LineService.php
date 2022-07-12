<?php

namespace App\Services;

use LINE\LINEBot;
use LINE\LINEBot\HTTPClient\CurlHTTPClient;
use LINE\LINEBot\MessageBuilder\TemplateMessageBuilder;
use LINE\LINEBot\MessageBuilder\TemplateBuilder\ButtonTemplateBuilder;
use LINE\LINEBot\MessageBuilder\TextMessageBuilder;
use LINE\LINEBot\TemplateActionBuilder\UriTemplateActionBuilder;

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

    public function FollowAction($event)
    {
        $message = '友達登録ありがとう！\n近くのお店を提案するよ！\nまずは話しかけてみてね！！';
        return new TemplateMessageBuilder($message);
    }

    // TextMessage
    public function MessageAction($event)
    {
        $text = $event->getText();
        $message = '';
        switch ($text) {
            case 'おやすみ':
                $message = 'おやすみなさい\nよい夢を...zzZ';
                $messageBuilder = new TextMessageBuilder($message);
                break;
            case 'おはよう':
                $message = 'おはようございます！';
            case 'こんにちは':
                $message = 'こんにちは！';
            default:
                $messageBuilder = $this->requireLocation($event, $message);
        }
        return $messageBuilder;
    }

    public function requireLocation($event, $word)
    {
        $uri = new UriTemplateActionBuilder('現在地を送る!', 'line://nv/location');
        $message = new ButtonTemplateBuilder(null, $word.'\n今どこにいるか教えてください！', null, [$uri]);
        $templateMessageBuilder = new TemplateMessageBuilder('位置情報を送ってね', $message);
        return $templateMessageBuilder;
    }

    // LocationMessage
    public function LocationAction($event)
    {
        $address = $event->getAddress();
        return $event->getAddress() ?? '位置情報がありません。';
    }

    // StampAction
    public function StampAction($event)
    {
        return new TextMessageBuilder('スタンプ送るなや');
    }

    // UnknownAction
    public function UnknownAction($event, $message)
    {
        return new TextMessageBuilder($message);
    }
}