<?php
namespace SecurionPay\Response;

class CrossSaleOfferCharge extends AbstractResponse
{

    public function __construct($response)
    {
        parent::__construct($response);
    }

    public function getAmount()
    {
        return $this->get('amount');
    }

    public function getCurrency()
    {
        return $this->get('currency');
    }

    public function getCapture()
    {
        return $this->get('capture');
    }
}
