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
        $content = $request->getContent();
        $signature = $request->header('x-line-signature');
        if (empty($signature)) {
            return abort(400, 'Signature is empty.');
        }
        if (!SignatureValidator::validateSignature(
                $request->getContent(),
                env('LINE_CHANNEL_SECRET'),
                $signature
        )) {
            abort(400);
        }

        $bot = $this->lineService->getBot();
        $events = $bot->parseEventRequest($content, $signature);
        foreach ($events as $event) {
            $replyToken = $event->getReplyToken();
            $bot->replyText($replyToken, $event->getText());
        }

        return 'ok!';
    }
}
