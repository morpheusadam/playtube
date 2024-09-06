<?php
declare(strict_types=1);

use PayFast\Exceptions\InvalidRequestException;
use PayFast\PayFastApi;
use PHPUnit\Framework\TestCase;

final class RefundsTest extends TestCase
{

    private static $api;
    public static $id;

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
        self::$id = '1124464';
    }

    /**
     * Test successful fetch
     */
    /*
    public function testFetch()
    {
        $response = self::$api->refunds->fetch(self::$id);

        self::assertEquals("success", $response['status']);

        return $response['data']['response']['status_text'];
    }
    */

    /**
     * Test unsuccessful fetch
     */
    public function testUnsuccessfulFetch(): void
    {
        $this->expectException(InvalidRequestException::class);

        self::$api->refunds->fetch('test');
    }

    /**
     * Test successful create
     * @return void
     */
    /*
    public function testCreate(): void
    {
        $response = self::$api->refunds->create(self::$id, ['amount' => 1, 'notify_buyer' => 0, 'reason' => 'Product returned', 'acc_type' => 'current']);

        self::assertContains($response['status'], ["success", "failed"]);
    }
    */

    /**
     * Test unsuccessful create
     */
    public function testUnsuccessfulCreate(): void
    {
        $this->expectException(InvalidRequestException::class);

        self::$api->refunds->create('abc', ['notify_buyer' => 0]);
    }

}
