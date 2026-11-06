<?php
/**
 * BuddyBoss Moderation Loader.
 *
 * An Moderation component, for users, groups, and site moderation.
 *
 * @since   BuddyBoss 1.5.6
 * @package BuddyBoss\Moderation
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Set up the bp-Moderation component.
 *
 * @since BuddyBoss 1.5.6
 */
function bboss_setup_moderation() {
	bboss()->moderation = new BBoss_Moderation_Component();
}

add_action( 'bp_setup_components', 'bboss_setup_moderation', 1 );
