<?php
namespace SecurionPay\Request;

use SecurionPay\Exception\MappingException;

abstract class AbstractRequest {

	private $data = array();

	public function __construct($dataArray = null) {
		if (is_array($dataArray)) {
			$this->data = $dataArray;
		} elseif ($dataArray !== null) {
			throw new MappingException('Constructor parameter must be an array');
		}
	}

	public function toArray() {
		$array = $this->data;

		foreach ($array as $key => $value) {
			if ($value instanceof AbstractRequest) {
				$array[$key] = $value->toArray();
			}
		}
		
		return $array;
	}

	public function get($field, $default = null) {
		if (!isset($this->data[$field])) {
			return $default;
		}

		return $this->data[$field];
	}

    public function getObject($field, $className)
    {
        if (!array_key_exists($field, $this->data)) {
            return null;
        }
        
        $object = new $className();
        $object->data = & $this->data[$field];
        
        return $object;
    }

	public function set($field, $value) {
	    if ($value instanceof AbstractRequest) {
	        $value = $value->toArray();
	    }
	    
	    if ($value === null) {
	        unset($this->data[$field]);
	        return $this;
	    }
	    
		$this->data[$field] = $value;
        return $this;
	}
}
