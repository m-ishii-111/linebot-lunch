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

    public function __construct($accessToken, $channelSecret)
    {
        $this->accessToken = $accessToken;
        $this->channelSecret = $channelSecret;
        $this->httpClient = new CurlHTTPClient($this->accessToken);
        $this->bot = new LINEBot($this->httpClient, ['channelSecret' => $this->channelSecret]);

        $messagesMst = new MessageMst();
        $this->messages = $messagesMst->getMessages();
        $this->shopLog = new ShopLog();
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
        $firstTime = $this->shopLog->doesNotExists($event->getUserId());
        $message = $firstTime ? $this->messages['follow']['first'] : $this->message['follow']['unblock'];
        return [[
            "type" => "text",
            "text" => $message,
        ]];
    }

    // TextMessage
    public function MessageAction($event)
    {
        $text = $event->getText();

        if ($text == $this->messages['reply_select']['final_answer']) {
            return $this->stampFormat('446', '1993');
        }

        if (in_array($text, $this->NGword())) {
            return $this->stampFormat('11538', '51626519');
        }

        if ($text == $this->messages['reply']['search']) {
            return [ $this->replyMessage($this->messages['reply']['please']) ];
        }

        switch ( timezone() ) {
            case 'midnight':
                $message = $this->messages['timezone']['midnight'];
                break;
            case 'morning':
                $message = $this->messages['timezone']['morning'];
                break;
            case 'noon':
                $message = $this->messages['timezone']['noon'];
                break;
            case 'night':
                $message = $this->messages['timezone']['night'];
                break;
            default:
                $message = "こんにちは！";
                break;
        }

        return [ $this->replyMessage($message) ];
    }

    private function replyMessage(string $message): array
    {
        return [
            "type" => "text",
            "text" => $message,
            "quickReply" => [
                "items"  => [
                    [
                        "type"   => "action",
                        "action" => [
                            "type"  => "location",
                            "label" => $this->messages['reply']['search']
                        ]
                    ],
                ]
            ]
        ];
    }

    private function afterReplyMessage(string $shopId, string $lat, string $lng): array
    {
        $query = "&shop_id={$shopId}&lat={$lat}&lng={$lng}";
        return [
            "type" => "text",
            "text" => $this->messages['reply']['after_suggest'],
            "quickReply" => [
                "items"  => [
                    [
                        "type"   => "action",
                        "action" => [
                            "type"  => "message",
                            "label" => $this->messages['reply_select']['final_answer'],
                            "text"  => $this->messages['reply_select']['final_answer']
                        ]
                    ],
                    [
                        "type"   => "action",
                        "action" => [
                            "type"  => "postback",
                            "label" => $this->messages['reply_select']['next_shop'],
                            "data"  => "type=search" . $query,
                            "displayText" => $this->messages['reply_select']['next_shop'],
                        ]
                    ]
                ]
            ]
        ];
    }

    // Restaurants Not Found
    public function NotFoundMessage(string $message = null): array
    {
        return [
            [
                "type" => "text",
                "text" => $message ?? $this->messages['location']['not_found'],
            ],
            [
                "type" => "sticker",
                "packageId" => '6136',
                "stickerId" => '10551392'
            ]
        ];
    }

    // LocationMessage
    public function LocationAction($lineUserId, $restaurants, $latitude, $longitude)
    {
        if ($restaurants['results_returned'] < 1) {
            error_log('line_user_id: '.$lineUserId.', error: restaurants not found.');
            return $this->NotFoundMessage();
        }

        $shops = $restaurants['shop'];

        //suggest filter
        $logs = $this->shopLog->getWeekLogs($lineUserId);
        $shopIds = array_column($logs, 'hp_shop_id');
        $suggest_filter = array_filter($shops, function ($shop) use ($shopIds) {
            return !in_array($shop['id'], $shopIds);
        });
        $shop = $suggest_filter;

        // lunch filter
        if (timezone() == 'noon') {
            $lunch_filter = array_filter($shops, function ($shop) {
                return $shop['lunch'] == 'あり';
            });
            $shops = $lunch_filter;
        }

        //midnight filter
        if (timezone() == 'midnight') {
            $midnight_filter = array_filter($shops, function ($shop) {
                return $shop['midnight'] == '営業している';
            });
            $shops = $midnight_filter;
        }

        if (count($shops) < 1) {
            error_log('line_user_id: '.$lineUserId.', info: shops is empty.');
            return $this->NotFoundMessage();
        }

        $shop = $shops[array_rand($shops)];
        $this->shopLog->insertLog($lineUserId, $shop);

        $response = [
            'type'     => 'flex',
            'altText'  => $shop['name'],
            'contents' => $this->returnFlexJson($shop)
        ];

        return [ $response, $this->afterReplyMessage($shop['id'], $latitude, $longitude) ];
    }

    // StampAction
    public function StampAction($event)
    {
        // GoodJobStampを送信
        return $this->stampFormat('11537', '52002735');
    }

    public function stampFormat($packageId, $stickerId)
    {
        return [[
            'type' => 'sticker',
            'packageId' => $packageId,
            'stickerId' => $stickerId
        ]];
    }

    // UnknownAction
    public function UnknownAction($event, $message)
    {
        return [
            "type" => "text",
            "text" => "その操作はサポートされていません。"
        ];
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
        if (timezone() == 'noon') {
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

    public function NGword()
    {
        return [
            'ばか',
            'バカ',
            '馬鹿',
            'あほ',
            'アホ',
            'まぬけ',
            '間抜け',
            'マヌケ',
        ];
    }
}