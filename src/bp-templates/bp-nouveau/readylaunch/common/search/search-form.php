<?php
/**
 * ReadyLaunch - Search form template.
 *
 * This template handles the search form display for various
 * BuddyPress components with proper input sanitization.
 *
 * @package BuddyBoss\Template
 * @subpackage BP_Nouveau\ReadyLaunch
 * @since BuddyBoss 2.9.00
 * @version 1.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

$search_term = ( is_search() && isset( $_GET['s'] ) ) ? sanitize_text_field( wp_unslash( $_GET['s'] ) ) : '';
?>

<div class="<?php bp_nouveau_search_container_class(); ?> bp-search" data-bp-search="<?php bp_nouveau_search_object_data_attr(); ?>">
	<form action="" method="get" class="bp-dir-search-form search-form-has-reset" id="<?php bp_nouveau_search_selector_id( 'search-form' ); ?>" role="search">

		<label for="<?php bp_nouveau_search_selector_id( 'search' ); ?>" class="bp-screen-reader-text"><?php bp_nouveau_search_default_text( '', false ); ?></label>

		<input id="<?php bp_nouveau_search_selector_id( 'search' ); ?>" name="<?php bp_nouveau_search_selector_name(); ?>" type="search"  value="<?php echo esc_attr( $search_term ); ?>" placeholder="<?php bp_nouveau_search_default_text(); ?>" />

		<button type="submit" id="<?php bp_nouveau_search_selector_id( 'search-submit' ); ?>" class="nouveau-search-submit search-form_submit" name="<?php bp_nouveau_search_selector_name( 'search_submit' ); ?>">
			<span class="bb-icons-rl-magnifying-glass" aria-hidden="true"></span>
			<span id="button-text" class="bp-screen-reader-text"><?php esc_html_e( 'Search', 'buddyboss' ); ?></span>
		</button>

		<button type="reset" class="search-form_reset">
			<span class="bb-icons-rl-x" aria-hidden="true"></span>
			<span class="bp-screen-reader-text"><?php esc_html_e( 'Reset', 'buddyboss' ); ?></span>
		</button>

	</form>
</div>
