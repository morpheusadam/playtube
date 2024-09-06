<?php
namespace SecurionPay\Connection;

use SecurionPay\Exception\ConnectionException;

class WordPressConnection extends Connection
{

    public function __construct()
    {
    }

    public function get($url, $headers)
    {
        return $this->httpRequest('GET', $url, $headers);
    }

    public function post($url, $requestBody, $headers)
    {
        return $this->httpRequest('POST', $url, $headers, $requestBody);
    }

    public function delete($url, $headers)
    {
        return $this->httpRequest('DELETE', $url, $headers);
    }

    private function httpRequest($httpMethod, $url, $headers = array(), $requestBody = null)
    {
        $headers['User-Agent'] .= ' WordPress/' . get_bloginfo('version');

        $response = wp_remote_request($url, 
            array(
                'method' => $httpMethod,
                'headers' => $headers,
                'body' => $requestBody,
                'timeout' => 62
            ));
        
        if (is_wp_error($response)) {
            throw new ConnectionException($response->get_error_message());
        }
        
        return array(
            'status' => $response['response']['code'],
            'headers' => $response['headers'],
            'body' => $response['body']
        );
    }
    
    public function multipart($url, $files, $form, $headers)
    {
        throw new \Exception('Multipart request in not supported by WordPressConnection');
    }
}
