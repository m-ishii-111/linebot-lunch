<?php

namespace App\Services;

use GuzzleHttp\Client;

class HotpepperService
{
    private $apiKey;
    private $baseUrl;
    private $client;

    public function __construct($apiKey, $baseUrl)
    {
        $this->apiKey  = $apiKey;
        $this->baseUrl = $baseUrl;
        $this->client = new Client();
    }

    public function getGenreMaster()
    {
        $method = 'GET';
        $format = 'json';

        $options = [
            'query' => [
                'key'    => $this->apiKey,
                'format' => $format,
            ],
        ];

        $response = $this->client->request($method, config('hotpepper.genre_url'), $options);
        $genres = json_decode($response->getBody(), true)['results'];

        return $genres;
    }

    public function searchGourmet($event)
    {
        $method = 'GET';
        $format = 'json';
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
                'format' => $format
            ],
        ];

        // 時間で勝手にパラメータつけちゃう
        if (timezone() == 'lunch') {
            $options['query']['lunch'] = 1;
        }
        if (timezone() == 'midnight') {
            $options['query']['midnight'] = 1;
            $options['query']['midnight_meal'] = 1;
        }

        $response = $this->client->request($method, $this->baseUrl, $options);
        $restaurants = json_decode($response->getBody(), true)['results'];

        return $restaurants;
    }
}