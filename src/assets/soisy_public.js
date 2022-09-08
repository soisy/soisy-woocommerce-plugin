window.addEventListener('DOMContentLoaded', (doc) => {
    const soisypublic = window.soisypublic;
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
            const min = parseFloat(soisypublic.min_amount) > 100 ? parseFloat(soisypublic.min_amount) : 100;
            const max = parseFloat(soisypublic.max_amount);
            if (actual >= min && actual <= max) {
                let el = document.createElement('soisy-loan-quote');
                el.setAttribute('shop-id', soisypublic.shop_id);
                el.setAttribute('amount', amount);
                el.setAttribute('instalments', soisypublic.quote_instalments_amount);
                if (!!soisypublic.soisy_zero && soisypublic.soisy_zero) {
                    el.setAttribute('zero-interest-rate', soisypublic.soisy_zero);
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