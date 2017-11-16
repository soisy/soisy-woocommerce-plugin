<?php
/**
 * @category Bitbull
 * @package  Bitbull_Soisy
 * @author   Martins Saukums <martins.saukums@bitbull.it>
 */

namespace Bitbull_Soisy\Includes;

class Settings
{
    /**
     * Instalment per options.
     * @return array
     */
    static function getInstalmentPeriod()
    {
        $result = [];

        for ($i = 3; $i <= 60; $i++) {
            $result[$i] = $i;
        }

        return $result;
    }

    /**
     * Upfront payment options.
     * @return array
     */
    static function getUpfrontPercentage()
    {
        return [
            100 => 'Off',
            10 => '10%',
            20 => '20%',
            30 => '30%',
            40 => '40%',
            50 => '50%',
            60 => '60%',
            70 => '70%',
            80 => '80%',
            90 => '90%',
        ];
    }

    /**
     * Return admin settings form for Soisy
     * @return array
     */
    static function adminSettingsForm()
    {

        $countryList = array();
        $countries_obj = new \WC_Countries();

        foreach ($countries_obj->__get('countries') as $key => $value) {
            $countryList[$key] = $value;
        }

        return array(
            'enabled' => array(
                'title' => __('Enable', 'soisy'),
                'type' => 'checkbox',
                'label' => __('Enable Soisy payment', 'soisy'),
                'default' => 'yes'
            ),

            'percentage' => array(
                'title' => __('Upfront payment percentage', 'soisy'),
                'type' => 'select',
                'description' => __('Select upfront payment percentage', 'soisy'),
                'default' => 'html',
                'class' => 'wc-enhanced-select',
                'options' => self::getUpfrontPercentage(),
                'desc_tip' => true,
            ),

            'title' => array(
                'title' => __('Title', 'soisy'),
                'type' => 'text',
                'description' => __('This controls the title which the user sees during checkout.', 'soisy'),
                'default' => __('Soisy', 'soisy'),
                'desc_tip' => true,
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
            ),

            'terms_and_conditions_link' => array(
                'title' => __('Terms and Conditions link', 'soisy'),
                'type' => 'text',
                'default' => 'https://www.soisy.it/privacy-policy/'
            ),

            'description' => array(
                'title' 		=> 'Description',
                'type' 			=> 'textarea',
                'description' 	=> 'This controls the description which the user sees during checkout.',
                'default' 		=> ''
            ),

            'enable_for_countries' => array(
                'title' => __('Enable for countries', 'soisy'),
                'type' => 'multiselect',
                'class' => 'wc-enhanced-select',
                'css' => 'width: 400px;',
                'description' => __('Payment from Specific Countries', 'soisy'),
                'options' => $countryList,
                'desc_tip' => true,
                'default' => 'IT',
                'custom_attributes' => array(
                    'data-placeholder' => __('Select county', 'woocommerce'),
                ),
            ),

            'instalments_period' => array(
                'title' => __('Instalments', 'woocommerce'),
                'type' => 'multiselect',
                'description' => __('Choose from 3 to 60', 'woocommerce'),
                'default' => 'html',
                'class' => 'wc-enhanced-select',
                'options' => self::getInstalmentPeriod(),
                'desc_tip' => true,
            ),

            'max_order_total' => array(
                'title' => __('Maximum Order Total', 'soisy'),
                'type' => 'text',
                'default' => ''
            ),

            'min_order_total' => array(
                'title' => __('Minimum Order Total', 'soisy'),
                'type' => 'text',
                'default' => '260'
            ),

            'loan_quote_placement' => array(
                'title' => __('Product page Loan Quote block placement class', 'soisy'),
                'type' => 'text',
                'description' => __('Choose a HTML class to append Loan Quote block in product page', 'soisy'),
                'default' => 'woocommerce-Price-amount'
            ),

            'loan_quote_text' => array(
                'title' => __('Text for product page loan quote block', 'soisy'),
                'type' => 'textarea',
                'description' => __('Provide text for product page loan quote block with placeholders for variables {INSTALMENT_AMOUNT},{INSTALMENT_PERIOD},{TOTAL_REPAID},{TAEG}',
                    'soisy'),
                'default' => 'You can also pay installments, eg € {INSTALMENT_AMOUNT}, in {INSTALMENT_PERIOD} months, with a total cost of € {TOTAL_REPAID} and TAEG {TAEG}. Just choose "Pay with Soisy" when choosing the payment method'
            ),

            'cart_loan_quote_placement' => array(
                'title' => __('Cart page Loan Quote block placement class', 'soisy'),
                'type' => 'text',
                'description' => __('Choose a HTML class to append Loan Quote block in product page', 'soisy'),
                'default' => 'shop_table_responsive'
            ),

            'cart_loan_quote_text' => array(
                'title' => __('Text for cart page loan quote block', 'soisy'),
                'type' => 'textarea',
                'description' => __('Provide text for cart page loan quote block with placeholders for variables {INSTALMENT_AMOUNT},{INSTALMENT_PERIOD},{TOTAL_REPAID},{TAEG}',
                    'soisy'),
                'default' => 'You can also pay installments, eg € {INSTALMENT_AMOUNT}, in {INSTALMENT_PERIOD} months, with a total cost of € {TOTAL_REPAID} and TAEG {TAEG}. Just choose "Pay with Soisy" when choosing the payment method'
            ),

            'information_about_loan' => array(
                'title' => __('Information about loan', 'soisy'),
                'type' => 'select',
                'default' => 'median',
                'class' => 'wc-enhanced-select',
                'desc_tip' => true,
                'options' => array(
                    'min' => __('Minimum', 'woocommerce'),
                    'median' => __('Average', 'woocommerce'),
                    'max' => __('Maximum', 'woocommerce'),
                ),
            ),

            'zero_interest' => array(
                'title' => __('Zero Interest rate', 'soisy'),
                'type' => 'select',
                'default' => 'median',
                'class' => 'wc-enhanced-select',
                'desc_tip' => true,
                'options' => array(
                    "false" => __('No', 'woocommerce'),
                    "true" => __('Yes', 'woocommerce')
                ),
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
                'label' => __('Instalment'),
                'options' => ($settings['instalments_period']) ? array_combine($settings['instalments_period'],
                    $settings['instalments_period']) : self::getInstalmentPeriod(),
                'description' => ' '
            ),
            esc_attr($id) . '-address' => array(
                'type' => 'text',
                'class' => array('form-row form-row-wide validate-required'),
                'label' => __('Address'),
            ),
            esc_attr($id) . '-civic-number' => array(
                'type' => 'text',
                'class' => array('form-row form-row-wide validate-required'),
                'label' => __('Civic Number'),
            ),
            esc_attr($id) . '-postcode' => array(
                'type' => 'text',
                'class' => array('form-row form-row-wide validate-required'),
                'label' => __('Postcode / ZIP'),
            ),
            esc_attr($id) . '-city' => array(
                'type' => 'text',
                'class' => array('form-row form-row-wide validate-required'),
                'label' => __('City'),
                //'placeholder' => __('Enter something'),
            ),
            esc_attr($id) . '-province' => array(
                'type' => 'state',
                'class' => array('form-row form-row-wide validate-required'),
                'label' => __('Province'),
            ),
            esc_attr($id) . '-phone' => array(
                'type' => 'text',
                'class' => array('form-row form-row-wide validate-required'),
                'label' => __('Phone'),
            ),
            esc_attr($id) . '-fiscal-code' => array(
                'type' => 'text',
                'class' => array('form-row form-row-wide validate-required'),
                'label' => __('Fiscal Code'),
            ),
            esc_attr($id) . '-checkbox' => array(
                'type' => 'checkbox',
                'class' => array('form-row form-row-wide validate-required'),
                'label' => __(' I Agree submitting the info to Soisy page') . '  '  . "<a target='_blank' href='{$settings['terms_and_conditions_link']}'>". __('Reed more') . "</a>",
            ),
        ];
    }
}