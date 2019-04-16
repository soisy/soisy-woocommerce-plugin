<?php


use Soisy\Client;
use SoisyPlugin\Includes\Helper;
use SoisyPlugin\Includes\Settings;

class QuotesTest extends WP_Ajax_UnitTestCase
{

    public function setup()
    {
        parent::setup();
    }

    /**
     * @test
     */
    public function response_is_stored()
    {
        $response = new \Soisy\Loan\Quotes('RESPONSE TEST');
        $this->assertEquals('RESPONSE TEST', $response->getResponse());
    }

    /**
     * @test
     */
    public function product_ajax_call()
    {
        $this->_setRole('administrator');

        $_POST['_nonce'] = wp_create_nonce('soisy_product_loan_info_block');
        $_POST['price']  = 655;

        try {
            $this->_handleAjax('soisy_product_loan_info_block');
        } catch (WPAjaxDieContinueException $e) {

            $expectedResponse = [
                'data'   => __('Loan quote text', 'soisy'),
                'object' => Settings::LOAN_QUOTE_CSS_CLASS
            ];
            $actualResponse = json_decode($this->_last_response, true);

            $this->assertEquals($expectedResponse, $actualResponse);
        }
    }

    /**
     * @test
     */
    public function cart_ajax_call()
    {
        $this->_setRole('administrator');

        $_POST['_nonce'] = wp_create_nonce('soisy_cart_loan_info_block');
        $_POST['price']  = 655;

        try {
            $this->_handleAjax('soisy_cart_loan_info_block');
        } catch (WPAjaxDieContinueException $e) {

            $expectedResponse = [
                'data'   => __('Cart loan quote text', 'soisy'),
                'object' => Settings::CART_LOAN_QUOTE_CSS_CLASS
            ];
            $actualResponse = json_decode($this->_last_response, true);

            $this->assertEquals($expectedResponse, $actualResponse);
        }
    }
}