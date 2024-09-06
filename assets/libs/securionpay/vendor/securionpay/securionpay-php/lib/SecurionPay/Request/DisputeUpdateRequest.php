<?php
namespace SecurionPay\Request;

class DisputeUpdateRequest extends AbstractRequest
{
    
    public function getDisputeId()
    {
        return $this->get('disputeId');
    }
    
    public function disputeId($disputeId)
    {
        return $this->set('disputeId', $disputeId);
    }
    
    /**
     * @return \SecurionPay\Request\DisputeEvidenceRequest
     */
    public function getEvidence()
    {
        return $this->getObject('evidence', '\SecurionPay\Request\DisputeEvidenceRequest');
    }

    public function evidence($evidence)
    {
        return $this->set('evidence', $evidence);
    }
}
