<?php
/**
 * Template for displaying the BP Object search form
 *
 * This template can be overridden by copying it to yourtheme/buddypress/search/search-form.php.
 *
 * @since   BuddyPress 3.0.0
 * @version 1.0.0
 */

$search_term =  ! empty( $_REQUEST['s'] ) ? esc_html( $_REQUEST['s'] ) : '';
?>

<div class="<?php bp_nouveau_search_container_class(); ?> bp-search-form">
	<form action="<?php echo esc_url( home_url( '/' ) ); ?>" method="get" class="bp-dir-search-form" id="<?php bp_nouveau_search_selector_id( 'search-form' ); ?>" role="search">

		<label for="<?php bp_nouveau_search_selector_id( 'search' ); ?>" class="bp-screen-reader-text"><?php bp_nouveau_search_default_text( '', false ); ?></label>

		<input
			id="<?php bp_nouveau_search_selector_id( 'search' ); ?>"
			name="s"
			type="search"
			value="<?php echo BP_Search::instance()->has_search_results() ? $search_term : '' ?>"
			placeholder="<?php echo BP_Search::instance()->has_search_results() ?  __( 'Search Network&hellip;', "buddyboss" ) : __( 'Try different keywords&hellip;', "buddyboss" ) ?>"
		/>

		<button type="submit" id="<?php bp_nouveau_search_selector_id( 'search-submit' ); ?>" class="nouveau-search-submit">
			<span class="bb-icon-l bb-icon-search" aria-hidden="true"></span>
			<span id="button-text" class="bp-screen-reader-text"><?php esc_html_e( 'Search', 'buddyboss' ); ?></span>
		</button>

	</form>
</div>
