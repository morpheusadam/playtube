<?php
namespace SecurionPay\Util;

use SecurionPay\Request\AbstractRequest;
use SecurionPay\Response\ListResponse;
use SecurionPay\Exception\MappingException;

class ObjectSerializer
{

    public function __construct()
    {
    }

    public function serialize($request, & $path)
    {
        if ($request == null) {
            return null;
        }
        
        $request = $this->normalizeRequest($request);
        $request = $this->handlePathVariables($request, $path);
        $request = $this->transformForJson($request);

        $json = json_encode($request);
        if ($json === false) {
            throw new MappingException($this->getJsonError('JSON encode failed'));
        }

        return $json;
    }

    private function transformForJson($array)
    {
        if ($array === array()) {
            return (object) null;
        }
    
        foreach ($array as $key => $value) {
            if (is_array($value)) {
                $array[$key] = $this->transformForJson($value);
            }
        }
    
        return $array;
    }
    
    public function deserialize($responseBody, $responseClass)
    {
        $response = json_decode($responseBody, true);
        
        if ($response === false) {
            throw new MappingException($this->getJsonError('JSON decode failed'));
        }
        
        return new $responseClass($response);
    }

    public function deserializeList($responseBody, $elementClass)
    {
        $response = json_decode($responseBody, true);
        
        if ($response === false) {
            throw new MappingException($this->getJsonError('JSON decode failed'));
        }
        
        return new ListResponse($response, $elementClass);
    }

    public function serializeToQueryString($request, & $path)
    {
        $request = $this->normalizeRequest($request);
        $request = $this->handlePathVariables($request, $path);
        
        return '?' . http_build_query($this->transformForQueryString($request), null, '&');
    }

    private function transformForQueryString($array)
    {
        foreach ($array as $key => $value) {
            if (is_array($value)) {
                $array[$key] = $this->transformForQueryString($value);
            } elseif (is_bool($value)) {
                $array[$key] = $value ? 'true' : 'false';
            }
        }
        
        return $array;
    }

    private function normalizeRequest($request)
    {
        if ($request instanceof AbstractRequest) {
            return $request->toArray();
        } elseif (is_array($request)) {
            return $request;
        } else {
            throw new MappingException('Request parameter must be request object or an array, but was: ' . gettype($request));
        }
    }
    
    public function handlePathVariables($request, & $path)
    {
        foreach ($this->findPathVars($path) as $var) {
            if (!array_key_exists($var, $request)) {
                throw new MappingException('Required attribute not found in request: ' . $var);
            }
            
            $path = str_replace('{' . $var . '}', $request[$var], $path);
            unset($request[$var]);
        }
        
        return $request;
    }

    public function findPathVars($path)
    {
        $matches = array();
        preg_match_all('/\{(\w+)\}/', $path, $matches);
        
        return $matches[1];
    }

    private function getJsonError($message)
    {
        if (function_exists('json_last_error_msg')) {
            return $message . ': ' . json_last_error_msg();
        } elseif (function_exists('json_last_error')) {
            static $errors = array(
                JSON_ERROR_NONE => 'No error has occurred',
                JSON_ERROR_DEPTH => 'The maximum stack depth has been exceeded',
                JSON_ERROR_STATE_MISMATCH => 'Invalid or malformed JSON',
                JSON_ERROR_CTRL_CHAR => 'Control character error, possibly incorrectly encoded',
                JSON_ERROR_SYNTAX => 'Syntax error',
                JSON_ERROR_UTF8 => 'Malformed UTF-8 characters, possibly incorrectly encoded'
            );
            
            $error = json_last_error();
            return $message . ': ' . (array_key_exists($error, $errors) ? $errors[$error] : 'Unknown error ' . $error);
        } else {
            return $message;
        }
    }
}
