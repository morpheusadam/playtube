<?php
namespace SecurionPay\Request;

class CreditRequest extends AbstractRequest
{

    public function getAmount()
    {
        return $this->get('amount');
    }

    public function amount($amount)
    {
        return $this->set('amount', $amount);
    }

    public function getCurrency()
    {
        return $this->get('currency');
    }
    
    public function currency($currency)
    {
        return $this->set('currency', $currency);
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
     * @return \SecurionPay\Request\CardRequest
     */
    public function getCard()
    {
        $card = $this->get('card');
        
        if (is_array($card)) {
            return $this->getObject('card', '\SecurionPay\Request\CardRequest');
        } else {
            return $card;
        }
    }
    
    public function card($card)
    {
        return $this->set('card', $card);
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
