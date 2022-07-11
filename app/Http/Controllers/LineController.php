<?php

namespace App\Http\Controllers;

use App\Services\LineService;
use Illuminate\Http\Request;
use LINE\LINEBot;
use LINE\LINEBot\Constant\HTTPHeader;
use LINE\LINEBot\Event\FollowEvent;
use LINE\LINEBot\Event\MessageEvent\TextMessage;
use LINE\LINEBot\HTTPClient\CurlHTTPClient;
use LINE\LINEBot\SignatureValidator;

class LineController extends Controller
{
    private $lineService;

    public function __construct(LineService $lineService)
    {
        $this->lineService = $lineService;
    }

    public function post(Request $request)
    {
        // $replyToken = $request->events[0]['replyToken'];
        // $this->lineService->SendReplyMessage($replyToken, 'サンプルメッセージ');

        error_log($request->header('x-line-signature'));
        error_log(print_r($request->getContent(), true));

        // $signature = $request->header(HTTPHeader::LINE_SIGNATURE);
        $signature = $request->header('x-line-signature');
        if (empty($signature)) {
            return abort(400, 'Bad Request');
        }
        error_log(base64_encode(hash_hmac('sha256', $request->getContent(), env('LINE_CHANNEL_SECRET'), true)));

        $events = $this->lineService->bot->parseEventRequest($request->getContent(),  $signature);
        foreach($events as $event)
        {
            if ($event instanceof TextMessage) {
                return $this->lineService->bot->replyText($event->getReplyToken(), $event->getText());
            }
            if ($event instanceof FollowEvent) {
                return $this->lineService->bot->replyText($event->getReplyToken(), '[bot]友達登録されたよ!');
            }
        }

        return 'ok!';
    }
}
