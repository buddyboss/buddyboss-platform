<?php
/**
 * BuddyBoss Admin Settings - Advanced General Panel.
 *
 * Registers fields for the General side panel of the Advanced feature.
 *
 * @package BuddyBoss\Core\Administration
 * @since BuddyBoss 3.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Register Advanced General panel fields.
 *
 * @since BuddyBoss 3.0.0
 *
 * @return void
 */
function bb_advanced_register_general_fields() {

	$feature_id = 'advanced';
	$panel_id   = 'general';

	// =========================================================================
	// SECTION 1: General
	// =========================================================================

	bb_register_feature_section(
		$feature_id,
		$panel_id,
		'advanced_general',
		array(
			'title'    => __( 'General', 'buddyboss' ),
			'order'    => 10,
			'help_url' => '636194',
		)
	);

	// Field 1: Page Requests — inline select with surrounding text (no toggle per Figma).
	// Figma: "Load [2 ▾] page requests on page load".
	bb_register_feature_field(
		$feature_id,
		$panel_id,
		'advanced_general',
		array(
			'name'                 => 'bb_ajax_request_page_load',
			'label'                => __( 'Page Requests', 'buddyboss' ),
			'type'                 => 'hidden',
			/* translators: %s: inline select for number of page requests. */
			'description'          => __( 'Load %s page requests on page load', 'buddyboss' ),
			'help_text'            => __( 'Select how many requests will be sent on page load. We recommend 1 request for high performing servers, and 2 for slower performing environments, or those who see conflicts with third party plugins.', 'buddyboss' ),
			'default'              => bb_get_ajax_request_page_load(),
			'sanitize_callback'    => 'absint',
			'description_controls' => array(
				array(
					'type'              => 'select',
					'name'              => 'bb_ajax_request_page_load',
					'default'           => bb_get_ajax_request_page_load(),
					'sanitize_callback' => 'absint',
					'options'           => array(
						array(
							'value' => '1',
							'label' => '1',
						),
						array(
							'value' => '2',
							'label' => '2',
						),
					),
				),
			),
			'order'                => 10,
		)
	);

	// Field 2: Link Previews (conditional on Activity component).
	if ( bp_is_active( 'activity' ) ) {
		bb_register_feature_field(
			$feature_id,
			$panel_id,
			'advanced_general',
			array(
				'name'              => '_bp_enable_activity_link_preview',
				'label'             => __( 'Link Previews', 'buddyboss' ),
				'description'       => __( 'When links are used in activity posts, display an image and excerpt from the site', 'buddyboss' ),
				'type'              => 'toggle',
				'default'           => bp_is_activity_link_preview_active( false ),
				'sanitize_callback' => 'absint',
				'order'             => 20,
			)
		);
	}

	// Field 3: Content Counts.
	bb_register_feature_field(
		$feature_id,
		$panel_id,
		'advanced_general',
		array(
			'name'              => 'bb-enable-content-counts',
			'label'             => __( 'Content Counts', 'buddyboss' ),
			'description'       => __( 'Enable content counts across your site', 'buddyboss' ),
			'help_text'         => __( 'Disabling content counts removes counts from pages like the Members Directory, Groups Directory, and Media pages (Photos & Videos). It also removes counts under profile tabs and can improve page load performance.', 'buddyboss' ),
			'type'              => 'toggle',
			'default'           => bb_enable_content_counts(),
			'sanitize_callback' => 'absint',
			'order'             => 30,
		)
	);

	// =========================================================================
	// SECTION 2: Page Loading
	// =========================================================================

	// The id stays 'advanced_activity' (shipped in 3.5.0) so fields that add-ons register into
	// this section keep showing; only the title changed when Members & Groups Loading joined it.
	bb_register_feature_section(
		$feature_id,
		$panel_id,
		'advanced_activity',
		array(
			'title'       => __( 'Page Loading', 'buddyboss' ),
			'description' => __( 'Use infinite scrolling to automatically load while scrolling down feeds and lists. Increasing the number of load items retrieved in each request may negatively impact page loading speeds.', 'buddyboss' ),
			'order'       => 20,
			'help_url'    => '636197',
		)
	);

	// Field 6: Feed Page Loading — two inline selects (conditional on Activity component).
	// Figma: "Load [10 ▾] activity posts at a time using [Infinite Scroll ▾]".
	if ( bp_is_active( 'activity' ) ) {
		$activity_per_page = apply_filters( 'bb_performance_activity_per_page', array() );
		$activity_per_page = bp_parse_args( $activity_per_page, array( 5, 10, 15, 20 ) );
		asort( $activity_per_page );

		$per_page_options = array();
		foreach ( $activity_per_page as $val ) {
			$per_page_options[] = array(
				'value' => (string) $val,
				'label' => (string) $val,
			);
		}

		$activity_autoload_options = apply_filters( 'bb_performance_activity_autoload', array() );
		$activity_autoload_options = bp_parse_args(
			$activity_autoload_options,
			array(
				'infinite'  => __( 'Infinite Scroll', 'buddyboss' ),
				'load_more' => __( 'Load More', 'buddyboss' ),
			)
		);

		$load_type_options = array();
		foreach ( $activity_autoload_options as $key => $label ) {
			$load_type_options[] = array(
				'value' => $key,
				'label' => $label,
			);
		}

		bb_register_feature_field(
			$feature_id,
			$panel_id,
			'advanced_activity',
			array(
				'name'                 => 'bb_load_activity_per_request',
				'label'                => __( 'Feed Page Loading', 'buddyboss' ),
				'type'                 => 'hidden',
				/* translators: 1: inline select for number of posts, 2: inline select for load type. */
				'description'          => __( 'Load %1$s activity posts at a time using %2$s', 'buddyboss' ),
				'default'              => bb_get_load_activity_per_request(),
				'sanitize_callback'    => 'absint',
				'description_controls' => array(
					array(
						'type'              => 'select',
						'name'              => 'bb_load_activity_per_request',
						'default'           => bb_get_load_activity_per_request(),
						'sanitize_callback' => 'absint',
						'options'           => $per_page_options,
					),
					array(
						'type'              => 'select',
						'name'              => 'bb_activity_load_type',
						'default'           => bp_get_option( 'bb_activity_load_type', 'infinite' ),
						'sanitize_callback' => 'bb_advanced_sanitize_activity_load_type',
						'options'           => $load_type_options,
					),
				),
				'order'                => 10,
			)
		);
	}

	// Field 7: Members & Groups Loading — inline select for the members and groups lists (directories,
	// profile Connections and Groups, group Members and Subgroups), like Feed Page Loading for activity.
	// Pagination is the default, infinite scroll is opt-in.

	/**
	 * Filters the options of the Members & Groups Loading setting.
	 *
	 * Options are merged over the defaults ('pagination' and 'infinite'), shown in the setting's
	 * select and accepted when the setting is saved. Only 'infinite' turns infinite scroll on;
	 * any other saved value loads the lists with pagination.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @param array $options Additional options as value => label. Default empty array.
	 */
	$directory_autoload_options = apply_filters( 'bb_performance_directory_autoload', array() );
	$directory_autoload_options = bp_parse_args(
		$directory_autoload_options,
		array(
			'pagination' => __( 'Pagination', 'buddyboss' ),
			'infinite'   => __( 'Infinite Scroll', 'buddyboss' ),
		)
	);

	$directory_load_type_options = array();
	foreach ( $directory_autoload_options as $key => $label ) {
		$directory_load_type_options[] = array(
			'value' => $key,
			'label' => $label,
		);
	}

	bb_register_feature_field(
		$feature_id,
		$panel_id,
		'advanced_activity',
		array(
			'name'                 => 'bb_directory_load_type',
			'label'                => __( 'Members & Groups Loading', 'buddyboss' ),
			'type'                 => 'hidden',
			/* translators: %s: inline select for the members and groups lists load type. */
			'description'          => __( 'Load members and groups lists using %s', 'buddyboss' ),
			'default'              => bb_get_directory_load_type(),
			'sanitize_callback'    => 'bb_advanced_sanitize_directory_load_type',
			'description_controls' => array(
				array(
					'type'              => 'select',
					'name'              => 'bb_directory_load_type',
					'default'           => bb_get_directory_load_type(),
					'sanitize_callback' => 'bb_advanced_sanitize_directory_load_type',
					'options'           => $directory_load_type_options,
				),
			),
			'order'                => 20,
		)
	);

	// =========================================================================
	// SECTION 3: Toolbar Settings
	// =========================================================================

	bb_register_feature_section(
		$feature_id,
		$panel_id,
		'advanced_toolbar',
		array(
			'title'    => __( 'Toolbar Settings', 'buddyboss' ),
			'order'    => 30,
			'help_url' => '636199',
		)
	);

	// Field 8: Toolbar for admins.
	bb_register_feature_field(
		$feature_id,
		$panel_id,
		'advanced_toolbar',
		array(
			'name'              => 'show-admin-adminbar',
			'label'             => __( 'Toolbar', 'buddyboss' ),
			'description'       => __( 'Show the Toolbar for logged-in admins', 'buddyboss' ),
			'type'              => 'toggle',
			'default'           => bp_show_admin_adminbar( true ),
			'sanitize_callback' => 'absint',
			'group'             => 'toolbar_settings',
			'order'             => 10,
		)
	);

	// Field 9: Toolbar for members.
	bb_register_feature_field(
		$feature_id,
		$panel_id,
		'advanced_toolbar',
		array(
			'name'              => 'show-login-adminbar',
			'label'             => '',
			'description'       => __( 'Show the Toolbar for logged-in members (non-admins)', 'buddyboss' ),
			'type'              => 'toggle',
			'default'           => bp_show_login_adminbar( true ),
			'sanitize_callback' => 'absint',
			'group'             => 'toolbar_settings',
			'order'             => 20,
		)
	);

	// Field 10: Toolbar for logged out.
	bb_register_feature_field(
		$feature_id,
		$panel_id,
		'advanced_toolbar',
		array(
			'name'              => 'hide-loggedout-adminbar',
			'label'             => '',
			'description'       => __( 'Show the Toolbar for logged out users', 'buddyboss' ),
			'type'              => 'toggle',
			'default'           => bp_hide_loggedout_adminbar( false ),
			'invert_value'      => true, // DB stores 1 = hide toolbar. Toggle shows "Show", so invert for display.
			'sanitize_callback' => 'absint',
			'group'             => 'toolbar_settings',
			'order'             => 30,
		)
	);
}
