<?php

class ClientTest extends WP_UnitTestCase
{

    private $client;


    public function setUp()
    {
        parent::setUp();

        $this->client = new \Soisy\Client(
            null,
            null,
            true
        );
    }

    /**
     * @test
     */
    public function get_amount()
    {
        $response = $this->getArrayFromResponse($this->client->getAmount([
            'amount'      => 35678,
            'instalments' => 3,
        ]));

        $this->assertArrayHasKey('min', $response);
        $this->assertArrayHasKey('median', $response);
        $this->assertArrayHasKey('max', $response);
    }

    private function getArrayFromResponse(\stdClass $response): array
    {
        return json_decode(json_encode($response), true);
    }
}