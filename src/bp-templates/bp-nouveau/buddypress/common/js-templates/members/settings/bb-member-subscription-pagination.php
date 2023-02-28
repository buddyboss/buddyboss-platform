<?php
/**
 * BP Nouveau member subscription Pagination template
 *
 * This template can be overridden by copying it to yourtheme/buddypress/common/js-templates/members/settings/bb-member-subscription-pagination.php.
 *
 * @since   BuddyBoss 2.2.6
 * @version 1.0.0
 */
?>

<script type="text/html" id="tmpl-bb-member-subscription-pagination">
	<#
	var options = data.options.options;
	if ( 1 < options.total_page ) {

		var paged = options.current_active;
		var max   = options.total_page;
		var links = [];

		if ( 1 <= paged ) {
			links.push( paged );
		}

		/** Add the pages around the current page to the array */
		if ( 3 <= paged ) {
			links.push( parseInt( paged - 1 ) );
			links.push( parseInt( paged - 2 ) );
		}

		if ( ( paged + 2 ) <= max ) {
			links.push( parseInt( paged + 2 ) );
			links.push( parseInt( paged + 1 ) );
		}
		#>

		<div class="bbp-pagination-links">
			<#
			if ( 1 < paged ) { #>
				<a class="prev page-numbers" data-page="{{ options.current_active-1 }}" href="#"><</a>
			<# }

			if ( -1 == jQuery.inArray( 1, links ) ) {
				if ( paged == 1) {
					#><span aria-current="page" class="page-numbers current">1</span><#
				} else {
					#><a class="page-numbers page" data-page="1" href="#">1</a><#
				}

				if ( -1 == jQuery.inArray( 2, links ) ) {
					#><span aria-current="page" class="page-numbers disabled">...</span><#
				}
			}

			links = links.sort( function( a, b ) { return a - b } );

			if ( 0 < links.length ) {
				for ( i = 0; i < links.length; i++ ) {
					if ( paged == links[i] ) {
						#><span aria-current="page" class="page-numbers current">{{ links[i] }}</span><#
					} else {
						#><a class="page-numbers page" data-page="{{ links[i] }}" href="#">{{ links[i] }}</a><#
					}
				}
			}

			if ( -1 == jQuery.inArray( max, links ) ) {
				if ( -1 == jQuery.inArray( max - 1, links ) ) {
					#><span aria-current="page" class="page-numbers disabled">...</span><#
				}

				if ( paged == max ) {
					#><span aria-current="page" class="page-numbers current">{{ max }}</span><#
				} else {
					#><a class="page-numbers page" data-page="{{ max }}" href="#">{{ max }}</a><#
				}
			}

			if ( paged < max ) { #>
				<a class="next page-numbers" data-page="{{ paged+1 }}" href="#">></a>
			<# } #>
		</div>
	<# } #>
</script>
