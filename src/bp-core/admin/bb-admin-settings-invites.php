<?php
/**
 * BuddyBoss Admin Settings - Email Invites Feature Registration.
 *
 * Registers the Email Invites feature in the Feature Registry and loads
 * all Email Invites settings (side panels, sections, fields).
 *
 * @package BuddyBoss\Core\Administration
 * @since BuddyBoss [BBVERSION]
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Register Email Invites feature and settings in Feature Registry.
 *
 * Registers the feature, side panels, and all field registrations.
 *
 * @since BuddyBoss [BBVERSION]
 */
function bb_admin_settings_register_email_invites_feature() {

	// =========================================================================
	// REGISTER FEATURE
	// =========================================================================

	bb_register_feature(
		'email_invites',
		array(
			'label'              => __( 'Email Invites', 'buddyboss' ),
			'description'        => __( 'Allow your members to send email invitations to non-members.', 'buddyboss' ),
			'icon'               => array(
				'type'  => 'font',
				'class' => 'bb-icons-rl bb-icons-rl-paper-plane-tilt',
			),
			'license_tier'       => 'free',
			'category'           => 'community',
			'standalone'         => true,
			'is_active_callback' => function () {
				return bp_is_active( 'invites' );
			},
			'components'         => array( 'invites' ),
			'settings_route'     => '/settings/email_invites',
			'order'              => 60,
		)
	);

	// When email invites is disabled, only the feature card is needed (so admin can re-enable).
	// Side panels, sections, and fields depend on email invite functions that aren't loaded.
	if ( ! bp_is_active( 'invites' ) ) {
		return;
	}

	// Load settings sub-files only when email invites is active.
	require_once __DIR__ . '/settings/invites/callbacks.php';

	// =========================================================================
	// SIDE PANELS
	// =========================================================================

	// Side Panel 1: Email Invite Settings (default).
	bb_register_side_panel(
		'email_invites',
		'email_invite_settings',
		array(
			'title'      => __( 'Email Invite Settings', 'buddyboss' ),
			'icon'       => array(
				'type'  => 'font',
				'class' => 'bb-icons-rl bb-icons-rl-gear',
			),
			'help_url'   => bp_get_admin_url(
				add_query_arg(
					array(
						'page'    => 'bp-help',
						'article' => 125952,
					),
					'admin.php'
				)
			),
			'order'      => 10,
			'is_default' => true,
		)
	);

	// =========================================================================
	// PANEL FIELDS — EMAIL INVITE SETTINGS
	// =========================================================================

	// -------------------------------------------------------------------------
	// SECTION: EMAIL INVITE SETTINGS
	// -------------------------------------------------------------------------

	bb_register_feature_section(
		'email_invites',
		'email_invite_settings',
		'email_invite_general',
		array(
			'title' => __( 'Email invite Settings', 'buddyboss' ),
			'order' => 10,
		)
	);

	// FIELD: Customize Email Subject (Toggle).
	bb_register_feature_field(
		'email_invites',
		'email_invite_settings',
		'email_invite_general',
		array(
			'name'              => 'bp-disable-invite-member-email-subject',
			'label'             => __( 'Email Subject', 'buddyboss' ),
			'type'              => 'toggle',
			'description'       => __( 'Allow members to customize the email subject', 'buddyboss' ),
			'default'           => absint( bp_get_option( 'bp-disable-invite-member-email-subject', 0 ) ),
			'sanitize_callback' => 'absint',
			'order'             => 10,
		)
	);

	// FIELD: Customize Email Content (Toggle).
	bb_register_feature_field(
		'email_invites',
		'email_invite_settings',
		'email_invite_general',
		array(
			'name'              => 'bp-disable-invite-member-email-content',
			'label'             => __( 'Email Content', 'buddyboss' ),
			'type'              => 'toggle',
			'description'       => __( 'Allow members to customize the email body content', 'buddyboss' ),
			'default'           => absint( bp_get_option( 'bp-disable-invite-member-email-content', 1 ) ),
			'sanitize_callback' => 'absint',
			'order'             => 20,
		)
	);

	// FIELD: Select Profile Type (Toggle).
	// Only show when profile types feature is enabled.
	if ( true === bp_member_type_enable_disable() ) {
		bb_register_feature_field(
			'email_invites',
			'email_invite_settings',
			'email_invite_general',
			array(
				'name'              => 'bp-disable-invite-member-type',
				'label'             => __( 'Set Profile Type', 'buddyboss' ),
				'type'              => 'toggle',
				'description'       => __( 'Allow members to select profile type of invitee', 'buddyboss' ),
				'help_text'         => sprintf(
					/* translators: %s: profile type link. */
					__( 'Customize this setting while editing any of your %s.', 'buddyboss' ),
					'<a href="' . esc_url( bb_get_feature_settings_url( 'members', 'profile_types' ) ) . '">' . __( 'profile type', 'buddyboss' ) . '</a>'
				),
				'default'           => absint( bp_get_option( 'bp-disable-invite-member-type', 0 ) ),
				'sanitize_callback' => 'absint',
				'order'             => 30,
			)
		);

		// Profile type child fields registered lazily via bb_email_invites_register_profile_type_fields()
		// in settings/invites/callbacks.php — they fire on bb_admin_settings_before_get_feature.
	}

	// =========================================================================
	// LEGACY HOOK DEPRECATION
	// =========================================================================

	/**
	 * Fires to register Email Invites tab settings fields and section.
	 *
	 * @since BuddyBoss 1.2.6
	 * @deprecated BuddyBoss [BBVERSION] Use {@see 'bb_register_feature_field'} instead.
	 *
	 * @param null $deprecated Deprecated. Previously BP_Admin_Setting_Email_Invites instance.
	 */
	do_action_deprecated(
		'bp_admin_setting_invites_register_fields',
		array( null ),
		'BuddyBoss [BBVERSION]',
		'bb_register_feature_field'
	);

	/**
	 * Fires after all Email Invites settings panels are registered.
	 * Allows third-party extensions to add more panels or fields.
	 *
	 * @since BuddyBoss [BBVERSION]
	 */
	do_action( 'bb_email_invites_after_register_settings_fields' );
}

add_action( 'bb_register_features', 'bb_admin_settings_register_email_invites_feature', 20 );
