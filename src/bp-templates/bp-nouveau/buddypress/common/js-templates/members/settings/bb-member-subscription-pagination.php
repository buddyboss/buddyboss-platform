<?php
/**
 * BP Nouveau member subscription Pagination template
 *
 * This template can be overridden by copying it to yourtheme/buddypress/common/js-templates/members/settings/bb-member-subscription-pagination.php.
 *
 * @since   BuddyBoss [BBVERSION]
 * @version 1.0.0
 */
?>

<script type="text/html" id="tmpl-bb-member-subscription-pagination">
	<# var options = data.options.options; #>

	<# if( options.total_page > 1 ) {

		var paged = options.current_active;
		var max   = options.total_page;
		var links = new Array();

		if ( paged >= 1 ) {
			links.push(paged);
		}

		/** Add the pages around the current page to the array */
		if ( paged >= 3 ) {
			links.push(paged - 1);
			links.push(paged - 2);
		}

		if ( ( paged + 2 ) <= max ) {
			links.push(paged + 2);
			links.push(paged + 1);
		}
		#>

		<div class="bbp-pagination-links">
			<# if (paged > 1) { #>
				<a class="prev page-numbers" data-page="{{ options.current_active-1 }}" href="#"><</a>
			<# } #>

			<#
			if ( ! jQuery.inArray( 1, links ) ) {
				if (paged == 1) {
					#> <span aria-current="page" class="page-numbers current">1</span> <#
				} else {
					#> <a class="page-numbers page" data-page="1" href="#">1</a> <#
				}

				if ( ! jQuery.inArray( 2, links.length ) ) {
					#> <span aria-current="page" class="page-numbers disabled">...</span> <#
				}
			}

			links.sort();
			if ( 0 < links.length ) {
				for (i = 1; i < links.length; i++) {
					if (paged == links[i]) {
						#> <span aria-current="page" class="page-numbers current">{{ links[i] }}</span> <#
					} else {
						#> <a class="page-numbers page" data-page="{{ links[i] }}" href="#">{{ links[i] }}</a> <#
					}
				}
			}

			if ( ! jQuery.inArray( max, links ) ) {
				if ( ! in_array( max - 1, links ) ) {
					#> <span aria-current="page" class="page-numbers disabled">...</span> <#
				}

				if (paged == max) {
					#> <span aria-current="page" class="page-numbers current">{{ max }}</span> <#
				} else {
					#> <a class="page-numbers page" data-page="{{ max }}" href="#">{{ max }}</a> <#
				}
			}

			if (paged < max) { #>
				<a class="next page-numbers" data-page="{{ paged+1 }}" href="#">></a>
			<# } #>
		</div>
	<# } #>
</script>
