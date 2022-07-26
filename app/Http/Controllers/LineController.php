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
use LINE\LINEBot\Event\MessageEvent\StickerMessage;
use LINE\LINEBot\Event\PostbackEvent;
use LINE\LINEBot\Event\UnfollowEvent;
use Log;

class LineController extends Controller
{
    private $lineService;
    private $hotpepperService;

    public function __construct(LineService $lineService, HotpepperService $hotpepperService)
    {
        $this->lineService = $lineService;
        $this->hotpepperService = $hotpepperService;
    }

    /**
     * 実質的なRouter
     */
    public function webhook(Request $request): string
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

        $bot = $this->lineService->getBot();
        $events = $bot->parseEventRequest($request->getContent(), $signature);
        foreach ($events as $event)
        {
            $replyToken = $event->getReplyToken();
            $lineUserId = $event->getUserId();

            switch (true) {
                //友達登録＆ブロック解除
                case $event instanceof \LINE\LINEBot\Event\FollowEvent:
                    $messageArray = $this->follow($event);
                    break;

                //メッセージの受信
                case $event instanceof \LINE\LINEBot\Event\MessageEvent\TextMessage:
                    $messageArray = $this->message($event);
                    break;

                //ポストバックイベントの受信
                case $event instanceof \LINE\LINEBot\Event\PostbackEvent:
                    $messageArray = $this->postback($event);
                    break;

                //位置情報の受信
                case $event instanceof \LINE\LINEBot\Event\MessageEvent\LocationMessage:
                    $messageArray = $this->location($event);
                    break;

                //スタンプの受信
                case $event instanceof \LINE\LINEBot\Event\MessageEvent\StickerMessage:
                    $messageArray = $this->sticker($event);
                    break;

                default:
                    $messageArray = $this->unknown($event);
                    break;
            }
            $this->sendMessage($replyToken, $lineUserId, $messageArray);
        }
        return 'ok!';
    }

    /**
     * Follow Controller
     *
     * @param \LINE\LINEBot\Event\FollowEvent
     * @return array
     */
    private function follow($event): array
    {
        return $this->lineService->followAction($event);
    }

    /**
     * Message Controller
     *
     * @param \LINE\LINEBot\Event\MessageEvent\TextMessage
     * @return array
     */
    private function message($event): array
    {
        return $this->lineService->MessageAction($event);
    }

    /**
     * Stamp Controller
     *
     * @param \LINE\LINEBot\Event\MessageEvent\StickerMessage
     * @return array
     */
    private function sticker($event): array
    {
        return $this->lineService->StampAction($event);
    }

    /**
     * Postback Controller
     *
     * @param \LINE\LINEBot\Event\PostbackEvent
     * @return array
     */
    private function postback($event): array
    {
        $query = $event->getPostbackData();
        if (!$query) {
            return $this->lineService->NotFoundMessage();
        }
        parse_str($query, $data);

        return $this->sendLocationAction(
            $event->getUserId(),
            $data['lat'],
            $data['lng']
        );
    }

    /**
     * Unknown Controller
     *
     * @param event
     */
     private function unknown($event): array
     {
        $message = 'その操作はサポートしてませんよ';
        error_log('Unknown or Undifined event :'.get_class($event).' / '.$event->getType());

        return $this->lineService->UnknownAction($message);
     }

    /**
     * Location Controller
     *
     * @param \LINE\LINEBot\Event\MessageEvent\LocationMessage
     * @return array
     */
    private function location($event): array
    {
        return $this->sendLocationAction(
            $event->getUserId(),
            $event->getLatitude(),
            $event->getLongitude()
        );
    }

    /**
     * Common Location Logic
     *
     * @param string $lineUserId
     * @param string $latitude
     * @param string $longitude
     *
     * @return array
     */
    private function sendLocationAction($lineUserId, $latitude, $longitude): array
    {
        $restaurants = $this->hotpepperService->searchGourmet($latitude, $longitude);
        if (empty($restaurants)) {
            return $this->lineService->NotFoundMessage();
        }

        return $this->lineService->LocationAction($lineUserId, $restaurants, $latitude, $longitude);
    }

    /**
     * Return LINEBot Messaging API
     *
     * @param string $replyToken
     * @param string $lineUserId
     * @param array  $response
     * @return void
     */
    private function sendMessage(string $replyToken, string $lineUserId, array $response): void
    {
        $uri = config('line.curl_uri');
        $post_data = [
            "replyToken" => $replyToken,
            "to"         => $lineUserId,
            "messages"   => $response
        ];

        $curl = curl_init( $uri );
        curl_setopt($curl, CURLOPT_POST, true);
        curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'POST');
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($post_data));
        curl_setopt($curl, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer '.config('line.access_token'),
            'Content-Type: application/json; charset=UTF-8'
        ]);

        $curlResult = curl_exec($curl);
        curl_close($curl);
    }
}