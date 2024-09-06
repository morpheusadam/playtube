<?php
namespace SecurionPay\Response;

class ThreeDSecureInfo extends AbstractResponse
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

    public function getEnrolled()
    {
        return $this->get('enrolled');
    }

    public function getLiabilityShift()
    {
        return $this->get('liabilityShift');
    }
    
    public function getVersion()
    {
        return $this->get('version');
    }
    
    public function getAuthenticationFlow()
    {
        return $this->get('authenticationFlow');
    }
}
