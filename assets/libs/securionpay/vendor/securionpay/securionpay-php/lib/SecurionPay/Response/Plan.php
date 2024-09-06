<?php
namespace SecurionPay\Response;

class Plan extends AbstractResponse
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

    public function getAmount()
    {
        return $this->get('amount');
    }

    public function getCurrency()
    {
        return $this->get('currency');
    }

    public function getInterval()
    {
        return $this->get('interval');
    }

    public function getIntervalCount()
    {
        return $this->get('intervalCount');
    }

    public function getBillingCycles()
    {
        return $this->get('billingCycles');
    }

    public function getName()
    {
        return $this->get('name');
    }

    public function getTrialPeriodDays()
    {
        return $this->get('trialPeriodDays');
    }

    public function getRecursTo()
    {
        return $this->get('recursTo');
    }

    public function getStatementDescription()
    {
        return $this->get('statementDescription');
    }

    public function getMetadata()
    {
        return $this->get('metadata');
    }
}
