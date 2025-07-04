<?php
/**
 * Template for displaying the BP Object search form
 *
 * This template handles the search form display for BuddyPress objects.
 * It includes a text input field and submit button with proper accessibility.
 *
 * @package BuddyBoss\Template
 * @subpackage BP_Nouveau\ReadyLaunch
 * @since BuddyBoss 2.9.00
 * @version 1.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

$search_term = ! empty( $_REQUEST['s'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['s'] ) ) : '';
?>

<div class="<?php bp_nouveau_search_container_class(); ?> bp-search-form">
	<form action="<?php echo esc_url( home_url( '/' ) ); ?>" method="get" class="bp-dir-search-form" id="<?php bp_nouveau_search_selector_id( 'search-form' ); ?>" role="search">

		<label for="<?php bp_nouveau_search_selector_id( 'search' ); ?>" class="bp-screen-reader-text"><?php bp_nouveau_search_default_text( '', false ); ?></label>

		<input
			id="<?php bp_nouveau_search_selector_id( 'search' ); ?>"
			name="s"
			type="search"
			value="<?php echo BP_Search::instance()->has_search_results() ? esc_attr( $search_term ) : ''; ?>"
			placeholder="<?php echo BP_Search::instance()->has_search_results() ? esc_attr__( 'Search Network&hellip;', 'buddyboss' ) : esc_attr__( 'Try different keywords&hellip;', 'buddyboss' ); ?>"
		/>

		<button type="submit" id="<?php bp_nouveau_search_selector_id( 'search-submit' ); ?>" class="nouveau-search-submit">
			<span class="bb-icons-rl-magnifying-glass" aria-hidden="true"></span>
			<span id="button-text" class="bp-screen-reader-text"><?php esc_html_e( 'Search', 'buddyboss' ); ?></span>
		</button>

	</form>
</div>
