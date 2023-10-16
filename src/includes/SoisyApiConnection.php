<?php
/**
 * @package  Soisy
 */
class SoisyApiConnection {
    private $soisyVars;
	
	public function __construct( $soisyVars ) {
	    $this->soisyVars = $soisyVars;
    }
	
	
	public function createSoisyOrder( $params ) {
	    $response = $this->doRequest( $this->getOrderCreationUrl(), 'POST', $params );
	
	    if ( isset( $response->token ) ) {
			
		    return $response->token;
	    }
	
	    return '';
    }
	
	public function getRedirectUrl( string $token ) {
        $baseUrl = $this->soisyVars['sandbox_mode'] ? $this->soisyVars['webappBaseUrl']['sandbox'] : $this->soisyVars['webappBaseUrl']['prod'];

        return $baseUrl . '/' . $this->soisyVars['shop_id'] . '#/loan-request?token=' . $token;
    }
	
	public function getApiUrl() {
        $url = $this->soisyVars['sandbox_mode'] ? $this->soisyVars['apiBaseUrl']['sandbox'] : $this->soisyVars['apiBaseUrl']['prod'];

        return $url . '/' . $this->soisyVars['shop_id'];
    }
	
	private function getOrderCreationUrl() {
		return $this->getApiUrl() . '/' . $this->soisyVars['path_order_creation'];
		//return $this->getApiUrl() . '/' . $this->soisyVars('path_order_creation');
	}
	
	private function doRequest( string $url, string $httpMethod = 'GET', array $params = [], int $timeout = null ) {
		$headers = [
			'X-Auth-Token' => $this->soisyVars['api_key'],
		];
		
		$timeout = ! is_null( $timeout ) ? $timeout : $this->soisyVars['timeout'];
		
		if ( $httpMethod == 'GET' && isset( $params ) ) {
			$url = $url . '?' . http_build_query( $params );
			$response = wp_remote_get( $url, [
				'timeout' => $timeout,
				'headers' => $headers
			] );
		} else {
			$response = wp_remote_post( $url, [
				'timeout' => $timeout,
				'headers' => $headers,
				'body'    => $params
			] );
		}
        
		if ( is_wp_error( $response ) ) {
			throw new \Error( $response->get_error_message() );
		}
		
		return json_decode( $response['body'] );
	}
}
