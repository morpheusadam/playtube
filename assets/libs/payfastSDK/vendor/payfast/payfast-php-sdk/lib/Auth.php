<?php

namespace PayFast;


use PayFast\Exceptions\InvalidRequestException;

class Auth
{

    /**
     * Generate signature for API
     * @param array $pfData (all of the header and body values to be sent to the API)
     * @param null|string $passPhrase
     * @return string
     */
    public static function generateApiSignature(array $pfData, string $passPhrase = null): string {

        // Construct variables
        foreach ($pfData as $key => $val) {
            $data[$key] = stripslashes($val);
        }

        if ($passPhrase !== null) {
            $pfData['passphrase'] = $passPhrase;
        }

        // Sort the array by key, alphabetically
        ksort($pfData);

        // Normalise the array into a parameter string
        $pfParamString = '';
        foreach ($pfData as $key => $val) {
            if ($key !== 'signature') {
                $pfParamString .= $key . '=' . urlencode($val) . '&';
            }
        }

        // Remove the last '&amp;' from the parameter string
        $pfParamString = substr($pfParamString, 0, -1);
        return md5($pfParamString);
    }

    /**
     * Generate signature for payment integrations
     * Sorts the parameters into the correct order
     * Attributes not in the sortAttributes list are excluded from the signature
     * i.e. setup
     * @param $data
     * @param null $passPhrase
     * @return string
     * @throws InvalidRequestException
     */
    public static function generateSignature($data, $passPhrase = null): string
    {
        $fields = ['merchant_id', 'merchant_key', 'return_url', 'cancel_url', 'notify_url', 'notify_method',
            'name_first', 'name_last', 'email_address', 'cell_number', 'm_payment_id', 'amount', 'item_name',
            'item_description', 'custom_int1', 'custom_int2', 'custom_int3', 'custom_int4', 'custom_int5',
            'custom_str1', 'custom_str2', 'custom_str3', 'custom_str4', 'custom_str5', 'email_confirmation',
            'confirmation_address', 'currency', 'payment_method', 'subscription_type', 'passphrase',
            'billing_date', 'recurring_amount', 'frequency', 'cycles', 'subscription_notify_email',
            'subscription_notify_webhook', 'subscription_notify_buyer'];

        $sortAttributes = array_filter($data, function ($key) use ($fields) {
            return in_array($key, $fields);
        }, ARRAY_FILTER_USE_KEY);

        if($passPhrase !== null && $passPhrase !== '') {
            $sortAttributes['passphrase'] = urlencode(trim($passPhrase));
        }

        // Some functionality requires the passphrase to be set
        if (isset($data['subscription_type']) && ($passPhrase === null || $passPhrase === '')) {
            throw new InvalidRequestException('Subscriptions require a passphrase to be set', 400);
        }

        // Create parameter string
        $pfOutput = '';
        foreach($sortAttributes as $attribute => $value) {
            if(!empty($value)) {
                $pfOutput .= $attribute .'='. urlencode(trim($value)) .'&';
            }
        }
        // Remove last ampersand
        $getString = substr($pfOutput, 0, -1);

        return md5($getString);
    }

}
