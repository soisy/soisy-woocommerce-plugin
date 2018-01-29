<?php
/**
 * @category Bitbull
 * @package  Bitbull_Soisy
 * @author   Gennaro Vietri <gennaro.vietri@bitbull.it>
*/
class Bitbull_Soisy_Exception extends Exception
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