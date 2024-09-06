<?php
namespace SecurionPay\Request;

class CardRequest extends AbstractRequest
{

    public function getId()
    {
        return $this->get('id');
    }

    public function id($id)
    {
        return $this->set('id', $id);
    }

    public function getCustomerId()
    {
        return $this->get('customerId');
    }

    public function customerId($customerId)
    {
        return $this->set('customerId', $customerId);
    }

    public function getNumber()
    {
        return $this->get('number');
    }

    public function number($number)
    {
        return $this->set('number', $number);
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

    public function getCvc()
    {
        return $this->get('cvc');
    }

    public function cvc($cvc)
    {
        return $this->set('cvc', $cvc);
    }

    public function getCardholderName()
    {
        return $this->get('cardholderName');
    }

    public function cardholderName($cardholderName)
    {
        return $this->set('cardholderName', $cardholderName);
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

    public function getAddressCountry()
    {
        return $this->get('addressCountry');
    }

    public function addressCountry($addressCountry)
    {
        return $this->set('addressCountry', $addressCountry);
    }

    /**
     * @return \SecurionPay\Request\FraudCheckDataRequest
     */
    public function getFraudCheckData()
    {
        return $this->getObject('fraudCheckData', '\SecurionPay\Request\FraudCheckDataRequest');
    }

    public function fraudCheckData($fraudCheckData)
    {
        return $this->set('fraudCheckData', $fraudCheckData);
    }
}
