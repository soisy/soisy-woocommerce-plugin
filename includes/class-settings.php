<?php
/**
 * @package  Soisy
 */

namespace SoisyPlugin\Includes;

use Soisy\Client;

class Settings
{
    const LOAN_QUOTE_CSS_CLASS = 'woocommerce-soisy-product-amount';
    const CART_LOAN_QUOTE_CSS_CLASS = 'woocommerce-soisy-cart-amount';

    const OPTION_NAME = 'woocommerce_soisy_settings';
    const INSTALMENT_TABLE_OPTION_NAME = self::OPTION_NAME . '_instalment_table';

    /**
     * Instalment per options.
     * @return array
     */
    static function getInstalmentPeriod()
    {
        $result = [];

        for ($i = Client::MIN_INSTALMENTS; $i <= Client::MAX_INSTALMENTS; $i++) {
            $result[$i] = $i;
        }

        return $result;
    }


    /**
     * Return admin settings form for Soisy
     * @return array
     */
    static function adminSettingsForm()
    {
        return array(
            'enabled' => array(
                'title' => __('Enable', 'soisy'),
                'type' => 'checkbox',
                'label' => __('Enable Soisy payment', 'soisy'),
                'default' => 'yes'
            ),

            'sandbox_mode' => array(
                'title' => __('Sandbox mode', 'soisy'),
                'type' => 'select',
                'default' => 'median',
                'class' => 'wc-enhanced-select',
                'desc_tip' => true,
                'options' => array(
                    0 => __('No', 'woocommerce'),
                    1 => __('Yes', 'woocommerce')
                ),
            ),

            'shop_id' => array(
                'title' => __('Shop ID', 'soisy'),
                'type' => 'text',
                'desc_tip' => true,
            ),

            'api_key' => array(
                'title' => __('API key', 'soisy'),
                'type' => 'text',
                'default' => ''
            )
        );
    }

    /**
     * Get values for soisy fields from billing adrress
     * @param $id
     * @param $key
     * @return mixed|null
     */
    static function getCheckoutFormFieldValueByKey($id, $key)
    {
        $values = [
            esc_attr($id) . '-instalment' => '',
            esc_attr($id) . '-address' => esc_attr(WC()->customer->get_billing_address_1()),
            esc_attr($id) . '-civic-number' => esc_attr(WC()->customer->get_billing_address_2()),
            esc_attr($id) . '-postcode' => esc_attr(WC()->customer->get_billing_postcode()),
            esc_attr($id) . '-city' => esc_attr(WC()->customer->get_billing_city()),
            esc_attr($id) . '-province' => esc_attr(WC()->customer->get_billing_state()),
            esc_attr($id) . '-phone' => esc_attr(WC()->customer->get_billing_phone()),
            esc_attr($id) . '-fiscal-code' => '',
            esc_attr($id) . '-checkbox' => '',
        ];

        return (array_key_exists($key, $values)) ? $values[$key] : null;
    }

    /**
     * Return checkout form fields for soisy payment
     * @param $id
     * @param $settings
     * @return array
     */
    static function checkoutForm($id, $settings)
    {

        return [
            esc_attr($id) . '-instalment' => array(
                'type' => 'select',
                'class' => array('form-row form-row-wide validate-required'),
                'label' => __('Instalment','soisy'),
                'options' => self::getInstalmentPeriod(),
                'description' => ' ',
                'required' => true,
            ),
            esc_attr($id) . '-fiscal-code' => array(
                'type' => 'text',
                'class' => array('form-row form-row-wide validate-required'),
                'label' => __('Fiscal Code','soisy'),
                'required' => true,
            ),
            esc_attr($id) . '-checkbox' => array(
                'type' => 'checkbox',
                'class' => array('form-row form-row-wide validate-required'),
                'label' => __('I Agree submitting the info to Soisy page','soisy') . '  '  . "<a target='_blank' href='https://www.soisy.it/privacy-policy/'>". __('Read Soisy Privacy','soisy') . "</a>",
                'required' => true,
            ),
        ];
    }
}