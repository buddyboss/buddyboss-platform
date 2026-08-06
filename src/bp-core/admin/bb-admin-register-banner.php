<?php
/**
 * "Register your email and get Memberships & Courses" banner.
 *
 * Shows an email-capture banner on the BuddyBoss Settings page
 * (admin.php?page=bb-settings) to free, unlicensed installs. Registering an
 * email issues a free license key from the mothership via Platform's existing
 * `bb_get_free_license` AJAX action; once the site is properly licensed (a key
 * exists AND is activated) the banner no longer renders.
 *
 * Display resolves to one of three modes:
 * - 'pitch'  — full banner with the "Register with Email" CTA and modal.
 * - 'notice' — amber "Complete setup by activating your license" strip only.
 * - 'none'   — nothing rendered, no assets enqueued.
 *
 * @package BuddyBoss\Core\Administration
 * @since BuddyBoss [BBVERSION]
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/*
 * Platform owns this banner. Older BuddyBoss Membership builds ship their own
 * copy (BbmsRegisterBannerCtrl in brand/, pending removal); its display gate
 * runs through this filter, so forcing it false suppresses the legacy banner
 * entirely — no assets enqueued, no markup injected — and Platform's banner
 * can never appear twice alongside it. Becomes a harmless no-op once the
 * Membership-side module is deleted.
 */
add_filter( 'bbms_bbplfm_show_register_banner', '__return_false', 999 );

/**
 * Whether any of the Membership product plugins are installed.
 *
 * Installed — not merely active — a deactivated plugin still means the site
 * owns the product, so it should be nudged to activate its license rather
 * than re-pitched a product it already has.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @return bool True when at least one product plugin exists on disk.
 */
function bb_admin_register_banner_products_installed() {

	/**
	 * Filter the plugin basenames that count as "Membership products installed".
	 *
	 * When any of these exist on disk the banner shows the activate-license
	 * notice instead of the register pitch.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @param array $plugins Plugin basenames relative to WP_PLUGIN_DIR.
	 */
	$plugins = apply_filters(
		'bb_admin_register_banner_product_plugins',
		array(
			'buddybossmembership/buddybossmembership.php',
			'buddybossmembership-courses/main.php',
		)
	);

	foreach ( (array) $plugins as $plugin_file ) {
		if ( '' !== $plugin_file && file_exists( WP_PLUGIN_DIR . '/' . $plugin_file ) ) {
			return true;
		}
	}

	return false;
}

/**
 * Resolve the register banner display mode for the current request.
 *
 * "Properly licensed" is composed from Platform's own DRM building blocks
 * (has_key + activation status) rather than is_valid()/is_addon_licensed(),
 * because those short-circuit to "licensed" on dev/staging environments
 * (WP_ENVIRONMENT_TYPE or a dev URL) with no opt-out — which would hide the
 * banner on any non-production site regardless of the real license state.
 *
 * The `bb_register_banner_registered` flag exists because the free key is
 * emailed, never stored locally — has_key() is still false immediately after
 * a successful registration, and without the flag the just-registered admin
 * would be pitched again on the next page load.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @return string One of 'pitch', 'notice', 'none'.
 */
function bb_admin_register_banner_mode() {
	$mode = 'none';

	if ( ! is_admin() || ! current_user_can( 'manage_options' ) ) {
		return $mode;
	}

	// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only page routing for display gating.
	$page = isset( $_GET['page'] ) ? sanitize_key( wp_unslash( $_GET['page'] ) ) : '';
	if ( 'bb-settings' !== $page ) {
		return $mode;
	}

	$has_key   = \BuddyBoss\Core\Admin\DRM\BB_DRM_Helper::has_key();
	$connector = new \BuddyBoss\Core\Admin\Mothership\BB_Plugin_Connector();
	$activated = (bool) $connector->getLicenseActivationStatus();

	if ( $has_key && $activated ) {
		$mode = 'none';
	} elseif ( $has_key ) {
		// A key exists but is not activated (pasted and never activated, or
		// the activation lapsed) — never re-pitch registration to a key holder.
		$mode = 'notice';
	} elseif ( bb_admin_register_banner_products_installed() ) {
		// No key, but the site already owns Membership/Courses — nudge to
		// activate instead of pitching an owned product.
		$mode = 'notice';
	} elseif ( get_option( 'bb_register_banner_registered' ) ) {
		// Registered via the banner; the key is in their inbox, not stored.
		$mode = 'notice';
	} else {
		$mode = 'pitch';
	}

	/**
	 * Filter the register banner display mode.
	 *
	 * Only reached on the BuddyBoss Settings page for an admin; return 'none'
	 * to suppress the banner, or force 'pitch'/'notice' for testing.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @param string $mode One of 'pitch', 'notice', 'none'.
	 */
	return apply_filters( 'bb_admin_register_banner_mode', $mode );
}

/**
 * Enqueue the register banner assets when the banner will render.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @return void
 */
function bb_admin_register_banner_enqueue() {
	$mode = bb_admin_register_banner_mode();

	if ( 'none' === $mode ) {
		return;
	}

	wp_enqueue_style(
		'bb-register-banner',
		buddypress()->plugin_url . 'bp-core/admin/css/register-banner.css',
		array(),
		bp_get_version()
	);

	// The modal (and its JS) only exists in pitch mode; the notice strip is static.
	if ( 'pitch' !== $mode ) {
		return;
	}

	wp_enqueue_script(
		'bb-register-banner',
		buddypress()->plugin_url . 'bp-core/admin/js/register-banner.js',
		array(),
		bp_get_version(),
		true
	);

	wp_localize_script(
		'bb-register-banner',
		'bbRegBanner',
		array(
			'ajaxUrl'    => admin_url( 'admin-ajax.php' ),
			// Platform's existing free-license Mothership issuance flow.
			'action'     => 'bb_get_free_license',
			'nonce'      => wp_create_nonce( 'bb_get_free_license' ),
			// Records registration so the notice mode persists across loads.
			'markAction' => 'bb_register_banner_mark_registered',
			'markNonce'  => wp_create_nonce( 'bb_register_banner_mark_registered' ),
			'i18n'       => array(
				'requiredFields' => __( 'Please fill in all fields.', 'buddyboss' ),
				'invalidEmail'   => __( 'Please enter a valid email address.', 'buddyboss' ),
				'genericError'   => __( 'Something went wrong. Please try again.', 'buddyboss' ),
			),
		)
	);
}
add_action( 'admin_enqueue_scripts', 'bb_admin_register_banner_enqueue' );

/**
 * Render the register banner for the resolved mode.
 *
 * Called from the Settings 2.0 page markup, inside .bb-admin-settings-wrap
 * and before the React mount (#bb-admin-settings) so React never manages —
 * and can never clobber — this node.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @return void
 */
function bb_admin_render_register_banner() {
	$mode = bb_admin_register_banner_mode();

	if ( 'none' === $mode ) {
		return;
	}

	$template = buddypress()->plugin_dir . 'bp-core/admin/templates/register-banner.php';
	if ( ! file_exists( $template ) ) {
		return;
	}

	// $mode is read by the template.
	include $template;
}

/**
 * AJAX: record that the admin registered their email via the banner.
 *
 * Capability is checked before the nonce per project convention. The flag
 * only demotes the banner from 'pitch' to 'notice'; the banner disappears
 * entirely only on real license activation.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @return void
 */
function bb_admin_register_banner_ajax_mark_registered() {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_send_json_error( array( 'message' => __( 'You are not allowed to do this.', 'buddyboss' ) ), 403 );
	}

	if ( ! check_ajax_referer( 'bb_register_banner_mark_registered', 'nonce', false ) ) {
		wp_send_json_error( array( 'message' => __( 'Security check failed.', 'buddyboss' ) ), 400 );
	}

	update_option( 'bb_register_banner_registered', 1 );
	wp_send_json_success();
}
add_action( 'wp_ajax_bb_register_banner_mark_registered', 'bb_admin_register_banner_ajax_mark_registered' );
