<?php
namespace SecurionPay\Request;

class CreditUpdateRequest extends AbstractRequest
{

    public function getCreditId()
    {
        return $this->get('creditId');
    }
    
    public function creditId($creditId)
    {
        return $this->set('creditId', $creditId);
    }
    
    public function getCustomerId()
    {
        return $this->get('customerId');
    }

    public function customerId($customerId)
    {
        return $this->set('customerId', $customerId);
    }

    public function getDescription()
    {
        return $this->get('description');
    }
    
    public function description($description)
    {
        return $this->set('description', $description);
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
