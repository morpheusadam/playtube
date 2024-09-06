<?php
namespace SecurionPay\Request;

class FraudCheckDataRequest extends AbstractRequest
{

    public function getIpAddress()
    {
        return $this->get('ipAddress');
    }

    public function ipAddress($ipAddress)
    {
        return $this->set('ipAddress', $ipAddress);
    }

    public function getEmail()
    {
        return $this->get('email');
    }

    public function email($email)
    {
        return $this->set('email', $email);
    }

    public function getUserAgent()
    {
        return $this->get('userAgent');
    }

    public function userAgent($userAgent)
    {
        return $this->set('userAgent', $userAgent);
    }

    public function getAcceptLanguage()
    {
        return $this->get('acceptLanguage');
    }

    public function acceptLanguage($acceptLanguage)
    {
        return $this->set('acceptLanguage', $acceptLanguage);
    }
}
