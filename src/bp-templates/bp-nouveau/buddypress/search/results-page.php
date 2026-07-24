<?php
/**
 * Template for displaying the search results of the page
 *
 * the template file to display search result page having buddypress container
 * dont make changes to this file,
 * instead create a folder 'buddyboss-global-search' inside your theme, copy this file over there, and make changes there
 *
 * This template can be overridden by copying it to yourtheme/buddypress/search/results-page.php.
 *
 * @package BuddyBoss\Core
 * @since   BuddyBoss 1.0.0
 * @version 1.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

$post_title = '';

if ( empty( $_GET['s'] ) || '' === $_GET['s'] ) {
	$post_title = __( 'No results found', "buddyboss-platform" );
} elseif ( BP_Search::instance()->has_search_results() ) {
	/* translators: %s: search query. */
	$post_title = sprintf( __( 'Showing results for \'%s\'', "buddyboss-platform" ), esc_html( $_GET['s'] ) );
} else {
	/* translators: %s: search query. */
	$post_title = sprintf( __( 'No results for \'%s\'', "buddyboss-platform" ), esc_html( $_GET['s'] ) );
}
?>
<header class="entry-header">
	<h1 class="entry-title">
		<?php echo stripslashes( $post_title ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- $post_title is composed of plain-text translations with the search term already escaped via esc_html() at assignment. ?>
	</h1>
</header>

<div id="buddypress">
	<?php bp_get_template_part("search/results-page-content"); ?>
</div>
