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
        ];

        DB::table('message_msts')->insert([
            [
                'type' => $type[1],
                'seq'  => 0,
                'message' => "友達登録ありがとう！\n近くのお店を提案するよ！\nまずは話しかけてみてね！！",
                'user_id' => 1,
            ],
            [
                'type' => $type[2],
                'seq' => 0,
                'message' => "近場のお店を検索します。\n今どこにいるか教えてください！",
                'user_id' => 1,
            ],
            [
                'type' => $type[2],
                'seq' => 1,
                'message' => "現在地を送る!",
                'user_id' => 1,
            ],
            [
                'type' => $type[2],
                'seq' => 9,
                'message' => "ごめぴ！\n見つかんなかった...(ﾃﾍﾍﾟﾛ",
                'user_id' => 1,
            ],
            [
                'type' => $type[3],
                'seq' => 1,
                'message' => "スタンプ送らないでよ...",
                'user_id' => 1,
            ],
        ]);
    }
}
