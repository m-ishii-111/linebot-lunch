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
        $validateSignature = SignatureValidator::validateSignature($httpRequestBody, $channelSecret, $signarure);
        if ($validateSignature) {
            return response()->json(200);
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
        } else {
            abort(400);
        }

    }
}
