<?php
namespace SecurionPay\Request;

class BlacklistRuleRequest extends AbstractRequest
{

    public function getRuleType()
    {
        return $this->get('ruleType');
    }

    public function ruleType($ruleType)
    {
        return $this->set('ruleType', $ruleType);
    }

    public function getFingerprint()
    {
        return $this->get('fingerprint');
    }

    public function fingerprint($fingerprint)
    {
        return $this->set('fingerprint', $fingerprint);
    }

    public function getCardNumber()
    {
        return $this->get('cardNumber');
    }

    public function cardNumber($cardNumber)
    {
        return $this->set('cardNumber', $cardNumber);
    }

    public function getIpAddress()
    {
        return $this->get('ipAddress');
    }

    public function ipAddress($ipAddress)
    {
        return $this->set('ipAddress', $ipAddress);
    }

    public function getIpCountry()
    {
        return $this->get('ipCountry');
    }

    public function ipCountry($ipCountry)
    {
        return $this->set('ipCountry', $ipCountry);
    }

    public function getMetadataKey()
    {
        return $this->get('metadataKey');
    }

    public function metadataKey($metadataKey)
    {
        return $this->set('metadataKey', $metadataKey);
    }

    public function getMetadataValue()
    {
        return $this->get('metadataValue');
    }

    public function metadataValue($metadataValue)
    {
        return $this->set('metadataValue', $metadataValue);
    }

    public function getEmail()
    {
        return $this->get('email');
    }

    public function email($email)
    {
        return $this->set('email', $email);
    }

    public function getUserAgent()
    {
        return $this->get('userAgent');
    }

    public function userAgent($userAgent)
    {
        return $this->set('userAgent', $userAgent);
    }

    public function getAcceptLanguage()
    {
        return $this->get('acceptLanguage');
    }

    public function acceptLanguage($acceptLanguage)
    {
        return $this->set('acceptLanguage', $acceptLanguage);
    }
    
    public function getCardCountry()
    {
        return $this->get('cardCountry');
    }
    
    public function cardCountry($cardCountry)
    {
        return $this->set('cardCountry', $cardCountry);
    }
    
    public function getCardBin()
    {
        return $this->get('cardBin');
    }
    
    public function cardBin($cardBin)
    {
        return $this->set('cardBin', $cardBin);
    }
    
    public function getCardIssuer()
    {
        return $this->get('cardIssuer');
    }
    
    public function cardIssuer($cardIssuer)
    {
        return $this->set('cardIssuer', $cardIssuer);
    }
}
