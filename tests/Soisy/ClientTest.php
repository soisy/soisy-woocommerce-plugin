<?php

use Soisy\Client;

class ClientTest extends WP_UnitTestCase
{

    /** @var Client */
    private $sandboxClient;

    /** @var Client */
    private $prodClient;


    public function setUp()
    {
        parent::setUp();

        $this->sandboxClient = new Client(
            null,
            null,
            true
        );

        $this->prodClient = new Client(
            'test',
            'test',
            false
        );
    }

    /**
     * @test
     */
    public function get_api_url()
    {
        $this->assertEquals('http://api.sandbox.soisy.it/api/shops/partnershop', $this->sandboxClient->getApiUrl());
        $this->assertEquals('https://api.soisy.it/api/shops/test', $this->prodClient->getApiUrl());
    }

    /**
     * @test
     */
    public function get_redirect_url()
    {
        $this->assertEquals(
            'http://shop.sandbox.soisy.it/partnershop#/loan-request?token=token',
            $this->sandboxClient->getRedirectUrl('token')
        );

        $this->assertEquals(
            'https://shop.soisy.it/test#/loan-request?token=token',
            $this->prodClient->getRedirectUrl('token')
        );
    }

    /**
     * @test
     */
    public function get_token_without_errors()
    {
        $token = $this->sandboxClient->getToken([
            'amount'      => 123456,
            'instalments' => 9,
        ]);

        $this->assertTrue(is_string($token));
    }

    /**
     * @test
     * @expectedException \DomainException
     */
    public function get_token_throws_domain_exception()
    {
        $this->sandboxClient->getToken([
            'amount'      => 100000,
            'instalments' => 1,
        ]);
    }

    /**
     * @test
     * @dataProvider errorMessagesDataProvider
     */
    public function error_messages(array $params, string $expectedErrorMessage)
    {
        try {
            $this->sandboxClient->getToken($params);
        } catch (\DomainException $e) {
            $this->assertEquals($expectedErrorMessage, trim($e->getMessage()));
        }
    }

    /**
     * @test
     */
    public function get_amount()
    {
        $response = $this->getArrayFromResponse($this->sandboxClient->getAmount([
            'amount'      => 35678,
            'instalments' => 3,
        ]));

        $this->assertArrayHasKey('min', $response);
        $this->assertArrayHasKey('median', $response);
        $this->assertArrayHasKey('max', $response);
    }

    public function errorMessagesDataProvider(): array
    {
        return [
            [
                [
                    'amount'      => 1,
                    'instalments' => 9,
                ],
                'Questo valore dovrebbe essere compreso tra 25000 e 1500000',
            ],
            [
                [
                    'amount'      => 123456,
                    'instalments' => 1,
                ],
                'Il numero di rate deve essere compreso fra 3 e 36',
            ],
            [
                [
                    'amount'      => 123456,
                    'instalments' => 12,
                    'fiscalCode'  => 'ERR',
                ],
                'Codice fiscale non valido',
            ],
        ];
    }

    private function getArrayFromResponse(\stdClass $response): array
    {
        return json_decode(json_encode($response), true);
    }
}