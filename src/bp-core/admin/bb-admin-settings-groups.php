<?php
/**
 * BuddyBoss Admin Settings - Groups Feature Registration.
 *
 * Registers the Groups feature in the Feature Registry and loads
 * all Groups settings (side panels, sections, fields).
 *
 * @package BuddyBoss\Core\Administration
 * @since BuddyBoss [BBVERSION]
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

// Load sanitize callbacks.
require_once __DIR__ . '/settings/groups/callbacks.php';

// Load panel field registrations.
require_once __DIR__ . '/settings/groups/settings-group-settings.php';
require_once __DIR__ . '/settings/groups/settings-group-images.php';
require_once __DIR__ . '/settings/groups/settings-group-headers.php';
require_once __DIR__ . '/settings/groups/settings-group-directory.php';
require_once __DIR__ . '/settings/groups/settings-access-control.php';
require_once __DIR__ . '/settings/groups/settings-group-navigation.php';

/**
 * Register Groups feature and settings in Feature Registry.
 *
 * Registers the feature, side panels, and delegates field registration
 * to panel-specific functions.
 *
 * @since BuddyBoss [BBVERSION]
 */
function bb_admin_settings_register_groups_feature() {

	// =========================================================================
	// REGISTER FEATURE
	// =========================================================================

	bb_register_feature(
		'groups',
		array(
			'label'              => __( 'Social Groups', 'buddyboss' ),
			'description'        => __( 'Allow members to create and join groups with shared activity feeds, messaging, forums, and media.', 'buddyboss' ),
			'icon'               => array(
				'type'  => 'font',
				'class' => 'bb-icons-rl bb-icons-rl-users-three',
			),
			'license_tier'       => 'free',
			'category'           => 'community',
			'standalone'         => true,
			'is_active_callback' => function () {
				return bp_is_active( 'groups' );
			},
			'settings_route'     => '/settings/groups',
			'order'              => 50,
		)
	);

	// When groups is disabled, only the feature card is needed (so admin can re-enable).
	// Side panels, sections, and fields depend on groups functions that aren't loaded.
	if ( ! bp_is_active( 'groups' ) ) {
		return;
	}

	// =========================================================================
	// SIDE PANELS
	// =========================================================================

	// Side Panel 1: Group Settings (default).
	bb_register_side_panel(
		'groups',
		'group_settings',
		array(
			'title'      => __( 'Group Settings', 'buddyboss' ),
			'icon'       => array(
				'type'  => 'font',
				'class' => 'bb-icons-rl bb-icons-rl-gear-six',
			),
			'help_url'   => bp_get_admin_url(
				add_query_arg(
					array(
						'page'    => 'bp-help',
						'article' => 62819,
					),
					'admin.php'
				)
			),
			'order'      => 10,
			'is_default' => true,
		)
	);

	// Side Panel 2: Group Images.
	bb_register_side_panel(
		'groups',
		'group_images',
		array(
			'title'      => __( 'Group Images', 'buddyboss' ),
			'icon'       => array(
				'type'  => 'font',
				'class' => 'bb-icons-rl bb-icons-rl-image',
			),
			'help_url'   => bp_get_admin_url(
				add_query_arg(
					array(
						'page'    => 'bp-help',
						'article' => 62819,
					),
					'admin.php'
				)
			),
			'order'      => 20,
			'is_default' => false,
		)
	);

	// Side Panel 3: Group Headers.
	bb_register_side_panel(
		'groups',
		'group_headers',
		array(
			'title'      => __( 'Group Headers', 'buddyboss' ),
			'icon'       => array(
				'type'  => 'font',
				'class' => 'bb-icons-rl bb-icons-rl-arrows-out-simple',
			),
			'help_url'   => '',
			'order'      => 30,
			'is_default' => false,
		)
	);

	// Side Panel 4: Group Directory.
	bb_register_side_panel(
		'groups',
		'group_directory',
		array(
			'title'      => __( 'Group Directory', 'buddyboss' ),
			'icon'       => array(
				'type'  => 'font',
				'class' => 'bb-icons-rl bb-icons-rl-grid-nine',
			),
			'help_url'   => '',
			'order'      => 40,
			'is_default' => false,
		)
	);

	// Side Panel 5: Access Controls.
	bb_register_side_panel(
		'groups',
		'access_controls',
		array(
			'title' => __( 'Access Controls', 'buddyboss' ),
			'icon'  => array(
				'type'  => 'font',
				'class' => 'bb-icons-rl bb-icons-rl-lock-simple',
			),
			'order' => 50,
		)
	);

	// Side Panel: All Groups (custom panel screen).
	bb_register_side_panel(
		'groups',
		'all_groups',
		array(
			'title'      => __( 'All Groups', 'buddyboss' ),
			'icon'       => array(
				'type'  => 'font',
				'class' => 'bb-icons-rl bb-icons-rl-list-bullets',
			),
			'order'      => 100,
			'is_default' => false,
			'divider'    => true,
		)
	);

	// Side Panel: Group Types (custom panel screen).
	bb_register_side_panel(
		'groups',
		'group_types',
		array(
			'title' => __( 'Group Types', 'buddyboss' ),
			'icon'  => array(
				'type'  => 'font',
				'class' => 'bb-icons-rl bb-icons-rl-tag',
			),
			'order' => 110,
		)
	);

	// Side Panel: Group Navigation.
	bb_register_side_panel(
		'groups',
		'group_navigation',
		array(
			'title' => __( 'Group Navigation', 'buddyboss' ),
			'icon'  => array(
				'type'  => 'font',
				'class' => 'bb-icons-rl bb-icons-rl-tabs',
			),
			'order' => 120,
		)
	);

	// =========================================================================
	// PANEL FIELDS
	// =========================================================================

	// Panel 1: Group Settings.
	bb_groups_register_settings_panel_fields();

	// Panel 2: Group Images.
	bb_groups_register_images_panel_fields();

	// Panel 3: Group Headers.
	bb_groups_register_headers_panel_fields();

	// Panel 4: Group Directory.
	bb_groups_register_directory_panel_fields();

	// Panel 5: Access Controls.
	bb_groups_register_access_control_fields();

	// Panel 6: Group Navigation.
	bb_groups_register_navigation_panel_fields();

	/**
	 * Fires after all Groups settings panels are registered.
	 * Allows third-party extensions to add more panels or fields.
	 *
	 * @since BuddyBoss [BBVERSION]
	 */
	do_action( 'bb_groups_after_register_settings_fields' );
}

add_action( 'bb_register_features', 'bb_admin_settings_register_groups_feature', 20 );
