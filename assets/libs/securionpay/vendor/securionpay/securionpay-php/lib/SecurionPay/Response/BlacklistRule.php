<?php
namespace SecurionPay\Response;

class BlacklistRule extends AbstractResponse
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

    public function getRuleType()
    {
        return $this->get('ruleType');
    }

    public function getFingerprint()
    {
        return $this->get('fingerprint');
    }

    public function getIpAddress()
    {
        return $this->get('ipAddress');
    }

    public function getIpCountry()
    {
        return $this->get('ipCountry');
    }

    public function getMetadataKey()
    {
        return $this->get('metadataKey');
    }

    public function getMetadataValue()
    {
        return $this->get('metadataValue');
    }

    public function getEmail()
    {
        return $this->get('email');
    }

    public function getUserAgent()
    {
        return $this->get('userAgent');
    }

    public function getAcceptLanguage()
    {
        return $this->get('acceptLanguage');
    }
    
    public function getCardCountry()
    {
        return $this->get('cardCountry');
    }
    
    public function getCardBin()
    {
        return $this->get('cardBin');
    }
    
    public function getCardIssuer()
    {
        return $this->get('cardIssuer');
    }
}
