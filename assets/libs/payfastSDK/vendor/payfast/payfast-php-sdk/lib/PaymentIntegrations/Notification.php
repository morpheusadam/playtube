<?php


namespace PayFast\PaymentIntegrations;


use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\GuzzleException;
use PayFast\Exceptions\InvalidRequestException;
use PayFast\PayFastBase;
use PayFast\PayFastPayment;
use Psr\Http\Message\StreamInterface;
use RuntimeException;

class Notification extends PayFastBase
{

    /**
     * Check if the payment notification is valid
     * @param array $pfData The data posted to the notification
     * @param array $checks Array of data validation checks to run
     * @return bool
     * @throws InvalidRequestException
     */
    public function isValidNotification(array $pfData, array $checks = []): bool {

        $pfData = $this->cleanNotificationData($pfData);
        $pfParamString = $this->dataToString($pfData);

        $check1 = $this->pfValidSignature($pfData, $pfParamString, PayFastPayment::$passPhrase);
        $check2 = $this->pfValidIP();
        $check3 = $this->pfValidData($pfData, $checks);
        $check4 = $this->pfValidServerConfirmation($pfParamString);

        return $check1 && $check2 && $check3 && $check4;
    }

    /**
     * Clean notification data
     * @param $pfData
     * @return mixed
     */
    private function cleanNotificationData($pfData) {
        // Strip any slashes in data
        foreach( $pfData as $key => $val ) {
            $pfData[$key] = stripslashes( $val );
        }
        return $pfData;
    }

    /**
     * Convert posted variables to a string
     * @param $pfData
     * @return string
     */
    private function dataToString($pfData): string {
        $pfParamString = '';
        foreach( $pfData as $key => $val ) {
            if( $key !== 'signature' ) {
                $pfParamString .= $key .'='. urlencode( $val ) .'&';
            } else {
                break;
            }
        }
        $pfParamString = substr( $pfParamString, 0, -1 );
        return $pfParamString;
    }

    /**
     * Verify the signature
     * @param $pfData
     * @param $pfParamString
     * @param null $pfPassphrase
     * @return bool
     */
    private function pfValidSignature( $pfData, $pfParamString, $pfPassphrase = null ): bool {
        if(!isset($pfData['signature'])) {
            PayFastPayment::$errorMsg[] = "Invalid signature";
            return false;
        }
        // Calculate security signature
        if($pfPassphrase === null) {
            $tempParamString = $pfParamString;
        } else {
            $tempParamString = $pfParamString.'&passphrase='.urlencode( $pfPassphrase );
        }

        $signature = md5( $tempParamString );
        if($pfData['signature'] !== $signature) {
            PayFastPayment::$errorMsg[] = "Invalid signature";
        }
        return ( $pfData['signature'] === $signature );
    }

    /**
     * Check that the notification has come from a valid PayFast domain
     * @return bool
     */
    private function pfValidIP(): bool
    {
        if(!isset($_SERVER['HTTP_REFERER'])) {
            PayFastPayment::$errorMsg[] = "This notification does not come from a valid PayFast domain";
            return false;
        }

        // Variable initialization
        $validHosts = [
            'www.payfast.co.za',
            'sandbox.payfast.co.za',
            'w1w.payfast.co.za',
            'w2w.payfast.co.za'
        ];

        $validIps = [];

        foreach ($validHosts as $pfHostname) {
            $ips = gethostbynamel($pfHostname);

            if ($ips !== false && is_array($ips)) {
                array_push($validIps, ...$ips);
            }
        }

        // Remove duplicates
        $validIps = array_unique($validIps);
        $referrerIp = gethostbyname(parse_url($_SERVER['HTTP_REFERER'])['host']);
        if (in_array($referrerIp, $validIps, true)) {
            return true;
        }
        PayFastPayment::$errorMsg[] = "This notification does not come from a valid PayFast domain";
        return false;
    }

    /**
     * Compare returned data
     * @param $pfData
     * @param array $checks
     * @return bool
     */
    private function pfValidData($pfData, array $checks = []): bool
    {
        if(!empty($checks)) {
            foreach($checks as $k => $v) {
                if($k === 'amount_gross') {
                    if (!isset($pfData['amount_gross'])) {
                        PayFastPayment::$errorMsg[] = "Parameter 'amount_gross' does not exist in the post data";
                        return false;
                    }
                    if(abs((float)$v - (float)$pfData['amount_gross']) > 0.01) {
                        PayFastPayment::$errorMsg[] = "The 'amount_gross' is ".$pfData['amount_gross'].", you expected ".$v;
                        return false;
                    }
                } else {
                    if (!isset($pfData[$k])) {
                        PayFastPayment::$errorMsg[] = "Parameter '".$k."' does not exist in the post data";
                        return false;
                    }
                    if($pfData[$k] !== $v) {
                        PayFastPayment::$errorMsg[] = "The '".$k."' is ".$pfData[$k].", you expected ".$v;
                        return false;
                    }
                }
            }
        }
        return true;
    }

    /**
     * Perform a server request to confirm the details
     * @param $pfParamString
     * @return bool|StreamInterface
     * @throws InvalidRequestException
     * @throws Exception
     */
    private function pfValidServerConfirmation($pfParamString) {
        try {
            $client = new Client(['base_uri' => PayFastPayment::$baseUrl.'/']);
            $response = $client->request('POST', 'eng/query/validate', [
                'headers'  => ['content-type' => 'application/x-www-form-urlencoded'],
                'body' => $pfParamString
            ]);
            if ((string)$response->getBody() === 'VALID') {
                return true;
            }
            PayFastPayment::$errorMsg[] = 'Invalid server confirmation';
            return false;
        } catch (ClientException $e) {
            $response = $e->getResponse();
            throw new InvalidRequestException($response->getBody()->getContents(), 400);
        } catch (GuzzleException $e) {
            throw new RuntimeException($e);
        }
    }

}
