<?php
namespace SecurionPay\Response;

class Subscription extends AbstractResponse
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

    public function getDeleted()
    {
        return $this->get('deleted', false);
    }

    public function getPlanId()
    {
        return $this->get('planId');
    }

    public function getCustomerId()
    {
        return $this->get('customerId');
    }

    public function getQuantity()
    {
        return $this->get('quantity');
    }

    public function getCaptureCharges()
    {
        return $this->get('captureCharges');
    }

    public function getStatus()
    {
        return $this->get('status');
    }

    public function getRemainingBillingCycles()
    {
        return $this->get('remainingBillingCycles');
    }

    public function getStart()
    {
        return $this->get('start');
    }

    public function getCurrentPeriodStart()
    {
        return $this->get('currentPeriodStart');
    }

    public function getCurrentPeriodEnd()
    {
        return $this->get('currentPeriodEnd');
    }

    public function getCanceledAt()
    {
        return $this->get('canceledAt');
    }

    public function getEndedAt()
    {
        return $this->get('endedAt');
    }

    public function getTrialStart()
    {
        return $this->get('trialStart');
    }

    public function getTrialEnd()
    {
        return $this->get('trialEnd');
    }

    public function getCancelAtPeriodEnd()
    {
        return $this->get('cancelAtPeriodEnd');
    }

    /**
     * @return \SecurionPay\Response\Shipping
     */
    public function getShipping()
    {
        return $this->getObject('shipping', '\SecurionPay\Response\Shipping');
    }

    /**
     * @return \SecurionPay\Response\Billing
     */
    public function getBilling()
    {
        return $this->getObject('billing', '\SecurionPay\Response\Billing');
    }

    /**
     * @return \SecurionPay\Response\ThreeDSecureInfo
     */
    public function getThreeDSecureInfo()
    {
        return $this->getObject('threeDSecureInfo', '\SecurionPay\Response\ThreeDSecureInfo');
    }
    
    /**
     * @return \SecurionPay\Response\ChargeFromCrossSale
     */
    public function getFromCrossSale()
    {
        return $this->getObject('fromCrossSale', '\SecurionPay\Response\ChargeFromCrossSale');
    }

    public function getMetadata()
    {
        return $this->get('metadata');
    }
}
