<?php
namespace SecurionPay\Request;

class CheckoutRequestCharge extends AbstractRequest
{

    public function getAmount()
    {
        return $this->get('amount');
    }

    public function amount($amount)
    {
        return $this->set('amount', $amount);
    }

    public function getCurrency()
    {
        return $this->get('currency');
    }

    public function currency($currency)
    {
        return $this->set('currency', $currency);
    }

    public function getCapture()
    {
        return $this->get('capture');
    }

    public function capture($capture)
    {
        return $this->set('capture', $capture);
    }

    public function getMetadata()
    {
        return $this->get('metadata');
    }
    
    public function metadata($metadata)
    {
        return $this->set('metadata', $metadata);
    }
}
