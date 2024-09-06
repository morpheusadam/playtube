<?php
namespace SecurionPay\Response;

class FraudDetails extends AbstractResponse
{
	
	public function __construct($response)
	{
		parent::__construct($response);
	}

	public function getStatus()
	{
	    return $this->get('status');
	}

	public function getScore()
    {
        return $this->get('score');
    }
}
