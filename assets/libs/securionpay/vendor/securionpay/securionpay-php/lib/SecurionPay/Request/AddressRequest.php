<?php
namespace SecurionPay\Request;

class AddressRequest extends AbstractRequest
{

    public function getLine1()
    {
        return $this->get('line1');
    }

    public function line1($line1)
    {
        return $this->set('line1', $line1);
    }

    public function getLine2()
    {
        return $this->get('line2');
    }

    public function line2($line2)
    {
        return $this->set('line2', $line2);
    }

    public function getZip()
    {
        return $this->get('zip');
    }

    public function zip($zip)
    {
        return $this->set('zip', $zip);
    }

    public function getCity()
    {
        return $this->get('city');
    }

    public function city($city)
    {
        return $this->set('city', $city);
    }

    public function getState()
    {
        return $this->get('state');
    }

    public function state($state)
    {
        return $this->set('state', $state);
    }

    public function getCountry()
    {
        return $this->get('country');
    }

    public function country($country)
    {
        return $this->set('country', $country);
    }
}
