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

// Legacy ReadyLaunch standalone admin page retired in BuddyBoss 3.0.0
// (redirects to Settings 2.0 Appearance; folder deleted). Deprecation stubs
// for its public helpers live in `deprecated/buddyboss/3.0.0.php`.

// Load the ReadyLaunch onboarding system.
require_once __DIR__ . '/rl-onboarding/index.php';
