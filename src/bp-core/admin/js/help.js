/**
 * BuddyBoss Help implementation.
 *
 * @since BuddyBoss 1.2.1
 */

(function( $ ) {
	$( window ).on(
		'load',
		function() {

			if ( $( '#bp-help-main-menu-wrap' ).length ) {
				$.ajax({
					url: BP_HELP.bb_resources_json_url,
					data: { parent : 0, filter: { order: 'ASC' } },
					success: function (response) {
						var bp_help_cards = '';
						$.each( response, function ( index, value ) {
							bp_help_cards += '<div class="bp-help-card bp-help-menu-wrap">\n' +
								'\t\t\t<div class="inside">';
							bp_help_cards += '<h2><a href="'+BP_HELP.bb_help_url+'&article='+value.id+'">'+value.title.rendered+'</a></h2>';
							bp_help_cards += value.content.rendered;
							bp_help_cards += '</div>\n' +
								'\t\t</div>';
						} );
						$( '#bp-help-main-menu-wrap' ).html(bp_help_cards);
					},
					dataType: 'json',
					error : function( error ) {
						console.log(error);
					}
				});
			}
		}
	);
})( jQuery );
