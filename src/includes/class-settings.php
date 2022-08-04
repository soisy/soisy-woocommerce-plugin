<?php
/**
 * @package  Soisy
 */

namespace Soisy\Includes;

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
		    'enabled'                  => [
			    'title'   => __( 'Enable', 'soisy' ),
			    'type'    => 'checkbox',
			    'label'   => __( 'Enable Soisy payment', 'soisy' ),
			    'default' => 'yes',
		    ],
		    'sandbox_mode'             => [
			    'title'       => __( 'Sandbox mode', 'soisy' ),
			    'type'        => 'select',
			    'default'     => 1,
			    'class'       => 'wc-enhanced-select',
			    'options'     => [
				    1 => __( 'Yes', 'woocommerce' ),
				    0 => __( 'No', 'woocommerce' ),
			    ],
			    'description' => __( 'Soisy sandbox param description', 'soisy' ),
		    ],
		    'shop_id'                  => [
			    'title'       => __( 'Shop ID', 'soisy' ),
			    'type'        => 'text',
			    'description' => __( 'Soisy shopId param description', 'soisy' ),
		    ],
		    'api_key'                  => [
			    'title'       => __( 'API key', 'soisy' ),
			    'type'        => 'text',
			    'default'     => '',
			    'description' => __( 'Soisy apiKey param description', 'soisy' ),
		    ],
		    'quote_instalments_amount' => [
			    'title'       => __( 'Instalments', 'soisy' ),
			    'type'        => 'text',
			    'description' => __( 'Number of Instalments (this applies only to quote widget)', 'soisy' ),
			    'default'     => soisyVars()['quote_instalments_amount']
		    ],
		    'min_amount'               => [
			    'title'       => __( 'Minimum amount', 'soisy' ),
			    'type'        => 'text',
			    'description' => __( 'Minimum financing amount', 'soisy' ),
			    'default'     => soisyVars()['min_amount']
		    ],
		    'max_amount'               => [
			    'title'       => __( 'Maximum amount', 'soisy' ),
			    'type'        => 'text',
			    'description' => __( 'Maximum financing amount', 'soisy' ),
			    'default'     => soisyVars()['max_amount']
		    ],
		    'soisy_zero'               => [
			    'title'       => __( 'Interest Free', 'soisy' ),
			    'type'        => 'select',
			    'default'     => 0,
			    'class'       => 'wc-enhanced-select',
			    'options'     => [
				    1 => __( 'Yes', 'woocommerce' ),
				    0 => __( 'No', 'woocommerce' ),
			    ],
			    'description' => __( 'Enable Zero interest rates. If enabled, your merchant fees will be updated accordingly, as per TOS Agreement', 'soisy' ),
		    ],
		    'logger'                   => [
			    'title'       => __( 'Activate debug logger', 'soisy' ),
			    'type'        => 'select',
			    'default'     => 0,
			    'class'       => 'wc-enhanced-select',
			    'options'     => [
				    1 => __( 'Yes', 'woocommerce' ),
				    0 => __( 'No', 'woocommerce' ),
			    ],
			    'description' => __( 'Enable The Debug Logger', 'soisy' ),
		    ],
		    'reset_zero'             => [
			    'type'  => 'hidden',
			    'default' => 'yes'
		    ]
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
	            'label' => __('I Agree submitting the info to Soisy page', 'soisy') . ' ' . "<a target='_blank' href='https://www.soisy.it/privacy-policy/'>" . __('Read Soisy Privacy', 'soisy') . "</a>",
	            'required' => true,
            ],
        ];
    }
}
