<?php

namespace PayFast;

use Exception;
use PayFast\Exceptions\InvalidRequestException;

/**
 * Class PayFast
 * @property mixed custom
 * @property mixed onsite
 * @property mixed notification
 * @package PayFast
 */
class PayFastPayment
{

    /** @var string Base URL for the API */
    public static $baseUrl;

    /** @var integer The merchant ID as given by the PayFast system */
    public static $merchantId;

    /** @var integer The merchant Key as given by the PayFast system */
    public static $merchantKey;

    /** @var string The passphrase is used to salt the signature */
    public static $passPhrase;

    /** @var string Test / sandbox mode */
    public static $testMode;

    /** @var array Error messages */
    public static $errorMsg = [];

    /**
     * PayFastPayment constructor.
     * @param $setup
     * @throws InvalidRequestException
     */
    public function __construct($setup)
    {
        if(isset($setup['merchantId'])) {
            self::$merchantId = $setup['merchantId'];
        } else {
            throw new InvalidRequestException('Missing parameter "merchantId"', 400);
        }
        if(isset($setup['merchantKey'])) {
            self::$merchantKey = $setup['merchantKey'];
        } else {
            throw new InvalidRequestException('Missing parameter "merchantKey"', 400);
        }
        self::$passPhrase = (isset($setup['passPhrase'])) ? $setup['passPhrase'] : null;
        self::$testMode = (isset($setup['testMode'])) ? $setup['testMode'] : false;
        self::$baseUrl = self::$testMode === true ? 'https://sandbox.payfast.co.za' : 'https://www.payfast.co.za';
    }

    /**
     * @param $property
     * @return mixed
     * @throws Exception
     */
    public function __get($property) {
        $class = ServiceMapper::getClass($property);
        if ($class !== null) {
            return new $class;
        } else {
            throw new Exception("Unknown method");
        }
    }

    /**
     * Set test mode value
     * @param false $value
     */
    public static function setTestMode($value = false) {
        self::$testMode = (bool) $value;
        self::$baseUrl = self::$testMode === true ? 'https://sandbox.payfast.co.za' : 'https://www.payfast.co.za';
    }

}
