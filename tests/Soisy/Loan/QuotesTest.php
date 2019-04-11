<?php


class QuotesTest extends WP_Ajax_UnitTestCase
{

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
    public function ajax_call()
    {
        $this->_handleAjax('soisy_product_loan_info_block');
    }
}