<?php

namespace App\Services;

use GuzzleHttp\Client;

class HotpepperService
{
    private $apiKey;
    private $baseUrl;

    public function __construct($apiKey, $baseUrl)
    {
        $this->apiKey  = $apiKey;
        $this->baseUrl = $baseUrl;
    }

    public function searchGourmet($event)
    {
        $client = new Client();

        $method = 'GET';
        $latitude  = $event->getLatitude();
        $longitude = $event->getLongitude();
        $range = 2;

        $options = [
            'query' => [
                'key'    => $this->apiKey,
                'lat'    => $latitude,
                'lng'    => $longitude,
                'range'  => $range,
                'datum'  => config('hotpepper.param_datum'),
                'order'  => config('hotpepper.param_order'),
                'count'  => config('hotpepper.param_count'),
                'format' => 'json'
            ],
        ];

        // 時間で勝手にパラメータつけちゃう
        $hour = date('H');
        if (10 < $hour && $hour < 16) {
            $options['query']['lunch'] = 1;
        }
        if ($hour >= 21) {
            $options['query']['midnight'] = 1;
            $options['query']['midnight_meal'] = 1;
        }

        $response = $client->request($method, $this->baseUrl, $options);
        $restaurants = json_decode($response->getBody(), true)['results'];

        return $restaurants;
    }
}