( function( $, data, wp ) {
	var wc_table_rate_rows_row_template = wp.template( 'table-rate-instalment-row-template' ),
		$rates_table                    = $( '#instalment_rates' ),
		$rates                          = $rates_table.find( 'tbody.table_rates' );
	var wc_table_rate_rows = {
		init: function() {
			$rates_table
				.on( 'click', 'a.add-rate', this.onAddRate )
				.on( 'click', 'a.remove', this.onRemoveRate );

			var rates_data = $rates.data( 'rates' );

            $( rates_data ).each( function( i ,value) {
				$rates.append( wc_table_rate_rows_row_template( {
					period:  value.period,
					amount: value.amount
				} ) );
			} );

            $rates.sortable( {
				items: 'tr',
				cursor: 'move',
				axis: 'y',
				handle: 'td',
				scrollSensitivity: 40,
				helper: function(e,ui){
					ui.children().each( function() {
						$( this ).width( $(this).width() );
					});
					ui.css( 'left', '0' );
					return ui;
				},
				start: function( event, ui ) {
					ui.item.css('background-color','#f6f6f6');
				},
				stop: function( event, ui ) {
					ui.item.removeAttr( 'style' );
					wc_table_rate_rows.reindexRows();
				}
			} );


			$( '#woocommerce_table_rate_calculation_type' ).change();
		},

		onAddRate: function( event ) {
			event.preventDefault();
			var target = $rates;

			target.append( wc_table_rate_rows_row_template( {
                period:  '',
                amount: ''
			} ) );

			$( '#woocommerce_table_rate_calculation_type', $rates_table ).change();
		},
		onRemoveRate: function( event ) {
			event.preventDefault();
			if ( confirm( data.i18n.delete_rates ) ) {
				var rate_ids  = [];

				$rates.find( 'tr td.check-column input:checked' ).each( function( i, el ) {
					var rate_id = $(el).closest( 'tr.table_rate' ).find( '.rate_id' ).val();
					rate_ids.push( rate_id );
					$(el).closest( 'tr.table_rate' ).addClass( 'deleting' );
				});

				$( 'tr.deleting').fadeOut( '300', function() {
					$( this ).remove();
				} );
			}
		},

		reindexRows: function() {
			var loop = 0;
			$rates.find( 'tr' ).each( function( index, row ) {
				$('input.text, input.checkbox, select.select', row ).each( function( i, el ) {
					var t = $(el);
					t.attr( 'name', t.attr('name').replace(/\[([^[]*)\]/, "[" + loop + "]" ) );
				});
				loop++;
			});
		}
	};

	wc_table_rate_rows.init();

})( jQuery, woocommerce_instalment_table_rate_rows, wp );
