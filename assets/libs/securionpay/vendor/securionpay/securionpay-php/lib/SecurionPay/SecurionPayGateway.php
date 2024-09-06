<?php
namespace SecurionPay;

use SecurionPay\Connection\Connection;
use SecurionPay\Connection\CurlConnection;
use SecurionPay\Exception\SecurionPayException;
use SecurionPay\Util\ObjectSerializer;

/**
 * @version 2.1.1-rc3
 */
class SecurionPayGateway
{
    const VERSION = '2.2.0';
    const DEFAULT_ENDPOINT = 'https://api.securionpay.com';
    const DEFAULT_UPLOADS_ENDPOINT = "https://uploads.securionpay.com/";
    
    private $objectSerializer;
    
    /**
     * @var \SecurionPay\Connection\Connection
     */
    private $connection;

    private $privateKey;

    private $endpoint = self::DEFAULT_ENDPOINT;

    private $uploadsEndpoint = self::DEFAULT_UPLOADS_ENDPOINT;
    
    private $userAgent;

    public function __construct($privateKey = null, Connection $connection = null)
    {
        $this->objectSerializer = new ObjectSerializer();
        
        $this->privateKey = $privateKey;
        $this->connection = $connection ? $connection : new CurlConnection();
    }

    /**
     * @param \SecurionPay\Request\ChargeRequest $request
     * @return \SecurionPay\Response\Charge
     */
    public function createCharge($request) {
        return $this->post('/charges', $request, '\SecurionPay\Response\Charge');
    }

    /**
     * @param \SecurionPay\Request\CaptureRequest $request
     * @return \SecurionPay\Response\Charge
     */
    public function captureCharge($request) {
        return $this->post('/charges/{chargeId}/capture', $request, '\SecurionPay\Response\Charge');
    }
    
    /**
     * @param string $chargeId
     * @return \SecurionPay\Response\Charge
     */
    public function retrieveCharge($chargeId) {
        return $this->get("/charges/{$chargeId}", '\SecurionPay\Response\Charge');
    }
    
    /**
     * @param \SecurionPay\Request\ChargeUpdateRequest $request
     * @return \SecurionPay\Response\Charge
     */
    public function updateCharge($request) {
        return $this->post('/charges/{chargeId}', $request, '\SecurionPay\Response\Charge');
    }
    
    /**
     * @param \SecurionPay\Request\RefundRequest $request
     * @return \SecurionPay\Response\Charge
     */
    public function refundCharge($request) {
        return $this->post('/charges/{chargeId}/refund', $request, '\SecurionPay\Response\Charge');
    }
    
    /**
     * @param \SecurionPay\Request\ChargeListRequest $request
     * @return \SecurionPay\Response\ListResponse
     */
    public function listCharges($request = null) {
        return $this->getList('/charges', $request, '\SecurionPay\Response\Charge');
    }

    /**
     * @param \SecurionPay\Request\CustomerRequest $request
     * @return \SecurionPay\Response\Customer
     */
    public function createCustomer($request) {
        return $this->post('/customers', $request, '\SecurionPay\Response\Customer');
    }
    
    /**
     * @param string $customerId
     * @return \SecurionPay\Response\Customer
     */
    public function retrieveCustomer($customerId) {
        return $this->get("/customers/{$customerId}", '\SecurionPay\Response\Customer');
    }

    /**
     * @param \SecurionPay\Request\CustomerUpdateRequest $request
     * @return \SecurionPay\Response\Customer
     */
    public function updateCustomer($request) {
        return $this->post('/customers/{customerId}', $request, '\SecurionPay\Response\Customer');
    }
    
    /**
     * @param string $customerId
     * @return \SecurionPay\Response\DeleteResponse
     */
    public function deleteCustomer($customerId) {
        return $this->delete("/customers/{$customerId}", null, '\SecurionPay\Response\DeleteResponse');
    }
    
    /**
     * @param \SecurionPay\Request\CustomerListRequest $request
     * @return \SecurionPay\Response\ListResponse
     */
    public function listCustomers($request = null) {
        return $this->getList('/customers', $request, '\SecurionPay\Response\Customer');
    }

    /**
     * @param \SecurionPay\Request\CardRequest $request
     * @return \SecurionPay\Response\Card
     */
    public function createCard($request) {
        return $this->post('/customers/{customerId}/cards', $request, '\SecurionPay\Response\Card');
    }

    /**
     * @param string $customerId
     * @param string $cardId
     * @return \SecurionPay\Response\Card
     */
    public function retrieveCard($customerId, $cardId) {
        return $this->get("/customers/{$customerId}/cards/{$cardId}", '\SecurionPay\Response\Card');
    }

    /**
     * @param \SecurionPay\Request\CardUpdateRequest $request
     * @return \SecurionPay\Response\Card
     */
    public function updateCard($request) {
        return $this->post('/customers/{customerId}/cards/{cardId}', $request, '\SecurionPay\Response\Card');
    }

    /**
     * @param string $customerId
     * @param string $cardId
     * @return \SecurionPay\Response\DeleteResponse
     */
    public function deleteCard($customerId, $cardId) {
        return $this->delete("/customers/{$customerId}/cards/{$cardId}", null, '\SecurionPay\Response\DeleteResponse');
    }

    /**
     * @param \SecurionPay\Request\CardListRequest $request
     * @return \SecurionPay\Response\ListResponse
     */
    public function listCards($request) {
        return $this->getList('/customers/{customerId}/cards', $request, '\SecurionPay\Response\Card');
    }

    /**
     * @param \SecurionPay\Request\SubscriptionRequest $request
     * @return \SecurionPay\Response\Subscription
     */
    public function createSubscription($request) {
        return $this->post('/customers/{customerId}/subscriptions', $request, '\SecurionPay\Response\Subscription');
    }

    /**
     * @param string $customerId
     * @param string $subscriptionId
     * @return \SecurionPay\Response\Subscription
     */
    public function retrieveSubscription($customerId, $subscriptionId) {
        return $this->get("/customers/{$customerId}/subscriptions/{$subscriptionId}", '\SecurionPay\Response\Subscription');
    }

    /**
     * @param \SecurionPay\Request\SubscriptionUpdateRequest $request
     * @return \SecurionPay\Response\Subscription
     */
    public function updateSubscription($request) {
        return $this->post('/customers/{customerId}/subscriptions/{subscriptionId}', $request, '\SecurionPay\Response\Subscription');
    }

    /**
     * @param \SecurionPay\Request\SubscriptionCancelRequest $request
     * @return \SecurionPay\Response\Subscription
     */
    public function cancelSubscription($request) {
        return $this->delete('/customers/{customerId}/subscriptions/{subscriptionId}', $request, '\SecurionPay\Response\Subscription');
    }

    /**
     * @param \SecurionPay\Request\SubscriptionListRequest $request
     * @return \SecurionPay\Response\ListResponse
     */
    public function listSubscriptions($request) {
        return $this->getList('/customers/{customerId}/subscriptions', $request, '\SecurionPay\Response\Subscription');
    }

    /**
     * @param \SecurionPay\Request\PlanRequest $request
     * @return \SecurionPay\Response\Plan
     */
    public function createPlan($request) {
        return $this->post('/plans', $request, '\SecurionPay\Response\Plan');
    }

    /**
     * @param string $planId
     * @return \SecurionPay\Response\Plan
     */
    public function retrievePlan($planId) {
        return $this->get("/plans/{$planId}", '\SecurionPay\Response\Plan');
    }

    /**
     * @param \SecurionPay\Request\PlanUpdateRequest $request
     * @return \SecurionPay\Response\Plan
     */
    public function updatePlan($request) {
        return $this->post('/plans/{planId}', $request, '\SecurionPay\Response\Plan');
    }

    /**
     * @param string $planId
     * @return \SecurionPay\Response\DeleteResponse
     */
    public function deletePlan($planId) {
        return $this->delete("/plans/{$planId}", null, '\SecurionPay\Response\DeleteResponse');
    }

    /**
     * @param \SecurionPay\Request\PlanListRequest $request
     * @return \SecurionPay\Response\ListResponse
     */
    public function listPlans($request = null) {
        return $this->getList('/plans', $request, '\SecurionPay\Response\Plan');
    }

    /**
     * @param string $eventId
     * @return \SecurionPay\Response\Event
     */
    public function retrieveEvent($eventId) {
        return $this->get("/events/{$eventId}", '\SecurionPay\Response\Event');
    }

    /**
     * @param \SecurionPay\Request\EventListRequest $request
     * @return \SecurionPay\Response\ListResponse
     */
    public function listEvents($request = null) {
        return $this->getList('/events', $request, '\SecurionPay\Response\Event');
    }

    /**
     * @param \SecurionPay\Request\TokenRequest $request
     * @return \SecurionPay\Response\Token
     */
    public function createToken($request) {
        return $this->post('/tokens', $request, '\SecurionPay\Response\Token');
    }

    /**
     * @param string $tokenId
     * @return \SecurionPay\Response\Token
     */
    public function retrieveToken($tokenId) {
        return $this->get("/tokens/{$tokenId}", '\SecurionPay\Response\Token');
    }

    /**
     * @param \SecurionPay\Request\BlacklistRuleRequest $request
     * @return \SecurionPay\Response\BlacklistRule
     */
    public function createBlacklistRule($request) {
        return $this->post('/blacklist', $request, '\SecurionPay\Response\BlacklistRule');
    }

    /**
     * @param string $blacklistRuleId
     * @return \SecurionPay\Response\BlacklistRule
     */
    public function retrieveBlacklistRule($blacklistRuleId) {
        return $this->get("/blacklist/{$blacklistRuleId}", '\SecurionPay\Response\BlacklistRule');
    }

    /**
     * @param string $blacklistRuleId
     * @return \SecurionPay\Response\DeleteResponse
     */
    public function deleteBlacklistRule($blacklistRuleId) {
        return $this->delete("/blacklist/{$blacklistRuleId}", null, '\SecurionPay\Response\DeleteResponse');
    }

    /**
     * @param \SecurionPay\Request\BlacklistRuleListRequest $request
     * @return \SecurionPay\Response\ListResponse
     */
    public function listBlacklistRules($request = null) {
        return $this->getList('/blacklist', $request, '\SecurionPay\Response\BlacklistRule');
    }

    /**
     * @param \SecurionPay\Request\CrossSaleOfferRequest $request
     * @return \SecurionPay\Response\CrossSaleOffer
     */
    public function createCrossSaleOffer($request) {
        return $this->post('/cross-sale-offers', $request, '\SecurionPay\Response\CrossSaleOffer');
    }

    /**
     * @param string $blacklistRuleId
     * @return \SecurionPay\Response\CrossSaleOffer
     */
    public function retrieveCrossSaleOffer($crossSaleOfferId) {
        return $this->get("/cross-sale-offers/{$crossSaleOfferId}", '\SecurionPay\Response\CrossSaleOffer');
    }

    /**
     * @param \SecurionPay\Request\CrossSaleOfferUpdateRequest $request
     * @return \SecurionPay\Response\CrossSaleOffer
     */
    public function updateCrossSaleOffer($request) {
        return $this->post('/cross-sale-offers/{crossSaleOfferId}', $request, '\SecurionPay\Response\CrossSaleOffer');
    }

    /**
     * @param string $crossSaleOfferId
     * @return \SecurionPay\Response\DeleteResponse
     */
    public function deleteCrossSaleOffer($crossSaleOfferId) {
        return $this->delete("/cross-sale-offers/{$crossSaleOfferId}", null, '\SecurionPay\Response\DeleteResponse');
    }

    /**
     * @param \SecurionPay\Request\CrossSaleOfferListRequest $request
     * @return \SecurionPay\Response\ListResponse
     */
    public function listCrossSaleOffers($request) {
        return $this->getList('/cross-sale-offers', $request, '\SecurionPay\Response\CrossSaleOffer');
    }

    /**
     * @param \SecurionPay\Request\CreditRequest $request
     * @return \SecurionPay\Response\Credit
     */
    public function createCredit($request) {
        return $this->post('/credits', $request, '\SecurionPay\Response\Credit');
    }
    
    /**
     * @param string creditId
     * @return \SecurionPay\Response\Credit
     */
    public function retrieveCredit($creditId) {
        return $this->get("/credits/{$creditId}", '\SecurionPay\Response\Credit');
    }
    
    /**
     * @param \SecurionPay\Request\CreditUpdateRequest $request
     * @return \SecurionPay\Response\Credit
     */
    public function updateCredit($request) {
        return $this->post('/credits/{creditId}', $request, '\SecurionPay\Response\Credit');
    }
    
    /**
     * @param \SecurionPay\Request\CreditListRequest $request
     * @return \SecurionPay\Response\ListResponse
     */
    public function listCredits($request = null) {
        return $this->getList('/credits', $request, '\SecurionPay\Response\Credit');
    }
    
    /**
     * @param string $file
     * @param string $purpose
     * @return \SecurionPay\Response\FileUpload
     */
    public function createFileUpload($file, $purpose) {
        $files = array('file' => $file);
        $form = array('purpose' => $purpose);
        
        return $this->multipart('/files', $files, $form, '\SecurionPay\Response\FileUpload');
    }
    
    /**
     * @param string $fileUploadId
     * @return \SecurionPay\Response\FileUpload
     */
    public function retrieveFileUpload($fileUploadId) {
        return $this->getFromEndpoint($this->uploadsEndpoint, "/files/{$fileUploadId}", '\SecurionPay\Response\FileUpload');
    }
    
    /**
     * @param \SecurionPay\Request\FileUploadListRequest $request
     * @return \SecurionPay\Response\ListResponse
     */
    public function listFileUploads($request = null) {
        return $this->listFromEndpoint($this->uploadsEndpoint, '/files', $request, '\SecurionPay\Response\FileUpload');
    }

    /**
     * @param string $disputeId
     * @return \SecurionPay\Response\Dispute
     */
    public function retrieveDispute($disputeId) {
        return $this->get("/disputes/{$disputeId}", '\SecurionPay\Response\Dispute');
    }
    
    /**
     * @param \SecurionPay\Request\DisputeUpdateRequest $request
     * @return \SecurionPay\Response\Dispute
     */
    public function updateDispute($request) {
        return $this->post('/disputes/{disputeId}', $request, '\SecurionPay\Response\Dispute');
    }

    /**
     * @param string $disputeId
     * @return \SecurionPay\Response\Dispute
     */
    public function closeDispute($disputeId) {
        return $this->post("/disputes/{$disputeId}/close", null, '\SecurionPay\Response\Dispute');
    }
    
    /**
     * @param \SecurionPay\Request\DisputeListRequest $request
     * @return \SecurionPay\Response\ListResponse
     */
    public function listDisputes($request = null) {
        return $this->getList('/disputes', $request, '\SecurionPay\Response\Dispute');
    }

    /**
     * @param string $fraudWarningId
     * @return \SecurionPay\Response\Dispute
     */
    public function retrieveFraudWarning($fraudWarningId) {
        return $this->get("/fraud-warnings/{$fraudWarningId}", '\SecurionPay\Response\FraudWarning');
    }
    
    /**
     * @param \SecurionPay\Request\FraudWarningListRequest $request
     * @return \SecurionPay\Response\ListResponse
     */
    public function listFraudWarnings($request = null) {
        return $this->getList('/fraud-warnings', $request, '\SecurionPay\Response\FraudWarning');
    }
    
    /**
     * @param \SecurionPay\Request\CheckoutRequest $request
     * @return string
     */
    public function signCheckoutRequest($request) {
        $path = '';
        $data = $this->objectSerializer->serialize($request, $path);
        
        $signarute = hash_hmac('sha256', $data, $this->privateKey);
        
        return base64_encode($signarute . "|" . $data);
    }

    private function get($path, $responseClass) {
        return $this->getFromEndpoint($this->endpoint, $path, $responseClass);
    }
    
    private function getFromEndpoint($endpoint, $path, $responseClass) {
        $response = $this->connection->get($endpoint . $path, $this->buildHeaders());
        $this->ensureSuccess($response);
        return $this->objectSerializer->deserialize($response['body'], $responseClass);
    }
    
    private function post($path, $request, $responseClass) {
        $requestBody = $this->objectSerializer->serialize($request, $path);
        $response = $this->connection->post($this->endpoint . $path, $requestBody, $this->buildHeaders());
        $this->ensureSuccess($response);
        return $this->objectSerializer->deserialize($response['body'], $responseClass);
    }
    
    private function multipart($path, $files, $form, $responseClass) {
        $response = $this->connection->multipart($this->uploadsEndpoint . $path, $files, $form, $this->buildHeaders());
        $this->ensureSuccess($response);
        return $this->objectSerializer->deserialize($response['body'], $responseClass);
    }
    
    private function getList($path, $request, $elementClass) {
        return $this->listFromEndpoint($this->endpoint, $path, $request, $elementClass);
    }

    private function listFromEndpoint($endpoint, $path, $request, $elementClass) {
        $url = $this->buildQueryString($endpoint . $path, $request);
        $response = $this->connection->get($url, $this->buildHeaders());
        $this->ensureSuccess($response);
        return $this->objectSerializer->deserializeList($response['body'], $elementClass);
    }
    
    private function delete($path, $request, $responseClass) {
        $url = $this->endpoint . $this->buildQueryString($path, $request);
        $response = $this->connection->delete($url, $this->buildHeaders());
        $this->ensureSuccess($response);
        return $this->objectSerializer->deserialize($response['body'], $responseClass);
    }
    
    private function ensureSuccess($response) {
        if ($response['status'] != 200) {
            $error = $this->objectSerializer->deserialize($response['body'], '\SecurionPay\Response\ErrorResponse');
            throw new SecurionPayException($error);
        }
        return $response;
    }
    
    private function buildQueryString($path, $request) {
        if ($request == null) {
            return $path;
        }

        $queryString = $this->objectSerializer->serializeToQueryString($request, $path);
        return $path . $queryString;
    }

    private function buildHeaders() {
        return array(
            'Authorization' => 'Basic ' . base64_encode($this->privateKey . ':'),
        	'Content-Type' => 'application/json',
            'User-Agent' => ($this->userAgent ? $this->userAgent . ' ' : '') . 'SecurionPay-PHP/' . self::VERSION . ' (PHP/' . phpversion() . ')'
        );
    }

    public function setPrivateKey($privateKey)
    {
        $this->privateKey = $privateKey;
    }

    public function setConnection(Connection $connection)
    {
        $this->connection = $connection;
    }

    public function setEndpoint($endpoint)
    {
        $this->endpoint = $endpoint;
    }
    
    public function setUploadsEndpoint($uploadsEndpoint)
    {
        $this->uploadsEndpoint = $uploadsEndpoint;
    }
    
    public function setUserAgent($userAgent)
    {
    	$this->userAgent = $userAgent;
    }
}
