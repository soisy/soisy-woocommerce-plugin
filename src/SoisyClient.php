<?php

namespace Soisy;

/**
 * @package  Soisy
 */
class SoisyClient
{
    const LOAN_QUOTE_CDN_JS = 'https://cdn.soisy.it/loan-quote-widget.js';

    const SANDBOX_SHOP_ID = 'partnershop';
    const SANDBOX_API_KEY = 'partnerkey';

    const QUOTE_INSTALMENTS_AMOUNT = 12;

    const MIN_AMOUNT = 100;
    const MAX_AMOUNT = 15000;

    const PATH_ORDER_CREATION = 'orders';
    const PATH_LOAN_QUOTE = 'loan-quotes';

    private $apiBaseUrl = [
        'sandbox' => 'https://api.sandbox.soisy.it/api/shops',
        'prod'    => 'https://api.soisy.it/api/shops'
    ];

    private $webappBaseUrl = [
        'sandbox' => 'https://shop.sandbox.soisy.it',
        'prod'    => 'https://shop.soisy.it'
    ];

    /** @var bool */
    private $isSandboxMode;

    /** @var string */
    private $apiKey;

    /** @var string */
    private $shopId;

    private $timeout = 4000;

    public function __construct(?string $shopId, ?string $apiKey, $sandboxMode = true)
    {
        if ($this->isSandboxModeWanted($sandboxMode)) {
            $this->isSandboxMode = true;
            $this->shopId        = self::SANDBOX_SHOP_ID;
            $this->apiKey        = self::SANDBOX_API_KEY;

            return;
        }

        $this->isSandboxMode = false;
        $this->shopId        = $shopId;
        $this->apiKey        = $apiKey;
    }
	

    public function createSoisyOrder(array $params): ?string
    {
	    $response = $this->doRequest( $this->getOrderCreationUrl(), 'POST', $params );
	
	    if (isset($response->token)) {
            return $response->token;
        }

        return null;
    }

    public function getRedirectUrl(string $token): string
    {
        $baseUrl = $this->isSandboxMode ? $this->webappBaseUrl['sandbox'] : $this->webappBaseUrl['prod'];

        return $baseUrl . '/' . $this->shopId . '#/loan-request?token=' . $token;
    }

    public function getApiUrl(): string
    {
        $url = $this->isSandboxMode ? $this->apiBaseUrl['sandbox'] : $this->apiBaseUrl['prod'];

        return $url . '/' . $this->shopId;
    }

    private function getOrderCreationUrl(): string
    {
        //return $this->getApiUrl() . 'SoisyClient.php/' . self::PATH_ORDER_CREATION;
        return $this->getApiUrl() . '/' . self::PATH_ORDER_CREATION;
    }

    private function doRequest(string $url, string $httpMethod = 'GET', array $params = [], int $timeout = null): \stdClass
    {
        $headers = [
            'X-Auth-Token' => $this->apiKey,
        ];

        $timeout = !is_null($timeout) ? $timeout : $this->timeout;

        if ($httpMethod == 'GET' && isset($params)) {
            $url = $url . '?' . http_build_query($params);
            $response = wp_remote_get($url, ['timeout' => $timeout, 'headers' => $headers]);
        } else {
            $response = wp_remote_post($url, ['timeout' => $timeout, 'headers' => $headers, 'body' => $params]);
        }

        if (is_wp_error( $response )) {
            throw new \Error($response->get_error_message());
        }

        return json_decode($response['body']);
    }

    private function isSandboxModeWanted($sandbox): bool
    {
        return $sandbox === "1" || $sandbox === 1 || $sandbox === true || is_null($sandbox);
    }
}