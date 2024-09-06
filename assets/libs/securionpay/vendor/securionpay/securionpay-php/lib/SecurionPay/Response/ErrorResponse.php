<?php
namespace SecurionPay\Response;

class ErrorResponse extends AbstractResponse
{

    public function getMessage()
    {
        return $this->getObject('error')->get('message');
    }

    public function getType()
    {
        return $this->getObject('error')->get('type');
    }

    public function getCode()
    {
        return $this->getObject('error')->get('code');
    }
    
    public function getIssuerDeclineCode()
    {
        return $this->getObject('error')->get('issuerDeclineCode');
    }
    
    public function getChargeId()
    {
        return $this->getObject('error')->get('chargeId');
    }

    public function getCreditId()
    {
        return $this->getObject('error')->get('creditId');
    }
    
    public function getBlacklistRuleId()
    {
        return $this->getObject('error')->get('blacklistRuleId');
    }
}
