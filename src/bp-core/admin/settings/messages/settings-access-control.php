<?php
/**
 * BuddyBoss Admin Settings - Messages Access Controls.
 *
 * Registers the Access Controls side panel, section, and field for the
 * Messages feature in the Settings 2.0 registry.
 *
 * All access-control logic lives in this file so it can be easily
 * extracted to Pro in the future. Pro populates the actual data
 * (types, options) via PHP filters.
 *
 * @package BuddyBoss\Core\Administration
 * @since   BuddyBoss 3.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Register Access Controls side panel, section, and field for Messages.
 *
 * Called from bb-admin-settings-messages.php after all other panels are
 * registered. Fires a hook so Pro (or third-party) can register
 * additional fields in the same panel.
 *
 * @since BuddyBoss 3.0.0
 */
function bb_messages_register_access_control_fields() {

	// -------------------------------------------------------------------------
	// SECTION: Message Access.
	// -------------------------------------------------------------------------
	bb_register_feature_section(
		'messages',
		'access_controls',
		'message_access',
		array(
			'title'      => __( 'Message Access', 'buddyboss-platform' ),
			'order'      => 10,
			'help_url'   => '636154',
			'pro_notice' => bb_admin_settings_get_pro_notice(
				array(
					'type'    => 'access_controls',
					'context' => 'section',
				)
			),
		)
	);

	// FIELD: Send Messages access control.
	bb_register_feature_field(
		'messages',
		'access_controls',
		'message_access',
		array(
			'name'               => 'bb-access-control-send-message',
			'label'              => __( 'Send Messages', 'buddyboss-platform' ),
			'type'               => 'access_control',
			'description'        => __( 'Select which members should have access to send messages to other members, based on:', 'buddyboss-platform' ),
			'default'            => '',
			'pro_only'           => true,
			'threaded'           => true,
			// Suffix copy that follows the bold role name on the threaded
			// toggle row (e.g. "Editor can send message to"). Per the Figma
			// layout, the All/Specific radios carry the rest of the meaning,
			// so the long "Members with the … can send messages to members
			// with - Any Member / With Specific …" sentence is no longer needed.
			'threaded_sub_label' => __( 'can send message to', 'buddyboss-platform' ),
			'order'              => 10,
			'sanitize_callback'  => function_exists( 'bb_sanitize_access_control_field' ) ? 'bb_sanitize_access_control_field' : 'bb_sanitize_access_control_fallback',
		)
	);

	// FIELD: Admin notice (displayed once at the end of the section).
	bb_register_feature_field(
		'messages',
		'access_controls',
		'message_access',
		array(
			'name'        => 'bb-messages-access-control-notice',
			'label'       => '',
			'type'        => 'notice',
			'description' => __( 'These settings do not apply to administrators or group messages.', 'buddyboss-platform' ),
			'notice_type' => 'info',
			'order'       => 100,
		)
	);

	/**
	 * Fires after the core Messages access-control fields are registered.
	 *
	 * Pro or third-party plugins can hook here to register additional
	 * access-control fields in the same side panel.
	 *
	 * @since BuddyBoss 3.0.0
	 */
	do_action( 'bb_messages_access_control_after_register_fields' );
}
