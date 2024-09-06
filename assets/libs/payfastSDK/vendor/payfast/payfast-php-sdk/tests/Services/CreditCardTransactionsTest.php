<?php
declare(strict_types=1);

use PayFast\Exceptions\InvalidRequestException;
use PayFast\PayFastApi;
use PHPUnit\Framework\TestCase;

final class CreditCardTransactionsTest extends TestCase
{

    private static $api;

    public static function setUpBeforeClass(): void
    {
        try {
            self::$api = new PayFastApi(
                [
                    'merchantId' => '10026755',
                    'passPhrase' => 'test_sandbox',
                    'testMode' => true
                ]
            );
        } catch (InvalidRequestException $e) {
            echo 'Caught exception: ',  $e->getMessage(), "\n";
        }
    }

    /**
     * Test successful fetch
     */
    public function testFetch()
    {
        $response = self::$api->creditCardTransactions->fetch('1124148');

        $this->assertEquals("success", $response['status']);
    }

    /**
     * Test unsuccessful fetch
     */
    public function testUnsuccessfulFetch()
    {
        $this->expectException(InvalidRequestException::class);

        self::$api->creditCardTransactions->fetch('test');
    }

}
