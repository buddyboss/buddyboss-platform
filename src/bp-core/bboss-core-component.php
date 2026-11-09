<?php
/**
 * Component classes.
 *
 * @package BuddyBoss\Core
 * @since BuddyPress 1.5.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'BBoss_Component' ) ) {
	require dirname( __FILE__ ) . '/classes/class-bboss-component.php';
}
