<?php
namespace SecurionPay\Request;

class CheckoutRequestCustomAmount extends AbstractRequest
{

    public function getMin()
    {
        return $this->get('min');
    }

    public function min($min)
    {
        return $this->set('min', $min);
    }

    public function getMax()
    {
        return $this->get('max');
    }

    public function max($max)
    {
        return $this->set('max', $max);
    }
}
