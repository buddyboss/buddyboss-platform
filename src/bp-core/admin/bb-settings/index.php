<?php
/**
 * BuddyBoss Core React Admin Settings
 *
 * @package BuddyBoss
 * @subpackage Core
 * @since BuddyBoss 2.9.00
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Load the readylaunch settings.
require_once __DIR__ . '/readylaunch/index.php';

require_once __DIR__ . '/rl-onboarding/class-bb-readylaunch-onboarding.php';

if ( class_exists( 'BB_ReadyLaunch_Onboarding' ) ) {
	return BB_ReadyLaunch_Onboarding::instance();
}
