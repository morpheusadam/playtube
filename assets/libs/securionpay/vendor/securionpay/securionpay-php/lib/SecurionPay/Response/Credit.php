<?php
namespace SecurionPay\Response;

class Credit extends AbstractResponse
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
    
    public function getAmount()
    {
        return $this->get('amount');
    }
    
    public function getCurrency()
    {
        return $this->get('currency');
    }
    
    public function getDescription()
    {
        return $this->get('description');
    }
    
    /**
     * @return \SecurionPay\Response\Card
     */
    public function getCard()
    {
        return $this->getObject('card', '\SecurionPay\Response\Card');
    }
    
    public function getCustomerId()
    {
        return $this->get('customerId');
    }
    
    /**
     * @return \SecurionPay\Response\ThreeDSecureInfo
     */
    public function getThreeDSecureInfo()
    {
        return $this->getObject('threeDSecureInfo', '\SecurionPay\Response\ThreeDSecureInfo');
    }
    
    public function getMetadata()
    {
        return $this->get('metadata');
    }

    public function getFailureCode()
    {
        return $this->get('failureCode');
    }

    public function getFailureIssuerDeclineCode()
    {
        return $this->get('failureIssuerDeclineCode');
    }
    
    public function getFailureMessage()
    {
        return $this->get('failureMessage');
    }

    public function getFast()
    {
        return $this->get('fast');
    }
}
