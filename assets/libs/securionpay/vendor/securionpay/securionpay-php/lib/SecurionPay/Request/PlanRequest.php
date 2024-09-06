<?php
namespace SecurionPay\Request;

class PlanRequest extends AbstractRequest
{

    public function getAmount()
    {
        return $this->get('amount');
    }

    public function amount($amount)
    {
        return $this->set('amount', $amount);
    }

    public function getCurrency()
    {
        return $this->get('currency');
    }

    public function currency($currency)
    {
        return $this->set('currency', $currency);
    }

    public function getInterval()
    {
        return $this->get('interval');
    }

    public function interval($interval)
    {
        return $this->set('interval', $interval);
    }

    public function getIntervalCount()
    {
        return $this->get('intervalCount');
    }

    public function intervalCount($intervalCount)
    {
        return $this->set('intervalCount', $intervalCount);
    }

    public function getName()
    {
        return $this->get('name');
    }

    public function name($name)
    {
        return $this->set('name', $name);
    }

    public function getTrialPeriodDays()
    {
        return $this->get('trialPeriodDays');
    }

    public function trialPeriodDays($trialPeriodDays)
    {
        return $this->set('trialPeriodDays', $trialPeriodDays);
    }

    public function getStatementDescription()
    {
        return $this->get('statementDescription');
    }

    public function statementDescription($statementDescription)
    {
        return $this->set('statementDescription', $statementDescription);
    }

    public function getMetadata()
    {
        return $this->get('metadata');
    }

    public function metadata($metadata)
    {
        return $this->set('metadata', $metadata);
    }

    public function getBillingCycles()
    {
        return $this->get('billingCycles');
    }

    public function billingCycles($billingCycles)
    {
        return $this->set('billingCycles', $billingCycles);
    }

    public function getRecursTo()
    {
        return $this->get('recursTo');
    }

    public function recursTo($recursTo)
    {
        return $this->set('recursTo', $recursTo);
    }
}
