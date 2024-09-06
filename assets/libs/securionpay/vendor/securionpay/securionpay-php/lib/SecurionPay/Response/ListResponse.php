<?php
namespace SecurionPay\Response;

class ListResponse extends AbstractResponse
{
	
    private $elementClass;
    
	public function __construct($response, $elementClass)
	{
		parent::__construct($response);
		
		$this->elementClass = $elementClass;
	}

	public function getList()
	{
		return $this->getObjectsList('list', $this->elementClass);
	}

	public function getHasMore()
	{
	    return $this->get('hasMore');
	}
	
	public function getTotalCount()
	{
		return $this->get('totalCount');
	}
}
