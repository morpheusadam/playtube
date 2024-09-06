<?php
namespace SecurionPay\Response;

class FraudWarning extends AbstractResponse
{

    public function __construct($response)
    {
        parent::__construct($response);
    }

    public function getId()
    {
        return $this->get('id');
    }

    public function getCreated()
    {
        return $this->get('created');
    }

    public function getCharge()
    {
        return $this->get('charge');
    }

    public function getActionable()
    {
        return $this->get('actionable');
    }
}
