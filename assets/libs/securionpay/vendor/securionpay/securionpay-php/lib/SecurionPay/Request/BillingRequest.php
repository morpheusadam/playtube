<?php
namespace SecurionPay\Request;

class BillingRequest extends AbstractRequest
{

    public function getName()
    {
        return $this->get('name');
    }

    public function name($name)
    {
        return $this->set('name', $name);
    }

    /**
     * @return \SecurionPay\Request\AddressRequest
     */
    public function getAddress()
    {
        return $this->getObject('address', '\SecurionPay\Request\AddressRequest');
    }

    public function address($address)
    {
        return $this->set('address', $address);
    }

    public function getVat()
    {
        return $this->get('vat');
    }

    public function vat($vat)
    {
        return $this->set('vat', $vat);
    }
}
