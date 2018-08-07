(function ($, data) {

    var instalmentSelect = {

        init: function (data) {
            this.data = data;
            if (!this.data.preselected_value) {
                this.data.preselected_value = $(this.data.select).first().val();
            }
            $(this.data.select).val(this.data.preselected_value);
            this.makeRequest();

            $(this.data.select).first().on('change', this.makeRequest.bind(this));
        },

        makeRequest: function () {
            var ajax_data = {
                action: this.data.action,
                instalments: this.getSelectValue()
            };


            $.ajax({
                context: this,
                type: "post",
                url: this.data.ajax_url,
                data: ajax_data,
                success: function (data) {
                    $(this.data.select).next().first().html(data.instalment_amount + ' ' + this.data.text);
                }
            });
        },

        getSelectValue: function () {

            return $(this.data.select).val();
        }
    };

    jQuery('body').on('updated_checkout', function () {
        instalmentSelect.init(data);
    });
})(jQuery, php_vars);