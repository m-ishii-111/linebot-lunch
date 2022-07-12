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
    private $hotpepperService;

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

    // 友達追加とブロック解除
    public function FollowAction($event)
    {
        $message = "友達登録ありがとう！\n近くのお店を提案するよ！\nまずは話しかけてみてね！！";
        return new TemplateMessageBuilder($message);
    }

    // TextMessage
    public function MessageAction($event)
    {
        $text = $event->getText();
        $message = '';
        switch ($text) {
            case 'おやすみ':
                $message = "おやすみなさい\nよい夢を...zzZ";
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

    // 現在地送るボタン
    public function requireLocation($event, $word)
    {
        $uri = new UriTemplateActionBuilder('現在地を送る!', 'line://nv/location');
        $message = new ButtonTemplateBuilder(null, $word."\n近場のお店を検索します。\n今どこにいるか教えてください！\n\nPowered by ホットペッパー Webサービス", null, [$uri]);
        $templateMessageBuilder = new TemplateMessageBuilder('位置情報を送ってね', $message);
        return $templateMessageBuilder;
    }

    // LocationMessage
    public function LocationAction($event, $restaurants)
    {
        error_log(print_r($restaurants, true));
        $replyToken = $event->getReplyToken();
        $count = $restaurants['results_returned'];
        if ($count == 0) {
            $this->SendReplyMessage($replyToken, '見つかんなかった...(ﾃﾍﾍﾟﾛ');
        }
        $shop = $restaurants['shop'][mt_rand(1, $count)];

        $postJson = $this->returnFlexJson($shop);
        $result = json_encode(['replyToken' => $replyToken, 'messages' => [$postJson]]);
        $curl = curl_init();
        //curl_exec() の返り値を文字列で返す
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        //POSTリクエスト
        curl_setopt($curl, CURLOPT_POST, true);
        //ヘッダを指定
        curl_setopt($curl, CURLOPT_HTTPHEADER, array('Authorization: Bearer '.$this->accessToken, 'Content-type: application/json'));
        //リクエストURL
        curl_setopt($curl, CURLOPT_URL, 'https://api.line.me/v2/bot/message/reply');
        //送信するデータ
        curl_setopt($curl, CURLOPT_POSTFIELDS, $result);

        $curlResult = curl_exec($curl);

        curl_close($curl);

        return $curlResult;
    }

    // StampAction
    public function StampAction($event)
    {
        return new TextMessageBuilder('スタンプ送るなや！！！');
    }

    // UnknownAction
    public function UnknownAction($event, $message)
    {
        return new TextMessageBuilder($message);
    }

    // flexMessage Template
    public function returnFlexJson($shop)
    {
        $content = [
            'type' => 'bubble',
            'hero' => [
                'type' => 'image',
                'url'  => $shop['photo']['moblie']['l'],
                'size' => 'full',
                'aspectRatio' => '20:13',
                'aspectMode'  => 'cover',
                'action' => [
                    'type' => 'uri',
                    'uri'  => 'http://linecorp.com.cover'
                ],
            ],
            'body' => [
                'type' => 'box',
                'layout' => 'vertical',
                'contents' => [
                    [
                        'type' => 'text',
                        'text' => $shop['name'],
                        'weight' => 'bold',
                        'size' => 'xl'
                    ],
                    [
                        'type' => 'box',
                        'layout' => 'vertical',
                        'contents' => [
                            'type' => 'text',
                            'text' => 'catch'
                        ],
                    ],
                    [
                        'type' => 'box',
                        'layout' => 'vertical',
                        'margin' => 'lg',
                        'spacing' => 'sm',
                        'contents' => [
                            [
                                'type' => 'box',
                                'layout' => 'baseline',
                                'spacing' => 'sm',
                                'contents' => [
                                    [
                                        'type' => 'text',
                                        'text' => 'Place',
                                        'color' => '#aaaaaa',
                                        'size' => 'sm',
                                        'flex' => 1
                                    ],
                                    [
                                        'type' => 'text',
                                        'text' => $shop['address'],
                                        'wrap' => true,
                                        'color' => '#666666',
                                        'size' => 'sm',
                                        'flex' => 5
                                    ],
                                ],
                            ],
                        ],
                        [
                            'type' => 'box',
                            'text' => $shop['budget']['average'],
                            'wrap' => true,
                            'color' => '#666666',
                            'size' => 'sm',
                            'flex' => 5
                        ],
                    ],
                ],
            ],
        ];
        return $content;
    }
}