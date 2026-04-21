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

// ReadyLaunch standalone admin page (`admin.php?page=bb-readylaunch`) was
// retired in BuddyBoss [BBVERSION]. Its URL now redirects to the Appearance
// feature in Settings 2.0. The folder + `readylaunch/index.php` module are
// scheduled for hard deletion in Phase 9 cleanup — leaving them not-required
// means the legacy helper functions stop loading (their deprecation stubs
// in `deprecated/buddyboss/3.0.0.php` cover any third-party callers).

// Load the ReadyLaunch onboarding system.
require_once __DIR__ . '/rl-onboarding/index.php';
