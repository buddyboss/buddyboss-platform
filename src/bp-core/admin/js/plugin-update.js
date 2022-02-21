/**
 * Loads for Update plugin BuddyBoss in wp-admin when new release with major changes.
 *
 * @since BuddyPress [BBVERSION]
 */
(function() {
	/**
	 * Open the Update BuddyBoss modal.
	 */
	var bp_update_open_modal = function() {
		var backdrop = document.getElementById( 'bp-update-backdrop' ),
			modal    = document.getElementById( 'bp-update-container' );
		console.log( 'bp_hello_open_modal' );
		if ( null === backdrop ) {
			return;
		}
		document.body.classList.add( 'bp-disable-scroll' );
		
		console.log( 'modal' );
		console.log( modal );
		// Show modal and overlay.
		backdrop.style.display = '';
		modal.style.display    = '';
		
		// Focus the "X" so bp_hello_handle_keyboard_events() works.
		var focus_target = modal.querySelectorAll( 'a[href], button' );
		focus_target     = Array.prototype.slice.call( focus_target );
		focus_target[0].focus();
		
		// Events.
		modal.addEventListener( 'keydown', bp_update_handle_keyboard_events );
		backdrop.addEventListener( 'click', bp_update_close_modal );
	};
	
	/**
	 * Close modal if "X" or background is touched.
	 *
	 * @param {Event} event - A click event.
	 */
	document.addEventListener(
		'click',
		function( event ) {
			var backdrop = document.getElementById( 'bp-update-backdrop' );
			if ( ! backdrop || ! document.getElementById( 'bp-update-container' ) ) {
				return;
			}
			
			var backdrop_click = backdrop.contains( event.target ),
				modal_close_click  = event.target.classList.contains( 'close-modal' );
			
			if ( ! modal_close_click && ! backdrop_click ) {
				return;
			}
			
			bp_update_close_modal();
		},
		false
	);
	
	/**
	 * Close the Hello modal.
	 */
	var bp_update_close_modal = function() {
		
		document.getElementById( 'bp-update-container' ).setAttribute( 'style', 'display:none' );
		document.getElementById( 'bp-update-backdrop' ).setAttribute('style', 'display:none' );
		document.body.className = document.body.className.replace('bp-disable-scroll','');
		
	};
	
	/**
	 * Restrict keyboard focus to elements within the Hello BuddyBoss modal.
	 *
	 * @param {Event} event - A keyboard focus event.
	 */
	var bp_update_handle_keyboard_events = function( event ) {
		var modal          = document.getElementById( 'bp-update-container' ),
			focus_targets  = Array.prototype.slice.call(
				modal.querySelectorAll( 'a[href], button' )
			),
			first_tab_stop = focus_targets[0],
			last_tab_stop  = focus_targets[ focus_targets.length - 1 ];
		
		// Check for TAB key press.
		if ( event.keyCode !== 9 ) {
			return;
		}
		
		// When SHIFT+TAB on first tab stop, go to last tab stop in modal.
		if ( event.shiftKey && document.activeElement === first_tab_stop ) {
			event.preventDefault();
			last_tab_stop.focus();
			
			// When TAB reaches last tab stop, go to first tab stop in modal.
		} else if ( document.activeElement === last_tab_stop ) {
			event.preventDefault();
			first_tab_stop.focus();
		}
	};
	
	/**
	 * Close modal if escape key is presssed.
	 *
	 * @param {Event} event - A keyboard focus event.
	 */
	document.addEventListener(
		'keyup',
		function( event ) {
			if ( event.keyCode === 27 ) {
				if ( ! document.getElementById( 'bp-update-backdrop' ) || ! document.getElementById( 'bp-update-container' ) ) {
					return;
				}
				
				bp_update_close_modal();
			}
		},
		false
	);
	
	// Init modal after the screen's loaded.
	console.log( 'test' );
	jQuery( document ).on( 'wp-plugin-update-success', function ( event, response ) {
		console.log( ' event.type ' + event.type );
		console.log( 'wp-plugin-update-success ' );
		
		if ( 'wp-' + response.update + '-update-success' === event.type
		     && 'akismet/akismet.php' === response.plugin ) { //buddyboss-platform/bp-loader.php
			console.log( response );
			console.log( BB_UPDATE.ajax_url );
			jQuery.ajax(
				{
					type: 'POST',
					url: BB_UPDATE.ajax_url,
					async: false,
					'data': {
						'action': 'bb_plugin_update',
					},
					success: function ( response_data ) {
						console.log( response_data );
						jQuery( '#wpfooter' ).after( response_data );
						bp_update_open_modal();
					}
				}
			);
		}
	} );
	
}());
