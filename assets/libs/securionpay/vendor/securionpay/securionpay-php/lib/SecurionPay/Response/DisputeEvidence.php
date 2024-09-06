<?php
namespace SecurionPay\Response;

class DisputeEvidence extends AbstractResponse
{

    public function __construct($response)
    {
        parent::__construct($response);
    }

    public function getProductDescription()
    {
        return $this->get('productDescription');
    }
    
    public function getCustomerEmail()
    {
        return $this->get('customerEmail');
    }
    
    public function getCustomerPurchaseIp()
    {
        return $this->get('customerPurchaseIp');
    }
    
    public function getCustomerSignature()
    {
        return $this->get('customerSignature');
    }
    
    public function getBillingAddress()
    {
        return $this->get('billingAddress');
    }
    
    public function getReceipt()
    {
        return $this->get('receipt');
    }
    
    public function getCustomerCommunication()
    {
        return $this->get('customerCommunication');
    }
    
    public function getServiceDate()
    {
        return $this->get('serviceDate');
    }
    
    public function getServiceDocumentation()
    {
        return $this->get('serviceDocumentation');
    }
    
    public function getDuplicateChargeId()
    {
        return $this->get('duplicateChargeId');
    }
    
    public function getDuplicateChargeDocumentation()
    {
        return $this->get('duplicateChargeDocumentation');
    }
    
    public function getDuplicateChargeExplanation()
    {
        return $this->get('duplicateChargeExplanation');
    }
    
    public function getRefundPolicy()
    {
        return $this->get('refundPolicy');
    }
    
    public function getRefundPolicyDisclosure()
    {
        return $this->get('refundPolicyDisclosure');
    }
    
    public function getRefundRefusalExplanation()
    {
        return $this->get('refundRefusalExplanation');
    }
    
    public function getCancellationPolicy()
    {
        return $this->get('cancellationPolicy');
    }
    
    public function getCancellationPolicyDisclosure()
    {
        return $this->get('cancellationPolicyDisclosure');
    }
    
    public function getCancellationRefusalExplanation()
    {
        return $this->get('cancellationRefusalExplanation');
    }
    
    public function getAccessActivityLogs()
    {
        return $this->get('accessActivityLogs');
    }
    
    public function getShippingAddress()
    {
        return $this->get('shippingAddress');
    }
    
    public function getShippingDate()
    {
        return $this->get('shippingDate');
    }
    
    public function getShippingCarrier()
    {
        return $this->get('shippingCarrier');
    }
    
    public function getShippingTrackingNumber()
    {
        return $this->get('shippingTrackingNumber');
    }
    
    public function getShippingDocumentation()
    {
        return $this->get('shippingDocumentation');
    }
    
    public function getUncategorizedText()
    {
        return $this->get('uncategorizedText');
    }
    
    public function getUncategorizedFile()
    {
        return $this->get('uncategorizedFile');
    }
}
