<?php
/**
 * BuddyBoss Admin Settings - Email Invites Feature Registration.
 *
 * Registers the Email Invites feature in the Feature Registry and loads
 * all Email Invites settings (side panels, sections, fields).
 *
 * @package BuddyBoss\Core\Administration
 * @since BuddyBoss 3.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Register Email Invites feature and settings in Feature Registry.
 *
 * Registers the feature, side panels, and all field registrations.
 *
 * @since BuddyBoss 3.0.0
 */
function bb_admin_settings_register_invites_feature() {

	// =========================================================================
	// REGISTER FEATURE
	// =========================================================================

	bb_register_feature(
		'invites',
		array(
			'label'              => __( 'Email Invites', 'buddyboss-platform' ),
			'description'        => __( 'Allow members to send email invitations to help encourage others to register and join your community.', 'buddyboss-platform' ),
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
			'settings_route'     => '/settings/invites',
			'order'              => 140,
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
		'invites',
		'email_invite_settings',
		array(
			'title'      => __( 'Email Invite Settings', 'buddyboss-platform' ),
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

	// Side Panel 2: Email Invites (list screen).
	bb_register_side_panel(
		'invites',
		'invites_list',
		array(
			'title' => __( 'Email Invites', 'buddyboss-platform' ),
			'icon'  => array(
				'type'  => 'font',
				'class' => 'bb-icons-rl bb-icons-rl-envelope-simple-open',
			),
			'order' => 20,
		)
	);

	// =========================================================================
	// PANEL FIELDS — EMAIL INVITE SETTINGS
	// =========================================================================

	// -------------------------------------------------------------------------
	// SECTION: EMAIL INVITE SETTINGS
	// -------------------------------------------------------------------------

	bb_register_feature_section(
		'invites',
		'email_invite_settings',
		'email_invite_general',
		array(
			'title'    => __( 'Email Invite Settings', 'buddyboss-platform' ),
			'order'    => 10,
			'help_url' => '636156',
		)
	);

	// FIELD: Customize Email Subject (Toggle).
	bb_register_feature_field(
		'invites',
		'email_invite_settings',
		'email_invite_general',
		array(
			'name'              => 'bp-disable-invite-member-email-subject',
			'label'             => __( 'Email Subject', 'buddyboss-platform' ),
			'type'              => 'toggle',
			'description'       => __( 'Allow members to customize the email subject', 'buddyboss-platform' ),
			'default'           => absint( bp_get_option( 'bp-disable-invite-member-email-subject', 0 ) ),
			'sanitize_callback' => 'absint',
			'order'             => 10,
		)
	);

	// FIELD: Customize Email Content (Toggle).
	bb_register_feature_field(
		'invites',
		'email_invite_settings',
		'email_invite_general',
		array(
			'name'              => 'bp-disable-invite-member-email-content',
			'label'             => __( 'Email Content', 'buddyboss-platform' ),
			'type'              => 'toggle',
			'description'       => __( 'Allow members to customize the email body content', 'buddyboss-platform' ),
			'default'           => absint( bp_get_option( 'bp-disable-invite-member-email-content', 1 ) ),
			'sanitize_callback' => 'absint',
			'order'             => 20,
		)
	);

	// FIELD: Select Profile Type (Toggle).
	// Only show when profile types feature is enabled.
	if ( true === bp_member_type_enable_disable() ) {
		bb_register_feature_field(
			'invites',
			'email_invite_settings',
			'email_invite_general',
			array(
				'name'              => 'bp-disable-invite-member-type',
				'label'             => __( 'Set Profile Type', 'buddyboss-platform' ),
				'type'              => 'toggle',
				'description'       => __( 'Allow members to select profile type of invitee', 'buddyboss-platform' ),
				'help_text'         => sprintf(
					/* translators: %s: profile type link. */
					__( 'Customize this setting while editing any of your %s.', 'buddyboss-platform' ),
					'<a href="' . esc_url( bb_get_feature_settings_url( 'members', 'profile_types' ) ) . '">' . __( 'profile type', 'buddyboss-platform' ) . '</a>'
				),
				'default'           => absint( bp_get_option( 'bp-disable-invite-member-type', 0 ) ),
				'sanitize_callback' => 'absint',
				'order'             => 30,
			)
		);

		// Profile type child fields registered lazily via bb_invites_register_profile_type_fields()
		// in settings/invites/callbacks.php — they fire on bb_admin_settings_before_get_feature.
	}

	// =========================================================================
	// LEGACY HOOK DEPRECATION
	// =========================================================================

	/**
	 * Fires to register Email Invites tab settings fields and section.
	 *
	 * @since BuddyBoss 1.2.6
	 * @deprecated BuddyBoss 3.0.0 Use {@see 'bb_register_feature_field'} instead.
	 *
	 * @param object $deprecated No-op stub. Previously BP_Admin_Setting_Email_Invites instance.
	 */
	do_action_deprecated(
		'bp_admin_setting_invites_register_fields',
		array(
			// phpcs:ignore PHPCompatibility.Classes.NewAnonymousClasses.Found -- PHP 7.4+ required.
			new class() {
				/**
				 * No-op: legacy add_section stub.
				 *
				 * @param mixed ...$args Arguments.
				 */
				public function add_section( ...$args ) {} // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.Found
				/**
				 * No-op: legacy add_field stub.
				 *
				 * @param mixed ...$args Arguments.
				 */
				public function add_field( ...$args ) {} // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.Found
			},
		),
		'BuddyBoss 3.0.0',
		'bb_register_feature_field'
	);

	/**
	 * Fires after all Email Invites settings panels are registered.
	 * Allows third-party extensions to add more panels or fields.
	 *
	 * @since BuddyBoss 3.0.0
	 */
	do_action( 'bb_invites_after_register_settings_fields' );
}

add_action( 'bb_register_features', 'bb_admin_settings_register_invites_feature', 20 );
