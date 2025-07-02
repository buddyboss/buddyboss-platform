<?php
/**
 * ReadyLaunch - The template for displaying gif media search dropdown.
 *
 * @since   BuddyBoss 2.9.00
 * @version 1.0.0
 */

?>
<script type="text/html" id="tmpl-gif-media-search-dropdown">
	<div class="gif-search-content">
		<div class="gif-search-query">
			<input type="search" placeholder="<?php esc_html_e( 'Search GIPHY...', 'buddyboss' ); ?>" class="search-query-input" />
			<span class="search-icon"></span>
		</div>
		<div class="gif-search-results" id="gif-search-results">
			<ul class="gif-search-results-list">
			</ul>
			<div class="gif-alert gif-no-results">
				<i class="bb-icons-rl-empty"></i>
				<p><?php esc_html_e( 'No results found', 'buddyboss' ); ?></p>
			</div>

			<div class="gif-alert gif-no-connection">
				<i class="bb-icons-rl-cloud-slash"></i>
				<p><?php esc_html_e( 'Could not connect to GIPHY', 'buddyboss' ); ?></p>
			</div>

		</div>
	</div>
</script>
