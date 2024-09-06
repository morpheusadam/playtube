<?php


namespace PayFast\Services;


use Exception;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\GuzzleException;
use PayFast\Exceptions\InvalidRequestException;
use PayFast\PayFastBase;
use PayFast\Request;
use PayFast\Validate;

class Subscriptions extends PayFastBase
{

    private const PATH = 'subscriptions';

    /**
     * Fetch a subscription
     * $payfast->subscriptions->fetch('dc0521d3-55fe-269b-fa00-b647310d760f');
     * @param $token
     * @return array
     * @throws Exception|GuzzleException
     */
    public function fetch($token = null) : array {
        if($token === null){
            throw new InvalidRequestException('Required "token" parameter missing', 400);
        }
        try {
            $response = Request::sendApiRequest('GET', self::PATH.'/'.$token.'/fetch');
            return json_decode($response->getContents(), true);
        } catch (ClientException $e) {
            $response = $e->getResponse();
            throw new InvalidRequestException($response->getBody()->getContents(), 400);
        } catch (Exception $e) {
            throw $e;
        }
    }

    /**
     * Pause a subscription
     * $payfast->subscriptions->pause('2afa4575-5628-051a-d0ed-4e071b56a7b0', ['cycles' => 1]);
     * @param null $token
     * @param array $options
     * @return array
     * @throws InvalidRequestException
     * @throws GuzzleException
     */
    public function pause($token = null, array $options = []) : array {
        if($token === null){
            throw new InvalidRequestException('Required "token" parameter missing', 400);
        }
        try {
            Validate::validateOptions($options, ['cycles' => 'int']);
            $response = Request::sendApiRequest('PUT', self::PATH.'/'.$token.'/pause', [], $options);
            return json_decode($response->getContents(), true);
        }catch (ClientException $e) {
            $response = $e->getResponse();
            throw new InvalidRequestException($response->getBody()->getContents(), 400);
        }
    }

    /**
     * Unpause a subscription
     * $payfast->subscriptions->unpause('2afa4575-5628-051a-d0ed-4e071b56a7b0');
     * @param null $token
     * @return array
     * @throws InvalidRequestException
     * @throws GuzzleException
     */
    public function unpause($token = null) : array {
        if($token === null){
            throw new InvalidRequestException('Required "token" parameter missing', 400);
        }
        try {
            $response = Request::sendApiRequest('PUT', self::PATH.'/'.$token.'/unpause');
            return json_decode($response->getContents(), true);
        } catch (ClientException $e) {
            $response = $e->getResponse();
            throw new InvalidRequestException($response->getBody()->getContents(), 400);
        }
    }

    /**
     * Cancel a subscription
     * $payfast->subscriptions->cancel('2afa4575-5628-051a-d0ed-4e071b56a7b0');
     * @param null $token
     * @return array
     * @throws InvalidRequestException
     * @throws GuzzleException
     */
    public function cancel($token = null) : array {
        if($token === null){
            throw new InvalidRequestException('Required "token" parameter missing', 400);
        }
        try {
            $response = Request::sendApiRequest('PUT', self::PATH.'/'.$token.'/cancel');
            return json_decode($response->getContents(), true);
        } catch (ClientException $e) {
            $response = $e->getResponse();
            throw new InvalidRequestException($response->getBody()->getContents(), 400);
        }
    }

    /**
     * Update a subscription
     * $payfast->subscriptions->update('2afa4575-5628-051a-d0ed-4e071b56a7b0', ['cycles' => 1]);
     * @param null $token
     * @param array $options
     * @return array
     * @throws InvalidRequestException
     * @throws GuzzleException
     */
    public function update($token = null, array $options = []) : array {
        if($token === null){
            throw new InvalidRequestException('Required "token" parameter missing', 400);
        }
        try {
            Validate::validateOptions($options, ['cycles' => 'int','frequency' => 'int','run_date' => 'date','amount' => 'int']);
            $response = Request::sendApiRequest('PATCH', self::PATH.'/'.$token.'/update', [], $options);
            return json_decode($response->getContents(), true);
        }catch (ClientException $e) {
            $response = $e->getResponse();
            throw new InvalidRequestException($response->getBody()->getContents(), 400);
        }
    }

    /**
     * Charge a tokenization payment
     * $payfast->subscriptions->adhoc('290ac9a6-25f1-cce4-5801-67a644068818', ['amount' => 500, 'item_name' => 'Test adhoc']);
     * @param null $token
     * @param array $options
     * @return array
     * @throws InvalidRequestException
     * @throws GuzzleException
     */
    public function adhoc($token = null, array $options = []) : array {
        if($token === null){
            throw new InvalidRequestException('Required "token" parameter missing', 400);
        }
        if(!isset($options['amount'])){
            throw new InvalidRequestException('Required "amount" parameter missing', 400);
        }
        if(!isset($options['item_name'])){
            throw new InvalidRequestException('Required "item_name" parameter missing', 400);
        }

        try {
            Validate::validateOptions($options, ['amount' => 'int','cc_cvv' => 'int']);
            $response = Request::sendApiRequest('POST', self::PATH.'/'.$token.'/adhoc', [], $options);
            return json_decode($response->getContents(), true);
        }catch (ClientException $e) {
            $response = $e->getResponse();
            throw new InvalidRequestException($response->getBody()->getContents(), 400);
        }
    }

}
