<?php
namespace SecurionPay\Response;

class FastCredit extends AbstractResponse
{

    public function __construct($response)
    {
        parent::__construct($response);
    }

    public function getSupported()
    {
        return $this->get('supported');
    }

    public function getUpdated()
    {
        return $this->get('updated');
    }
}
