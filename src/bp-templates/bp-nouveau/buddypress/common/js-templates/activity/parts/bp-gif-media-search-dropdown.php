<?php
/**
 * The template for displaying gif media search dropdown
 *
 * This template can be overridden by copying it to yourtheme/buddypress/common/js-templates/activity/parts/bp-gif-media-search-dropdown.php.
 *
 * @since   1.0.0
 * @version 1.0.0
 */

?>
<script type="text/html" id="tmpl-gif-media-search-dropdown">
	<div class="gif-search-content">
		<div class="gif-search-query">
			<input type="search" placeholder="<?php _e('Search GIPHY', 'buddyboss'); ?>" class="search-query-input" />
			<span class="search-icon"></span>
		</div>
		<div class="gif-search-results" id="gif-search-results">
			<ul class="gif-search-results-list" >
			</ul>
		</div>
	</div>
</script>
