<?php

namespace Soisy;

use Exception as BaseException;

/**
 * @package  Soisy
*/
class Exception extends BaseException
{
    /**
     * @var array
    */
    protected $_validationMessages = null;

    public function setValidationMessages($messages)
    {
        $this->_validationMessages = $messages;
    }

    public function getValidationMessages()
    {
        return $this->_validationMessages;
    }
}