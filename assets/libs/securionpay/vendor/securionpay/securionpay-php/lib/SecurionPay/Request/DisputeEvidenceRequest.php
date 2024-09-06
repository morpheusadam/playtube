<?php
namespace SecurionPay\Request;

class DisputeEvidenceRequest extends AbstractRequest
{

    public function getProductDescription()
    {
        return $this->get('productDescription');
    }
    
    public function productDescription($productDescription)
    {
        return $this->set('productDescription', $productDescription);
    }

    public function getCustomerEmail()
    {
        return $this->get('customerEmail');
    }
    
    public function customerEmail($customerEmail)
    {
        return $this->set('customerEmail', $customerEmail);
    }
    
    public function getCustomerPurchaseIp()
    {
        return $this->get('customerPurchaseIp');
    }
    
    public function customerPurchaseIp($customerPurchaseIp)
    {
        return $this->set('customerPurchaseIp', $customerPurchaseIp);
    }
    
    public function getCustomerSignature()
    {
        return $this->get('customerSignature');
    }
    
    public function customerSignature($customerSignature)
    {
        return $this->set('customerSignature', $customerSignature);
    }
    
    public function getBillingAddress()
    {
        return $this->get('billingAddress');
    }
    
    public function billingAddress($billingAddress)
    {
        return $this->set('billingAddress', $billingAddress);
    }
    
    public function getReceipt()
    {
        return $this->get('receipt');
    }
    
    public function receipt($receipt)
    {
        return $this->set('receipt', $receipt);
    }
    
    public function getCustomerCommunication()
    {
        return $this->get('customerCommunication');
    }
    
    public function customerCommunication($customerCommunication)
    {
        return $this->set('customerCommunication', $customerCommunication);
    }
    
    public function getServiceDate()
    {
        return $this->get('serviceDate');
    }
    
    public function serviceDate($serviceDate)
    {
        return $this->set('serviceDate', $serviceDate);
    }
    
    public function getServiceDocumentation()
    {
        return $this->get('serviceDocumentation');
    }
    
    public function serviceDocumentation($serviceDocumentation)
    {
        return $this->set('serviceDocumentation', $serviceDocumentation);
    }
    
    public function getDuplicateChargeId()
    {
        return $this->get('duplicateChargeId');
    }
    
    public function duplicateChargeId($duplicateChargeId)
    {
        return $this->set('duplicateChargeId', $duplicateChargeId);
    }
    
    public function getDuplicateChargeDocumentation()
    {
        return $this->get('duplicateChargeDocumentation');
    }
    
    public function duplicateChargeDocumentation($duplicateChargeDocumentation)
    {
        return $this->set('duplicateChargeDocumentation', $duplicateChargeDocumentation);
    }
    
    public function getDuplicateChargeExplanation()
    {
        return $this->get('duplicateChargeExplanation');
    }
    
    public function duplicateChargeExplanation($duplicateChargeExplanation)
    {
        return $this->set('duplicateChargeExplanation', $duplicateChargeExplanation);
    }
    
    public function getRefundPolicy()
    {
        return $this->get('refundPolicy');
    }
    
    public function refundPolicy($refundPolicy)
    {
        return $this->set('refundPolicy', $refundPolicy);
    }
    
    public function getRefundPolicyDisclosure()
    {
        return $this->get('refundPolicyDisclosure');
    }
    
    public function refundPolicyDisclosure($refundPolicyDisclosure)
    {
        return $this->set('refundPolicyDisclosure', $refundPolicyDisclosure);
    }
    
    public function getRefundRefusalExplanation()
    {
        return $this->get('refundRefusalExplanation');
    }
    
    public function refundRefusalExplanation($refundRefusalExplanation)
    {
        return $this->set('refundRefusalExplanation', $refundRefusalExplanation);
    }
    
    public function getCancellationPolicy()
    {
        return $this->get('cancellationPolicy');
    }
    
    public function cancellationPolicy($cancellationPolicy)
    {
        return $this->set('cancellationPolicy', $cancellationPolicy);
    }
    
    public function getCancellationPolicyDisclosure()
    {
        return $this->get('cancellationPolicyDisclosure');
    }
    
    public function cancellationPolicyDisclosure($cancellationPolicyDisclosure)
    {
        return $this->set('cancellationPolicyDisclosure', $cancellationPolicyDisclosure);
    }
    
    public function getCancellationRefusalExplanation()
    {
        return $this->get('cancellationRefusalExplanation');
    }
    
    public function cancellationRefusalExplanation($cancellationRefusalExplanation)
    {
        return $this->set('cancellationRefusalExplanation', $cancellationRefusalExplanation);
    }
    
    public function getAccessActivityLogs()
    {
        return $this->get('accessActivityLogs');
    }
    
    public function accessActivityLogs($accessActivityLogs)
    {
        return $this->set('accessActivityLogs', $accessActivityLogs);
    }
    
    public function getShippingAddress()
    {
        return $this->get('shippingAddress');
    }
    
    public function shippingAddress($shippingAddress)
    {
        return $this->set('shippingAddress', $shippingAddress);
    }
    
    public function getShippingDate()
    {
        return $this->get('shippingDate');
    }
    
    public function shippingDate($shippingDate)
    {
        return $this->set('shippingDate', $shippingDate);
    }
    
    public function getShippingCarrier()
    {
        return $this->get('shippingCarrier');
    }
    
    public function shippingCarrier($shippingCarrier)
    {
        return $this->set('shippingCarrier', $shippingCarrier);
    }
    
    public function getShippingTrackingNumber()
    {
        return $this->get('shippingTrackingNumber');
    }
    
    public function shippingTrackingNumber($shippingTrackingNumber)
    {
        return $this->set('shippingTrackingNumber', $shippingTrackingNumber);
    }
    
    public function getShippingDocumentation()
    {
        return $this->get('shippingDocumentation');
    }
    
    public function shippingDocumentation($shippingDocumentation)
    {
        return $this->set('shippingDocumentation', $shippingDocumentation);
    }
    
    public function getUncategorizedText()
    {
        return $this->get('uncategorizedText');
    }
    
    public function uncategorizedText($uncategorizedText)
    {
        return $this->set('uncategorizedText', $uncategorizedText);
    }
    
    public function getUncategorizedFile()
    {
        return $this->get('uncategorizedFile');
    }
    
    public function uncategorizedFile($uncategorizedFile)
    {
        return $this->set('uncategorizedFile', $uncategorizedFile);
    }
}
