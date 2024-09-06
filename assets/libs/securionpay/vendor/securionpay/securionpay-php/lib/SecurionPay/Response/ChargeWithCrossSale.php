<?php
namespace SecurionPay\Response;

class ChargeWithCrossSale extends AbstractResponse
{

    public function __construct($response)
    {
        parent::__construct($response);
    }

    public function getOfferId()
    {
        return $this->get('offerId');
    }

    public function getPartnerId()
    {
        return $this->get('partnerId');
    }

    public function getChargeId()
    {
        return $this->get('chargeId');
    }

    public function getAmount()
    {
        return $this->get('amount');
    }

    public function getCurrency()
    {
        return $this->get('currency');
    }
    
    public function getSubscriptionId()
    {
        return $this->get('subscriptionId');
    }
}
