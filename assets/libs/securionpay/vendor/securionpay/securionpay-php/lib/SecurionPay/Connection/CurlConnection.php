<?php
namespace SecurionPay\Connection;

use SecurionPay\Exception\ConnectionException;

class CurlConnection extends Connection
{

    private $extraOptions;

    public function __construct($extraOptions = array())
    {
        if (!extension_loaded('curl')) {
            throw new \Exception('Please install the PHP cURL extension');
        }
        
        $this->extraOptions = $extraOptions;
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

    public function multipart($url, $files, $form, $headers)
    {
        $request = array();
        
        foreach ($files as $key => $file) {
            $request[$key] = curl_file_create($file, null, basename($file));
        }
        foreach ($form as $key => $value) {
            $request[$key] = $value;
        }
        
        unset($headers['Content-Type']);
        
        return $this->httpRequest('POST', $url, $headers, $request);
    }
    
    private function httpRequest($httpMethod, $url, $headers = array(), $requestBody = null)
    {
        $version = curl_version();
        $headers['User-Agent'] .= ' Curl/' . $version['version'];
        
        $curlOpts = array(
            CURLOPT_CUSTOMREQUEST => $httpMethod,
            CURLOPT_URL => $url,
            CURLOPT_HTTPHEADER => $this->buildHeaders($headers),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_TIMEOUT => 62
        );
        
        if ($requestBody) {
            $curlOpts[CURLOPT_POSTFIELDS] = $requestBody;
        }
        
        if (!empty($this->extraOptions)) {
            $curlOpts = $this->extraOptions + $curlOpts;
        }

        $curl = curl_init();
        curl_setopt_array($curl, $curlOpts);
        $responseBody = curl_exec($curl);
        $responseInfo = curl_getinfo($curl);
        
        if ($responseBody === false) {
            $error = curl_error($curl);
            curl_close($curl);
            throw new ConnectionException($error);
        }
        
        curl_close($curl);
        
        return array(
            'status' => $responseInfo['http_code'],
            'headers' => array(
                'Content-Type' => $responseInfo['content_type']
            ),
            'body' => $responseBody
        );
    }

    private function buildHeaders($headers)
    {
        $result = array();
        
        foreach ($headers as $name => $value) {
            $result[] = $name . ': ' . $value;
        }
        
        return $result;
    }
}
