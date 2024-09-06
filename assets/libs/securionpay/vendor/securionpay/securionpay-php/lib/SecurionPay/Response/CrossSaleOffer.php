<?php
namespace SecurionPay\Response;

class CrossSaleOffer extends AbstractResponse
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

    public function getDeleted()
    {
        return $this->get('deleted', false);
    }

    /**
     * @return \SecurionPay\Response\CrossSaleOfferCharge
     */
    public function getCharge()
    {
        return $this->getObject('charge', '\SecurionPay\Response\CrossSaleOfferCharge');
    }

    /**
     * @return \SecurionPay\Response\CrossSaleOfferSubscription
     */
    public function getSubscription()
    {
        return $this->getObject('subscription', '\SecurionPay\Response\CrossSaleOfferSubscription');
    }

    public function getTemplate()
    {
        return $this->get('template');
    }

    public function getTitle()
    {
        return $this->get('title');
    }

    public function getDescription()
    {
        return $this->get('description');
    }

    public function getImageUrl()
    {
        return $this->get('imageUrl');
    }

    public function getCompanyName()
    {
        return $this->get('companyName');
    }

    public function getCompanyLocation()
    {
        return $this->get('companyLocation');
    }

    public function getTermsAndConditionsUrl()
    {
        return $this->get('termsAndConditionsUrl');
    }

    public function getPartnerId()
    {
        return $this->get('partnerId');
    }

    public function getVisibleForAllPartners()
    {
        return $this->get('visibleForAllPartners');
    }

    /**
     * @return string[]
     */
    public function getVisibleForPartnerIds()
    {
        return $this->get('visibleForPartnerIds');
    }

    public function getUrl()
    {
        return $this->get('url');
    }
    
    public function getMetadata()
    {
        return $this->get('metadata');
    }
}
