<?php

namespace Soisy;

use Soisy\Log\LoggerInterface;

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
     * Base url for API calls
     *
     * @var array
     */
    protected $_apiBaseUrlArray = [
        1 => 'http://api.sandbox.soisy.it/api/shops',
        0 => 'https://api.soisy.it/api/shops'
    ];

    /**
     * Base url for Soisy webapp
     *
     * @var array
     */
    protected $_webappBaseUrlArray = [
        1 => 'http://shop.sandbox.soisy.it',
        0 => 'https://shop.soisy.it'
    ];

    /**
     * Sandbox mode on/of
     *
     * @var bool
     */
    protected $_sandboxMode;

    /**
     * API key
     *
     * @var string
     */
    protected $_apiKey;

    /**
     * Shop ID
     *
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
     * @var stdClass
     */
    protected $_response = null;

    /**
     * @var LoggerInterface
     */
    protected $_logger;

    /**
     * @param string          $shopId
     * @param string          $apiKey
     * @param LoggerInterface $logger
     * @param bool            $sandboxMode
     */
    public function __construct($shopId, $apiKey, LoggerInterface $logger, $sandboxMode)
    {
        $this->_logger = $logger;
        $this->_sandboxMode = $sandboxMode;

        if ($sandboxMode) {
            $this->_shopId = self::SANDBOX_SHOP_ID;
            $this->_apiKey = self::SANDBOX_API_KEY;

            return;
        }

        $this->_shopId = $shopId;
        $this->_apiKey = $apiKey;
    }

    /**
     * @param array $params
     * @return stdClass
     */
    public function getAmount(array $params)
    {
        $rawResponse = $this->_doRequest($this->_getLoanQuoteUrl(), self::HTTP_METHOD_GET, $params);

        return $rawResponse;
    }

    /**
     * Perform a search for suggestions
     *
     * @param array $params
     *
     * @return Token
     */
    public function getToken(array $params)
    {
        $rawResponse = $this->_doRequest($this->_getOrderCreationUrl(), self::HTTP_METHOD_POST, $params);

        $result = new Token();
        $result->setResponse($rawResponse);

        return $result;
    }

    /**
     * @return string
     */
    protected function _getOrderCreationUrl()
    {
        return $this->_getApiUrl() . '/' . self::PATH_ORDER_CREATION;
    }

    /**
     * @return string
     */
    protected function _getLoanQuoteUrl()
    {
        return $this->_getApiUrl() . '/' . self::PATH_LOAN_QUOTE;
    }

    /**
     * Build and return the redirect url
     *
     * @param string $token
     * @return string
     */
    public function getRedirectUrl($token)
    {
        return $this->_getRedirectUrl() . '/' . $this->_shopId . '#/loan-request?token=' . $token;
    }

    /**
     * Build and execute request via CURL.
     *
     * @param string $url
     * @param string $httpMethod
     * @param array $params
     * @param int $timeout
     * @return stdClass
     * @throws Soisy_Exception
     */
    protected function _doRequest($url, $httpMethod = self::HTTP_METHOD_GET, $params = [], $timeout = null)
    {
        $this->_logger->debug("Performing API request to url: " . $url . " with method: " . $httpMethod);
        $this->_logger->debug("Params: " . print_r($params, true));

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

        $output = json_decode(curl_exec($ch));
        $httpStatusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        $errorNumber = curl_errno($ch);

        curl_close($ch);

        $this->_logger->debug("HTTP Status Code: " . $httpStatusCode);
        $this->_logger->debug("Curl Error: " . $error);
        $this->_logger->debug("Curl Error Numbre: " . $errorNumber);
        $this->_logger->debug("Raw response: " . print_r($output, true));

        if ($this->_isInvalidResponse($output)) {
            throw new Soisy_Exception('cURL error = ' . $error, $errorNumber);
        }

        if (200 != $httpStatusCode) {
            if ($this->_isInvalidErrorResponse($output)) {
                throw new Soisy_Exception('Empty error response');
            }
            $validationMessages = [];
            switch ($httpStatusCode) {
                case 400:
                    $message = 'Some fields contains errors';
                    $validationMessages = $this->_parseValidationMessages($output);
                    break;

                default:
                    $message = 'API unavailable, HTTP STATUS CODE = ' . $httpStatusCode;
            }

            $e = new Soisy_Exception($message);
            $e->setValidationMessages($validationMessages);

            return $e->getValidationMessages();
        }

        return $output;
    }

    /**
     * @param string $response
     * @return array
     */
    protected function _parseValidationMessages($response)
    {
        $validationMessages = [];
        foreach ($response->errors as $field => $errors) {
            foreach ($errors as $error) {
                $validationMessages[] = $field . ': ' . $error;
            }
        }

        return $validationMessages;
    }

    /**
     * @param $response
     * @return bool
     */
    protected function _isInvalidErrorResponse($response)
    {
        return (!isset($response->errors));
    }

    /**
     * @param $response
     * @return bool
     */
    protected function _isInvalidResponse($response)
    {
        return empty($response)
            || !is_object($response);
    }


    /**
     * Get redirect url
     *
     * @return mixed|null
     */
    protected function _getRedirectUrl()
    {
        return ($this->_webappBaseUrlArray[$this->_sandboxMode]) ? $this->_webappBaseUrlArray[$this->_sandboxMode] : $this->_webappBaseUrlArray[0];
    }

    /**
     * Get API url
     *
     * @return mixed
     */
    protected function _getApiUrl()
    {
        $url = ($this->_apiBaseUrlArray[$this->_sandboxMode]) ? $this->_apiBaseUrlArray[$this->_sandboxMode] : $this->_apiBaseUrlArray[0];

        return $url . '/' . $this->_shopId;
    }
}