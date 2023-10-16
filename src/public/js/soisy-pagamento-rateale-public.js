window.addEventListener('DOMContentLoaded', (doc) => {
	const soisypublic = window.soisyVars;
	//console.log(soisypublic);
	const widgetID = soisypublic.widget_id;
	const container = document.getElementById(widgetID);
	let updAmount;
	if (!!document.getElementById('updatedAmount')) {
		updAmount = document.getElementById('updatedAmount').dataset.amount;
	}
	const hasVariation = document.querySelector('form.variations_form.cart');
	if (!!container){
		const pv = !!hasVariation ? JSON.parse(hasVariation.dataset.product_variations): null;
		let selects = {};
		const renderWidget = (container, amount) => {
			const updAvailable = !!document.getElementById('updatedAmount') ? document.getElementById('updatedAmount').dataset.available:0;
			const actual = parseFloat(amount);
				const min = parseFloat(soisypublic.min_amount);
				const max = parseFloat(soisypublic.max_amount);
				//console.log(actual)
				switch (true){
					case !updAvailable:
					case !!updAvailable && updAvailable>0:
						if (actual >= min && actual <= max) {
							let el = document.createElement('soisy-loan-quote');
							el.setAttribute('shop-id', soisypublic.shop_id);
							el.setAttribute('amount', amount);
							el.setAttribute('instalments', soisypublic.quote_instalments_amount);
							if (!!soisypublic.soisy_zero && soisypublic.soisy_zero) {
								el.setAttribute('zero-interest-rate', soisypublic.soisy_zero);
							}

							container.innerHTML = el.outerHTML;
						}
						else {
								container.innerHTML = '';
							}
					break;
					default:
						container.innerHTML = '';
						break;
				}

				listenUpdate();
		};

		const customSniff = () => {
			let price, htmlPrice;
			setInterval(function () {
				//const variableProd = document.querySelector('form.variations_form');
				if (!!hasVariation) {
					const firstCheck = hasVariation.querySelector('.woocommerce-Price-amount.amount');
					if (!!firstCheck) {
						price = firstCheck.dataset.price;
						if (!!price) {
							if (price === htmlPrice) {
								return;
							}
							renderWidget(container, price);
							htmlPrice = price;
						} else {
							const priceCheck = hasVariation.querySelectorAll('.single_variation_wrap .woocommerce-Price-amount.amount bdi').forEach((node) => {
								if (node.closest('del') || node.innerText == htmlPrice) {
									return;
								}

								htmlPrice = node.innerText;
								price = node.innerText.replace(/[^\d^\,.-]/g, '').replaceAll(soisypublic.thousand_sep, '').replace(soisypublic.decimal_sep, '.');

								renderWidget(container, price);
							});
						}
					}
				}
			}, 1000);
		};

		customSniff();
		//cart function
		let c = 0;
		const listenUpdate = () => {
			const updCartButt = document.querySelector('.woocommerce-cart-form');
			if (!!updCartButt) {
				updCartButt.onsubmit = (e) => {
					document.querySelector('.woocommerce-cart .woocommerce-notices-wrapper').innerHTML = '';
					const myInterval = setInterval(function () {
						if ( !!document.querySelector('.woocommerce-cart .woocommerce-notices-wrapper' +
							' .woocommerce-message' )) {
							clearInterval(myInterval);
							renderWidget(document.getElementById(widgetID), document.getElementById('updatedAmount').dataset.amount);
						}
					}, 100);
				};
			}

			document.querySelectorAll('.woocommerce-cart td.product-remove a.remove').forEach((node) => {
				node.onclick = (e) =>
				{
					document.querySelector('.woocommerce-cart .woocommerce-notices-wrapper').innerHTML = '';
					const myInterval = setInterval(function () {
						if (!!document.querySelector('.woocommerce-cart .woocommerce-notices-wrapper .woocommerce-message')) {
							clearInterval(myInterval);
							renderWidget(document.getElementById(widgetID), document.getElementById('updatedAmount').dataset.amount);
						}
					}, 100);
				}
			});

		};

		listenUpdate();


		const match = () => {
			if (!!pv) {
				pv.forEach( ( variation ) => {
					const Attrs = Object.entries( variation.attributes );
					let check = 0;
					for ( const [ rawField, val ] of Attrs ) {
						const idField = rawField.replace('attribute_', '');
						selects[ rawField ] = idField;
						const Field = document.getElementById( idField );
						if ( !!Field && Field.value === val ) {
							check++;
						}
					}
					if (check === Object.keys( Attrs ).length) {
						renderWidget( container, variation.display_price );
						return;
					}
				});
			} else {
				const amount = updAmount > 0 ? updAmount : soisypublic.amount;
				renderWidget( container, amount );
			}
		}
		match();

		let custom_template = true;
		for( let i in selects ) {
			let sel = document.getElementById(selects[i]);
			if (!!sel) {
				sel.addEventListener('change', ( e ) => {
					if (!!container) {
						match();
					}
				});
				custom_template = false;
			}
		};
		if (!!custom_template) {
			customSniff();
		}

		const reset = document.querySelector('.reset_variations');
		if (!!reset) {
			reset.onclick = () => {
				container.innerHTML='';
			};
		}

	}

	// checkout function
	const payNow = document.querySelector('button[name="woocommerce_checkout_place_order"]');
	if (!!payNow) {
		
	}


});
