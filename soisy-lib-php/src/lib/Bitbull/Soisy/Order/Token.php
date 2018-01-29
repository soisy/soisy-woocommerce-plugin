<?php
/**
 * @category Bitbull
 * @package  Bitbull_Soisy
 * @author   Gennaro Vietri <gennaro.vietri@bitbull.it>
 */
class Bitbull_Soisy_Order_Token
{
    protected $_response = null;

    public function __construct($response = null)
    {
        if ($response) {
            $this->setResponse($response);
        }
    }

    public function setResponse($response)
    {
        $this->_response = $response;
    }

    public function getToken()
    {
        return (isset($this->_response->token)) ? $this->_response->token : null;
    }

    public function getErrorFromSoisy()
    {
        return (isset($this->_response[0])) ? strtok($this->_response[0], ':') : null;
    }
}