<?php

namespace Soisy;

use Soisy\Order\Token;

/**
 * @package  Soisy
 */
class Client
{

    const SANDBOX_SHOP_ID = 'partnershop';
    const SANDBOX_API_KEY = 'partnerkey';

    const QUOTE_INSTALMENTS_AMOUNT = 6;
    const MIN_INSTALMENTS = 3;
    const MAX_INSTALMENTS = 60;

    const MIN_AMOUNT = 250;
    const MAX_AMOUNT = 30000;

    const HTTP_METHOD_GET = 'GET';
    const HTTP_METHOD_POST = 'POST';

    const PATH_ORDER_CREATION = 'orders';
    const PATH_LOAN_QUOTE = 'loan-quotes';

    /**
     * @var array
     */
    protected $_apiBaseUrlArray = [
        1 => 'http://api.sandbox.soisy.it/api/shops',
        0 => 'https://api.soisy.it/api/shops'
    ];

    /**
     * @var array
     */
    protected $_webappBaseUrlArray = [
        1 => 'http://shop.sandbox.soisy.it',
        0 => 'https://shop.soisy.it'
    ];

    /**
     * @var bool
     */
    protected $_sandboxMode;

    /**
     * @var string
     */
    protected $_apiKey;

    /**
     * @var string
     */
    protected $_shopId;

    /**
     * Timeout for API connection wait
     * in milliseconds
     *
     * @var int
     */
    protected $_connectTimeout = 4000;

    /**
     * Timeout for API response wait
     * in milliseconds
     *
     * @var int
     */
    protected $_timeout = 4000;

    /**
     * @var \stdClass
     */
    protected $_response = null;

    public function __construct(?string $shopId, ?string $apiKey, bool $sandboxMode)
    {
        $this->_sandboxMode = $sandboxMode;

        if ($sandboxMode) {
            $this->_shopId = self::SANDBOX_SHOP_ID;
            $this->_apiKey = self::SANDBOX_API_KEY;

            return;
        }

        $this->_shopId = $shopId;
        $this->_apiKey = $apiKey;
    }

    public function getAmount(array $params): \stdClass
    {
        $rawResponse = $this->doRequest($this->getLoanQuoteUrl(), self::HTTP_METHOD_GET, $params);

        return $rawResponse;
    }

    public function getToken(array $params): Token
    {
        $rawResponse = $this->doRequest($this->getOrderCreationUrl(), self::HTTP_METHOD_POST, $params);

        $result = new Token();
        $result->setResponse($rawResponse);

        return $result;
    }

    public function getRedirectUrl(string $token): string
    {
        $baseUrl = $this->_webappBaseUrlArray[$this->_sandboxMode] ? $this->_webappBaseUrlArray[$this->_sandboxMode] : $this->_webappBaseUrlArray[0];
        return $baseUrl . '/' . $this->_shopId . '#/loan-request?token=' . $token;
    }

    protected function getOrderCreationUrl(): string
    {
        return $this->getApiUrl() . '/' . self::PATH_ORDER_CREATION;
    }

    protected function getLoanQuoteUrl(): string
    {
        return $this->getApiUrl() . '/' . self::PATH_LOAN_QUOTE;
    }

    protected function doRequest(string $url, string $httpMethod = self::HTTP_METHOD_GET, array $params = [], int $timeout = null): \stdClass
    {
        $ch = curl_init();

        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'X-Auth-Token: ' . $this->_apiKey,
        ]);

        if ($httpMethod == self::HTTP_METHOD_POST) {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
        } elseif ($httpMethod == self::HTTP_METHOD_GET && isset($params)) {
            $url = $url . '?' . http_build_query($params);
        }


        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT_MS, $this->_connectTimeout);
        curl_setopt($ch, CURLOPT_TIMEOUT_MS, !is_null($timeout) ? $timeout : $this->_timeout);

        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);

        $output         = json_decode(curl_exec($ch));
        $httpStatusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error          = curl_error($ch);
        $errorNumber    = curl_errno($ch);

        curl_close($ch);

        if ($this->_isInvalidResponse($output)) {
            throw new Exception('cURL error = ' . $error, $errorNumber);
        }

        if (200 != $httpStatusCode) {
            if ($this->_isInvalidErrorResponse($output)) {
                throw new Exception('Empty error response');
            }
            $validationMessages = [];
            switch ($httpStatusCode) {
                case 400:
                    $message            = 'Some fields contains errors';
                    $validationMessages = $this->_parseValidationMessages($output);
                    break;

                default:
                    $message = 'API unavailable, HTTP STATUS CODE = ' . $httpStatusCode;
            }

            $e = new Exception($message);
            $e->setValidationMessages($validationMessages);

            return $e->getValidationMessages();
        }

        return $output;
    }

    protected function _parseValidationMessages(string $response): array
    {
        $validationMessages = [];
        foreach ($response->errors as $field => $errors) {
            foreach ($errors as $error) {
                $validationMessages[] = $field . ': ' . $error;
            }
        }

        return $validationMessages;
    }

    protected function _isInvalidErrorResponse($response): bool
    {
        return (!isset($response->errors));
    }


    protected function _isInvalidResponse($response): bool
    {
        return empty($response)
            || !is_object($response);
    }

    /**
     * @return mixed
     */
    protected function getApiUrl()
    {
        $url = ($this->_apiBaseUrlArray[$this->_sandboxMode]) ? $this->_apiBaseUrlArray[$this->_sandboxMode] : $this->_apiBaseUrlArray[0];

        return $url . '/' . $this->_shopId;
    }
}