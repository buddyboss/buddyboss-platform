/**
 * BuddyBoss Help implementation.
 *
 * @since BuddyBoss 1.2.1
 */

(function( $ ) {
	$( window ).on(
		'load',
		function() {

			var bp_help_wpapi = new WPAPI({ endpoint: 'http://localhost/buddyboss/wp-json' });
			bp_help_wpapi.docs = bp_help_wpapi.registerRoute( 'wp/v2', '/docs/(?P<id>)', {
				params: [ 'before', 'after', 'author', 'parent', 'post', 'order', 'orderby' ]
			} );

			if ( $( '#bp-help-main-menu-wrap' ).length ) {
				bp_help_wpapi.docs().parent(0).order('asc').orderby('menu_order').then(function (docs) {
					var bp_help_cards = '';
					$.each( docs, function ( index, value ) {
						bp_help_cards += '<div class="bp-help-card bp-help-menu-wrap">\n' +
							'\t\t\t<div class="inside">';
						bp_help_cards += '<h2><a href="'+BP_HELP.bb_help_url+'&article='+value.id+'">'+value.title.rendered+'</a></h2>';
						bp_help_cards += value.content.rendered;
						bp_help_cards += '</div>\n' +
							'\t\t</div>';
					} );
					$( '#bp-help-main-menu-wrap' ).html(bp_help_cards);
				});
			}

			if ( $( '#bp-help-content-area' ).length ) {
				var url = new URL(window.location.href);
				var article_id = url.searchParams.get("article");
				bp_help_wpapi.docs().id( article_id ).then(function (doc) {
					$( '#bp-help-content-area' ).append('<h1>' + doc.title.rendered + '</h1>');
					$( '#bp-help-content-area' ).append(doc.content.rendered);
				});
			}
		}
	);
})( jQuery );
