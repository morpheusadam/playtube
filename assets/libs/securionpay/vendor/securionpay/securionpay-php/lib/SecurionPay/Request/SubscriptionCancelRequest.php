<?php
namespace SecurionPay\Request;

class SubscriptionCancelRequest extends AbstractRequest
{

    public function getSubscriptionId()
    {
        return $this->get('subscriptionId');
    }

    public function subscriptionId($subscriptionId)
    {
        return $this->set('subscriptionId', $subscriptionId);
    }

    public function getCustomerId()
    {
        return $this->get('customerId');
    }

    public function customerId($customerId)
    {
        return $this->set('customerId', $customerId);
    }

    public function getAtPeriodEnd()
    {
        return $this->get('atPeriodEnd');
    }

    public function atPeriodEnd($atPeriodEnd)
    {
        return $this->set('atPeriodEnd', $atPeriodEnd);
    }
}
