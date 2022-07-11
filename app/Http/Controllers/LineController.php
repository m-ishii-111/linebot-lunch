<?php

namespace App\Http\Controllers;

use App\Services\LineService;
use Illuminate\Http\Request;

class LineController extends Controller
{
    private $lineService;

    public function __construct(LineService $lineService)
    {
        $this->lineService = $lineService;
    }

    public function post(Request $request)
    {
        $replyToken = $request->events[0]['replyToken'];
        $this->lineService->SendReplyMessage($replyToken, 'サンプルメッセージ');
    }
}
