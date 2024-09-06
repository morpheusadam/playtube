<?php
namespace SecurionPay\Request;

class CheckoutRequestThreeDSecure extends AbstractRequest
{
    
    public function getEnable()
    {
        return $this->get('enable');
    }
    
    public function enable($enable)
    {
        return $this->set('enable', $enable);
    }
    
    public function getRequireEnrolledCard()
    {
        return $this->get('requireEnrolledCard');
    }
    
    public function requireEnrolledCard($requireEnrolledCard)
    {
        return $this->set('requireEnrolledCard', $requireEnrolledCard);
    }
    
    public function getRequireSuccessfulLiabilityShiftForEnrolledCard()
    {
        return $this->get('requireSuccessfulLiabilityShiftForEnrolledCard');
    }
    
    public function requireSuccessfulLiabilityShiftForEnrolledCard($requireSuccessfulLiabilityShiftForEnrolledCard)
    {
        return $this->set('requireSuccessfulLiabilityShiftForEnrolledCard', $requireSuccessfulLiabilityShiftForEnrolledCard);
    }
}
