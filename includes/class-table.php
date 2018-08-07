<?php
/**
 * @category Bitbull
 * @package  Bitbull_Soisy
 * @author   Martins Saukums <martins.saukums@bitbull.it>
 */

namespace Bitbull_Soisy\Includes;

class Table {

    /**
     * Return admin settings instalment table for soisy
     * @return array
     */
    static function adminSettingsTableForm($instance)
    {
        wp_enqueue_script('woocommerce_instalment_table_rate_rows');

        ?>
        <table id="instalment_rates" class="instalment widefat" cellspacing="0" style="position:relative;">
            <thead>
            <tr>
                <th class="check-column"><input type="checkbox"></th>
                <th><?php _e('Instalments', 'Soisy'); ?>&nbsp;
                    <a class="tips" data-tip="<?php _e('Instalments','Soisy'); ?>">[?]</a>
                </th>
                <th class="cost cost_per_item"><?php _e('Price', 'Soisy'); ?>&nbsp;
                    <a class="tips" data-tip="<?php _e('Price', 'Soisy'); ?>">[?]</a>
                </th>
            </tr>
            </thead>
            <tfoot>
            <tr>
                <th colspan="4">
                    <a href="#" class="add-rate button button-primary"><?php _e('Add Instalment Rate', 'Soisy'); ?></a>
                    <a href="#" class="remove button"><?php _e('Delete selected rows', 'Soisy'); ?></a>
                </th>
            </tfoot>
            <tbody class="table_rates"
                   data-rates="<?php echo esc_attr(wp_json_encode($instance->get_form_data())); ?>"></tbody>
        </table>
        <script type="text/template" id="tmpl-table-rate-instalment-row-template">
            <tr class="table_rate">
                <td class="check-column">
                    <input type="checkbox" name="select"/>
                    <input type="hidden" class="rate_id" name="rate_id[]" value="{{{ data.period }}}"/>
                </td>
                <td>
                    <select class="select" name="instalment_period[]" style="min-width:100px;">
                        <?php foreach ($instance->settings['instalments_period'] as $instalmentsPeriod): ?>
                            <option value="<?php echo $instalmentsPeriod; ?>" <# if(data.period ==  '<?php echo $instalmentsPeriod; ?>') { #> selected="selected" <# } #> ><?php echo $instalmentsPeriod; ?></option>
                        <?php endforeach; ?>
                    </select>
                </td>
                <td class="cost cost_per_item">
                    <input type="text" class="text" value="{{{ data.amount }}}"
                           name="instalment_amount[]"
                           placeholder="<?php _e('0', 'Soisy'); ?>" size="4"/>
                </td>
            </tr>
        </script>
        <?php
    }
}