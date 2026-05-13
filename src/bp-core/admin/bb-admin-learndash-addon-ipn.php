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
const BB_LEARNDASH_ADDON_IPN_TYPE = 'bb-addon-promo-learndash';
const BB_LEARNDASH_ADDON_IPN_ID   = 'install';

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
function bb_learndash_addon_is_active() {
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
function bb_learndash_addon_site_uses_learndash() {
	// SFWD_LMS gates the check: an enabled flag in the option without LD
	// LMS active means the user toggled the integration on once and then
	// deactivated LD — the addon isn't relevant in that state.
	if ( ! class_exists( 'SFWD_LMS' ) ) {
		return false;
	}

	$options = bp_get_option( 'bp_ld_sync_settings', array() );
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
function bb_learndash_addon_has_active_license() {
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
function bb_learndash_addon_license_is_lite() {
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
function bb_learndash_addon_sync_ipn() {
	// Bail if the notifications wrapper isn't loaded — happens during very
	// early Platform boot or on partial installs where the DRM/Mothership
	// classes haven't autoloaded yet. Without this guard we'd fatal instead
	// of silently no-op'ing.
	if ( ! class_exists( '\BuddyBoss\Core\Admin\DRM\BB_Notifications' ) ) {
		return;
	}

	// Only do this for admins who can act on the notice.
	if ( ! \BuddyBoss\Core\Admin\DRM\BB_Notifications::has_access() ) {
		return;
	}

	$notifications = new \BuddyBoss\Core\Admin\DRM\BB_Notifications();

	// Suppress when the addon is already installed/active. Always dismiss
	// here too so a stale notice from a previous state is cleaned up.
	if ( bb_learndash_addon_is_active() ) {
		$notifications->dismiss_events( BB_LEARNDASH_ADDON_IPN_TYPE );
		return;
	}

	// Show only when the site appears to use LD AND the license is activated
	// (so we don't pitch the addon to unlicensed installs) AND the active
	// plan is NOT bb-platform-lite (LD addon is not part of the Lite tier).
	if (
		! bb_learndash_addon_site_uses_learndash() ||
		! bb_learndash_addon_has_active_license() ||
		bb_learndash_addon_license_is_lite()
	) {
		// If conditions are no longer met (e.g. license was deactivated
		// or downgraded to Lite after the notice was published), clean it up.
		$notifications->dismiss_events( BB_LEARNDASH_ADDON_IPN_TYPE );
		return;
	}

	// Bell-icon SVG (LearnDash-orange #f97316) — passes through BB_Notifications
	// unchanged because the wrapper detects the leading "<svg" and skips its
	// default <img> wrapping. Crisper than a PNG and themable per notification.
	$icon_svg = '<svg width="24" height="24" fill="#f97316" style="height: 100%; width: 100%;" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M0 0h24v24H0V0z" fill="none"></path><path d="M18 16v-5c0-3.07-1.64-5.64-4.5-6.32V4c0-.83-.67-1.5-1.5-1.5s-1.5.67-1.5 1.5v.68C7.63 5.36 6 7.92 6 11v5l-2 2v1h16v-1l-2-2zm-5 0h-2v-2h2v2zm0-4h-2V8h2v4zm-1 10c1.1 0 2-.9 2-2h-4c0 1.1.89 2 2 2z"></path></svg>';

	$notifications->add(
		array(
			'id'      => BB_LEARNDASH_ADDON_IPN_ID,
			'type'    => BB_LEARNDASH_ADDON_IPN_TYPE,
			'title'   => __( 'Are you using LearnDash? Install our Add-on', 'buddyboss' ),
			'content' => sprintf(
				'<p>%1$s</p><p>%2$s</p>',
				esc_html__( 'As part of our update to 3.0 we’ve moved our LearnDash Integration from core into it’s own add-on.', 'buddyboss' ),
				esc_html__( 'If you are using LearnDash with BuddyBoss Platform/Theme, please install it now or your user’s experience will be impacted.', 'buddyboss' )
			),
			'segment' => '',
			'saved'   => time(),
			'end'     => '',
			'icon'    => $icon_svg,
			'buttons' => array(
				'main' => array(
					'text'   => __( 'Install LearnDash Add-on', 'buddyboss' ),
					'url'    => admin_url( 'admin.php?page=buddyboss-addons' ),
					'target' => '_self',
				),
			),
		)
	);
}
add_action( 'admin_init', 'bb_learndash_addon_sync_ipn', 30 );
