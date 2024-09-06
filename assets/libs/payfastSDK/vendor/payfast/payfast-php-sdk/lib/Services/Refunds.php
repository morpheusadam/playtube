<?php


namespace PayFast\Services;


use Exception;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\GuzzleException;
use PayFast\Exceptions\InvalidRequestException;
use PayFast\PayFastBase;
use PayFast\PayFastApi;
use PayFast\Request;
use PayFast\Validate;

class Refunds extends PayFastBase
{

    private const PATH = 'refunds';

    /**
     * Refunds constructor.
     * @throws InvalidRequestException
     */
    public function __construct(){
        if(PayFastApi::$testMode === true) {
            throw new InvalidRequestException('Sorry but Refunds is not available in Sandbox mode', 400);
        }
    }

    /**
     * Fetch a refund
     * $payfast->refunds->fetch('dc0521d3-55fe-269b-fa00-b647310d760f');
     * @param $id
     * @return array
     * @throws Exception|GuzzleException
     */
    public function fetch($id = null) : array {
        if($id === null){
            throw new InvalidRequestException('Required "id" parameter missing', 400);
        }
        try {
            $response = Request::sendApiRequest('GET', self::PATH.'/'.$id);
            return json_decode($response->getContents(), true);
        } catch (ClientException $e) {
            $response = $e->getResponse();
            throw new InvalidRequestException($response->getBody()->getContents(), 400);
        }
    }

    /**
     * Create a new refund
     * $payfast->refunds->create('2afa4575-5628-051a-d0ed-4e071b56a7b0', ['amount' => 50, 'reason' => 'Product returned', 'acc_type' => 'savings']);
     * @param null $id
     * @param array $options
     * @return array
     * @throws InvalidRequestException
     * @throws GuzzleException
     */
    public function create($id = null, array $options = []) : array {
        if($id === null){
            throw new InvalidRequestException('Required "id" parameter missing', 400);
        }
        if(!isset($options['notify_buyer'])) {
            $options['notify_buyer'] = 1;
        }
        try {
            Validate::validateOptions($options, ['amount' => 'int', 'acc_type' => 'accType']);
            $response = Request::sendApiRequest('POST', self::PATH.'/'.$id, [], $options);
            return json_decode($response->getContents(), true);
        }catch (ClientException $e) {
            $response = $e->getResponse();
            throw new InvalidRequestException($response->getBody()->getContents(), 400);
        } catch (InvalidRequestException $e) {
            throw $e;
        }
    }

}
