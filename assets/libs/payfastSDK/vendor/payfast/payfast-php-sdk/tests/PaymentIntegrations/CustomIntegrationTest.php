<?php
declare(strict_types=1);

use PayFast\Exceptions\InvalidRequestException;
use PayFast\PayFastPayment;
use PHPUnit\Framework\TestCase;

final class CustomIntegrationTest extends TestCase
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
            'return_url' => 'https://your.domain/return.php',
            'cancel_url' => 'https://your.domain/cancel.php',
            'notify_url' => 'https://your.domain/notify.php',
            // Buyer details
            'name_first' => 'First Name',
            'name_last'  => 'Last Name',
            'email_address'=> 'test@test.com',
            // Transaction details
            'm_payment_id' => '1234', //Unique payment ID to pass through to notify_url
            'amount' => '10.00',
            'item_name' => 'Order#123'
        ];
    }

    /**
     * Test form creation
     */
    public function testFormCreation()
    {
        $htmlForm = self::$payFastPayment->custom->createFormFields($this->data, ['value' => 'PAY ME NOW', 'class' => 'btn']);

        $this->expectOutputString($htmlForm);

        print($htmlForm);
    }

    /**
     * Test exception is thrown if required parameter is missing
     */
    public function testFormCreationException(): void
    {
        $this->expectException(InvalidRequestException::class);

        unset($this->data['item_name']);

        self::$payFastPayment->custom->createFormFields($this->data, ['value' => 'PAY ME NOW', 'class' => 'btn']);
    }


}
