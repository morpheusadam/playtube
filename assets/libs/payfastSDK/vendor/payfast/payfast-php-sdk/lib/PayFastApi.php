<?php

namespace PayFast;

use Exception;
use PayFast\Exceptions\InvalidRequestException;
use RuntimeException;

/**
 * Class PayFast
 * @property mixed subscriptions
 * @property mixed transactionHistory
 * @property mixed creditCardTransactions
 * @package PayFast
 */
class PayFastApi
{

    /** @var string Base URL for the API */
    public static $apiUrl = 'https://api.payfast.co.za';

    /** @var integer The merchant ID as given by the PayFast system */
    public static $merchantId;

    /** @var string The passphrase is used to salt the signature */
    public static $passPhrase;

    /** @var string The API version used for API requests */
    public static $version;

    /** @var string Test / sandbox mode */
    public static $testMode;

    /**
     * PayFastApi constructor.
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
        self::$passPhrase = $setup['passPhrase'] ?? null;
        self::$testMode = $setup['testMode'] ?? false;
        self::$version = $setup['version'] ?? 'v1';
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
        }

        throw new RuntimeException("Unknown method");
    }

}
