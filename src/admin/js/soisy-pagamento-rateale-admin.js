jQuery(function ($) {
	'use strict';
	function matchCustom(params, data) {
		// If there are no search terms, return all of the data
		if ($.trim(params.term) === '') {
			return data;
		}

		// Do not display the item if there is no 'text' property
		if (typeof data.text === 'undefined') {
			return null;
		}

		// `params.term` should be the term that is used for searching
		// `data.text` is the text that is displayed for the data object
		let terms = params.term.toLowerCase();
		let compare=data.text.toLowerCase()
		//if (data.text.indexOf(params.term) > -1) {
		if (compare.indexOf(terms) > -1) {
			var modifiedData = $.extend({}, data, true);

			// You can return modified objects from here
			// This includes matching the `children` how you want in nested data sets
			return modifiedData;
		}

		// Return `null` if the term should not be displayed
		return null;
	}


	let adminDataCats = window.adminVars.haystacks.allCats;
	//console.log(adminDataCats);

	$(document).ready(function () {
		const $selCats = $('#woocommerce_soisy_show_exclusions');
		if (!!$selCats) {
            const target = $('#woocommerce_soisy_excluded_cat');
		$selCats.select2({
			placeholder: 'Select Category',
			matcher: matchCustom,
			multiple: true,
			data: adminDataCats,
			tags: true
		});

                if (!!target.val) {
                    //console.log(target);
                    const selected = target.val().split(',');
                    $selCats.val(selected);
                    $selCats.trigger('change');
                    $selCats.on('change.select2', function (e) {
                        target.val($(this).val());
                    })
                }
            }
	});



});