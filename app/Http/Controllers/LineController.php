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

        error_log(print_r($request->session()->all(), true));

        $bot = $this->lineService->getBot();
        $events = $bot->parseEventRequest($request->getContent(), $signature);
        foreach ($events as $event)
        {
            $replyToken = $event->getReplyToken();

            switch (true) {
                //友達登録＆ブロック解除
                case $event instanceof \LINE\LINEBot\Event\FollowEvent:
                    $messageBuilder = $this->lineService->followAction($event);
                    break;

                //メッセージの受信
                case $event instanceof \LINE\LINEBot\Event\MessageEvent\TextMessage:
                    $messageBuilder = $this->lineService->MessageAction($event);
                    break;

                //位置情報の受信
                case $event instanceof \LINE\LINEBot\Event\MessageEvent\LocationMessage:
                    $restaurants = $this->hotpepperService->searchGourmet($event);
                    if (!$restaurants) {
                        $this->lineService->SendReplyMessage($replyToken, $restaurants);
                    }
                    $this->lineService->LocationAction($event, $restaurants);
                    // 次へボタンを送信する
                    return 'ok!';
                    break;

                //スタンプの受信
                case $event instanceof \LINE\LINEBot\Event\MessageEvent\StickerMessage:
                    $messageBuilder = $this->lineService->StampAction($event);
                    break;

                //選択肢とか選んだ時に受信するイベント
                case $event instanceof \LINE\LINEBot\Event\PostbackEvent:
                //ブロック
                case $event instanceof \LINE\LINEBot\Event\UnfollowEvent:
                default:
                    $replyMessage = 'その操作はサポートしてません。.[' . get_class($event) . '][' . $event->getType() . ']';
                    error_log('Unknown or Undifined event :'.get_class($event).' / '.$event->getType());
                    $messageBuilder = $this->lineService->UnknownAction($event, $replyMessage);
                    break;
            }
            $bot->replyMessage($replyToken, $messageBuilder);
        }
        return 'ok!';
    }
}