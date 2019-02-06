<?php
/**
 * BP Object search form
 *
 * @since BuddyPress 3.0.0
 * @version 3.1.0
 */
$search_term =  ! empty( $_REQUEST['s'] ) ? $_REQUEST['s'] : '';
?>

<div class="<?php bp_nouveau_search_container_class(); ?> bp-search-form" data-bp-search="<?php bp_nouveau_search_object_data_attr() ;?>">
	<form action="<?php echo esc_url( home_url( '/' ) ); ?>" method="get" class="bp-dir-search-form" id="<?php bp_nouveau_search_selector_id( 'search-form' ); ?>" role="search">

		<label for="<?php bp_nouveau_search_selector_id( 'search' ); ?>" class="bp-screen-reader-text"><?php bp_nouveau_search_default_text( '', false ); ?></label>

		<input
			id="<?php bp_nouveau_search_selector_id( 'search' ); ?>"
			name="s"
			type="search"
			value="<?php echo BP_Search::instance()->has_search_results() ? $search_term : '' ?>"
			placeholder="<?php echo BP_Search::instance()->has_search_results() ?  __( 'Search Network...', "buddyboss" ) : __( 'Try different keywords...', "buddyboss" ) ?>"
		/>

		<button type="submit" id="<?php bp_nouveau_search_selector_id( 'search-submit' ); ?>" class="nouveau-search-submit" name="s">
			<span class="dashicons dashicons-search" aria-hidden="true"></span>
			<span id="button-text" class="bp-screen-reader-text"><?php echo esc_html_x( 'Search', 'button', 'buddyboss' ); ?></span>
		</button>

	</form>
</div>
