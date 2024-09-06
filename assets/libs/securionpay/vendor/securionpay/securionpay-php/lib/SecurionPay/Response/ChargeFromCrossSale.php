<?php
namespace SecurionPay\Response;

class ChargeFromCrossSale extends AbstractResponse
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
}
