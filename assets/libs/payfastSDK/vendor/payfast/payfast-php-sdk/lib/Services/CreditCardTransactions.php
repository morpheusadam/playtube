<?php


namespace PayFast\Services;


use Exception;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\GuzzleException;
use PayFast\Exceptions\InvalidRequestException;
use PayFast\PayFastBase;
use PayFast\Request;
use RuntimeException;

class CreditCardTransactions extends PayFastBase
{

    private const PATH = 'process/query';

    /**
     * Query a credit card transaction
     * $payfast->creditCardTransactions->fetch('1124148');
     * @param $token
     * @return array
     * @throws Exception
     */
    public function fetch($token = null) : array {
        if($token === null){
            throw new InvalidRequestException('Required "token" parameter missing', 400);
        }
        try {
            $response = Request::sendApiRequest('GET', self::PATH.'/'.$token);
            return json_decode($response->getContents(), true);
        } catch (ClientException $e) {
            $response = $e->getResponse();
            throw new InvalidRequestException($response->getBody()->getContents(), 400);
        } catch (GuzzleException $e) {
            throw new RuntimeException($e);
        }
    }

}
