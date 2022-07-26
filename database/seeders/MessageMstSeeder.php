<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class MessageMstSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $type = [
            1 => 'follow',
            2 => 'location',
            3 => 'stamp',
            4 => 'message',
            5 => 'reply',
            6 => 'reply_select',
            7 => 'timezone',
        ];

        DB::table('message_msts')->insert([
            [
                'type' => $type[1],
                'seq'  => 'first',
                'message' => "友達登録ありがとう！\n近くのお店を提案するよ！\nまずは話しかけてみてね！！",
                'user_id' => 1,
            ],
            [
                'type' => $type[1],
                'seq'  => 'unblock',
                'message' => "待ってたよ...。\nやっと、解除してくれたね...？",
                'user_id' => 1,
            ],
            [
                'type' => $type[2],
                'seq'  => 'please',
                'message' => "近場のお店を検索します。\n今どこにいるか教えてください！",
                'user_id' => 1,
            ],
            [
                'type' => $type[2],
                'seq'  => 'send',
                'message' => "現在地を送る!",
                'user_id' => 1,
            ],
            [
                'type' => $type[2],
                'seq'  => 'not_found',
                'message' => "ごめぴ！\n見つかんなかった...(ﾃﾍﾍﾟﾛ",
                'user_id' => 1,
            ],
            [
                'type' => $type[3],
                'seq'  => 'stamp',
                'message' => "スタンプ送らないでよ...",
                'user_id' => 1,
            ],
            [
                'type' => $type[5],
                'seq'  => 'search',
                'message' => "お店を探す",
                'user_id' => 1
            ],
            [
                'type' => $type[5],
                'seq'  => 'after_suggest',
                'message' => "ここでいい？",
                'user_id' => 1
            ],
            [
                'type' => $type[5],
                'seq'  => 'please',
                'message' => "もう一回送って！",
                'user_id' => 1
            ],
            [
                'type' => $type[6],
                'seq'  => 'final_answer',
                'message' => "ここにする",
                'user_id' => 1
            ],
            [
                'type' => $type[6],
                'seq'  => 'next_shop',
                'message' => "次の店を探す",
                'user_id' => 1
            ],
            [
                'type' => $type[7],
                'seq'  => 'morning',
                'message' => "おはよう！\nいい朝だね！",
                'user_id' => 1
            ],
            [
                'type' => $type[7],
                'seq'  => 'noon',
                'message' => "こんにちは！\nランチの時間だね！",
                'user_id' => 1
            ],
            [
                'type' => $type[7],
                'seq'  => 'night',
                'message' => "こんばんは！\n今日はどこで食べる？",
                'user_id' => 1
            ],
            [
                'type' => $type[7],
                'seq'  => 'midnight',
                'message' => "こんな夜遅くに店探すの...？",
                'user_id' => 1
            ],
        ]);
    }
}
