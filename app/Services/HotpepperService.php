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

        $option = [
            'query' => [
                'key'    => $this->baseUrl,
                'lat'    => $latitude,
                'lng'    => $longitude,
                'range'  => $range,
                'datum'  => 'world',
                'order'  => '4',
                'format' => 'json'
            ],
        ];

        // 時間で勝手にパラメータつけちゃう
        $hour = date('i');
        if ($hour >= 11 && $hour < 16) {
            $options['query']['lunch'] = 1;
        }
        if ($hour >= 16 || $hour < 23) {
            $options['query']['midnight'] = 1;
        }
        if ($hour >= 23) {
            $options['query']['midnight_meal'] = 1;
        }

        $response = $client->request($method, $this->baseUrl, $options);
        error_log(print_r($response->getBody(), true));
        $restaurants = json_decode($response->getBody(), true)['results'];

        return $restaurants;
    }
}