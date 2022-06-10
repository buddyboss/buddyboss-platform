<?php
/**
 * Code to hook into the WP Customizer
 *
 * @since BuddyPress 3.0.0
 * @version 3.1.0
 */

/**
 * Add a specific panel for the BP Nouveau Template Pack.
 *
 * @since BuddyPress 3.0.0
 *
 * @param WP_Customize_Manager $wp_customize WordPress customizer.
 */
function bp_nouveau_customize_register( WP_Customize_Manager $wp_customize ) {
	if ( ! bp_is_root_blog() ) {
		return;
	}

	require_once( trailingslashit( bp_nouveau()->includes_dir ) . 'customizer-controls.php' );
	require_once( trailingslashit( bp_nouveau()->includes_dir ) . 'profile-header-customizer-controls.php' );
	$wp_customize->register_control_type( 'BP_Nouveau_Nav_Customize_Control' );
	$wp_customize->register_control_type( 'BP_Nouveau_Profile_Header_Customize_Control' );
	$bp_nouveau_options = bp_nouveau_get_appearance_settings();
	//@todo is the BuddyBoss Platform really translatable?
	$wp_customize->add_panel( 'bp_nouveau_panel', array(
		'description' => __( 'Customize the appearance of the BuddyBoss Platform.', 'buddyboss' ),
		'title'       => __( 'BuddyBoss Platform', 'buddyboss' ),
		'priority'    => 200,
	) );

	/**
	 * Filters the BuddyPress Nouveau customizer sections and their arguments.
	 *
	 * @since BuddyPress 3.0.0
	 *
	 * @param array $value Array of Customizer sections.
	 */
	$sections = apply_filters( 'bp_nouveau_customizer_sections', array(
		'bp_nouveau_user_primary_nav' => array(
			'title'       => __( 'Profile Navigation', 'buddyboss' ),
			'panel'       => 'bp_nouveau_panel',
			'priority'    => 50,
			'description' => __( 'Customize the navigation menu for member profiles. In the preview window, navigate to a user to preview your changes.', 'buddyboss' ),
		),
		'bp_nouveau_user_profile_header' => array(
			'title'       => __( 'Profile Action Buttons', 'buddyboss' ),
			'panel'       => 'bp_nouveau_panel',
			'priority'    => 50,
			'description' => __( 'Customize the order of the action buttons in profile headers, visible when viewing other member\'s profiles.', 'buddyboss' ),
		),
		'bp_nouveau_mail' => array(
			'title'       => __( 'BuddyBoss Emails', 'buddyboss' ),
			'panel'       => 'bp_nouveau_panel',
			'priority'    => 80,
			'description' => __( 'Customize the appearance of emails sent by BuddyBoss.', 'buddyboss' ),
		),
	) );

	// Add the sections to the customizer
	foreach ( $sections as $id_section => $section_args ) {
		$wp_customize->add_section( $id_section, $section_args );
	}

	/**
	 * Filters the BuddyPress Nouveau customizer settings and their arguments.
	 *
	 * @since BuddyPress 3.0.0
	 *
	 * @param array $value Array of Customizer settings.
	 */
	$settings = apply_filters( 'bp_nouveau_customizer_settings', array(
		'bp_nouveau_appearance[user_nav_display]' => array(
			'index'             => 'user_nav_display',
			'capability'        => 'bp_moderate',
			'sanitize_callback' => 'absint',
			'transport'         => 'refresh',
			'type'              => 'option',
		),
		'bp_nouveau_appearance[user_default_tab]' => array(
			'index'             => 'user_default_tab',
			'capability'        => 'bp_moderate',
			'transport'         => 'refresh',
			'type'              => 'option',
		),
		'bp_nouveau_appearance[user_nav_order]' => array(
			'index'             => 'user_nav_order',
			'capability'        => 'bp_moderate',
			'sanitize_callback' => 'bp_nouveau_sanitize_nav_order',
			'transport'         => 'refresh',
			'type'              => 'option',
		),
		'bp_nouveau_appearance[user_profile_actions_order]' => array(
			'index'             => 'user_profile_actions_order',
			'capability'        => 'bp_moderate',
			'sanitize_callback' => 'bp_nouveau_sanitize_nav_order',
			'transport'         => 'refresh',
			'type'              => 'option',
		),
		'bp_nouveau_appearance[user_nav_hide]' => array(
			'index'             => 'user_nav_hide',
			'capability'        => 'bp_moderate',
			'sanitize_callback' => 'bp_nouveau_sanitize_nav_hide',
			'transport'         => 'refresh',
			'type'              => 'option',
		),
		'bp_nouveau_appearance[user_profile_actions_display]' => array(
			'index'             => 'user_profile_actions_display',
			'capability'        => 'bp_moderate',
			'sanitize_callback' => 'bp_nouveau_sanitize_nav_order',
			'transport'         => 'refresh',
			'type'              => 'option',
		),
		'bp_nouveau_appearance[activity_dir_layout]' => array(
			'index'             => 'activity_dir_layout',
			'capability'        => 'bp_moderate',
			'sanitize_callback' => 'absint',
			'transport'         => 'refresh',
			'type'              => 'option',
		),
		'bp_nouveau_appearance[activity_dir_tabs]' => array(
			'index'             => 'activity_dir_tabs',
			'capability'        => 'bp_moderate',
			'sanitize_callback' => 'absint',
			'transport'         => 'refresh',
			'type'              => 'option',
		),
		'bp_nouveau_appearance[members_dir_layout]' => array(
			'index'             => 'members_dir_layout',
			'capability'        => 'bp_moderate',
			'sanitize_callback' => 'absint',
			'transport'         => 'refresh',
			'type'              => 'option',
		),
		'bp_nouveau_appearance[members_dir_tabs]' => array(
			'index'             => 'members_dir_tabs',
			'capability'        => 'bp_moderate',
			'sanitize_callback' => 'absint',
			'transport'         => 'refresh',
			'type'              => 'option',
		),
		'bp_nouveau_appearance[groups_dir_layout]' => array(
			'index'             => 'groups_dir_layout',
			'capability'        => 'bp_moderate',
			'sanitize_callback' => 'absint',
			'transport'         => 'refresh',
			'type'              => 'option',
		),
		'bp_nouveau_appearance[sites_dir_layout]' => array(
			'index'             => 'sites_dir_layout',
			'capability'        => 'bp_moderate',
			'sanitize_callback' => 'absint',
			'transport'         => 'refresh',
			'type'              => 'option',
		),
		'bp_nouveau_appearance[sites_dir_tabs]' => array(
			'index'             => 'sites_dir_tabs',
			'capability'        => 'bp_moderate',
			'sanitize_callback' => 'absint',
			'transport'         => 'refresh',
			'type'              => 'option',
		),
		'bp_nouveau_appearance[bp_emails]' => array(
			'index'             => 'bp_emails',
			'capability'        => 'bp_moderate',
			'sanitize_callback' => 'absint',
			'transport'         => 'refresh',
			'type'              => 'option',
		),
	) );

	// Add the settings
	foreach ( $settings as $id_setting => $setting_args ) {
		$args = array();

		if ( empty( $setting_args['index'] ) || ! isset( $bp_nouveau_options[ $setting_args['index'] ] ) ) {
			continue;
		}

		$args = array_merge( $setting_args, array( 'default' => $bp_nouveau_options[ $setting_args['index'] ] ) );

		$wp_customize->add_setting( $id_setting, $args );
	}

	// Default options for the users default tab.

	$options = array();
	if ( bp_is_active( 'xprofile' ) ) {
		$options['profile'] = __( 'Profile', 'buddyboss' );
	}
	if ( bp_is_active( 'activity' ) ) {
		$options['activity'] = __( 'Timeline', 'buddyboss' );
	}
	if ( bp_is_active( 'friends' ) ) {
		$options['friends'] = __( 'Connections', 'buddyboss' );
	}
	if ( bp_is_active( 'groups' ) ) {
		$options['groups'] = __( 'Groups', 'buddyboss' );
	}
	if ( bp_is_active( 'forums' ) ) {
		$options['forums'] = __( 'Forums', 'buddyboss' );
	}
	if ( bp_is_active( 'media' ) ) {
		$options['media'] = __( 'Photos', 'buddyboss' );
	}
	if ( bp_is_active( 'media' ) && bp_is_profile_document_support_enabled() ) {
		$options['document'] = __( 'Documents', 'buddyboss' );
	}
	if ( bp_is_active( 'media' ) && bp_is_profile_video_support_enabled() ) {
		$options['video'] = __( 'Videos', 'buddyboss' );
	}

	$controls = array(
		'user_nav_display' => array(
			'label'    => __( 'Display the profile navigation vertically.', 'buddyboss' ),
			'section'  => 'bp_nouveau_user_primary_nav',
			'settings' => 'bp_nouveau_appearance[user_nav_display]',
			'type'     => 'checkbox',
		),
		'user_default_tab' => array(
			'label'       => __( 'Profile navigation order', 'buddyboss' ),
			'description' => __( 'Set the default navigation tab when viewing a member profile. The dropdown only shows tabs that are available to all members.', 'buddyboss' ),
			'section'     => 'bp_nouveau_user_primary_nav',
			'settings'    => 'bp_nouveau_appearance[user_default_tab]',
			'type'        => 'select',
			'choices'     => apply_filters( 'user_default_tab_options_list', $options ),
		),
		'user_nav_order'   => array(
			'class'    => 'BP_Nouveau_Nav_Customize_Control',
			'label'    => __( 'Reorder the primary navigation for a member.', 'buddyboss' ),
			'section'  => 'bp_nouveau_user_primary_nav',
			'settings' => 'bp_nouveau_appearance[user_nav_order]',
			'type'     => 'user',
		),
		'user_nav_hide'   => array(
			'class'    => 'BP_Nouveau_Nav_Customize_Control',
			'label'    => __( 'Hide the primary navigation for a member.', 'buddyboss' ),
			'section'  => 'bp_nouveau_user_primary_nav',
			'settings' => 'bp_nouveau_appearance[user_nav_hide]',
			'type'     => 'user',
		),

		'user_profile_actions_display'   => array(
			'class'    => 'BP_Nouveau_Profile_Header_Customize_Control',
			'label'    => __( 'Customize the order of the action buttons in profile headers, visible when viewing other member\'s profiles.', 'buddyboss' ),
			'section'  => 'bp_nouveau_user_profile_header',
			'settings' => 'bp_nouveau_appearance[user_profile_actions_display]',
			'type'     => 'header_button',
		),

		'mail_layout'      => array(
			'section'  => 'bp_nouveau_mail',
			'settings' => 'bp_nouveau_appearance[bp_emails]',
		),
	);

	/**
	 * Filters the BuddyPress Nouveau customizer controls and their arguments.
	 *
	 * @since BuddyPress 3.0.0
	 *
	 * @param array $value Array of Customizer controls.
	 */
	$controls = apply_filters( 'bp_nouveau_customizer_controls', $controls );


	// Add the controls to the customizer's section.
	foreach ( $controls as $id_control => $control_args ) {
		if ( empty( $control_args['class'] ) ) {
			$wp_customize->add_control( $id_control, $control_args );
		} else {
			$wp_customize->add_control( new $control_args['class']( $wp_customize, $id_control, $control_args ) );
		}
	}
}
add_action( 'bp_customize_register', 'bp_nouveau_customize_register', 10, 1 );

/**
 * Enqueue needed JS for our customizer Settings & Controls
 *
 * @since BuddyPress 3.0.0
 */
function bp_nouveau_customizer_enqueue_scripts() {
	$min = bp_core_get_minified_asset_suffix();

	wp_enqueue_script(
		'bp-nouveau-customizer',
		trailingslashit( bp_get_theme_compat_url() ) . "js/customizer{$min}.js",
		array( 'jquery', 'jquery-ui-sortable', 'customize-controls', 'iris', 'underscore', 'wp-util' ),
		bp_nouveau()->version,
		true
	);

	wp_localize_script( 'bp-nouveau-customizer', 'BP_Customizer', [
		'emailCustomizerUrl'    => bp_email_get_redirect_to_customizer_url(),
		'platformCustomizerUrl' => admin_url( 'customize.php?autofocus[panel]=bp_nouveau_panel' )
	] );

	/**
	 * Fires after Nouveau enqueues its required javascript.
	 *
	 * @since BuddyPress 3.0.0
	 */
	do_action( 'bp_nouveau_customizer_enqueue_scripts' );
}
add_action( 'customize_controls_enqueue_scripts', 'bp_nouveau_customizer_enqueue_scripts' );


/**
 * Return profile header buttons
 *
 * @since BuddyBoss 1.5.1
 *
 * @return mixed|void
 */

function bp_nouveau_customizer_user_profile_actions() {

	$buttons = array();

	if ( bp_is_active( 'friends' ) ) {
		$buttons['member_friendship'] = __( 'Connect', 'buddyboss' );
	}

	if ( bp_is_active( 'activity' ) && bp_is_activity_follow_active() ) { // add follow button
		$buttons['member_follow'] = __( 'Follow', 'buddyboss' );
	}

	$bp_force_friendship_to_message = bp_force_friendship_to_message();

	if ( bp_is_active( 'messages' )
	     && ( ! $bp_force_friendship_to_message
	          || ( $bp_force_friendship_to_message && bp_is_active( 'friends' ) ) )
	) {
		$buttons['private_message'] = __( 'Message', 'buddyboss' );
	}

	//Member switch button
	$buttons['member_switch'] = __( 'View As', 'buddyboss' );

	$buttons = apply_filters( 'bp_nouveau_customizer_user_profile_actions', $buttons );

	return $buttons;
}
