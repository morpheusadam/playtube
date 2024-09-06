<?php
namespace SecurionPay\Response;

class Dispute extends AbstractResponse
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

    public function getUpdated()
    {
        return $this->get('updated');
    }
    
    public function getAmount()
    {
        return $this->get('amount');
    }
    
    public function getCurrency()
    {
        return $this->get('currency');
    }
    
    public function getStatus()
    {
        return $this->get('status');
    }
    
    public function getReason()
    {
        return $this->get('reason');
    }
    
    public function getAcceptedAsLost()
    {
        return $this->get('acceptedAsLost');
    }
    
    /**
     * @return \SecurionPay\Response\DisputeEvidence
     */
    public function getEvidence()
    {
        return $this->getObject('evidence', '\SecurionPay\Response\DisputeEvidence');
    }
    
    /**
     * @return \SecurionPay\Response\DisputeEvidenceDetails
     */
    public function getEvidenceDetails()
    {
        return $this->getObject('evidenceDetails', '\SecurionPay\Response\DisputeEvidenceDetails');
    }
    
    /**
     * @return \SecurionPay\Response\Charge
     */
    public function getCharge()
    {
        return $this->getObject('charge', '\SecurionPay\Response\Charge');
    }
}
