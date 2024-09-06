<?php
namespace SecurionPay\Response;

class DeleteResponse extends AbstractResponse {
	
	public function __construct($response) {
		parent::__construct($response);
	}

	public function getId() {
		return $this->get('id');
	}
}
