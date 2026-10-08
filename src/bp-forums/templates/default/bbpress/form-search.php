<?php

/**
 * Search
 *
 * @package BuddyBoss\Theme
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

?>

<form role="search" method="get" id="bbp-search-form" action="<?php bbp_search_url(); ?>">
	<div>
		<label class="screen-reader-text hidden" for="bbp_search"><?php echo wp_kses_post( __( 'Search Forums&hellip;', 'buddyboss-platform' ) ); ?></label>
		<input type="hidden" name="action" value="bbp-search-request" />
		<input tabindex="<?php bbp_tab_index(); ?>" type="text" value="<?php echo esc_attr( bbp_get_search_terms() ); ?>" name="bbp_search" id="bbp_search" placeholder="<?php echo esc_attr( html_entity_decode( __( 'Search Forums&hellip;', 'buddyboss-platform' ), ENT_QUOTES, 'UTF-8' ) ); ?>" />
		<input tabindex="<?php bbp_tab_index(); ?>" class="button" type="submit" id="bbp_search_submit" value="<?php esc_attr_e( 'Search', 'buddyboss-platform' ); ?>" />
	</div>
</form>
