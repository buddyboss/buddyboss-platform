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
	<# var options = data.options; #>
	<div class="bbp-pagination-links">
		<# if (options.current_active > 1) { #>
			<a class="prev page-numbers" data-page="{{ options.current_active-1 }}" href="#"><</a>
		<# } #>
		<# if (options.left_dots) {#>
			<a class="page-numbers page" data-page="1" href="#">1</a>
			<span aria-current="page" class="page-numbers disabled">...</span>
		<# } #>

		<#
		for (var i = options.nav_begin; i <= options.nav_end; i++) {
			if (options.current_active == i) { #>
				<span aria-current="page" class="page-numbers <# if (options.current_active == i) print('current') #>">{{ i }}</span>
			<# } else { #>
				<a class="page-numbers page" data-page="{{ i }}" href="#">{{ i }}</a>
			<#
			}
		}
		#>

		<# if (options.right_dots) {#>
			<span aria-current="page" class="page-numbers disabled">...</span>
			<a class="page-numbers page" data-page="{{ options.total_page }}" href="#">{{ options.total_page }}</a>
		<# } #>

		<# if (options.current_active < options.total_page) { #>
			<a class="next page-numbers" data-page="{{ options.current_active+1 }}" href="#">></a>
		<# } #>
	</div>
</script>
