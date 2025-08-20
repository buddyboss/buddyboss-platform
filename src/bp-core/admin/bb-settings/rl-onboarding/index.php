<?php
/**
 * BuddyBoss ReadyLaunch Onboarding Loader
 *
 * @package BuddyBoss
 * @subpackage Core
 * @since BuddyBoss 2.10.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Load the ReadyLaunch onboarding implementation.
require_once __DIR__ . '/class-bb-readylaunch-onboarding.php';

// Initialize the ReadyLaunch onboarding wizard.
if ( class_exists( 'BB_ReadyLaunch_Onboarding' ) ) {
	return BB_ReadyLaunch_Onboarding::instance();
}
