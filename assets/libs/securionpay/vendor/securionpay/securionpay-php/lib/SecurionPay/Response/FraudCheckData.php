<?php
namespace SecurionPay\Response;

class FraudCheckData extends AbstractResponse
{

    public function __construct($response)
    {
        parent::__construct($response);
    }

    public function getIpAddress()
    {
        return $this->get('ipAddress');
    }

    public function getIpCountry()
    {
        return $this->get('ipCountry');
    }

    public function getEmail()
    {
        return $this->get('email');
    }

    public function getUserAgent()
    {
        return $this->get('userAgent');
    }

    public function getAcceptLanguage()
    {
        return $this->get('acceptLanguage');
    }
}
