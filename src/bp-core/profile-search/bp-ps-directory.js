
jQuery.cookie( 'bp-members-scope', 'all', {
	path: '/',
	secure: ( 'https:' === window.location.protocol )
} );

jQuery.removeCookie('bp-members-filter', {
	path: '/',
	secure: ( 'https:' === window.location.protocol )
} );
