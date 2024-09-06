<?php
namespace SecurionPay\Request;

class SubscriptionRequest extends AbstractRequest
{

    public function getCustomerId()
    {
        return $this->get('customerId');
    }

    public function customerId($customerId)
    {
        return $this->set('customerId', $customerId);
    }

    public function getPlanId()
    {
        return $this->get('planId');
    }

    public function planId($planId)
    {
        return $this->set('planId', $planId);
    }

    /**
     * @return \SecurionPay\Request\CardRequest
     */
    public function getCard()
    {
        return $this->getObject('card', '\SecurionPay\Request\CardRequest');
    }

    public function card($card)
    {
        return $this->set('card', $card);
    }

    public function getQuantity()
    {
        return $this->get('quantity');
    }

    public function quantity($quantity)
    {
        return $this->set('quantity', $quantity);
    }

    public function getCaptureCharges()
    {
        return $this->get('captureCharges');
    }

    public function captureCharges($captureCharges)
    {
        return $this->set('captureCharges', $captureCharges);
    }

    public function getTrialEnd()
    {
        return $this->get('trialEnd');
    }

    public function trialEnd($trialEnd)
    {
        return $this->set('trialEnd', $trialEnd);
    }

    /**
     * @return \SecurionPay\Request\ShippingRequest
     */
    public function getShipping()
    {
        return $this->getObject('shipping', '\SecurionPay\Request\ShippingRequest');
    }

    public function shipping($shipping)
    {
        return $this->set('shipping', $shipping);
    }

    /**
     * @return \SecurionPay\Request\BillingRequest
     */
    public function getBilling()
    {
        return $this->getObject('billing', '\SecurionPay\Request\BillingRequest');
    }

    public function billing($billing)
    {
        return $this->set('billing', $billing);
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
