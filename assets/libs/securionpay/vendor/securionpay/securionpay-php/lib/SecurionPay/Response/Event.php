<?php
namespace SecurionPay\Response;

class Event extends AbstractResponse
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

    public function getType()
    {
        return $this->get('type');
    }

    public function getData()
    {
        $data = $this->get('data');
        
        switch ($data['objectType']) {
            case 'customer':
                return new Customer($data);
            case 'card':
                return new Card($data);
            case 'charge':
                return new Charge($data);
            case 'credit':
                return new Credit($data);
            case 'dispute':
                return new Dispute($data);
            case 'plan':
                return new Plan($data);
            case 'subscription':
                return new Subscription($data);
            case 'crossSaleOffer':
                return new CrossSaleOffer($data);
            default:
                return $data;
        }
    }
    
    public function getLog()
    {
        return $this->get('log');
    }
}
