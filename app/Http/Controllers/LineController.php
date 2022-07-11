<?php

namespace App\Http\Controllers;

use App\Services\LineService;
use Illuminate\Http\Request;
use LINE\LINEBot\Constant\HTTPHeader;

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
        if (empty($signature)) {
            return abort(400, 'Bad Request');
        }

        $this->lineService->sortingEvent($request);
    }
}
