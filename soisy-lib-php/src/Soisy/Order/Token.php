<?php

namespace Soisy\Order;

/**
 * @package  Soisy
 */
class Token
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