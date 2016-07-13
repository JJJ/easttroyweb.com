jQuery( document ).ready( function ( $ ) {

	$( document ).on( 'click', '.news-calendar-widget .prev-month a, .news-calendar-widget .next-month a', function () {
		var ajaxArgs = [],
				newMonth = $( this ).attr( 'rel' ),
				divCalendar = $( this ).closest( 'div.news-calendar-widget' ),
				tdSpinner = divCalendar.find( 'td.ajax-spinner' ),
				divRel = divCalendar.attr( 'rel' ),
				relSplit = divRel.split( '|' ),
				widgetID = relSplit[0],
				lang = relSplit[1];

		ajaxArgs = {
			action: 'get-news-widget-calendar-month',
			date: newMonth,
			widget_id: widgetID,
			nonce: nmArgs.nonce
		};

		if ( lang !== '' ) {
			ajaxArgs['pll_load_front'] = 1;
			ajaxArgs['lang'] = lang;
		}

		divCalendar.find( 'td.ajax-spinner div' ).css( 'middle', parseInt( ( tdSpinner.height() - 16 ) / 2 ) + 'px' ).css( 'left', parseInt( ( tdSpinner.width() - 16 ) / 2 ) + 'px' ).fadeIn( 300 );

		$.ajax( {
			type: 'POST',
			url: nmArgs.ajaxurl,
			data: ajaxArgs,
			dataType: 'html'
		} )
				.done( function ( data ) {
					divCalendar.fadeOut( 300, function () {
						divCalendar.replaceWith( data );
						$( '#news-calendar-' + widgetID ).fadeIn( 300 );
					} );
				} ).fail( function ( data ) {
			//
		} );

		return false;
	} );
} );