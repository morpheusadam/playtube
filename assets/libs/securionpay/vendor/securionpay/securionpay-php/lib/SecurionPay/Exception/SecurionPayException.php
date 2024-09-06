<?php
namespace SecurionPay\Exception;

use SecurionPay\Response\ErrorResponse;

class SecurionPayException extends \Exception
{
    private $type;

    private $issuerDeclineCode;
    private $chargeId;
    private $creditId;
    private $blacklistRuleId;
    
    public function __construct($error = null)
    {
        if ($error instanceof ErrorResponse) {
            parent::__construct($error->getMessage());
            
            $this->type = $error->getType();
            $this->code = $error->getCode();
            $this->issuerDeclineCode = $error->getIssuerDeclineCode();
            $this->chargeId = $error->getChargeId();
            $this->creditId = $error->getCreditId();
            $this->blacklistRuleId = $error->getBlacklistRuleId();
        } else {
            parent::__construct($error);
        }
    }

    public function getType()
    {
        return $this->type;
    }
    
    public function getIssuerDeclineCode()
    {
        return $this->issuerDeclineCode;
    }

    public function getChargeId()
    {
        return $this->chargeId;
    }

    public function getCreditId()
    {
        return $this->creditId;
    }
    
    public function getBlacklistRuleId()
    {
        return $this->blacklistRuleId;
    }
}
