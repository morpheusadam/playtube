<?php
namespace SecurionPay\Request;

class CheckoutRequest extends AbstractRequest
{

    /**
     * @return \SecurionPay\Request\CheckoutRequestCharge
     */
    public function getCharge()
    {
        return $this->getObject('charge', '\SecurionPay\Request\CheckoutRequestCharge');
    }

    public function charge($charge)
    {
        return $this->set('charge', $charge);
    }

    /**
     * @return \SecurionPay\Request\CheckoutRequestSubscription
     */
    public function getSubscription()
    {
        return $this->getObject('subscription', '\SecurionPay\Request\CheckoutRequestSubscription');
    }

    public function subscription($subscription)
    {
        return $this->set('subscription', $subscription);
    }

    /**
     * @return \SecurionPay\Request\CheckoutRequestCustomCharge
     */
    public function getCustomCharge()
    {
        return $this->getObject('customCharge', '\SecurionPay\Request\CheckoutRequestCustomCharge');
    }

    public function customCharge($customCharge)
    {
        return $this->set('customCharge', $customCharge);
    }

    public function getCustomerId()
    {
        return $this->get('customerId');
    }

    public function customerId($customerId)
    {
        return $this->set('customerId', $customerId);
    }

    public function getCrossSaleOfferIds()
    {
        return $this->get('crossSaleOfferIds');
    }

    public function crossSaleOfferIds($crossSaleOfferIds)
    {
        return $this->set('crossSaleOfferIds', $crossSaleOfferIds);
    }

    public function getRememberMe()
    {
        return $this->get('rememberMe');
    }

    public function rememberMe($rememberMe)
    {
        return $this->set('rememberMe', $rememberMe);
    }

    /**
     * @return \SecurionPay\Request\CheckoutRequestThreeDSecure
     */
    public function getThreeDSecure()
    {
        return $this->getObject('threeDSecure', '\SecurionPay\Request\CheckoutRequestThreeDSecure');
    }
    
    public function threeDSecure($threeDSecure)
    {
        return $this->set('threeDSecure', $threeDSecure);
    }

    public function getTermsAndConditionsUrl()
    {
        return $this->get('termsAndConditionsUrl');
    }
    
    public function termsAndConditionsUrl($termsAndConditionsUrl)
    {
        return $this->set('termsAndConditionsUrl', $termsAndConditionsUrl);
    }
}
