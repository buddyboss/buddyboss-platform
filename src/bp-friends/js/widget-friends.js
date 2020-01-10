jQuery( document ).ready(
	function() {
			friend_widget_click_handler();

			// WP 4.5 - Customizer selective refresh support.
		if ( 'undefined' !== typeof wp && wp.customize && wp.customize.selectiveRefresh ) {
			wp.customize.selectiveRefresh.bind(
				'partial-content-rendered',
				function() {
					friend_widget_click_handler();
				}
			);
		}
	}
);

function friend_widget_click_handler() {
	jQuery( '.widget div#friends-list-options a' ).on(
		'click',
		function() {
			var link = this;
			jQuery( link ).addClass( 'loading' );

			jQuery( '.widget div#friends-list-options a' ).removeClass( 'selected' );
			jQuery( this ).addClass( 'selected' );
			jQuery( document ).find( '.widget div.more-block > a' ).attr( "data-page",2 );
			jQuery.post(
				ajaxurl,
				{
					action: 'widget_friends',
					'cookie': encodeURIComponent( document.cookie ),
					'_wpnonce': jQuery( 'input#_wpnonce-friends' ).val(),
					'max-friends': jQuery( 'input#friends_widget_max' ).val(),
					'filter': jQuery( this ).attr( 'id' )
				},
				function(response)
				{
					jQuery( link ).removeClass( 'loading' );
					//friend_widget_response( response );
					
					jQuery( '.widget ul#friends-list' ).fadeOut(
						200,
						function() {
							if ( 'undefined' !== typeof response.contents ) {
								if ( 'undefined' !== typeof response.next_page && response.next_page != 0 ) {
									jQuery( document ).find( '.widget div.more-block' ).show();
									jQuery( document ).find( '.widget div.more-block  > a' ).attr( "data-page",response.next_page );
								}else{
									jQuery( document ).find( '.widget div.more-block' ).hide();
								}
								jQuery( document ).find( '.widget ul#friends-list' ).html( response.contents ).fadeOut( 2000 );
							}
							jQuery( '.widget ul#friends-list' ).fadeIn( 200 );
						}
					);
				}
			);

			return false;
		}
	);

	jQuery( '.widget a.more-connection' ).on(
		'click',
		function() {
			var _this = jQuery( this );
			jQuery( _this ).addClass( 'loading' );
			var data_page = _this.attr( "data-page" ),
			    filter_id = jQuery( '.widget div#friends-list-options a.selected' ).attr( 'id' );
			
			jQuery.post(
				ajaxurl,
				{
					action: 'widget_friends',
					'cookie': encodeURIComponent( document.cookie ),
					'_wpnonce': jQuery( 'input#_wpnonce-friends' ).val(),
					'max-friends': jQuery( 'input#friends_widget_max' ).val(),
					'filter': filter_id,
					'page': data_page
				},
				function(response)
				{
					jQuery( _this ).removeClass( 'loading' );
					if ( 'undefined' !== typeof response.contents ) {
						if ( 'undefined' !== typeof response.next_page && response.next_page != 0 ) {
							_this.parent().show();
							_this.attr( "data-page",response.next_page );
						}else{
							_this.parent().hide();
						}
						jQuery( document ).find( '.widget ul#friends-list' ).append( response.contents ).fadeIn( 3000 );
					}
				}
			);

			return false;
		}
	);
}

function friend_widget_response(response) {
	response = response.substr( 0, response.length - 1 );
	response = response.split( '[[SPLIT]]' );

	if ( response[0] !== '-1' ) {
		jQuery( '.widget ul#friends-list' ).fadeOut(
			200,
			function() {
				jQuery( '.widget ul#friends-list' ).html( response[1] );
				jQuery( '.widget ul#friends-list' ).fadeIn( 200 );
			}
		);

	} else {
		jQuery( '.widget ul#friends-list' ).fadeOut(
			200,
			function() {
				var message = '<p>' + response[1] + '</p>';
				jQuery( '.widget ul#friends-list' ).html( message );
				jQuery( '.widget ul#friends-list' ).fadeIn( 200 );
			}
		);
	}
}
