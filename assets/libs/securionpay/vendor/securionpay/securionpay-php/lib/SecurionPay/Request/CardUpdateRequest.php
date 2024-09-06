<?php
namespace SecurionPay\Request;

class CardUpdateRequest extends AbstractRequest
{

    public function getCardId()
    {
        return $this->get('cardId');
    }

    public function cardId($cardId)
    {
        return $this->set('cardId', $cardId);
    }

    public function getCustomerId()
    {
        return $this->get('customerId');
    }

    public function customerId($customerId)
    {
        return $this->set('customerId', $customerId);
    }

    public function getExpMonth()
    {
        return $this->get('expMonth');
    }

    public function expMonth($expMonth)
    {
        return $this->set('expMonth', $expMonth);
    }

    public function getExpYear()
    {
        return $this->get('expYear');
    }

    public function expYear($expYear)
    {
        return $this->set('expYear', $expYear);
    }

    public function getCardholderName()
    {
        return $this->get('cardholderName');
    }

    public function cardholderName($cardholderName)
    {
        return $this->set('cardholderName', $cardholderName);
    }

    public function getAddressCountry()
    {
        return $this->get('addressCountry');
    }

    public function addressCountry($addressCountry)
    {
        return $this->set('addressCountry', $addressCountry);
    }

    public function getAddressCity()
    {
        return $this->get('addressCity');
    }

    public function addressCity($addressCity)
    {
        return $this->set('addressCity', $addressCity);
    }

    public function getAddressState()
    {
        return $this->get('addressState');
    }

    public function addressState($addressState)
    {
        return $this->set('addressState', $addressState);
    }

    public function getAddressZip()
    {
        return $this->get('addressZip');
    }

    public function addressZip($addressZip)
    {
        return $this->set('addressZip', $addressZip);
    }

    public function getAddressLine1()
    {
        return $this->get('addressLine1');
    }

    public function addressLine1($addressLine1)
    {
        return $this->set('addressLine1', $addressLine1);
    }

    public function getAddressLine2()
    {
        return $this->get('addressLine2');
    }

    public function addressLine2($addressLine2)
    {
        return $this->set('addressLine2', $addressLine2);
    }

    public function checkFastCredit($checkFastCredit)
    {
        return $this->set('checkFastCredit', $checkFastCredit);
    }

    public function getCheckFastCredit($checkFastCredit)
    {
        return $this->get('checkFastCredit');
    }
}
