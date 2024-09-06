<?php
namespace SecurionPay\Response;

class FileUpload extends AbstractResponse
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
    
    public function getPurpose()
    {
        return $this->get('purpose');
    }
    
    public function getSize()
    {
        return $this->get('size');
    }
    
    public function getType()
    {
        return $this->get('type');
    }
    
    public function getUrl()
    {
        return $this->get('url');
    }
}
