<?php
namespace SecurionPay\Response;

class Refund extends AbstractResponse
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

    public function getCreated()
    {
        return $this->get('created');
    }
}
