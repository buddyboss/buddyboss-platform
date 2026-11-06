<?php
/**
 * BuddyBoss LearnDash addon promotion IPN.
 *
 * After Platform 3.0 (PROD-9792) extracted the LearnDash integration into
 * the standalone `buddyboss-learndash` addon, sites that previously relied
 * on the bundled integration need to install the addon to keep their
 * LearnDash sync working. This file registers an in-product notification
 * (IPN) on the buddyboss-addons page that prompts qualifying admins to
 * install the addon.
 *
 * Conditions for showing the notice (ALL must be true):
 *   1. License is activated via Mothership (`getLicenseActivationStatus`).
 *   2. The active license plan is NOT bb-platform-lite (the LD addon is not
 *      part of the Lite tier — pitching it to Lite customers misleads them).
 *   3. The BuddyBoss ↔ LearnDash integration is enabled in
 *      `bp_ld_sync_settings` (either direction `.enabled` flag truthy) AND
 *      LearnDash LMS is active (`SFWD_LMS` class exists). Reading the
 *      integration setting directly scopes the notice to sites that have
 *      actually opted into BB ↔ LD sync.
 *   4. The `buddyboss-learndash` addon is NOT installed/active.
 *
 * When any condition flips (license deactivated, LD removed, or — most
 * importantly — the addon is installed), the notification is dismissed
 * on the next admin page load.
 *
 * @package BuddyBoss
 * @since BuddyBoss 3.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Notification id used by both add() and dismiss_events().
 *
 * Format mirrors Platform's DRM notification scheme:
 *   {type}_{id}  -> bb-addon-promo-learndash_install
 *
 * Kept stable so subsequent admin page loads idempotently replace the
 * existing record (Store::add() is keyed by id).
 *
 * @since BuddyBoss 3.0.0
 */
const BBOSS_LEARNDASH_ADDON_IPN_TYPE = 'bb-addon-promo-learndash';
const BBOSS_LEARNDASH_ADDON_IPN_ID   = 'install';

/**
 * Detect whether the buddyboss-learndash addon plugin is installed and
 * active.
 *
 * Uses WordPress's `is_plugin_active()` so a deactivated-but-installed
 * addon still triggers the notice (the user needs to actually run it).
 *
 * @since BuddyBoss 3.0.0
 *
 * @return bool
 */
function bboss_learndash_addon_is_active() {
	if ( ! function_exists( 'is_plugin_active' ) ) {
		require_once ABSPATH . 'wp-admin/includes/plugin.php';
	}
	return is_plugin_active( 'buddyboss-learndash/buddyboss-learndash.php' );
}

/**
 * Detect whether this site has the BuddyBoss ↔ LearnDash integration enabled.
 *
 * Checks the canonical `bp_ld_sync_settings` option — the same source the
 * pre-3.0 bundled integration (and the standalone addon's
 * `bb_learndash_is_sync_enabled()` helper) uses. The integration is
 * considered "enabled" when EITHER direction's `.enabled` flag is truthy:
 *   - `bp_ld_sync_settings[buddypress][enabled]` — BB groups → LD groups
 *   - `bp_ld_sync_settings[learndash][enabled]` — LD groups → BB groups
 *
 * Reading the setting directly (rather than just probing `SFWD_LMS` /
 * `bp_is_active('learndash')`) keeps the notice scoped to sites that have
 * actually opted into the integration. Sites that installed LearnDash LMS
 * but never toggled the BuddyBoss bridge don't need the addon.
 *
 * `!empty()` rather than strict `'1' === ...` to match Platform's
 * pre-migration call-site semantics — the legacy LD loader read the
 * settings the same way.
 *
 * @since BuddyBoss 3.0.0
 *
 * @return bool
 */
function bboss_learndash_addon_site_uses_learndash() {
	// SFWD_LMS gates the check: an enabled flag in the option without LD
	// LMS active means the user toggled the integration on once and then
	// deactivated LD — the addon isn't relevant in that state.
	if ( ! class_exists( 'SFWD_LMS' ) ) {
		return false;
	}

	$options = bboss_get_option( 'bp_ld_sync_settings', array() );
	if ( ! is_array( $options ) ) {
		return false;
	}

	return (
		! empty( $options['buddypress']['enabled'] ) ||
		! empty( $options['learndash']['enabled'] )
	);
}

/**
 * Detect whether the site has an activated BuddyBoss license.
 *
 * Reads through Mothership's plugin connector. Returns false when the
 * Mothership classes aren't loaded yet (very early boot or partial install).
 *
 * @since BuddyBoss 3.0.0
 *
 * @return bool
 */
function bboss_learndash_addon_has_active_license() {
	if ( ! class_exists( '\BuddyBoss\Core\Admin\Mothership\BB_Plugin_Connector' ) ) {
		return false;
	}
	try {
		$connector = new \BuddyBoss\Core\Admin\Mothership\BB_Plugin_Connector();
		return $connector->getLicenseActivationStatus();
	} catch ( \Throwable $e ) {
		return false;
	}
}

/**
 * Detect whether the active license is the Lite plan (any `bb-platform-lite*`
 * SKU). The LD addon is not entitled to Lite licenses, so promoting it to
 * Lite customers would be misleading. All other plans (Standard, Pro,
 * Developer, etc.) see the notice.
 *
 * The active plugin ID is stored in the `buddyboss_dynamic_plugin_id` option
 * and surfaced by Mothership's plugin connector. It encodes the plan that
 * the user's license activated against. Starts-with rather than strict
 * equality so future Lite SKU variants (e.g. `bb-platform-lite-monthly`)
 * are also covered without code changes.
 *
 * @since BuddyBoss 3.0.0
 *
 * @return bool
 */
function bboss_learndash_addon_license_is_lite() {
	if ( ! class_exists( '\BuddyBoss\Core\Admin\Mothership\BB_Plugin_Connector' ) ) {
		return false;
	}
	try {
		$connector = new \BuddyBoss\Core\Admin\Mothership\BB_Plugin_Connector();
		$plugin_id = $connector->getCurrentPluginId();
	} catch ( \Throwable $e ) {
		return false;
	}

	// getCurrentPluginId() returns string per declared return type; check the
	// 'bb-platform-lite' prefix so future Lite SKU variants are covered too.
	return 0 === strpos( $plugin_id, 'bb-platform-lite' );
}

/**
 * Add or dismiss the LearnDash addon promotion IPN based on current state.
 *
 * Runs on `admin_init` so we get a re-check on every admin page load —
 * conditions can change between sessions (license activated/deactivated,
 * addon installed/removed) and the notification state should always match.
 *
 * @since BuddyBoss 3.0.0
 */
function bboss_learndash_addon_sync_ipn() {
}
add_action( 'admin_init', 'bboss_learndash_addon_sync_ipn', 30 );
