<?php


namespace PayFast\PaymentIntegrations;


use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\GuzzleException;
use PayFast\Auth;
use PayFast\Exceptions\InvalidRequestException;
use PayFast\PayFastBase;
use PayFast\PayFastPayment;
use RuntimeException;

class OnsiteIntegration extends PayFastBase
{

    private const PATH = 'onsite/process';

    private function dataToString($dataArray)
    {
        // Create parameter string
        $pfOutput = '';
        foreach ($dataArray as $key => $val) {
            if ($val !== '') {
                $pfOutput .= $key . '=' . urlencode(trim($val)) . '&';
            }
        }
        // Remove last ampersand
        return substr($pfOutput, 0, -1);
    }

    /**
     * Generate payment identifier
     * @param $data
     * @return mixed|null
     * @throws InvalidRequestException
     * @throws Exception
     */
    public function generatePaymentIdentifier($data)
    {
        if(PayFastPayment::$testMode === true) {
            throw new InvalidRequestException('Sorry but Onsite is not available in Sandbox mode', 400);
        }

        $authDetails = [
            'merchant_id' => PayFastPayment::$merchantId,
            'merchant_key' => PayFastPayment::$merchantKey
        ];
        $data = array_merge($authDetails, $data);

        // Generate signature
        $data["signature"] = Auth::generateSignature($data, PayFastPayment::$passPhrase);

        // Convert the data array to a string
        $pfParamString = $this->dataToString($data);

        try {
            $client = new Client(['base_uri' => PayFastPayment::$baseUrl.'/']);
            $response = $client->request('POST', self::PATH, [
                'headers'  => ['content-type' => 'application/x-www-form-urlencoded'],
                'body' => $pfParamString
            ]);
            $rsp = json_decode($response->getBody(), true);
            if ($rsp['uuid']) {
                return $rsp['uuid'];
            }
            return null;
        } catch (ClientException $e) {
            $response = $e->getResponse();
            throw new InvalidRequestException($response->getBody()->getContents(), 400);
        } catch (GuzzleException $e) {
            throw new RuntimeException($e);
        }

    }

}
