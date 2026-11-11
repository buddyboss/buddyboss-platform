<?php
/**
 * BuddyBoss Admin Settings - On-screen Notifications Panel.
 *
 * Registers sections and fields for the On-screen Notifications side panel.
 *
 * @package BuddyBoss\Core\Administration
 * @since BuddyBoss 3.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Register On-screen Notifications panel sections and fields.
 *
 * @since BuddyBoss 3.0.0
 *
 * @return void
 */
function bb_notifications_register_on_screen_panel_fields() {

	// -------------------------------------------------------------------------
	// SECTION: On-screen Notifications
	// -------------------------------------------------------------------------
	bb_register_feature_section(
		'notifications',
		'on_screen_notifications',
		'on_screen_notifications',
		array(
			'title'       => __( 'On-screen Notifications', 'buddyboss' ),
			'description' => '',
			'order'       => 10,
			'help_url'    => '636147',
		)
	);

	// FIELD: Enable on-screen notifications.
	bb_register_feature_field(
		'notifications',
		'on_screen_notifications',
		'on_screen_notifications',
		array(
			'name'              => '_bp_on_screen_notifications_enable',
			'label'             => __( 'On-screen notifications', 'buddyboss' ),
			'type'              => 'toggle',
			'description'       => __( 'Enable on-screen notifications', 'buddyboss' ),
			'help_text'         => __( 'Show members new notifications received while on a page on-screen.', 'buddyboss' ),
			'default'           => bp_get_option( '_bp_on_screen_notifications_enable', 0 ),
			'sanitize_callback' => 'absint',
			'order'             => 10,
		)
	);

	// FIELD: Position on Screen.
	bb_register_feature_field(
		'notifications',
		'on_screen_notifications',
		'on_screen_notifications',
		array(
			'name'              => '_bp_on_screen_notifications_position',
			'label'             => __( 'Position on Screen', 'buddyboss' ),
			'type'              => 'image_radio',
			'default'           => bp_get_option( '_bp_on_screen_notifications_position', 'right' ),
			'sanitize_callback' => 'bb_notifications_sanitize_position',
			'options'           => array(
				array(
					'label' => __( 'Bottom Left', 'buddyboss' ),
					'value' => 'left',
					'image' => 'notification-position-left',
				),
				array(
					'label' => __( 'Bottom Right', 'buddyboss' ),
					'value' => 'right',
					'image' => 'notification-position-right',
				),
			),
			'order'             => 20,
			'conditional'       => array(
				'field'  => '_bp_on_screen_notifications_enable',
				'value'  => true,
				'action' => 'disable',
			),
		)
	);

	// FIELD: Mobile Support.
	bb_register_feature_field(
		'notifications',
		'on_screen_notifications',
		'on_screen_notifications',
		array(
			'name'              => '_bp_on_screen_notifications_mobile_support',
			'label'             => __( 'Mobile Support', 'buddyboss' ),
			'type'              => 'toggle',
			'description'       => __( 'Show on-screen notifications on small screens', 'buddyboss' ),
			'help_text'         => __( 'Enable this option to show on-screen notifications at the bottom of the screen smaller than 500px wide.', 'buddyboss' ),
			'default'           => bp_get_option( '_bp_on_screen_notifications_mobile_support', 0 ),
			'sanitize_callback' => 'absint',
			'order'             => 30,
			'conditional'       => array(
				'field'  => '_bp_on_screen_notifications_enable',
				'value'  => true,
				'action' => 'disable',
			),
		)
	);

	// FIELD: Automatically Hide.
	$visibility_options = array(
		array(
			'label' => __( 'Never Hide', 'buddyboss' ),
			'value' => 'never',
		),
		array(
			'label' => __( '5 Seconds', 'buddyboss' ),
			'value' => '5',
		),
		array(
			'label' => __( '10 Seconds', 'buddyboss' ),
			'value' => '10',
		),
		array(
			'label' => __( '30 Seconds', 'buddyboss' ),
			'value' => '30',
		),
		array(
			'label' => __( '1 Minute', 'buddyboss' ),
			'value' => '60',
		),
		array(
			'label' => __( '2 Minutes', 'buddyboss' ),
			'value' => '120',
		),
		array(
			'label' => __( '3 Minutes', 'buddyboss' ),
			'value' => '180',
		),
		array(
			'label' => __( '4 Minutes', 'buddyboss' ),
			'value' => '240',
		),
		array(
			'label' => __( '5 Minutes', 'buddyboss' ),
			'value' => '300',
		),
	);

	bb_register_feature_field(
		'notifications',
		'on_screen_notifications',
		'on_screen_notifications',
		array(
			'name'              => '_bp_on_screen_notifications_visibility',
			'label'             => __( 'Automatically Hide', 'buddyboss' ),
			'type'              => 'select',
			'default'           => bp_get_option( '_bp_on_screen_notifications_visibility', 'never' ),
			'sanitize_callback' => 'bb_notifications_sanitize_visibility',
			'options'           => $visibility_options,
			'order'             => 40,
			'conditional'       => array(
				'field'  => '_bp_on_screen_notifications_enable',
				'value'  => true,
				'action' => 'disable',
			),
		)
	);

	// FIELD: Show in Browser Tab.
	bb_register_feature_field(
		'notifications',
		'on_screen_notifications',
		'on_screen_notifications',
		array(
			'name'              => '_bp_on_screen_notifications_browser_tab',
			'label'             => __( 'Show in Browser Tab', 'buddyboss' ),
			'type'              => 'toggle',
			'description'       => __( 'Show new notifications in the title of the browser tab', 'buddyboss' ),
			'help_text'         => __( 'Update the page &lt;title&gt; tab when new notifications are received.', 'buddyboss' ),
			'default'           => bp_get_option( '_bp_on_screen_notifications_browser_tab', 0 ),
			'sanitize_callback' => 'absint',
			'order'             => 50,
			'conditional'       => array(
				'field'  => '_bp_on_screen_notifications_enable',
				'value'  => true,
				'action' => 'disable',
			),
		)
	);

	// Notice: Members can manage on-screen notification preferences.
	if ( ! function_exists( 'bb_enabled_legacy_email_preference' ) || ! bb_enabled_legacy_email_preference() ) {
		bb_register_feature_field(
			'notifications',
			'on_screen_notifications',
			'on_screen_notifications',
			array(
				'name'              => '_bp_on_screen_notifications_notice',
				'label'             => '',
				'type'              => 'notice',
				'description'       => __( 'Members can manage which on-screen notifications they receive in their notification preferences by enabling or disabling the "Web" options.', 'buddyboss' ),
				'notice_type'       => 'info',
				'sanitize_callback' => '__return_empty_string',
				'order'             => 60,
			)
		);
	}

	/**
	 * Fires after On-screen Notifications section fields are registered.
	 *
	 * @since BuddyBoss 3.0.0
	 */
	do_action( 'bb_notifications_on_screen_after_settings_fields' );
}
