<?php
/**
 * @package  Soisy
 */

namespace SoisyPlugin\Includes;

class Settings
{

    const LOAN_QUOTE_CSS_CLASS = 'woocommerce-soisy-product-amount';
    const CART_LOAN_QUOTE_CSS_CLASS = 'woocommerce-soisy-cart-amount';

    const OPTION_NAME = 'woocommerce_soisy_settings';


    /**
     * Return admin settings form for Soisy
     *
     * @return array
     */
    static function adminSettingsForm()
    {
        return [
            'enabled' => [
                'title'   => __('Enable', 'soisy'),
                'type'    => 'checkbox',
                'label'   => __('Enable Soisy payment', 'soisy'),
                'default' => 'yes'
            ],
            'sandbox_mode' => [
                'title'    => __('Sandbox mode', 'soisy'),
                'type'     => 'select',
                'default'  => 1,
                'class'    => 'wc-enhanced-select',
                'desc_tip' => true,
                'options'  => [
                    1 => __('Yes', 'woocommerce'),
                    0 => __('No', 'woocommerce'),
                ],
            ],
            'shop_id' => [
                'title'    => __('Shop ID', 'soisy'),
                'type'     => 'text',
                'desc_tip' => true,
            ],
            'api_key' => [
                'title'   => __('API key', 'soisy'),
                'type'    => 'text',
                'default' => '',
            ],
        ];
    }

    static function getCheckoutFormFieldValueByKey($id, $key)
    {
        $values = [
            esc_attr($id) . '-phone'        => esc_attr(WC()->customer->get_billing_phone()),
            esc_attr($id) . '-checkbox'     => '',
        ];

        return (array_key_exists($key, $values)) ? $values[$key] : null;
    }

    static function checkoutForm($id)
    {

        return [
            esc_attr($id) . '-checkbox'    => [
                'type'     => 'checkbox',
                'class'    => ['form-row form-row-wide validate-required'],
                'label'    => __('I Agree submitting the info to Soisy page', 'soisy') . '  ' . "<a target='_blank' href='https://www.soisy.it/privacy-policy/'>" . __('Read Soisy Privacy', 'soisy') . "</a>",
                'required' => true,
            ],
        ];
    }
}