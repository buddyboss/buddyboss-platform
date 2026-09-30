<?php
/**
 * BuddyBoss Admin Settings - Registration Panel.
 *
 * Registers sections and fields for the Registration side panel.
 *
 * @package BuddyBoss\Core\Administration
 * @since BuddyBoss 3.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Register Registration panel sections and fields.
 *
 * @since BuddyBoss 3.0.0
 *
 * @return void
 */
function bb_registration_register_panel_fields() {

	$feature_id = 'registration';
	$panel_id   = 'registration';

	// =========================================================================
	// SECTION 1: Registration — registration_general
	// =========================================================================

	bb_register_feature_section(
		$feature_id,
		$panel_id,
		'registration_general',
		array(
			'title'       => __( 'Registration', 'buddyboss' ),
			'description' => '',
			'order'       => 10,
			'help_url'    => '636020',
		)
	);

	// Field 1: Enable Registration.
	bb_register_feature_field(
		$feature_id,
		$panel_id,
		'registration_general',
		array(
			'name'              => 'bp-enable-site-registration',
			'label'             => __( 'Enable Registration', 'buddyboss' ),
			'description'       => __( 'Allow non-members to register new accounts.', 'buddyboss' ),
			'type'              => 'toggle',
			'default'           => 0,
			'sanitize_callback' => 'absint',
			'refresh_panels'    => true,
			'order'             => 10,
		)
	);

	// Disable dependent fields when registration is OFF, unless Invites component is active
	// (invited users still need the registration form settings).
	// $reg_disable_conditional — used for fields with no other conditional (Registration Form radio, restrictions).
	// $reg_fields_disabled — static flag for fields that already have a conditional (Legal, Email/Password confirm).
	// refresh_panels on the toggle re-evaluates these on each change.
	$reg_disable_conditional = array();
	$reg_fields_disabled     = false;
	if ( ! bp_is_active( 'invites' ) && ! bp_enable_site_registration() ) {
		$reg_fields_disabled = true;
	}
	if ( ! bp_is_active( 'invites' ) ) {
		$reg_disable_conditional = array(
			'field'  => 'bp-enable-site-registration',
			'value'  => true,
			'action' => 'disable',
		);
	}

	// Field 3: Registration Form (radio: BuddyBoss Registration / Custom URL).
	$reg_form_args = array(
		'name'              => 'allow-custom-registration',
		'label'             => __( 'Registration Form', 'buddyboss' ),
		'description'       => sprintf(
			/* translators: %s: URL to BuddyBoss Settings 2.0 → Appearance → Pages panel */
			__( 'Use the default BuddyBoss registration form. Make sure to configure the <a href="%s">registration pages</a>.', 'buddyboss' ),
			esc_url( bb_get_feature_settings_url( 'appearance', 'pages' ) )
		),
		'type'              => 'radio',
		'default'           => 0,
		'sanitize_callback' => 'bb_registration_sanitize_form_type',
		'options'           => array(
			array(
				'value' => '0',
				'label' => __( 'BuddyBoss Registration', 'buddyboss' ),
			),
			array(
				'value' => '1',
				'label' => __( 'Custom URL', 'buddyboss' ),
			),
		),
		'group'             => 'registration_form',
		'order'             => 30,
	);
	if ( ! empty( $reg_disable_conditional ) ) {
		$reg_form_args['conditional'] = $reg_disable_conditional;
	}
	bb_register_feature_field( $feature_id, $panel_id, 'registration_general', $reg_form_args );

	// Field 3b: Custom URL (conditional: when Custom URL is truthy/selected).
	bb_register_feature_field(
		$feature_id,
		$panel_id,
		'registration_general',
		array(
			'name'              => 'register-page-url',
			'label'             => '',
			'type'              => 'text',
			'default'           => '',
			'sanitize_callback' => 'esc_url_raw',
			'placeholder'       => __( 'Enter custom URL', 'buddyboss' ),
			'conditional'       => array(
				'field'  => 'allow-custom-registration',
				'value'  => true,
				'action' => 'show',
			),
			'disabled'          => $reg_fields_disabled,
			'group'             => 'registration_form',
			'order'             => 35,
		)
	);

	// Field 4: Legal Agreement.
	// Conditional: show when allow-custom-registration is falsy (BuddyBoss Registration).
	bb_register_feature_field(
		$feature_id,
		$panel_id,
		'registration_general',
		array(
			'name'              => 'register-legal-agreement',
			'label'             => '',
			'description'       => __( 'Add Legal Agreement checkbox to register form', 'buddyboss' ),
			'help_text'         => __( 'Require non-members to explicitly agree to your Terms of Service and Privacy Policy before registering.', 'buddyboss' ),
			'type'              => 'toggle',
			'default'           => 0,
			'sanitize_callback' => 'absint',
			'conditional'       => array(
				'field'  => 'allow-custom-registration',
				'value'  => false,
				'action' => 'show',
			),
			'disabled'          => $reg_fields_disabled,
			'group'             => 'registration_form',
			'order'             => 40,
		)
	);

	// Field 5: Confirm Email.
	bb_register_feature_field(
		$feature_id,
		$panel_id,
		'registration_general',
		array(
			'name'              => 'register-confirm-email',
			'label'             => '',
			'description'       => __( 'Add Email confirmation to register form.', 'buddyboss' ),
			'type'              => 'toggle',
			'default'           => 0,
			'sanitize_callback' => 'absint',
			'conditional'       => array(
				'field'  => 'allow-custom-registration',
				'value'  => false,
				'action' => 'show',
			),
			'disabled'          => $reg_fields_disabled,
			'group'             => 'registration_form',
			'order'             => 50,
		)
	);

	// Field 6: Confirm Password.
	bb_register_feature_field(
		$feature_id,
		$panel_id,
		'registration_general',
		array(
			'name'              => 'register-confirm-password',
			'label'             => '',
			'description'       => __( 'Add Password confirmation to register form.', 'buddyboss' ),
			'type'              => 'toggle',
			'default'           => 0,
			'sanitize_callback' => 'absint',
			'conditional'       => array(
				'field'  => 'allow-custom-registration',
				'value'  => false,
				'action' => 'show',
			),
			'disabled'          => $reg_fields_disabled,
			'group'             => 'registration_form',
			'order'             => 60,
		)
	);

	// =========================================================================
	// SECTION 2: Registration Restrictions — registration_restrictions
	// Disabled when registration OFF (whole section greyed) or Custom URL mode.
	// Section-level disable applies opacity + pointer-events CSS for clear visual feedback.
	// =========================================================================

	// When reg is OFF: disable section via reg toggle check (full section greyed out).
	// When reg is ON: disable section via Custom URL check (restrictions only for BB Registration).
	$restrictions_section_conditional = array(
		'field'  => 'allow-custom-registration',
		'value'  => false,
		'action' => 'disable',
	);
	if ( $reg_fields_disabled ) {
		$restrictions_section_conditional = array(
			'field'  => 'bp-enable-site-registration',
			'value'  => true,
			'action' => 'disable',
		);
	}

	bb_register_feature_section(
		$feature_id,
		$panel_id,
		'registration_restrictions',
		array(
			'title'       => __( 'Registration Restrictions', 'buddyboss' ),
			'description' => __( 'Domain restrictions can be configured to limit new user registrations to specific domains or extensions. This setting is only available when using the BuddyBoss Registration Form.', 'buddyboss' ),
			'order'       => 20,
			'conditional' => $restrictions_section_conditional,
			'help_url'    => '636023',
		)
	);

	// Field 7: Domain Restrictions (custom repeater).
	bb_register_feature_field(
		$feature_id,
		$panel_id,
		'registration_restrictions',
		array(
			'name'              => 'bb-domain-restrictions',
			'label'             => __( 'Domain Restrictions', 'buddyboss' ),
			'description'       => __( 'Add domain(s) to restrict new users from being able to register, you can use a wildcard (*) symbol to apply restrictions to an entire extension. When multiple restrictions are in place, a domain will always take priority over an extension.', 'buddyboss' ),
			'type'              => 'domain_restrictions',
			'default'           => array(),
			'sanitize_callback' => 'bb_registration_sanitize_domain_restrictions',
			'order'             => 10,
		)
	);

	// Field 8: Email Restrictions (custom repeater).
	bb_register_feature_field(
		$feature_id,
		$panel_id,
		'registration_restrictions',
		array(
			'name'              => 'bb-email-restrictions',
			'label'             => __( 'Email Restrictions', 'buddyboss' ),
			'description'       => __( 'Enter the email addresses you want to allow for user registrations. Add one address per field.', 'buddyboss' ),
			'type'              => 'email_restrictions',
			'default'           => array(),
			'sanitize_callback' => 'bb_registration_sanitize_email_restrictions',
			'order'             => 20,
		)
	);

	/**
	 * Fires after registration restrictions section fields are registered.
	 *
	 * @since BuddyBoss 3.0.0
	 */
	do_action( 'bb_registration_after_restrictions_settings_fields' );

	// Field 9: Enable Social Login (Pro placeholder).
	// Platform registers with a generic name. Pro enriches via bb_admin_settings_format_field_data
	// filter to map to the actual option key (bb-enable-sso) and unlock the toggle.
	bb_register_feature_field(
		$feature_id,
		$panel_id,
		'registration_general',
		array(
			'name'              => 'bb-social-login',
			'label'             => __( 'Enable Social Login', 'buddyboss' ),
			'description'       => __( 'Allow users to sign in with social login.', 'buddyboss' ),
			'type'              => 'toggle',
			'default'           => 0,
			'sanitize_callback' => 'absint',
			'pro_only'          => true,
			'group'             => 'social_login',
			'order'             => 70,
		)
	);

	// Field 10: SSO Provider Cards (Pro placeholder).
	// Always visible as a preview — shows greyed-out provider cards when Pro is not active.
	// Pro enriches via bb_admin_settings_format_field_data to inject real provider data.
	bb_register_feature_field(
		$feature_id,
		$panel_id,
		'registration_general',
		array(
			'name'              => '_bb-sso-providers',
			'label'             => '',
			'type'              => 'sso_providers',
			'default'           => array(),
			'sanitize_callback' => '__return_empty_array',
			'pro_only'          => true,
			'group'             => 'social_login',
			'conditional'       => array(
				'field'  => 'bb-social-login',
				'value'  => true,
				'action' => 'disable',
			),
			'providers'         => array(
				array(
					'id'    => 'google',
					'label' => __( 'Google', 'buddyboss' ),
					'icon'  => '',
				),
				array(
					'id'    => 'facebook',
					'label' => __( 'Facebook', 'buddyboss' ),
					'icon'  => '',
				),
				array(
					'id'    => 'twitter',
					'label' => __( 'X', 'buddyboss' ),
					'icon'  => '',
				),
				array(
					'id'    => 'linkedin',
					'label' => __( 'Linkedin', 'buddyboss' ),
					'icon'  => '',
				),
				array(
					'id'    => 'apple',
					'label' => __( 'Apple', 'buddyboss' ),
					'icon'  => '',
				),
			),
			'order'             => 75,
		)
	);

	// Field 11: SSO App Notice (hidden by default, Pro shows when bbapp() exists).
	bb_register_feature_field(
		$feature_id,
		$panel_id,
		'registration_general',
		array(
			'name'              => '_bb-sso-app-notice',
			'label'             => '',
			'type'              => 'notice',
			'notice_type'       => 'info',
			'description'       => __( 'Any changes will require new iOS and Android app builds.', 'buddyboss' ),
			'sanitize_callback' => '__return_empty_string',
			'group'             => 'social_login',
			'hidden'            => true,
			'order'             => 76,
		)
	);

	/**
	 * Fires after registration general section fields are registered.
	 * Pro hooks here to enrich the SSO field and add provider cards.
	 *
	 * @since BuddyBoss 3.0.0
	 */
	do_action( 'bb_registration_after_general_settings_fields' );
}
