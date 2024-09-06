<?php
namespace SecurionPay\Response;

class Charge extends AbstractResponse
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

    public function getAmountRefunded()
    {
        return $this->get('amountRefunded');
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

    public function getSubscriptionId()
    {
        return $this->get('subscriptionId');
    }
    
    public function getCaptured()
    {
        return $this->get('captured');
    }

    public function getRefunded()
    {
        return $this->get('refunded');
    }

    /**
     * @return \SecurionPay\Response\Refund[]
     */
    public function getRefunds()
    {
        return $this->getObjectsList('refunds', '\SecurionPay\Response\Refund');
    }

    public function getDisputed()
    {
        return $this->get('disputed');
    }

    /**
     * @return \SecurionPay\Response\FraudDetails
     */
    public function getFraudDetails()
    {
        return $this->getObject('fraudDetails', '\SecurionPay\Response\FraudDetails');
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

    /**
     * @return \SecurionPay\Response\ChargeWithCrossSale[]
     */
    public function getWithCrossSales()
    {
        return $this->getObjectsList('withCrossSales', '\SecurionPay\Response\ChargeWithCrossSale');
    }

    /**
     * @return \SecurionPay\Response\Dispute
     */
    public function getDispute()
    {
        return $this->getObject('dispute', '\SecurionPay\Response\Dispute');
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
}
