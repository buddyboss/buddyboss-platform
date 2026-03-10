<?php
/**
 * BuddyBoss Admin Settings - Moderation: Content Reporting Panel.
 *
 * Registers sections and fields for the Content Reporting side panel.
 * Fields are dynamically generated from registered moderation content types.
 *
 * Content type fields are registered lazily via
 * bb_admin_settings_before_get_feature because the moderation content type
 * classes (BP_Moderation_Activity, BP_Moderation_Groups, etc.) are
 * instantiated at `bp_init` priority 1, which fires after
 * `bb_register_features` (at `bp_loaded` priority 5). Calling
 * bp_moderation_content_types() during feature registration returns
 * an empty array since the filter callbacks aren't registered yet.
 *
 * @package BuddyBoss\Core\Administration
 * @since BuddyBoss [BBVERSION]
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Register Content Reporting panel sections and static fields.
 *
 * Registers the sections and the Email Notification field eagerly.
 * Per-content-type fields are registered lazily via
 * bb_moderation_lazy_register_content_type_fields().
 *
 * @since BuddyBoss [BBVERSION]
 */
function bb_moderation_register_content_reporting_fields() {

	// -------------------------------------------------------------------------
	// SECTION: Content Reporting
	// -------------------------------------------------------------------------
	bb_register_feature_section(
		'moderation',
		'content_reporting',
		'content_reporting',
		array(
			'title' => __( 'Content Reporting', 'buddyboss' ),
			'order' => 10,
		)
	);

	// -------------------------------------------------------------------------
	// SECTION: Email Notification
	// -------------------------------------------------------------------------
	bb_register_feature_section(
		'moderation',
		'content_reporting',
		'reporting_email_notification',
		array(
			'title' => __( 'Email Notification', 'buddyboss' ),
			'order' => 100,
		)
	);

	// FIELD: Email Notification (Toggle).
	bb_register_feature_field(
		'moderation',
		'content_reporting',
		'reporting_email_notification',
		array(
			'name'              => 'bpm_reporting_email_notification',
			'label'             => __( 'Email Notification', 'buddyboss' ),
			'type'              => 'toggle',
			'description'       => __( 'Notify administrators when content has been automatically hidden', 'buddyboss' ),
			'default'           => bp_is_moderation_reporting_email_notification_enable( false ),
			'sanitize_callback' => 'absint',
			'order'             => 10,
		)
	);
}

/**
 * Lazily register per-content-type reporting fields.
 *
 * Hooked to `bb_admin_settings_before_get_feature` which fires during the
 * AJAX request to fetch feature settings. By this point `bp_init` has fired
 * and bp_moderation_content_types() returns the full list of content types.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param string $feature_id The feature being fetched.
 */
function bb_moderation_lazy_register_content_type_fields( $feature_id ) {
	if ( 'moderation' !== $feature_id ) {
		return;
	}

	// Prevent double-registration if called multiple times.
	static $registered = false;
	if ( $registered ) {
		return;
	}
	$registered = true;

	// Get all moderation content types and exclude member-related types.
	$content_types = bp_moderation_content_types();
	unset( $content_types[ BP_Moderation_Members::$moderation_type ] );
	unset( $content_types[ BP_Moderation_Members::$moderation_type_report ] );

	$field_order = 10;

	foreach ( $content_types as $slug => $type_label ) {

		// Skip the member blocking type (handled in Member Moderation panel).
		if ( BP_Moderation_Members::$moderation_type === $slug ) {
			continue;
		}

		// FIELD: Allow {type} to be reported (Toggle).
		$reporting_field_name = 'bpm_reporting_content_reporting_' . $slug;
		bb_register_feature_field(
			'moderation',
			'content_reporting',
			'content_reporting',
			array(
				'name'              => $reporting_field_name,
				'label'             => $type_label,
				'type'              => 'toggle',
				/* translators: %s: content type label (lowercase). */
				'description'       => sprintf( __( 'Allow %s to be reported', 'buddyboss' ), strtolower( $type_label ) ),
				'default'           => bp_is_moderation_content_reporting_enable( false, $slug ),
				'sanitize_callback' => 'absint',
				'order'             => $field_order,
			)
		);

		// FIELD: Auto hide {type} after X reports (Toggle with inline number).
		bb_register_feature_field(
			'moderation',
			'content_reporting',
			'content_reporting',
			array(
				'name'                 => 'bpm_reporting_auto_hide_' . $slug,
				'label'                => '',
				'type'                 => 'checkbox',
				/* translators: %s: number input for threshold. */
				'description'          => sprintf(
					/* translators: 1: content type label (lowercase), 2: threshold number input placeholder. */
					__( 'Auto hide %1$s after %2$s reports', 'buddyboss' ),
					strtolower( $type_label ),
					'%s'
				),
				'default'              => bp_is_moderation_auto_hide_enable( false, $slug ),
				'sanitize_callback'    => 'absint',
				'parent_field'         => $reporting_field_name,
				'description_controls' => array(
					array(
						'type'              => 'number',
						'name'              => 'bpm_reporting_auto_hide_threshold_' . $slug,
						'default'           => bp_moderation_reporting_auto_hide_threshold( 5, $slug ),
						'sanitize_callback' => 'bb_moderation_sanitize_auto_hide_threshold',
						'min'               => 1,
						'max'               => 99,
						'step'              => 1,
					),
				),
				'order'                => $field_order + 1,
			)
		);

		$field_order += 10;
	}
}
add_action( 'bb_admin_settings_before_get_feature', 'bb_moderation_lazy_register_content_type_fields' );
