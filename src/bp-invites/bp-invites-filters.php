<?php
/**
 * BuddyBoss Invites Filters.
 *
 * @package BuddyBoss\Invites\Filters
 * @since BuddyPress 1.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

// Make Send Invites extension to default in group
add_filter( 'bp_groups_default_extension', 'bp_set_send_invites_group_default_tab' );

/**
 * Make Send Invites extension to default in group.
 *
 * @since BuddyBoss 1.0.0
 *
 * @param $component
 *
 * @return mixed
 */
function bp_set_send_invites_group_default_tab( $component ) {

	// Get the group nav order based on the customizer settings.
	$nav_tabs = bp_nouveau_get_appearance_settings( 'group_nav_order' );
	if ( isset( $nav_tabs[0] ) && 'send-invites' === $nav_tabs[0] && bp_is_active( 'invites' ) ) {
		if ( bp_group_is_member()  ) {
			return $nav_tabs[0];
		}
	}

	return $component;
}
