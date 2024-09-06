<?php
namespace SecurionPay\Response;

class DisputeEvidenceDetails extends AbstractResponse
{

    public function __construct($response)
    {
        parent::__construct($response);
    }

    public function getDueBy()
    {
        return $this->get('dueBy');
    }
    
    public function getHasEvidence()
    {
        return $this->get('hasEvidence');
    }
    
    public function getPastDue()
    {
        return $this->get('pastDue');
    }
    
    public function getSubmissionCount()
    {
        return $this->get('submissionCount');
    }
}
