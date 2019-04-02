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

        for ($i = \Bitbull_Soisy_Client::MIN_INSTALMENTS; $i <= \Bitbull_Soisy_Client::MAX_INSTALMENTS; $i++) {
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
                'description' => ' '
            ),
            esc_attr($id) . '-address' => array(
                'type' => 'text',
                'class' => array('form-row form-row-wide validate-required'),
                'label' => __('Address','soisy'),
            ),
            esc_attr($id) . '-civic-number' => array(
                'type' => 'text',
                'class' => array('form-row form-row-wide validate-required'),
                'label' => __('Civic Number','soisy'),
            ),
            esc_attr($id) . '-postcode' => array(
                'type' => 'text',
                'class' => array('form-row form-row-wide validate-required'),
                'label' => __('Postcode / ZIP','soisy'),
            ),
            esc_attr($id) . '-city' => array(
                'type' => 'text',
                'class' => array('form-row form-row-wide validate-required'),
                'label' => __('City','soisy'),
            ),
            esc_attr($id) . '-province' => array(
                'type' => 'state',
                'class' => array('form-row form-row-wide validate-required'),
                'label' => __('Province','soisy'),
            ),
            esc_attr($id) . '-phone' => array(
                'type' => 'text',
                'class' => array('form-row form-row-wide validate-required'),
                'label' => __('Phone','soisy'),
            ),
            esc_attr($id) . '-fiscal-code' => array(
                'type' => 'text',
                'class' => array('form-row form-row-wide validate-required'),
                'label' => __('Fiscal Code','soisy'),
            ),
            esc_attr($id) . '-checkbox' => array(
                'type' => 'checkbox',
                'class' => array('form-row form-row-wide validate-required'),
                'label' => __('I Agree submitting the info to Soisy page','soisy') . '  '  . "<a target='_blank' href='https://www.soisy.it/privacy-policy/'>". __('Read more','soisy') . "</a>",
            ),
        ];
    }
}