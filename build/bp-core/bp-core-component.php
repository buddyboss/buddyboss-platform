<?php
/**
 * Component classes.
 *
 * @package BuddyBoss\Core
 * @since BuddyPress 1.5.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'BP_Component' ) ) {
	require dirname( __FILE__ ) . '/classes/class-bp-component.php';
}
