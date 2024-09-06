<?php
namespace SecurionPay\Request;

class CaptureRequest extends AbstractRequest
{
    
	public function getChargeId()
	{
	    return $this->get('chargeId');
	}
	
	public function chargeId($chargeId)
	{
	    return $this->set('chargeId', $chargeId);
	}
}
