<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class MessageMst extends Model
{
    use HasFactory;

    protected $hidden = [
        'user_id',
        'created_at',
        'updated_at',
    ];

    public function getMessages()
    {
        $records = $this->get()->toArray();
        $messages = [];
        foreach ($records as $record) {
            $messages[$record['type']][$record['seq']] = $record['message'];
        }
        return $messages;
    }

    public function upsertMessage($input)
    {
        unset($input['_token']);
        $userId = Auth::id();
        $data = [
            ['type' => 'follow',   'seq' => 0, 'message' => $input['follow'],          'user_id' => $userId],
            ['type' => 'location', 'seq' => 0, 'message' => $input['location'],        'user_id' => $userId],
            ['type' => 'location', 'seq' => 1, 'message' => $input['location_button'], 'user_id' => $userId],
            ['type' => 'location', 'seq' => 9, 'message' => $input['not_found'],       'user_id' => $userId],
            ['type' => 'stamp',    'seq' => 0, 'message' => $input['stamp'],           'user_id' => $userId],
        ];
        $this->upsert($data, ['type', 'seq'], ['message', 'user_id']);
    }
}
