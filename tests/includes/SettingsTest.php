<?php


class SettingsTest extends WP_UnitTestCase
{

    /**
     * @test
     */
    public function settings_list()
    {
        $settings = \SoisyPlugin\Includes\Settings::adminSettingsForm();

        $this->assertEquals(array_keys($settings), [
            'enabled',
            'sandbox_mode',
            'shop_id',
            'api_key',
        ]);
    }
}