<?php
/**
 * Appearance Feature Loader.
 *
 * Loads the Appearance feature runtime code. Only included by BB_Feature_Loader
 * when the feature is considered active. Appearance is always-active (no toggle),
 * so this runs on every request once the feature is registered.
 *
 * Timing: loaded during bp_loaded priority 5 via the feature loader chain. At this
 * point bp_setup_components (priority 2) has already fired, so bp_is_active() works.
 *
 * Phase 1 scope: empty runtime — the Appearance feature has no new classes or
 * frontend behavior. The existing BB_Readylaunch singleton (bp-core/classes/) keeps
 * owning the ReadyLaunch runtime. Admin settings register via bb-feature-config.php
 * → admin/settings.php.
 *
 * @package BuddyBoss\Features\Community\Appearance
 * @since BuddyBoss [BBVERSION]
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

// No runtime code yet. Later phases add panel-conditional engine hooks here if needed.
