<?php

namespace extend\http;

use GuzzleHttp\Client;

class Http
{

    public static function get($url,$data)
    {
        $option = ['verify'=>false,];
        $client   = new Client($option);

        $headers = ["Referer"=>$url];
        $response = $client->request('GET', $url,['query' => $data, 'headers'=>$headers]);
        if ($response->getStatusCode() == 200) {
            return json_decode($response->getBody()->getContents(), true);
        }

        return false;
    }


    public static function post($url,$data)
    {
        $option = [
            'verify'=>false,
        ];
        $client   = new Client($option);

        $headers = ["Referer"=>$url];
        $response = $client->request('POST', $url,['json' => $data,'headers'=>$headers, 'timeout' => 1.5 ]);
        if ($response->getStatusCode() == 200) {
            return json_decode($response->getBody()->getContents(), true);
        }

        return false;
    }

}