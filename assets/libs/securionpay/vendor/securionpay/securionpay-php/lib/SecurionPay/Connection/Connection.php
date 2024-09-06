<?php
namespace SecurionPay\Connection;

abstract class Connection
{

    abstract public function get($url, $headers);

    abstract public function post($url, $requestBody, $headers);

    abstract public function delete($url, $headers);
    
    abstract public function multipart($url, $files, $form, $headers);
}
