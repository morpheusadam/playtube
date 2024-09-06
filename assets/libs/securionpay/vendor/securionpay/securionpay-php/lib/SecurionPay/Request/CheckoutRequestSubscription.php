<?php
namespace SecurionPay\Request;

class CheckoutRequestSubscription extends AbstractRequest
{

    public function getPlanId()
    {
        return $this->get('planId');
    }

    public function planId($planId)
    {
        return $this->set('planId', $planId);
    }

    public function getCaptureCharges()
    {
        return $this->get('captureCharges');
    }

    public function captureCharges($captureCharges)
    {
        return $this->set('captureCharges', $captureCharges);
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
