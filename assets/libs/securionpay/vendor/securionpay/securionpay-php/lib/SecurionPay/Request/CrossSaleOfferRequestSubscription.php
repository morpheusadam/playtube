<?php
namespace SecurionPay\Request;

class CrossSaleOfferRequestSubscription extends AbstractRequest
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
}
