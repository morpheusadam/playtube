<?php
declare(strict_types=1);

use PayFast\Exceptions\InvalidRequestException;
use PayFast\PayFastPayment;
use PHPUnit\Framework\TestCase;

final class PaymentIntegrationTest extends TestCase
{

    private $init;

    protected function setUp(): void
    {
        $this->init = [
            'merchantId' => '10000100',
            'merchantKey' => '46f0cd694581a',
            'passPhrase' => '',
            'testMode' => true
        ];
    }

    /**
     * Test instantiation of PayFast payment
     */
    public function testInstantiationOfPayFastPayment()
    {
        $obj = new PayFastPayment($this->init);

        $this->assertInstanceOf('\PayFast\PayFastPayment', $obj);
    }

    /**
     * Make sure exception is thrown if required parameter is missing
     */
    public function testPayFastPaymentException(): void
    {
        $this->expectException(InvalidRequestException::class);

        unset($this->init['merchantId']);

        new PayFastPayment($this->init);
    }


}
