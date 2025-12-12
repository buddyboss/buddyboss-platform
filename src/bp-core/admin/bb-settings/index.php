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

// Load the base Setup Wizard Manager class first.
require_once buddypress()->plugin_dir . 'bp-core/classes/class-bb-setup-wizard-manager.php';

// Load the readylaunch settings.
require_once __DIR__ . '/readylaunch/index.php';

// Load the ReadyLaunch onboarding system.
require_once __DIR__ . '/rl-onboarding/index.php';
