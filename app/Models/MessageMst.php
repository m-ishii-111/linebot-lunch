<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

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
}
