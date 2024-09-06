<?php

namespace PayFast;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Psr\Http\Message\StreamInterface;

class Request
{

    /**
     * Request
     * @param string $method
     * @param string $uri
     * @param array $queryParams Query params: ['foo' => 'bar']
     * @param array $jsonData json data: ['foo' => 'bar']
     * @return StreamInterface
     * @throws GuzzleException
     */
    public static function sendApiRequest(string $method, string $uri, array $queryParams=[], array $jsonData=[]) {
        $client = new Client(['base_uri' => PayFastApi::$apiUrl.'/']);

        $params = [];

        if(!empty($queryParams)) {
            $params['query'] = $queryParams;
        }
        if(PayFastApi::$testMode === true){
            $params['query']['testing'] = 'true';
        }

        if(!empty($jsonData)) {
            $params['json'] = $jsonData;
        }

        $params['headers'] = [
            'merchant-id' => PayFastApi::$merchantId,
            'version'     => PayFastApi::$version,
            'timestamp'   => date("Y-m-d\TH:i:sO")
        ];

        $signatureData = array_merge($params['headers'], $queryParams, $jsonData);

        $signature = Auth::generateApiSignature($signatureData, PayFastApi::$passPhrase);

        $params['headers']['signature'] = $signature;

        try {
            $response = $client->request($method, $uri, $params);
            return $response->getBody();
        } catch (GuzzleException $e) {
            throw $e;
        }
    }

}
