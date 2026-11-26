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
function bboss_advanced_register_general_fields() {

	$feature_id = 'advanced';
	$panel_id   = 'general';

	// =========================================================================
	// SECTION 1: General
	// =========================================================================

	bboss_register_feature_section(
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
	bboss_register_feature_field(
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
			'default'              => bboss_get_ajax_request_page_load(),
			'sanitize_callback'    => 'absint',
			'description_controls' => array(
				array(
					'type'              => 'select',
					'name'              => 'bb_ajax_request_page_load',
					'default'           => bboss_get_ajax_request_page_load(),
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
	if ( bboss_is_active( 'activity' ) ) {
		bboss_register_feature_field(
			$feature_id,
			$panel_id,
			'advanced_general',
			array(
				'name'              => '_bp_enable_activity_link_preview',
				'label'             => __( 'Link Previews', 'buddyboss' ),
				'description'       => __( 'When links are used in activity posts, display an image and excerpt from the site', 'buddyboss' ),
				'type'              => 'toggle',
				'default'           => bboss_is_activity_link_preview_active( false ),
				'sanitize_callback' => 'absint',
				'order'             => 20,
			)
		);
	}

	// Field 3: Content Counts.
	bboss_register_feature_field(
		$feature_id,
		$panel_id,
		'advanced_general',
		array(
			'name'              => 'bb-enable-content-counts',
			'label'             => __( 'Content Counts', 'buddyboss' ),
			'description'       => __( 'Enable content counts across your site', 'buddyboss' ),
			'help_text'         => __( 'Disabling content counts removes counts from pages like the Members Directory, Groups Directory, and Media pages (Photos & Videos). It also removes counts under profile tabs and can improve page load performance.', 'buddyboss' ),
			'type'              => 'toggle',
			'default'           => bboss_enable_content_counts(),
			'sanitize_callback' => 'absint',
			'order'             => 30,
		)
	);

	// =========================================================================
	// SECTION 2: Page Loading
	// =========================================================================

	bboss_register_feature_section(
		$feature_id,
		$panel_id,
		'advanced_page_loading',
		array(
			'title'       => __( 'Page Loading', 'buddyboss' ),
			'description' => __( 'Use infinite scrolling to automatically load while scrolling down feeds and lists. Increasing the number of load items retrieved in each request may negatively impact page loading speeds.', 'buddyboss' ),
			'order'       => 20,
			'help_url'    => '636197',
		)
	);

	// Before BuddyBoss 3.8.0 this section was "advanced_activity"; fields added under that ID still land here.
	add_filter( 'bb_feature_section_aliases', 'bb_advanced_general_section_aliases', 10, 3 );

	// Field 6: Feed Page Loading — two inline selects (conditional on Activity component).
	// Figma: "Load [10 ▾] activity posts at a time using [Infinite Scroll ▾]".
	if ( bboss_is_active( 'activity' ) ) {
		$activity_per_page = apply_filters( 'bb_performance_activity_per_page', array() );
		$activity_per_page = bboss_parse_args( $activity_per_page, array( 5, 10, 15, 20 ) );
		asort( $activity_per_page );

		$per_page_options = array();
		foreach ( $activity_per_page as $val ) {
			$per_page_options[] = array(
				'value' => (string) $val,
				'label' => (string) $val,
			);
		}

		$activity_autoload_options = apply_filters( 'bb_performance_activity_autoload', array() );
		$activity_autoload_options = bboss_parse_args(
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

		bboss_register_feature_field(
			$feature_id,
			$panel_id,
			'advanced_page_loading',
			array(
				'name'                 => 'bb_load_activity_per_request',
				'label'                => __( 'Feed Page Loading', 'buddyboss' ),
				'type'                 => 'hidden',
				/* translators: 1: inline select for number of posts, 2: inline select for load type. */
				'description'          => __( 'Load %1$s activity posts at a time using %2$s', 'buddyboss' ),
				'default'              => bboss_get_load_activity_per_request(),
				'sanitize_callback'    => 'absint',
				'description_controls' => array(
					array(
						'type'              => 'select',
						'name'              => 'bb_load_activity_per_request',
						'default'           => bboss_get_load_activity_per_request(),
						'sanitize_callback' => 'absint',
						'options'           => $per_page_options,
					),
					array(
						'type'              => 'select',
						'name'              => 'bb_activity_load_type',
						'default'           => bboss_get_option( 'bb_activity_load_type', 'infinite' ),
						'sanitize_callback' => 'bboss_advanced_sanitize_activity_load_type',
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
	 * @since BuddyBoss 3.8.0
	 *
	 * @param array $options Additional options as value => label. Default empty array.
	 */
	$directory_autoload_options = apply_filters( 'bb_performance_directory_autoload', array() );
	$directory_autoload_options = bboss_parse_args(
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

	bboss_register_feature_field(
		$feature_id,
		$panel_id,
		'advanced_page_loading',
		array(
			'name'                 => 'bb_directory_load_type',
			'label'                => __( 'Members & Groups Loading', 'buddyboss' ),
			'type'                 => 'hidden',
			/* translators: %s: inline select for the members and groups lists load type. */
			'description'          => __( 'Load members and groups lists using %s', 'buddyboss' ),
			'default'              => bboss_get_directory_load_type(),
			'sanitize_callback'    => 'bboss_advanced_sanitize_directory_load_type',
			'description_controls' => array(
				array(
					'type'              => 'select',
					'name'              => 'bb_directory_load_type',
					'default'           => bboss_get_directory_load_type(),
					'sanitize_callback' => 'bboss_advanced_sanitize_directory_load_type',
					'options'           => $directory_load_type_options,
				),
			),
			'order'                => 20,
		)
	);

	// =========================================================================
	// SECTION 3: Toolbar Settings
	// =========================================================================

	bboss_register_feature_section(
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
	bboss_register_feature_field(
		$feature_id,
		$panel_id,
		'advanced_toolbar',
		array(
			'name'              => 'show-admin-adminbar',
			'label'             => __( 'Toolbar', 'buddyboss' ),
			'description'       => __( 'Show the Toolbar for logged-in admins', 'buddyboss' ),
			'type'              => 'toggle',
			'default'           => bboss_show_admin_adminbar( true ),
			'sanitize_callback' => 'absint',
			'group'             => 'toolbar_settings',
			'order'             => 10,
		)
	);

	// Field 9: Toolbar for members.
	bboss_register_feature_field(
		$feature_id,
		$panel_id,
		'advanced_toolbar',
		array(
			'name'              => 'show-login-adminbar',
			'label'             => '',
			'description'       => __( 'Show the Toolbar for logged-in members (non-admins)', 'buddyboss' ),
			'type'              => 'toggle',
			'default'           => bboss_show_login_adminbar( true ),
			'sanitize_callback' => 'absint',
			'group'             => 'toolbar_settings',
			'order'             => 20,
		)
	);

	// Field 10: Toolbar for logged out.
	bboss_register_feature_field(
		$feature_id,
		$panel_id,
		'advanced_toolbar',
		array(
			'name'              => 'hide-loggedout-adminbar',
			'label'             => '',
			'description'       => __( 'Show the Toolbar for logged out users', 'buddyboss' ),
			'type'              => 'toggle',
			'default'           => bboss_hide_loggedout_adminbar( false ),
			'invert_value'      => true, // DB stores 1 = hide toolbar. Toggle shows "Show", so invert for display.
			'sanitize_callback' => 'absint',
			'group'             => 'toolbar_settings',
			'order'             => 30,
		)
	);
}
// @bbwt-com-start compat-layer
// bbwt-rename:compat-aliases — the pre-rename name of the function above (decision 0013): declared right after it, guarded on it, so the old name exists exactly when the new one does.
if ( function_exists( 'bboss_advanced_register_general_fields' ) && ! function_exists( 'bb_advanced_register_general_fields' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_advanced_register_general_fields()}.
	 *
	 * @deprecated Use bboss_advanced_register_general_fields() instead.
	 */
	function bb_advanced_register_general_fields() {
		return bboss_advanced_register_general_fields();
	}
}
// @bbwt-com-end compat-layer


/**
 * Keep the pre-rename ID of the Page Loading section working.
 *
 * Before BuddyBoss 3.8.0 the Advanced > General card that holds Feed Page Loading was the
 * "advanced_activity" section, registered only while the Activity component is active. It is
 * now "advanced_page_loading" (it also holds Members & Groups Loading), so a field that an
 * add-on still registers into "advanced_activity" is moved into it, under the same condition.
 *
 * @since BuddyBoss 3.8.0
 *
 * @param array  $aliases       Old section ID => current section ID.
 * @param string $feature_id    Feature ID.
 * @param string $side_panel_id Side panel ID.
 * @return array
 */
function bboss_advanced_general_section_aliases( $aliases, $feature_id, $side_panel_id ) {
	if ( 'advanced' === $feature_id && 'general' === $side_panel_id && bboss_is_active( 'activity' ) ) {
		$aliases['advanced_activity'] = 'advanced_page_loading';
	}

	return $aliases;
}
// @bbwt-com-start compat-layer
// bbwt-rename:compat-aliases — the pre-rename name of the function above (decision 0013): declared right after it, guarded on it, so the old name exists exactly when the new one does.
if ( function_exists( 'bboss_advanced_general_section_aliases' ) && ! function_exists( 'bb_advanced_general_section_aliases' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_advanced_general_section_aliases()}.
	 *
	 * @deprecated Use bboss_advanced_general_section_aliases() instead.
	 */
	function bb_advanced_general_section_aliases( $aliases, $feature_id, $side_panel_id ) {
		return bboss_advanced_general_section_aliases( $aliases, $feature_id, $side_panel_id );
	}
}
// @bbwt-com-end compat-layer

