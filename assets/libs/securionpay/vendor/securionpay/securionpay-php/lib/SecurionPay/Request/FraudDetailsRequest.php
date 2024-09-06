<?php
namespace SecurionPay\Request;

class FraudDetailsRequest extends AbstractRequest
{

    public function getStatus()
    {
        return $this->get('status');
    }
    
    public function status($status)
    {
        return $this->set('status', $status);
    }
}
