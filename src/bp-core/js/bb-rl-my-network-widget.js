jQuery( document ).ready(
	function() {
		my_network_click_handler();
	}
);

function my_network_click_handler() {
	var widget           = '.widget-bb-rl-follow-my-network-widget';
	var widgetTabLinks   = 'div.bb-rl-members-item-options a';
	var widgetMemberList = '.bb-rl-my-network-members-list';
	jQuery( widget + ' ' + widgetTabLinks ).on(
		'click',
		function() {
			var link          = this;
			var currentWidget = jQuery( link ).parents( widget );
			var targetList    = jQuery( currentWidget ).find( widgetMemberList );

			jQuery( currentWidget ).find( widgetTabLinks ).removeClass( 'selected' );
			jQuery( this ).addClass( 'loading selected' );
			jQuery( targetList ).addClass( 'loading' );

			var seeAllLink = jQuery( this ).data( 'see-all-link' );
			if ( '' !== seeAllLink ) {
				jQuery( currentWidget ).find( '.bb-rl-see-all a' ).attr( 'href', seeAllLink );
			}

			jQuery.post(
				ajaxurl,
				{
					action       : 'widget_follow_my_network',
					'cookie'     : encodeURIComponent( document.cookie ),
					'_wpnonce'   : jQuery( currentWidget ).find( 'input#_wpnonce-follow-my-network' ).val(),
					'max-members': jQuery( currentWidget ).find( 'input#bb_rl_my_network_widget_max' ).val(),
					'filter'     : jQuery( this ).attr( 'id' )
				},
				function( response ) {
					jQuery( link ).removeClass( 'loading' );
					jQuery( targetList ).removeClass( 'loading' );
					if ( 'undefined' !== typeof response.success && response.success === 1 ) {
						jQuery( targetList ).fadeOut(
							200,
							function () {
								jQuery( targetList ).html( response.data );
								jQuery( targetList ).fadeIn( 200 );
							}
						);
					} else {

						jQuery( targetList ).fadeOut(
							200,
							function () {
								var message = ( 'undefined' !== typeof response.data ) ? '<p>' + response.data + '</p>' : '';
								jQuery( targetList ).html( message );
								jQuery( targetList ).fadeIn( 200 );
							}
						);
					}
				}
			);

			return false;
		}
	);
}
