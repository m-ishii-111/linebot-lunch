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
        $data = [];
        $userId = Auth::id();
        foreach($input as $field => $value) {
            $fields = explode('-', $field);
            $data[] = ['type' => $fields[0], 'seq' => $fields[1], 'message' => $value, 'user_id' => $userId];
        }

        $this->upsert($data, ['type', 'seq'], ['message', 'user_id']);
    }
}
