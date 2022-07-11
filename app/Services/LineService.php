<?php

namespace App\Services;

use LINE\LINEBot;
use LINE\LINBot\Event\FollowEvent;
use LINE\LINEBot\Event\MessageEvent\TextMessage;
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
        $this->channleSecret = $channelSecret;
        $this->httpClient = new CurlHTTPClient($this->accessToken);
        $this->bot = new LINEBot($this->httpClient, ['channelSecret' => $this->channelSecret]);
    }

    public function sortingEvent($request)
    {
        $events = $this->bot->parseEventRequet($request->getContent(), $signature);
        foreach($events as $event)
        {
            if ($event instanceof TextMessage) {
                return $this->bot->replyText($event->getReplyToken(), $event->getText());
            }
            if ($event instanceof FollowEvent) {
                return $this->bot->replyText($event->getReplyToken(), '[bot]友達登録されたよ!');
            }
        }

        return 'ok!';
    }

    public function SendReplyMessage($replyToken, string $text): \LINE\LINEBot\Response
    {
        $textMessageBuilder = new \LINE\LINEBot\MessageBuilder\TextMessageBuilder($text);
        return $this->bot->replyMessage($replyToken, $textMessageBuilder);
    }
}