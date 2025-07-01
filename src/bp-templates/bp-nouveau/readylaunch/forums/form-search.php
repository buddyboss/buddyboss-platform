<?php
/**
 * Search Form Template
 *
 * @package BuddyBoss\Template
 * @subpackage BP_Nouveau\ReadyLaunch
 * @since BuddyBoss 2.9.00
 * @version 1.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;
?>

<form role="search" method="get" id="bbp-search-form" action="<?php bbp_search_url(); ?>">
	<div>
		<label class="screen-reader-text hidden" for="bbp_search"><?php esc_html_e( 'Search Forums&hellip;', 'buddyboss' ); ?></label>
		<input type="hidden" name="action" value="bbp-search-request" />
		<input tabindex="<?php bbp_tab_index(); ?>" type="text" value="<?php echo esc_attr( bbp_get_search_terms() ); ?>" name="bbp_search" id="bbp_search" placeholder="<?php esc_attr_e( 'Search Forums&hellip;', 'buddyboss' ); ?>" />
		<input tabindex="<?php bbp_tab_index(); ?>" class="bb-rl-button bb-rl-button--brandFill bb-rl-button--small button" type="submit" id="bbp_search_submit" value="<?php esc_attr_e( 'Search', 'buddyboss' ); ?>" />
	</div>
</form>
