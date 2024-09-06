<?php
namespace SecurionPay\Request;

class CheckoutRequestCustomCharge extends AbstractRequest
{

    public function getAmountOptions()
    {
        return $this->get('amountOptions');
    }

    public function amountOptions($amountOptions)
    {
        return $this->set('amountOptions', $amountOptions);
    }

    /**
     * @return \SecurionPay\Request\CheckoutRequestCustomAmount
     */
    public function getCustomAmount()
    {
        return $this->getObject('customAmount', '\SecurionPay\Request\CheckoutRequestCustomAmount');
    }

    public function customAmount($customAmount)
    {
        return $this->set('customAmount', $customAmount);
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
