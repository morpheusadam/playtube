<?php
namespace SecurionPay\Request;

class CrossSaleOfferUpdateRequest extends AbstractRequest
{

    public function getCrossSaleOfferId()
    {
        return $this->get('crossSaleOfferId');
    }
    
    public function crossSaleOfferId($crossSaleOfferId)
    {
        return $this->set('crossSaleOfferId', $crossSaleOfferId);
    }

    /**
     * @return \SecurionPay\Request\CrossSaleOfferRequestCharge
     */
    public function getCharge()
    {
        return $this->getObject('charge', '\SecurionPay\Request\CrossSaleOfferRequestCharge');
    }

    public function charge($charge)
    {
        return $this->set('charge', $charge);
    }

    /**
     * @return \SecurionPay\Request\CrossSaleOfferRequestSubscription
     */
    public function getSubscription()
    {
        return $this->getObject('subscription', '\SecurionPay\Request\CrossSaleOfferRequestSubscription');
    }

    public function subscription($subscription)
    {
        return $this->set('subscription', $subscription);
    }

    public function getTemplate()
    {
        return $this->get('template');
    }

    public function template($template)
    {
        return $this->set('template', $template);
    }

    public function getTitle()
    {
        return $this->get('title');
    }

    public function title($title)
    {
        return $this->set('title', $title);
    }

    public function getDescription()
    {
        return $this->get('description');
    }

    public function description($description)
    {
        return $this->set('description', $description);
    }

    public function getImageData()
    {
        return $this->get('imageData');
    }

    public function imageData($imageData)
    {
        return $this->set('imageData', $imageData);
    }

    public function getCompanyName()
    {
        return $this->get('companyName');
    }

    public function companyName($companyName)
    {
        return $this->set('companyName', $companyName);
    }

    public function getCompanyLocation()
    {
        return $this->get('companyLocation');
    }

    public function companyLocation($companyLocation)
    {
        return $this->set('companyLocation', $companyLocation);
    }

    public function getTermsAndConditionsUrl()
    {
        return $this->get('termsAndConditionsUrl');
    }

    public function termsAndConditionsUrl($termsAndConditionsUrl)
    {
        return $this->set('termsAndConditionsUrl', $termsAndConditionsUrl);
    }

    public function getUrl()
    {
        return $this->get('url');
    }
    
    public function url($url)
    {
        return $this->set('url', $url);
    }
    
    public function getVisibleForAllPartners()
    {
        return $this->get('visibleForAllPartners');
    }

    public function visibleForAllPartners($visibleForAllPartners)
    {
        return $this->set('visibleForAllPartners', $visibleForAllPartners);
    }

    public function getVisibleForPartnerIds()
    {
        return $this->get('visibleForPartnerIds');
    }

    public function visibleForPartnerIds($visibleForPartnerIds)
    {
        return $this->set('visibleForPartnerIds', $visibleForPartnerIds);
    }

    public function getMetadata()
    {
        return $this->get('metadata');
    }

    public function metadata($metadata)
    {
        return $this->set('metadata', $metadata);
    }
}
