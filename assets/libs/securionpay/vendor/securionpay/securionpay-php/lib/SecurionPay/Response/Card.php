<?php
namespace SecurionPay\Response;

class Card extends AbstractResponse
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

    public function getDeleted()
    {
        return $this->get('deleted', false);
    }

    public function getFirst6()
    {
        return $this->get('first6');
    }

    public function getLast4()
    {
        return $this->get('last4');
    }

    public function getFingerprint()
    {
        return $this->get('fingerprint');
    }

    public function getExpMonth()
    {
        return $this->get('expMonth');
    }

    public function getExpYear()
    {
        return $this->get('expYear');
    }

    public function getCardholderName()
    {
        return $this->get('cardholderName');
    }

    public function getCustomerId()
    {
        return $this->get('customerId');
    }

    public function getBrand()
    {
        return $this->get('brand');
    }

    public function getType()
    {
        return $this->get('type');
    }

    public function getCountry()
    {
        return $this->get('country');
    }

    public function getAddressLine1()
    {
        return $this->get('addressLine1');
    }

    public function getAddressLine2()
    {
        return $this->get('addressLine2');
    }

    public function getAddressCity()
    {
        return $this->get('addressCity');
    }

    public function getAddressState()
    {
        return $this->get('addressState');
    }

    public function getAddressZip()
    {
        return $this->get('addressZip');
    }

    public function getAddressCountry()
    {
        return $this->get('addressCountry');
    }

    /**
     * @return \SecurionPay\Response\FraudCheckData
     */
    public function getFraudCheckData()
    {
        return $this->getObject('fraudCheckData', '\SecurionPay\Response\FraudCheckData');
    }

    /**
     * @return \SecurionPay\Response\FastCredit
     */
    public function getFastCredit()
    {
        return $this->getObject('fastCredit', '\SecurionPay\Response\FastCredit');
    }
}
