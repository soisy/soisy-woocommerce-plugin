<?php

use SoisyPlugin\Includes\Helper;

/**
 * Class HelperTest
 *
 * @package Soisy_Woocommerce_Plugin
 */
class HelperTest extends WP_UnitTestCase
{

    /**
     * @test
     */
    public function min_and_max_correct_amount()
    {
        $this->assertFalse(Helper::isCorrectAmount(200));
        $this->assertFalse(Helper::isCorrectAmount(40000));
        $this->assertTrue(Helper::isCorrectAmount(12345));
    }

    /**
     * @test
     */
    public function formatted_number_output()
    {
        $this->assertEquals('12,34', Helper::formatNumber(12.34));
        $this->assertEquals('1.234,56', Helper::formatNumber(1234.56));
    }
}
