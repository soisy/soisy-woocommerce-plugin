<?php

namespace Soisy\Loan;

class Quotes
{

    /** @var null|mixed */
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

    /**
     * @return mixed
     */
    public function getResponse()
    {
        return $this->_response;
    }
}