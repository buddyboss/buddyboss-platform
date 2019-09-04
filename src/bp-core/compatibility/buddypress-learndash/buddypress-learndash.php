<?php
// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Main
 *
 * @return void
 */
function BUDDYPRESS_LEARNDASH_init() {

	global $BUDDYPRESS_LEARNDASH;
	require_once buddypress()->compatibility_dir . 'buddypress-learndash/includes/main-class.php';

	$BUDDYPRESS_LEARNDASH = BuddyPress_LearnDash_Plugin::instance();

}

BUDDYPRESS_LEARNDASH_init();

/**
 * Must be called after hook 'plugins_loaded'
 * @return BuddyPress for LearnDash Plugin main controller object
 */
function buddypress_learndash() {
	global $BUDDYPRESS_LEARNDASH;

	$BUDDYPRESS_LEARNDASH->bp_learndash_loader = BuddyPress_Learndash_Loader::instance();

	if ( bp_is_active( 'groups' ) ) {
		$BUDDYPRESS_LEARNDASH->bp_learndash_groups = BuddyPress_Learndash_Groups::instance();
	}

	return $BUDDYPRESS_LEARNDASH;
}