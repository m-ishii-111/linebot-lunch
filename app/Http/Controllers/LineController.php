<?php

namespace App\Http\Controllers;

use App\Services\LineService;
use Illuminate\Http\Request;
use LINE\LINEBot;
use LINE\LINEBot\Constant\HTTPHeader;
use LINE\LINEBot\Event\FollowEvent;
use LINE\LINEBot\Event\MessageEvent\TextMessage;
use LINE\LINEBot\HTTPClient\CurlHTTPClient;

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

        $signature = $request->header(HTTPHeader::LINE_SIGNATURE);
        dd($signature);
        if (empty($signature)) {
            return abort(400, 'Bad Request');
        }

        $events = $this->lineService->bot->parseEventRequest($request->getContent(),  $signature[0]);
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
