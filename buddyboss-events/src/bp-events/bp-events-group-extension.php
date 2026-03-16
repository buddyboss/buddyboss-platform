<?php
/**
 * BuddyBoss Events Group Extension Loader.
 *
 * Registers the group extension on bp_init (priority 11) so the groups
 * component is fully initialised before the tab is added.
 *
 * @package BuddyBoss\Events
 * @since BuddyBoss Events 1.0.0
 */

defined( 'ABSPATH' ) || exit;

require_once dirname( __FILE__ ) . '/classes/class-bp-events-group-extension.php';

add_action( 'bp_init', function() {
	if ( bp_is_active( 'groups' ) ) {
		bp_register_group_extension( 'BP_Events_Group_Extension' );
	}
}, 11 );
