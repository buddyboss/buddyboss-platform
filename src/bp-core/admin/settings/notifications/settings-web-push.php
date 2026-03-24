<?php
/**
 * BuddyBoss Admin Settings - Web Push Notifications Panel.
 *
 * Registers sections and fields for the Web Push Notifications side panel.
 * Platform shows a "install Pro" notice; Pro extends via bb_after_register_features
 * to inject OneSignal fields.
 *
 * @package BuddyBoss\Core\Administration
 * @since BuddyBoss [BBVERSION]
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Register Web Push Notifications panel sections and fields.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @return void
 */
function bb_notifications_register_web_push_panel_fields() {

	// Minimum Pro version that supports Settings 2.0 OneSignal integration.
	$min_pro_settings2_version = '2.0.2';
	$pro_available             = function_exists( 'bb_platform_pro' );
	$bridge_available          = function_exists( 'bb_integration_bridge' );

	// Check if OneSignal integration feature is disabled via the Integration Bridge.
	$onesignal_feature_disabled = false;
	if (
		$pro_available &&
		$bridge_available &&
		bb_integration_bridge()->is_managed_integration( 'onesignal' ) &&
		! bb_integration_bridge()->is_integration_feature_enabled( 'onesignal' )
	) {
		$onesignal_feature_disabled = true;
	}

	// Check if Pro OLD (installed but doesn't register OneSignal as managed integration).
	$is_pro_old = $pro_available && $bridge_available && ! bb_integration_bridge()->is_managed_integration( 'onesignal' );

	// -------------------------------------------------------------------------
	// SECTION: Web Push Notifications
	// Hide the default section title when Pro NEW registers its own OneSignal section header.
	// Show it when: Pro not installed (placeholder), OneSignal disabled, or Pro OLD (notice).
	// -------------------------------------------------------------------------
	$show_web_push_title = true;
	if ( ! $onesignal_feature_disabled && ! $is_pro_old ) {
		if ( ! $pro_available ) {
			// Pro not installed — OneSignal placeholder section is the primary header.
			$show_web_push_title = false;
		} elseif (
			version_compare( bb_platform_pro()->version, $min_pro_settings2_version, '>' ) &&
			( ! function_exists( 'bb_enabled_legacy_email_preference' ) || ! bb_enabled_legacy_email_preference() )
		) {
			// Pro NEW with modern notifications — Pro registers its own OneSignal section.
			$show_web_push_title = false;
		}
	}

	bb_register_feature_section(
		'notifications',
		'onesignal',
		'onesignal',
		array(
			'title'       => $show_web_push_title ? __( 'Web Push Notifications', 'buddyboss' ) : '',
			'description' => '',
			'order'       => 10,
		)
	);

	if ( $onesignal_feature_disabled ) {
		// Pro installed but OneSignal feature disabled via Settings 2.0 toggle —
		// show a centered empty state card directing admin to enable the integration.
		bb_register_feature_field(
			'notifications',
			'onesignal',
			'onesignal',
			array(
				'name'                  => '_bb_onesignal_disabled_notice',
				'label'                 => '',
				'type'                  => 'empty_state',
				'icon'                  => 'bb-icons-rl bb-icons-rl-warning-circle',
				'empty_state_title'     => __( 'OneSignal Integration is Disabled', 'buddyboss' ),
				'empty_state_description' => __( 'OneSignal integration for BuddyBoss is currently disabled. Enable it to configure web push notifications.', 'buddyboss' ),
				'button_label'          => __( 'BuddyBoss Settings', 'buddyboss' ),
				'button_url'            => admin_url( 'admin.php?page=bb-settings' ),
				'sanitize_callback'     => '__return_empty_string',
				'order'                 => 10,
			)
		);
	} elseif ( ! $pro_available ) {
		// Pro not installed — show OneSignal section with pro-gated disabled fields.
		bb_notifications_register_web_push_pro_placeholder_fields();
	} elseif ( $is_pro_old ) {
		// Pro installed but OLD (doesn't register OneSignal as managed integration).
		// Show centered empty state card directing admin to update Pro.
		bb_register_feature_field(
			'notifications',
			'onesignal',
			'onesignal',
			array(
				'name'                    => '_bb_onesignal_pro_update_notice',
				'label'                   => '',
				'type'                    => 'empty_state',
				'icon'                    => 'bb-icons-rl bb-icons-rl-warning-circle',
				'empty_state_title'       => __( 'BuddyBoss Platform Pro Update Required', 'buddyboss' ),
				'empty_state_description' => __( 'Please update to the latest version of BuddyBoss Pro to manage your web push notification settings.', 'buddyboss' ),
				'button_label'            => __( 'Update Now', 'buddyboss' ),
				'button_url'              => admin_url( 'update-core.php' ),
				'sanitize_callback'       => '__return_empty_string',
				'order'                   => 10,
			)
		);
	} elseif (
		$pro_available &&
		version_compare( bb_platform_pro()->version, $min_pro_settings2_version, '<=' )
	) {
		// Pro installed but older version.
		bb_register_feature_field(
			'notifications',
			'onesignal',
			'onesignal',
			array(
				'name'              => '_bb_web_push_pro_outdated_notice',
				'label'             => '',
				'type'              => 'notice',
				'description'       => sprintf(
					/* translators: %s: BuddyBoss Pro link. */
					__( 'Please update %s to version 2.0.3 to use web push notifications on your site.', 'buddyboss' ),
					'<a target="_blank" rel="noopener noreferrer" href="' . esc_url( 'https://www.buddyboss.com/platform' ) . '">' . __( 'BuddyBoss Platform Pro', 'buddyboss' ) . '</a>'
				),
				'sanitize_callback' => '__return_empty_string',
				'order'             => 10,
			)
		);
	} elseif (
		$pro_available &&
		function_exists( 'bb_enabled_legacy_email_preference' ) &&
		bb_enabled_legacy_email_preference()
	) {
		// Pro installed but legacy notification mode.
		bb_register_feature_field(
			'notifications',
			'onesignal',
			'onesignal',
			array(
				'name'              => '_bb_web_push_legacy_notice',
				'label'             => '',
				'type'              => 'notice',
				'description'       => __( 'Web Push Notifications are not supported when using the legacy notifications system.', 'buddyboss' ),
				'sanitize_callback' => '__return_empty_string',
				'order'             => 10,
			)
		);
	}

	// Pro injects its OneSignal fields via bb_after_register_features or
	// bb_notifications_after_register_settings_fields hook.

	/**
	 * Fires after Web Push Notifications section fields are registered.
	 * Pro hooks here to add OneSignal API credentials and notification settings fields.
	 *
	 * @since BuddyBoss [BBVERSION]
	 */
	do_action( 'bb_notifications_web_push_after_settings_fields' );
}

/**
 * Register placeholder OneSignal fields when Pro is not installed.
 *
 * Shows the OneSignal connection section (status badge, API credentials,
 * connect button) in disabled/pro-gated state so users see the Figma
 * design rather than a plain "install Pro" notice.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @return void
 */
function bb_notifications_register_web_push_pro_placeholder_fields() {

	$feature_id = 'notifications';
	$panel_id   = 'onesignal';

	// -------------------------------------------------------------------------
	// SECTION: OneSignal Connection (pro-gated placeholder).
	// -------------------------------------------------------------------------
	bb_register_feature_section(
		$feature_id,
		$panel_id,
		'onesignal_connection',
		array(
			'title'       => __( 'OneSignal', 'buddyboss' ),
			'order'       => 20,
			'status'      => array(
				'type' => 'warning',
				'text' => __( 'Not Connected', 'buddyboss' ),
			),
			'pro_notice'  => array(
				'show'       => true,
				'badge_text' => __( 'UPGRADE PRO', 'buddyboss' ),
				'badge_icon' => 'bb-icons-rl-crown-simple',
				'link_url'   => 'https://www.buddyboss.com/platform/',
			),
			'description' => sprintf(
				/* translators: %s: OneSignal URL */
				__( 'To use <a href="%s" target="_blank" rel="noopener noreferrer">OneSignal</a> for web push notifications, create an app in your account and enter the API credentials from the settings below.', 'buddyboss' ),
				'https://onesignal.com/'
			),
		)
	);

	// App ID (pro_only password field).
	bb_register_feature_field(
		$feature_id,
		$panel_id,
		'onesignal_connection',
		array(
			'name'              => 'bb-web-push-app-id',
			'label'             => __( 'API Credentials', 'buddyboss' ),
			'type'              => 'password',
			'default'           => '',
			'sanitize_callback' => '__return_empty_string',
			'placeholder'       => __( 'Enter OneSignal APP ID', 'buddyboss' ),
			'pro_only'          => true,
			'group'             => array(
				'key'   => 'web_push_credentials',
				'label' => __( 'App ID', 'buddyboss' ),
			),
			'order'             => 10,
		)
	);

	// REST API Key (pro_only password field).
	bb_register_feature_field(
		$feature_id,
		$panel_id,
		'onesignal_connection',
		array(
			'name'              => 'bb-web-push-rest-api-key',
			'label'             => '',
			'type'              => 'password',
			'default'           => '',
			'sanitize_callback' => '__return_empty_string',
			'placeholder'       => __( 'Enter Rest API key', 'buddyboss' ),
			'pro_only'          => true,
			'group'             => array(
				'key'   => 'web_push_credentials',
				'label' => __( 'Rest API Key', 'buddyboss' ),
			),
			'order'             => 20,
		)
	);

	// Connect button (pro_only, disabled).
	bb_register_feature_field(
		$feature_id,
		$panel_id,
		'onesignal_connection',
		array(
			'name'              => '_bb_web_push_connect',
			'label'             => '',
			'type'              => 'input_button',
			'button_label'      => __( 'Connect', 'buddyboss' ),
			'button_only'       => true,
			'pro_only'          => true,
			'sanitize_callback' => '__return_empty_string',
			'group'             => 'web_push_credentials',
			'order'             => 30,
		)
	);
}
