( function( $ ) {

	var total_posts = 0,
		cache_total = -1,
		cached = 0,
		progress = 0,
		batch = 50,
		progress_bar,
		notice,
		processing = false;

	// animate progressbar
	function progress_output( percent, $element ) {
		var progressBarWidth = Math.ceil( percent * $element.width() / 100 );
		$element.attr( "aria-valuenow", percent );
		$element.find( 'div' ).animate( {
			width: progressBarWidth
		}, 500 );
		$element.find( 'span' ).html( percent + "% " );
	}

	function Success( response ) {
		// console.log( 'success', response );
	}


	function Error( response ) {
		var error = $( '#rpbt_error' );
		if ( response.length ) {
			if ( error.length ) {
				$( '#rpbt_error' ).text( 'Error: ' + response );
			} else {
				alert( response );
			}
		}
		console.log( 'error', response );
	}

	// Submit form click event
	$( "#cache_form" ).on( "submit.rpbt_cache", {
		id: 'rpbt_cache_posts'
	}, submitEvent );


	function submitEvent( event ) {
		// Stop the form from submitting normally
		event.preventDefault();

		processing = true;

		var form = $( this ).closest( 'form' );

		if ( !form.length ) {
			return;
		}

		var button = $( "#cache_posts", form );

		// Get form data as an object.
		var form_data = form.serialize();

		wp.ajax.send( 'rpbt_cache_get_cache_settings', {
			success: Success,
			error: Error,
			data: {
				data: form_data,
				security: rpbt_cache.nonce_ajax
			}
		} ).done( function( response ) {

			if ( response.count.length ) {

				var template_data = {};

				form_data = response;
				cache_total = parseInt( form_data.total );
				total_posts = ( cache_total === -1 ) ? parseInt( form_data.count ) : cache_total;
				form_data.offset = parseInt( progress );
				batch = parseInt( form_data.batch );

				// Data used in template
				template_data.count = response.count;
				template_data.cache_count = total_posts;
				template_data.plugin = rpbt_cache.settings_page;
				template_data.show = rpbt_cache.show;
				template_data.hide = rpbt_cache.hide;
				template_data.parameters = response.parameters;

				var form_container = $( '#rpbt_cache_forms' );
				$( '#rpbt_cache_forms' ).add( '.updated' ).hide();
				form_container.attr( "aria-hidden", "true" );

				var template = wp.template( "rpbt-progress-barr" );
				$( '.wrap.rpbt_cache.wrap' ).append( template( template_data ) );
				button.attr( "aria-expanded", "true" );

				window.scrollTo( 0, 0 );

				var progress_bar_container = $( '#rpbt_cache_progress_bar_container' );
				progress_bar_container.attr( "aria-hidden", "false" );

				progress_bar = $( '#rpbt_cache_progressbar' );
				notice = $( '#rpbt_notice' );
				progress_output( 0, progress_bar );
				notice.text( 'Cached 0 Posts' );

				cache_posts( form_data );
				// console.log( 'form_data', form_data );
			}
		} );
	}


	function cache_posts( form_data ) {

		wp.ajax.send( 'rpbt_cache_posts', {
			success: Success,
			error: Error,
			data: {
				data: form_data,
				security: rpbt_cache.nonce_ajax
			}
		} ).done( function( response ) {

			if ( response.done ) {
				var total = cached + response.cached;
				notice.text( 'Finished Caching ' + ( total ) + ' Posts!' );
				notice.css( 'color', 'green' );
				progress_output( 100, progress_bar );
				cached = 0;

				// console.log( 'finished caching posts' );
			} else {

				cached = cached + parseInt( response.cached );
				progress = cached + parseInt( batch );
				progress = ( total_posts < batch ) ? total_posts : cached;
				notice.text( 'Cached ' + cached + ' Posts' );
				form_data.offset = parseInt( progress );
				form_data.total = cache_total;

				progress_output( parseInt( 100 * ( progress / total_posts ) ), progress_bar );

				setTimeout( function() {
					cache_posts( form_data )
				}, 2000 );

				//console.log( 'form_data', form_data );
			}
		} );
	}

	// Toggle parameters section.
	$( document ).on( 'click', '.rpbt_cache_parameter_toggle', function( event ) {
		event.preventDefault();
		var text = $( this ).text();
		$( this ).text( ( text == rpbt_cache.show ? rpbt_cache.hide : rpbt_cache.show ) );
		var parameters = $( '#rpbt_parameters' );

		parameters.toggle();
		var visible = parameters.is( ':visible' );
		$( this ).attr( "aria-expanded", visible ? "true" : "false" );
		parameters.attr( "aria-hidden", visible ? "false" : "true" );
	} );

	// Adds a reset link.
	$( '#cache_form' ).after( $( '<a href="#" class="rpbt_cache_reset">' + rpbt_cache.reset + '</a>' ) );

	// Click event to reset form fields to default values.
	$( document ).on( 'click', '.rpbt_cache_reset', function( event ) {
		event.preventDefault();
		$( "#cache_form" ).find( ':input' ).each( function() {
			var name = $( this ).attr( "name" );

			if ( rpbt_cache.defaults.hasOwnProperty( name ) ) {
				$( this ).val( rpbt_cache.defaults[ name ] );
			}
		} );
	} );

} )( jQuery );