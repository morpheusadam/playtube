<?php
namespace SecurionPay\Request;

class ChargeUpdateRequest extends AbstractRequest
{

    public function getChargeId()
    {
        return $this->get('chargeId');
    }

    public function chargeId($chargeId)
    {
        return $this->set('chargeId', $chargeId);
    }

    public function getDescription()
    {
        return $this->get('description');
    }

    public function description($description)
    {
        return $this->set('description', $description);
    }

    public function getCustomerId()
    {
        return $this->get('customerId');
    }

    public function customerId($customerId)
    {
        return $this->set('customerId', $customerId);
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
     * @return \SecurionPay\Request\FraudDetailsRequest
     */
    public function getFraudDetails()
    {
        return $this->getObject('fraudDetails', '\SecurionPay\Request\FraudDetailsRequest');
    }

    public function fraudDetails($fraudDetails)
    {
        return $this->set('fraudDetails', $fraudDetails);
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
