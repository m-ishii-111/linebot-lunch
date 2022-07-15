<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class ShopLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'line_user_id',
        'hp_shop_id',
        'hp_genre_code',
        'hp_genre_name',
    ];

    public function getWeekLogs($lineUserId)
    {
        return $this->select('hp_shop_id', 'hp_genre_code')
                    ->where('line_user_id', $lineUserId)
                    ->orderBy('search_date', 'desc')
                    ->limit(10)
                    ->get()
                    ->toArray();
    }

    public function insertLog($lineUserId, $shop)
    {
        $this->insert([
            'line_user_id' => $lineUserId,
            'hp_shop_id' => $shop['id'],
            'hp_genre_code' => $shop['genre']['code'],
            'hp_genre_name' => $shop['genre']['name'],
        ]);
    }
}
