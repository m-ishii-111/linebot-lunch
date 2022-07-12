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

            switch (true) {
                //友達登録＆ブロック解除
                case $event instanceof \LINEBot\Event\FollowEvent:
                    $replyMessage = '登録＆解除';
                    break;
                //メッセージの受信
                case $event instanceof \LINEBot\Event\MessageEvent\TextMessage:
                    $replyMessage = $this->messageType($event);
                    break;

                //位置情報の受信
                case $event instanceof \LINEBot\Event\MessageEvent\LocationMessage:
                    $replyMessage = '位置情報';
                    break;

                //選択肢とか選んだ時に受信するイベント
                case $event instanceof \LINEBot\Event\PostbackEvent:
                    break;
                //ブロック
                case $event instanceof \LINEBot\Event\UnfollowEvent:
                    break;
                default:
                    $body = $event->getEventBody();
                    logger()->warning('Unknown event. ['. get_class($event) . ']', compact('body'));
            }
            $bot->replyText($replyToken, $replyMessage);
        }
        return 'ok!';

        // $bot->replyText($replyToken, $replyMessage);
        // $this->lineService->SendReplyMessage($replyToken, 'サンプルテキスト');
    }

    private function messageType($event): string
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
}