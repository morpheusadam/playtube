<?php
namespace SecurionPay\Response;

class Address extends AbstractResponse
{

    public function __construct($response)
    {
        parent::__construct($response);
    }

    public function getLine1()
    {
        return $this->get('line1');
    }

    public function getLine2()
    {
        return $this->get('line2');
    }

    public function getZip()
    {
        return $this->get('zip');
    }

    public function getCity()
    {
        return $this->get('city');
    }

    public function getState()
    {
        return $this->get('state');
    }

    public function getCountry()
    {
        return $this->get('country');
    }
}
