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
 */
function bb_notifications_register_web_push_panel_fields() {

	// -------------------------------------------------------------------------
	// SECTION: Web Push Notifications
	// When Pro is active and modern notification mode, the section title is empty
	// because Pro registers its own "OneSignal" section as the primary header.
	// -------------------------------------------------------------------------
	$show_web_push_title = true;
	if (
		function_exists( 'bb_platform_pro' ) &&
		version_compare( bb_platform_pro()->version, '2.0.2', '>' ) &&
		( ! function_exists( 'bb_enabled_legacy_email_preference' ) || ! bb_enabled_legacy_email_preference() )
	) {
		$show_web_push_title = false;
	}

	bb_register_feature_section(
		'notifications',
		'web_push_notifications',
		'web_push_notifications',
		array(
			'title'       => $show_web_push_title ? __( 'Web Push Notifications', 'buddyboss' ) : '',
			'description' => '',
			'order'       => 10,
		)
	);

	// Determine which notice to show based on Pro availability and notification mode.
	if ( ! function_exists( 'bb_platform_pro' ) ) {
		// Pro not installed.
		bb_register_feature_field(
			'notifications',
			'web_push_notifications',
			'web_push_notifications',
			array(
				'name'              => '_bb_web_push_pro_notice',
				'label'             => '',
				'type'              => 'notice',
				'description'       => sprintf(
					/* translators: %s: BuddyBoss Pro link. */
					__( 'Please install %s to use web push notifications on your site.', 'buddyboss' ),
					'<a href="' . esc_url( 'https://www.buddyboss.com/platform' ) . '" target="_blank">' . __( 'BuddyBoss Platform Pro', 'buddyboss' ) . '</a>'
				),
				'sanitize_callback' => '__return_empty_string',
				'order'             => 10,
			)
		);
	} elseif (
		function_exists( 'bb_platform_pro' ) &&
		version_compare( bb_platform_pro()->version, '2.0.2', '<=' )
	) {
		// Pro installed but older version.
		bb_register_feature_field(
			'notifications',
			'web_push_notifications',
			'web_push_notifications',
			array(
				'name'              => '_bb_web_push_pro_outdated_notice',
				'label'             => '',
				'type'              => 'notice',
				'description'       => sprintf(
					/* translators: %s: BuddyBoss Pro link. */
					__( 'Please update %s to version 2.0.3 to use web push notifications on your site.', 'buddyboss' ),
					'<a target="_blank" href="' . esc_url( 'https://www.buddyboss.com/platform' ) . '">' . __( 'BuddyBoss Platform Pro', 'buddyboss' ) . '</a>'
				),
				'sanitize_callback' => '__return_empty_string',
				'order'             => 10,
			)
		);
	} elseif (
		function_exists( 'bb_platform_pro' ) &&
		function_exists( 'bb_enabled_legacy_email_preference' ) &&
		bb_enabled_legacy_email_preference()
	) {
		// Pro installed but legacy notification mode.
		bb_register_feature_field(
			'notifications',
			'web_push_notifications',
			'web_push_notifications',
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
