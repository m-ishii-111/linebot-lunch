<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\LineService;
use App\Services\HotpepperService;
use LINE\LINEBot\Constant\HTTPHeader;
use LINE\LINEBot\SignatureValidator;
use LINE\LINEBot\Event\FollowEvent;
use LINE\LINEBot\Event\MessageEvent\TextMessage;
use LINE\LINEBot\Event\MessageEvent\LocationMessage;
use LINE\LINEBot\Event\PostbackEvent;
use LINE\LINEBot\Event\UnfollowEvent;
use Log;

class LineController extends Controller
{
    private $lineService;
    private $hotpepperService;

    public function __construct(LineService $lineService, HotpepperService $hotpepperService)
    {
        $this->lineService = $lineService;
        $this->hotpepperService = $hotpepperService;
    }

    public function webhook(Request $request)
    {
        // 署名検証
        $signature = $request->header(config('line.header_signature'));
        if (!SignatureValidator::validateSignature(
                $request->getContent(),
                config('line.channel_secret'),
                $signature
        )) {
            abort(400);
        }

        $bot = $this->lineService->getBot();
        $events = $bot->parseEventRequest($request->getContent(), $signature);
        foreach ($events as $event)
        {
            $replyToken = $event->getReplyToken();
            $lineUserId = $event->getUserId();

            switch (true) {
                //友達登録＆ブロック解除
                case $event instanceof \LINE\LINEBot\Event\FollowEvent:
                    $messageArray = $this->lineService->followAction($event);
                    break;

                //メッセージの受信
                case $event instanceof \LINE\LINEBot\Event\MessageEvent\TextMessage:
                    $messageArray = $this->lineService->MessageAction($event);
                    Log::debug($messageArray);
                    break;

                //次候補
                case $event instanceof \LINE\LINEBot\Event\PostbackEvent:
                    $data = $event->getPostbackData();
                    $latitude = $data["lat"];
                    $longitude = $data["lng"];
                    $restaurants = $this->hotpepperService->searchGourmet($latitude, $longitude);
                    if (empty($restaurants)) {
                        $messageArray = $this->lineService->NotFoundMessage();
                    }
                    $messageArray = $this->lineService->LocationAction($lineUserId, $restaurants, $latitude, $longitude);
                    break;
                //位置情報の受信
                case $event instanceof \LINE\LINEBot\Event\MessageEvent\LocationMessage:
                    $latitude = $event->getLatitude();
                    $longitude = $event->getLongitude();
                    $restaurants = $this->hotpepperService->searchGourmet($latitude, $longitude);
                    if (empty($restaurants)) {
                        $messageArray = $this->lineService->NotFoundMessage();
                    }
                    $messageArray = $this->lineService->LocationAction($lineUserId, $restaurants, $latitude, $longitude);
                    break;

                //スタンプの受信
                case $event instanceof \LINE\LINEBot\Event\MessageEvent\StickerMessage:
                    $messageArray = $this->lineService->StampAction($event);
                    break;
                    break;
                //ブロック
                case $event instanceof \LINE\LINEBot\Event\UnfollowEvent:
                default:
                    $message = 'その操作はサポートしてません。.[' . get_class($event) . '][' . $event->getType() . ']';
                    error_log('Unknown or Undifined event :'.get_class($event).' / '.$event->getType());
                    $messageArray = $this->lineService->UnknownAction($event, $message);
                    break;
            }
            $this->sendMessage($replyToken, $lineUserId, $messageArray);
        }
        return 'ok!';
    }

    private function sendMessage(string $replyToken, string $lineUserId, array $response)
    {
        $uri = 'https://api.line.me/v2/bot/message/reply';
        $post_data = [
            "replyToken" => $replyToken,
            "to"         => $lineUserId,
            "messages"   => $response
        ];

        error_log(print_r($response, true));

        $curl = curl_init( $uri );
        curl_setopt($curl, CURLOPT_POST, true);
        curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'POST');
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($post_data));
        curl_setopt($curl, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer '.config('line.access_token'),
            'Content-Type: application/json; charset=UTF-8'
        ]);

        $curlResult = curl_exec($curl);
        curl_close($curl);
    }
}