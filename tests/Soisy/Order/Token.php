<?php


class TokenTest extends WP_UnitTestCase
{

    /**
     * @test
     */
    public function response_is_stored()
    {
        $response = new \Soisy\Order\Token('token');
        $this->assertEquals('token', $response->getResponse());
    }
}