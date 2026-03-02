/* jshint unused: false */

function bp_get_querystring( n ) {
	var half = location.search.split( n + '=' )[1];
	return half ? decodeURIComponent( half.split( '&' )[0] ) : null;
}


/* Code to make parent menu selected for all type of submenu */

jQuery( document ).ready(
	function() {

			jQuery( '.menu-item-has-children .sub-menu li' ).each(
				function () {

					if (jQuery( this ).hasClass( 'current-menu-item' )) {

						if ( ! jQuery( this ).parent().parent().hasClass( 'current-menu-parent' )) {
							jQuery( this ).parent().parent().addClass( 'current-menu-parent' );
						}
					}
				}
			);

	}
);
