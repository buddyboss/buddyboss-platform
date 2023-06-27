<?php
/**
 * The template for BP Object search form
 *
 * This template can be overridden by copying it to yourtheme/buddypress/common/search/search-form.php.
 *
 * @since   BuddyPress 3.0.0
 * @version 1.0.0
 */

$search_term = ( is_search() && isset( $_GET['s'] ) ) ? $_GET['s'] : '';
?>

<div class="<?php bp_nouveau_search_container_class(); ?> bp-search" data-bp-search="<?php bp_nouveau_search_object_data_attr() ;?>">
	<form action="" method="get" class="bp-dir-search-form search-form-has-reset" id="<?php bp_nouveau_search_selector_id( 'search-form' ); ?>" role="search">

		<label for="<?php bp_nouveau_search_selector_id( 'search' ); ?>" class="bp-screen-reader-text"><?php bp_nouveau_search_default_text( '', false ); ?></label>

		<input id="<?php bp_nouveau_search_selector_id( 'search' ); ?>" name="<?php bp_nouveau_search_selector_name(); ?>" type="search"  value="<?php echo $search_term ?>" placeholder="<?php bp_nouveau_search_default_text(); ?>" />

		<button type="submit" id="<?php bp_nouveau_search_selector_id( 'search-submit' ); ?>" class="nouveau-search-submit search-form_submit" name="<?php bp_nouveau_search_selector_name( 'search_submit' ); ?>">
			<span class="bb-icon-l bb-icon-search" aria-hidden="true"></span>
			<span id="button-text" class="bp-screen-reader-text"><?php esc_html_e( 'Search', 'buddyboss' ); ?></span>
		</button>

		<button type="reset" class="search-form_reset">
			<span class="bb-icon-rf bb-icon-times" aria-hidden="true"></span>
			<span class="bp-screen-reader-text"><?php esc_html_e( 'Reset', 'buddyboss' ); ?></span>
		</button>

	</form>
</div>
