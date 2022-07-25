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
                    Log::debug($messgeArray);
                    break;

                //位置情報の受信
                case $event instanceof \LINE\LINEBot\Event\MessageEvent\LocationMessage:
                    $restaurants = $this->hotpepperService->searchGourmet($event);
                    $messageArray = $this->lineService->LocationAction($event, $restaurants);
                    // 次へボタンを送信する
                    break;

                //スタンプの受信
                case $event instanceof \LINE\LINEBot\Event\MessageEvent\StickerMessage:
                    $messageArray = $this->lineService->StampAction($event);
                    break;

                //選択肢とか選んだ時に受信するイベント
                case $event instanceof \LINE\LINEBot\Event\PostbackEvent:
                //ブロック
                case $event instanceof \LINE\LINEBot\Event\UnfollowEvent:
                default:
                    $message = 'その操作はサポートしてません。.[' . get_class($event) . '][' . $event->getType() . ']';
                    error_log('Unknown or Undifined event :'.get_class($event).' / '.$event->getType());
                    $messageArray = $this->lineService->UnknownAction($event, $message);
                    break;
            }
            $this->sendMessage($replyToken, $lineUserId, $messageArray);
            // $bot->replyMessage($replyToken, $messageBuilder);
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

        Log::debug($response);

        $curl = curl_init( config('line.curl_url') );
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_POST, true);
        curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'POST');
        curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($post_data));
        curl_setopt($curl, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer '.config('line.access_token'),
            'Content-Type: application/json; charset=UTF-8']
        );

        $curlResult = curl_exec($curl);
        curl_close($curl);
    }
}