<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\LineService;
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

    public function __construct(LineService $lineService)
    {
        $this->lineService = $lineService;
    }

    public function webhook(Request $request)
    {
        $signature = $request->header('x-line-signature');
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
            $replyMessage = 'その操作はサポートしてません。.[' . get_class($event) . '][' . $event->getType() . ']';

            error_log(get_class($event) . ' : ' . $event->getType());

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
                    $address = print_r($event->getAddress(), true);
                    $this->lineService->SendReplyMessage("位置情報が送られた。\n".$address);
                    // $messageBuilder = $this->lineService->LocationAction($event);
                    break;

                //スタンプの受信
                case $event instanceof \LINE\LINEBot\Event\StickerMessage:
                    $messageBuilder = $this->lineService->StampAction($event);
                    break;

                //選択肢とか選んだ時に受信するイベント
                case $event instanceof \LINE\LINEBot\Event\PostbackEvent:
                    break;
                //ブロック
                case $event instanceof \LINE\LINEBot\Event\UnfollowEvent:
                    break;
                default:
                    $body = $event->getEventBody();
                    logger()->warning('Unknown event. ['. get_class($event) . ']', compact('body'));
                    $messageBuilder = $this->lineService->UnknownAction($event, $message);
            }
            $bot->replyMessage($replyToken, $messageBuilder);
        }
        return 'ok!';
    }
}