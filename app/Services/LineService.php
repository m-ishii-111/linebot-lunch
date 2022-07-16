<?php

namespace App\Services;

use App\Models\MessageMst;
use App\Models\ShopLog;

use LINE\LINEBot;
use LINE\LINEBot\HTTPClient\CurlHTTPClient;
use LINE\LINEBot\MessageBuilder\TemplateMessageBuilder;
use LINE\LINEBot\MessageBuilder\TemplateBuilder\ButtonTemplateBuilder;
use LINE\LINEBot\MessageBuilder\TextMessageBuilder;
use LINE\LINEBot\MessageBuilder\StickerMessageBuilder;

use LINE\LINEBot\TemplateActionBuilder\UriTemplateActionBuilder;

class LineService
{
    private $accessToken;
    private $channelSecret;
    private $httpClient;
    private $bot;
    private $hotpepperService;

    private $messages;
    private $shopLog;

    private $timezone;

    public function __construct($accessToken, $channelSecret)
    {
        $this->accessToken = $accessToken;
        $this->channelSecret = $channelSecret;
        $this->httpClient = new CurlHTTPClient($this->accessToken);
        $this->bot = new LINEBot($this->httpClient, ['channelSecret' => $this->channelSecret]);

        $messagesMst = new MessageMst();
        $this->messages = $messagesMst->getMessages();
        $this->shopLog = new ShopLog();

        $this->timeZone = $this->getTimezone();
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
        $message = $messages['follow'][0];
        return new TemplateMessageBuilder($message);
    }

    // TextMessage
    public function MessageAction($event)
    {
        $text = $event->getText();
        $message = '';
        switch ($this->timeZone) {
            case 'midnight':
                $message = "こんな夜遅くに店探すの？\n";
            case 'morning':
                $message = "おはよう！\n";
            case 'noon':
                $message = "こんにちは！\nランチの時間だね！\n";
            case 'night':
                $message = "こんばんは！\n今日はどこで食べる？\n";
            default:
                $message = "こんにちは！\n";
        }
        $messageBuilder = $this->requireLocation($event, $message);

        return $messageBuilder;
    }

    // 時間帯を取得する
    public function getTimezone()
    {
        $hour = date("i");
        if (5 < $hour && $hour <= 10 ) {
            $time = 'morning';
        } elseif (10 < $hour && $hour <= 15) {
            $time = 'noon';
        } elseif (15 < $hour && $hour <= 22) {
            $time = 'night';
        } else {
            $time = 'midnight';
        }
    }

    // 現在地送るボタン
    public function requireLocation($event, $word)
    {
        $uri = new UriTemplateActionBuilder($this->messages['location'][1], 'line://nv/location');
        $message = new ButtonTemplateBuilder(null, $word.$this->messages['location'][0], null, [$uri]);
        $templateMessageBuilder = new TemplateMessageBuilder('位置情報を送ってね', $message);
        return $templateMessageBuilder;
    }

    // LocationMessage
    public function LocationAction($event, $restaurants)
    {
        $replyToken = $event->getReplyToken();
        $lineUserId = $event->getUserId();
        if (!isset($restaurants['results_returned']) || $restaurants['results_returned'] == 0) {
            error_log('line_user_id: '.$lineUserId.', error: restaurants not found.');
            $this->SendReplyMessage($replyToken, $this->messages['location'][9]);
        }

        $count = $restaurants['results_returned'] - 1;
        $logs = $this->shopLog->getWeekLogs($lineUserId);
        $shopIds = array_column($logs, 'hp_shop_id');
        // $genreCodes = array_column($logs, 'hp_genre_code');
        $logsCount = count($shopIds);

        $shops = $restaurants['shop'];
        if ($count != $logsCount || $logsCount != 0) {
            $shop_filter_id = array_filter($shops, function ($shop) use ($shopIds) {
                return !in_array($shop['id'], $shopIds);
            });
            if (count($shop_filter_id) == 0) {
                // 0件なら終わる
                $this->SendReplyMessage($replyToken, $this->messages['location'][9]);
            }
            $shops = $shop_filter_id;
        }

        $shop = $shops[array_rand($shops)];
        $this->shopLog->insertLog($lineUserId, $shop);

        $postJsonArray = $this->returnFlexJson($shop);
        $postArray = ['type' => 'flex', 'altText' => 'flex message', 'contents' => $postJsonArray];
        $result = json_encode(['replyToken' => $replyToken, 'to' => [ $lineUserId ], 'messages' => [ $postArray ]]);

        $curl = curl_init();
        //curl_exec() の返り値を文字列で返す
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        //POSTリクエスト
        curl_setopt($curl, CURLOPT_POST, true);
        //ヘッダを指定
        curl_setopt($curl, CURLOPT_HTTPHEADER, array('Authorization: Bearer '.$this->accessToken, 'Content-Type: application/json; charset=UTF-8'));
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
        return new StickerMessageBuilder('11538', '51626501');
        // return new TextMessageBuilder($this->messages['stamp'][0]);
    }

    public function stampJson()
    {
        return [
            'type' => 'sticker',
            'packageId' => '11538',
            'stickerId' => '51626501'
        ];
    }

    // UnknownAction
    public function UnknownAction($event, $message)
    {
        return new TextMessageBuilder($message);
    }

    // flexMessage Template
    public function returnFlexJson($shop)
    {
        $thumbnail    = $shop['photo']['mobile']['l'] ?? config('line.noimage');
        $shopUrl      = $shop['urls']['sp'] ?? $shop['urls']['pc'];
        $name         = $shop['name'] ?? '-';
        $catch        = $shop['catch'] ?? '-';
        $genre        = $shop['genre']['name'] ?? '-';
        $budget       = $shop['budget']['average'] ?? '-';
        $open         = $shop['open'] ?? '-';
        $close        = $shop['close'] ?? '-';
        $lunch        = $shop['lunch'] ?? '';
        $address      = $shop['address'] ?? '-';
        $coupon       = $shop['coupon_urls']['sp'] ?? $shop['coupon_urls']['sp'];
        $googleMapUri = config('line.google_map_uri').'?api=1&query='.$shop['lat'].','.$shop['lng'].'&zoom=20';

        $content = [
            'type' => 'bubble',
            'hero' => [
                'type' => 'image',
                'url'  => $thumbnail,
                'size' => 'full',
                'aspectRatio' => '20:13',
                'aspectMode'  => 'cover',
                'action' => [
                    'type' => 'uri',
                    'uri'  => $shopUrl,
                ],
            ],
            'body' => [
                'type' => 'box',
                'layout' => 'vertical',
                'contents' => [
                    [
                        'type' => 'text',
                        'text' => $name,
                        'weight' => 'bold',
                        'size' => 'xl'
                    ],
                    [
                        'type' => 'box',
                        'layout' => 'vertical',
                        'contents' => [
                            [
                                'type' => 'text',
                                'text' => $catch,
                                'wrap' => true
                            ]
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
                                'paddingBottom' => 'sm',
                                'contents' => [
                                    [
                                        'type' => 'text',
                                        'text' => "ジャンル",
                                        'wrap' => true,
                                        'color' => '#aaaaaa',
                                        'size' => 'sm',
                                        'flex' => 1,
                                    ],
                                    [
                                        'type' => 'text',
                                        'text' => $genre,
                                        'wrap' => true,
                                        'color' => '#666666',
                                        'size' => 'sm',
                                        'flex' => 5,
                                    ],
                                ],
                            ],
                            [
                                'type' => 'box',
                                'layout' => 'baseline',
                                'spacing' => 'sm',
                                'paddingBottom' => 'sm',
                                'contents' => [
                                    [
                                        'type' => 'text',
                                        'text' => "金額",
                                        'wrap' => true,
                                        'color' => '#aaaaaa',
                                        'size' => 'sm',
                                        'flex' => 1,
                                    ],
                                    [
                                        'type' => 'text',
                                        'text' => $budget,
                                        'wrap' => true,
                                        'color' => '#666666',
                                        'size' => 'sm',
                                        'flex' => 5,
                                    ],
                                ],
                            ],
                            [
                                'type' => 'box',
                                'layout' => 'baseline',
                                'spacing' => 'sm',
                                'paddingBottom' => 'sm',
                                'contents' => [
                                    [
                                        'type' => 'text',
                                        'text' => "営業\n時間",
                                        'wrap' => true,
                                        'color' => '#aaaaaa',
                                        'size' => 'sm',
                                        'flex' => 1,
                                    ],
                                    [
                                        'type' => 'text',
                                        'text' => $open,
                                        'wrap' => true,
                                        'color' => '#666666',
                                        'size' => 'sm',
                                        'flex' => 5,
                                    ],
                                ],
                            ],
                            [
                                'type' => 'box',
                                'layout' => 'baseline',
                                'spacing' => 'sm',
                                'paddingBottom' => 'sm',
                                'contents' => [
                                    [
                                        'type' => 'text',
                                        'text' => "定休日",
                                        'wrap' => true,
                                        'color' => '#aaaaaa',
                                        'size' => 'sm',
                                        'flex' => 1,
                                    ],
                                    [
                                        'type' => 'text',
                                        'text' => $close,
                                        'wrap' => true,
                                        'color' => '#666666',
                                        'size' => 'sm',
                                        'flex' => 5,
                                    ],
                                ],
                            ],
                            [
                                'type' => 'box',
                                'layout' => 'baseline',
                                'spacing' => 'sm',
                                'paddingBottom' => 'sm',
                                'contents' => [
                                    [
                                        'type' => 'text',
                                        'text' => '住所',
                                        'wrap' => true,
                                        'color' => '#aaaaaa',
                                        'size' => 'sm',
                                        'flex' => 1
                                    ],
                                    [
                                        'type' => 'text',
                                        'text' => $address,
                                        'wrap' => true,
                                        'color' => '#666666',
                                        'size' => 'sm',
                                        'flex' => 5
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            'footer' => [
                'type' => 'box',
                'layout' => 'horizontal',
                'contents' => [
                    [
                        'type' => 'button',
                        'action' => [
                            'type' => 'uri',
                            'label' => 'クーポン',
                            'uri' => $coupon,
                        ]
                    ],
                    [
                        'type' => 'button',
                        'action' => [
                            'type' => 'uri',
                            'label' => 'Google Map',
                            'uri' => $googleMapUri,
                        ]
                    ]
                ]
            ]
        ];

        // お昼時はランチ情報を追加
        if ($this->timeZone == 'lunch') {
            $content['body']['contents'][2]['contents'][] = [
                'type' => 'box',
                'layout' => 'baseline',
                'spacing' => 'sm',
                'paddingBottom' => 'sm',
                'contents' => [
                    [
                        'type' => 'text',
                        'text' => "ランチ",
                        'wrap' => true,
                        'color' => '#aaaaaa',
                        'size' => 'sm',
                        'flex' => 1,
                    ],
                    [
                        'type' => 'text',
                        'text' => $lunch,
                        'wrap' => true,
                        'color' => '#666666',
                        'size' => 'sm',
                        'flex' => 5,
                    ],
                ],
            ];
        }

        // 情報提供元の追加
        $content['body']['contents'][2]['contents'][] = [
            'type' => 'box',
            'layout' => 'baseline',
            'spacing' => 'sm',
            'paddingTop' => 'md',
            'contents' => [
                [
                    'type' => 'text',
                    'text' => "Powered by ホットペッパー Webサービス",
                    'color' => '#aaaaaa',
                    'size' => 'xxs',
                ],
            ],
        ];

        return $content;
    }
}