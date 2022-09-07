window.addEventListener('DOMContentLoaded', (doc) => {
    const wp = window.wp;
    const hasVariation = document.querySelector('form.variations_form.cart');
    if (!!hasVariation) {
        const container=document.querySelector('.woocommerce-variation-add-to-cart');

        const pv = JSON.parse(hasVariation.dataset.product_variations);
        let selects = [];

        const removeOldWidget = (container) => {
            const el = document.querySelector('soisy-loan-quote');
            if (!!el) {
                el.remove();
            }
        };
        const renderWidget = (container, amount) => {
            const actual = parseFloat(amount);
            const min = parseFloat(wp.min_amount) > 100 ? parseFloat(wp.min_amount) : 100;
            const max = parseFloat(wp.max_amount);
            if (actual >= min && actual <= max) {
                let el = document.createElement('soisy-loan-quote');
                el.setAttribute('shop-id', wp.shop_id);
                el.setAttribute('amount', amount);
                el.setAttribute('instalments', wp.quote_instalments_amount);
                if (!!wp.soisy_zero && wp.soisy_zero) {
                    el.setAttribute('zero-interest-rate', wp.soisy_zero);
                }

                container.prepend(el);
            }

        };

        const match = () => {
            removeOldWidget(container);
            pv.forEach((variation) => {
                const Attrs = Object.entries(variation.attributes);
                let check = 0;
                for (const [rawField, val] of Attrs) {
                    const idField = rawField.replace('attribute_', '');
                    selects[rawField] = idField;
                    const Field = document.getElementById(idField);
                    if (!!Field && Field.value===val) {
                        check++;
                    }
                    //console.log(idField, val);
                }
                if (check === Object.keys(Attrs).length) {
                    renderWidget(container, variation.display_price);
                    return;
                }

            });
        }
        match();

        for(let i in selects ) {
            let sel = selects[i];

            document.getElementById(sel).addEventListener('change', (e) => {
                if (!!container) {
                    match();
                }
            });
        };
        const reset = document.querySelector('.reset_variations');
        if (!!reset) {
            reset.onclick = () => {
                removeOldWidget()
            };
        }


    }

});