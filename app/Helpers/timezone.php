<?php

if (!function_exists('timezone')) {
    // 時間帯を取得する
    function timezone(): string
    {
        $hour = date("H");
        if (5 < $hour && $hour <= 10 ) {
            $time = 'morning';
        } elseif (10 < $hour && $hour < 16) {
            $time = 'noon';
        } elseif (16 <= $hour && $hour <= 21) {
            $time = 'night';
        } else {
            $time = 'midnight';
        }
        return $time;
    }
}
