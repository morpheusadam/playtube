<?php
declare(strict_types=1);

use PayFast\Exceptions\InvalidRequestException;
use PayFast\PayFastPayment;
use PHPUnit\Framework\TestCase;

final class OnsiteIntegrationTest extends TestCase
{

    private static $payFastPayment;
    private $data;

    public static function setUpBeforeClass(): void
    {
        self::$payFastPayment = new PayFastPayment([
            'merchantId' => '10000100',
            'merchantKey' => '46f0cd694581a',
            'passPhrase' => '',
            'testMode' => true
        ]);
    }

    protected function setUp(): void
    {
        $this->data = [
            // Merchant details
            'return_url' => 'https://yoursite.com/return.php', // Requires HTTPS
            'cancel_url' => 'https://yoursite.com/cancel.php', // Requires HTTPS
            'notify_url' => 'https://yoursite.com/notify.php', // Requires HTTPS
            // Buyer details
            'name_first' => 'First Name',
            'name_last'  => 'Last Name',
            'email_address'=> 'test@test.com',
            // Transaction details
            'm_payment_id' => '1234', //Unique payment ID to pass through to notify_url
            'amount' => '5.00'
        ];
    }

    /**
     * Make sure exception is thrown if required parameter is invalid
     */
    public function testGeneratePaymentIdentifierException(): void
    {
        $this->expectException(InvalidRequestException::class);

        self::$payFastPayment->onsite->generatePaymentIdentifier($this->data);
    }


}
