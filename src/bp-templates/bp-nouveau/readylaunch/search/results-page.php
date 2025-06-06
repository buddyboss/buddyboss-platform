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
 * @since   BuddyBoss [BBVERSION]
 * @version 1.0.0
 */

$post_title = '';

if ( empty( $_GET['s'] ) || '' === $_GET['s'] ) {
	$post_title = __( 'No results found', "buddyboss" );
	$no_results = ' bb-rl-no-search-results';
} elseif ( BP_Search::instance()->has_search_results() ) {
	$post_title = sprintf( __( 'Showing results for <span class="bb-rl-result-label">\'%s\'</span>', "buddyboss" ), esc_html( $_GET['s'] ) );
} else {
	$post_title = sprintf( __( 'No results for <span class="bb-rl-result-label">\'%s\'</span>', "buddyboss" ), esc_html( $_GET['s'] ) );
	$no_results = ' bb-rl-no-search-results';
}
?>
<div class="bb-rl-container<?php echo $no_results; ?>">
	<div class="bb-rl-search-results-container">

		<header class="entry-header">
			<h1 class="entry-title">
				<?php echo stripslashes( $post_title ); ?>
			</h1>
		</header>

		<div id="buddypress">
			<?php bp_get_template_part("search/results-page-content"); ?>
		</div>

	</div>
</div>
