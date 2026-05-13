<?php
/**
 * BuddyBoss Admin Settings - Moderation: Member Moderation Panel.
 *
 * Registers sections and fields for the Member Moderation side panel.
 *
 * @package BuddyBoss\Core\Administration
 * @since BuddyBoss [BBVERSION]
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Register Member Moderation panel fields.
 *
 * All fields are registered in a single section so the React UI renders
 * them inside one card — matching the Figma design.
 *
 * @since BuddyBoss [BBVERSION]
 */
function bb_moderation_register_member_moderation_fields() {

	// Single section for the entire Member Moderation panel (one card in React UI).
	bb_register_feature_section(
		'moderation',
		'member_moderation',
		'member_moderation_settings',
		array(
			'title'    => __( 'Member Moderation', 'buddyboss' ),
			'order'    => 10,
			'help_url' => '636191',
		)
	);

	// FIELD: Member Blocking (Toggle).
	bb_register_feature_field(
		'moderation',
		'member_moderation',
		'member_moderation_settings',
		array(
			'name'              => 'bpm_blocking_member_blocking',
			'label'             => __( 'Member Blocking', 'buddyboss' ),
			'type'              => 'toggle',
			'description'       => __( 'Allow members to block other members', 'buddyboss' ),
			'help_text'         => __( 'When a member is blocked, their profile and all of their content are hidden from the member who blocked them.', 'buddyboss' ),
			'default'           => bp_is_moderation_member_blocking_enable( false ),
			'sanitize_callback' => 'absint',
			'order'             => 10,
		)
	);

	// FIELD: Member Reporting (Toggle).
	bb_register_feature_field(
		'moderation',
		'member_moderation',
		'member_moderation_settings',
		array(
			'name'              => 'bb_blocking_member_reporting',
			'label'             => __( 'Member Reporting', 'buddyboss' ),
			'type'              => 'toggle',
			'description'       => __( 'Allow members to report other members', 'buddyboss' ),
			'help_text'         => sprintf(
				/* translators: %s: reporting categories link. */
				__( 'If a member believes another member has violated one of your %s, they can report it to the site administrators.', 'buddyboss' ),
				sprintf(
					'<a href="%s">%s</a>',
					esc_url( bb_get_feature_settings_url( 'moderation', 'reporting_categories' ) ),
					__( 'reporting categories', 'buddyboss' )
				)
			),
			'default'           => bb_is_moderation_member_reporting_enable( false ),
			'sanitize_callback' => 'absint',
			'order'             => 20,
		)
	);

	// FIELD: Auto Suspend after X blocks (Toggle with inline number).
	// Depends on Member Blocking being enabled.
	//
	// NOTE: Both auto-suspend toggles share the same `group.key` so the
	// React renderer treats them as one logical "Auto Suspend" field — the
	// "Auto Suspend" label only renders on the first row, and the SCSS rule
	// `&--grouped:not(--full-width) { border-bottom: none }` suppresses the
	// horizontal divider between the two rows so they read as one field
	// group (matches Figma).
	bb_register_feature_field(
		'moderation',
		'member_moderation',
		'member_moderation_settings',
		array(
			'name'                 => 'bpm_blocking_auto_suspend',
			'label'                => __( 'Auto Suspend', 'buddyboss' ),
			'type'                 => 'toggle',
			/* translators: %s: threshold number input placeholder. */
			'description'          => __( 'Auto suspend members after %s blocks', 'buddyboss' ),
			'default'              => bp_is_moderation_auto_suspend_enable( false ),
			'sanitize_callback'    => 'absint',
			'description_controls' => array(
				array(
					'type'              => 'number',
					'name'              => 'bpm_blocking_auto_suspend_threshold',
					'default'           => bp_moderation_auto_suspend_threshold( 5 ),
					'sanitize_callback' => 'bb_moderation_sanitize_auto_suspend_threshold',
					'min'               => 1,
					'step'              => 1,
				),
			),
			'conditional'          => array(
				'field'  => 'bpm_blocking_member_blocking',
				'value'  => true,
				'action' => 'disable',
			),
			'group'                => array(
				'key' => 'auto_suspend',
			),
			'order'                => 30,
		)
	);

	// FIELD: Auto Suspend after X reports (Toggle with inline number).
	// Depends on Member Reporting being enabled.
	bb_register_feature_field(
		'moderation',
		'member_moderation',
		'member_moderation_settings',
		array(
			'name'                 => 'bb_reporting_auto_suspend',
			'label'                => '',
			'type'                 => 'toggle',
			/* translators: %s: threshold number input placeholder. */
			'description'          => __( 'Auto suspend members after %s reports', 'buddyboss' ),
			'default'              => bb_is_moderation_auto_suspend_report_enable( false ),
			'sanitize_callback'    => 'absint',
			'description_controls' => array(
				array(
					'type'              => 'number',
					'name'              => 'bb_reporting_auto_suspend_threshold',
					'default'           => bb_moderation_auto_suspend_report_threshold( 5 ),
					'sanitize_callback' => 'bb_moderation_sanitize_auto_suspend_threshold',
					'min'               => 1,
					'step'              => 1,
				),
			),
			'conditional'          => array(
				'field'  => 'bb_blocking_member_reporting',
				'value'  => true,
				'action' => 'disable',
			),
			'group'                => array(
				'key' => 'auto_suspend',
			),
			'order'                => 40,
		)
	);

	// FIELD: Email Notification (Toggle).
	// Depends on either Member Blocking OR Member Reporting being enabled.
	bb_register_feature_field(
		'moderation',
		'member_moderation',
		'member_moderation_settings',
		array(
			'name'              => 'bpm_blocking_email_notification',
			'label'             => __( 'Email Notification', 'buddyboss' ),
			'type'              => 'toggle',
			'description'       => __( 'Notify administrators when members have been automatically suspended', 'buddyboss' ),
			'default'           => bp_is_moderation_blocking_email_notification_enable( false ),
			'sanitize_callback' => 'absint',
			'conditional'       => array(
				'operator'   => 'OR',
				'action'     => 'disable',
				'conditions' => array(
					array(
						'field' => 'bpm_blocking_member_blocking',
						'value' => true,
					),
					array(
						'field' => 'bb_blocking_member_reporting',
						'value' => true,
					),
				),
			),
			'order'             => 50,
		)
	);
}
