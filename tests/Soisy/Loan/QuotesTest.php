<?php


class QuotesTest extends WP_UnitTestCase
{

    /**
     * @test
     */
    public function response_is_stored()
    {
        $response = new \Soisy\Loan\Quotes('RESPONSE TEST');
        $this->assertEquals('RESPONSE TEST', $response->getResponse());
    }
}