jQuery( document ).ready(
	function() {
			member_widget_click_handler();
			member_widget_online_click_handler();

			// WP 4.5 - Customizer selective refresh support.
		if ( 'undefined' !== typeof wp && wp.customize && wp.customize.selectiveRefresh ) {
			wp.customize.selectiveRefresh.bind(
				'partial-content-rendered',
				function() {
					member_widget_click_handler();
					member_widget_online_click_handler();
				}
			);
		}

			// Set the interval and the namespace event
		if ( typeof wp !== 'undefined' && typeof wp.heartbeat !== 'undefined' ) {
			jQuery( document ).on(
				'heartbeat-send',
				function ( event, data ) {
					if ( jQuery( '#boss_whos_online_widget_heartbeat' ).length ) {
						   data.boss_whos_online_widget = jQuery( '#boss_whos_online_widget_heartbeat' ).data( 'max' );
					}
					if ( jQuery( '#boss_recently_active_widget_heartbeat' ).length ) {
						  data.boss_recently_active_widget = jQuery( '#boss_recently_active_widget_heartbeat' ).data( 'max' );
					}

					if ( jQuery( '#recently-active-members' ).length ) {
						data.buddyboss_members_widget_active = jQuery( '#recently-active-members' ).data( 'max' );
					}
					jQuery( '.bs-heartbeat-reload' ).removeClass( 'hide' );
				}
			);

			jQuery( document ).on(
				'heartbeat-tick',
				function ( event, data ) {
					// Check for our data, and use it.
					if ( jQuery( '#boss_whos_online_widget_total_heartbeat' ).length ) {
								jQuery( '#boss_whos_online_widget_total_heartbeat' ).html( data.boss_whos_online_widget_total );
					}
					if ( jQuery( '#boss_whos_online_widget_heartbeat' ).length ) {
							jQuery( '#boss_whos_online_widget_heartbeat' ).html( data.boss_whos_online_widget );
					}
					if ( jQuery( '#boss_whos_online_widget_connections' ).length ) {
						jQuery( '#boss_whos_online_widget_connections' ).html( data.boss_whos_online_widget_connection );
					}

					if ( jQuery( '#who-online-members-list-options #online-members' ).length ) {
						jQuery( '#who-online-members-list-options #online-members .widget-num-count' ).html( data.boss_whos_online_widget_total );
					}

					if ( jQuery( '#who-online-members-list-options #connection-members' ).length ) {
						jQuery( '#who-online-members-list-options #connection-members .widget-num-count' ).html( data.boss_whos_online_widget_total_connection );
					}

					if ( jQuery( '#boss_recently_active_widget_heartbeat' ).length ) {
						jQuery( '#boss_recently_active_widget_heartbeat' ).html( data.boss_recently_active_widget );
					}

					// Update active members list on Members widget if currently active tab is visible.
					if (
						jQuery( '#members-list' ).length &&
						jQuery( '#recently-active-members').length &&
						jQuery( '#recently-active-members').hasClass( 'selected' ) ) {
						jQuery( '.widget_bp_core_members_widget' ).find('#members-list').html( data.buddyboss_members_widget_active );
					}

					jQuery( '.bs-heartbeat-reload' ).addClass( 'hide' );
				}
			);

		}

		if ( jQuery( '#boss_whos_online_widget_connections' ).length ) {
			jQuery( '#boss_whos_online_widget_connections' ).hide();
			jQuery( '#online-members' ).addClass( 'selected' );
		}

	}
);

function member_widget_click_handler() {
	jQuery( '.widget div#members-list-options a' ).on(
		'click',
		function() {
			var link = this;
			jQuery( link ).addClass( 'loading' );

			jQuery( '.widget div#members-list-options a' ).removeClass( 'selected' );
			jQuery( this ).addClass( 'selected' );

			jQuery.post(
				ajaxurl,
				{
					action: 'widget_members',
					'cookie': encodeURIComponent( document.cookie ),
					'_wpnonce': jQuery( 'input#_wpnonce-members' ).val(),
					'max-members': jQuery( 'input#members_widget_max' ).val(),
					'filter': jQuery( this ).attr( 'id' )
				},
				function(response)
				{
					jQuery( link ).removeClass( 'loading' );
					member_widget_response( response );
				}
			);

			return false;
		}
	);
}

function member_widget_response( response ) {
	var result = jQuery.parseJSON( response );

	if ( result.success === 1 ) {
		jQuery( '.widget ul#members-list' ).fadeOut(
			200,
			function () {
				jQuery( '.widget ul#members-list' ).html( result.data );
				jQuery( '.widget ul#members-list' ).fadeIn( 200 );
			}
		);

		if ( true === result.show_more ) {
			jQuery( '.more-block' ).removeClass( 'bp-hide' );
		} else {
			jQuery( '.more-block' ).addClass( 'bp-hide' );
		}
	} else {

		jQuery( '.widget ul#members-list' ).fadeOut(
			200,
			function () {
				var message = '<p>' + result.data + '</p>';
				jQuery( '.widget ul#members-list' ).html( message );
				jQuery( '.widget ul#members-list' ).fadeIn( 200 );
			}
		);
	}
}

function member_widget_online_click_handler() {
	jQuery( '.widget div#who-online-members-list-options a' ).on(
		'click',
		function() {
			var link = this;
			jQuery( link ).addClass( 'loading' );

			jQuery( '.widget div#who-online-members-list-options a' ).removeClass( 'selected' );
			jQuery( this ).addClass( 'selected' );

			var div = jQuery( this ).attr( 'data-content' );
			jQuery( '.widget_bp_core_whos_online_widget .widget-content' ).hide();
			jQuery( '.widget_bp_core_whos_online_widget #' + div ).show();

			jQuery( link ).removeClass( 'loading' );
			return false;
		}
	);
}
