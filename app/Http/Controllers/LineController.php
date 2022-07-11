<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\LineService;
use LINE\LINEBot\SignatureValidator;

class LineController extends Controller
{
    public function post(Request $request)
    {
        $signarure = $request->header('X-Line-Signature');
        $httpRequestBody = $request->getContent();
        $validateSignature = SignatureValidator::validateSignature($httpRequestBody, env('LINE_CHANNEL_SECRET'), $signarure);
        if (!$validateSignature) {
            abort(400);
        }

        // return response()->json(200);

        $bot = LineService::lineSdk();
        try {
            $events = $bot->perseEventRequest($httpRequestBody, $signature);
            foreach ($events as $event) {
                // イベントごとの処理を書いていく
                if ($eveent instanceof LINE\LINEBot\Event\MessageEvent\TextMessage) {
                    $bot->replyText($event->getReplyToken(), 'こんにちは!');
                }
            }
        } catch (\Exception $e) {
            Log::debug($e);
        }
    }
}
