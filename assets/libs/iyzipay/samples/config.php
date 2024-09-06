<?php

require_once(dirname(__DIR__).'/IyzipayBootstrap.php');

IyzipayBootstrap::init();
$url = 'https://sandbox-api.iyzipay.com';
if ($pt->config->iyzipay_mode == '0') {
	$url = 'https://api.iyzipay.com';
}

class Config
{
    public static function options()
    {
    	global $pt,$url;
        $options = new \Iyzipay\Options();
        $options->setApiKey($pt->config->iyzipay_key);
        $options->setSecretKey($pt->config->iyzipay_secret_key);
        $options->setBaseUrl($url);

        return $options;
    }
}
$ConversationId = rand(11111111,99999999);
$request = new \Iyzipay\Request\CreateCheckoutFormInitializeRequest();
$request->setLocale(\Iyzipay\Model\Locale::TR);
$request->setConversationId($ConversationId);
$request->setCurrency(\Iyzipay\Model\Currency::TL);
$request->setBasketId("B".rand(11111111,99999999));
$request->setPaymentGroup(\Iyzipay\Model\PaymentGroup::PRODUCT);
$request->setEnabledInstallments(array(2, 3, 6, 9));



$buyer = new \Iyzipay\Model\Buyer();
$buyer->setId($pt->config->iyzipay_buyer_id);
$buyer->setName($pt->config->iyzipay_buyer_name);
$buyer->setSurname($pt->config->iyzipay_buyer_surname);
$buyer->setGsmNumber($pt->config->iyzipay_buyer_gsm_number);
$buyer->setEmail($pt->config->iyzipay_buyer_email);
$buyer->setIdentityNumber($pt->config->iyzipay_identity_number);
$buyer->setRegistrationAddress($pt->config->iyzipay_address);
$buyer->setCity($pt->config->iyzipay_city);
$buyer->setCountry($pt->config->iyzipay_country);
$buyer->setZipCode($pt->config->iyzipay_zip);
$request->setBuyer($buyer);



$shippingAddress = new \Iyzipay\Model\Address();
$shippingAddress->setContactName($pt->config->iyzipay_buyer_name.' '.$pt->config->iyzipay_buyer_surname);
$shippingAddress->setCity($pt->config->iyzipay_city);
$shippingAddress->setCountry($pt->config->iyzipay_country);
$shippingAddress->setAddress($pt->config->iyzipay_address);
$shippingAddress->setZipCode($pt->config->iyzipay_zip);
$request->setShippingAddress($shippingAddress);

$billingAddress = new \Iyzipay\Model\Address();
$billingAddress->setContactName($pt->config->iyzipay_buyer_name.' '.$pt->config->iyzipay_buyer_surname);
$billingAddress->setCity($pt->config->iyzipay_city);
$billingAddress->setCountry($pt->config->iyzipay_country);
$billingAddress->setAddress($pt->config->iyzipay_address);
$billingAddress->setZipCode($pt->config->iyzipay_zip);
$request->setBillingAddress($billingAddress);