<?php
/**
 * BuddyBoss Admin Settings - Members Feature Registration.
 *
 * Registers the Members feature in the Feature Registry and loads
 * all Members settings (side panels, sections, fields).
 *
 * @package BuddyBoss\Core\Administration
 * @since BuddyBoss [BBVERSION]
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Register Members feature and settings in Feature Registry.
 *
 * Registers the feature, side panels, and delegates field registration
 * to panel-specific functions.
 *
 * @since BuddyBoss [BBVERSION]
 */
function bb_admin_settings_register_members_feature() {

	// =========================================================================
	// REGISTER FEATURE
	// =========================================================================

	bb_register_feature(
		'members',
		array(
			'label'              => __( 'Member Profiles', 'buddyboss' ),
			'description'        => __( 'Create customizable member profiles with extended fields, avatars, cover images, profile types, and member directories.', 'buddyboss' ),
			'icon'               => array(
				'type'  => 'font',
				'class' => 'bb-icons-rl bb-icons-rl-user-square',
			),
			'license_tier'       => 'free',
			'category'           => 'community',
			'standalone'         => true,
			'required'           => true,
			'components'         => array( 'members', 'xprofile', 'friends' ),
			'is_active_callback' => function () {
				return bp_is_active( 'xprofile' );
			},
			'settings_route'     => '/settings/members',
			'order'              => 20,
		)
	);

	// When xprofile is disabled, only the feature card is needed (so admin can re-enable).
	// Side panels, sections, and fields depend on xprofile functions that aren't loaded.
	if ( ! bp_is_active( 'xprofile' ) ) {
		return;
	}

	// Load settings sub-files only when xprofile is active to avoid parsing callbacks,
	// field registrations, and meta-field definitions when the feature is off.
	require_once __DIR__ . '/settings/members/callbacks.php';
	require_once __DIR__ . '/settings/members/settings-profile-name.php';
	require_once __DIR__ . '/settings/members/settings-profile-image.php';
	require_once __DIR__ . '/settings/members/settings-profile-headers.php';
	require_once __DIR__ . '/settings/members/settings-member-directory.php';
	require_once __DIR__ . '/settings/members/settings-member-connection.php';
	require_once __DIR__ . '/settings/members/settings-access-control.php';
	require_once __DIR__ . '/settings/members/settings-profile-types.php';
	require_once __DIR__ . '/settings/members/settings-profile-search.php';
	require_once __DIR__ . '/settings/members/settings-profile-navigation.php';

	// =========================================================================
	// SIDE PANELS
	// =========================================================================

	// Side Panel 1: Profile Name (default).
	bb_register_side_panel(
		'members',
		'profile_name',
		array(
			'title'      => __( 'Profile Name', 'buddyboss' ),
			'icon'       => array(
				'type'  => 'font',
				'class' => 'bb-icons-rl bb-icons-rl-user',
			),
			'help_url'   => bp_get_admin_url(
				add_query_arg(
					array(
						'page'    => 'bp-help',
						'article' => 62793,
					),
					'admin.php'
				)
			),
			'order'      => 10,
			'is_default' => true,
		)
	);

	// Side Panel 2: Profile Image.
	bb_register_side_panel(
		'members',
		'profile_image',
		array(
			'title'      => __( 'Profile Image', 'buddyboss' ),
			'icon'       => array(
				'type'  => 'font',
				'class' => 'bb-icons-rl bb-icons-rl-image',
			),
			'help_url'   => bp_get_admin_url(
				add_query_arg(
					array(
						'page'    => 'bp-help',
						'article' => 125202,
					),
					'admin.php'
				)
			),
			'order'      => 20,
			'is_default' => false,
		)
	);

	// Side Panel 3: Profile Headers.
	bb_register_side_panel(
		'members',
		'profile_headers',
		array(
			'title'      => __( 'Profile Headers', 'buddyboss' ),
			'icon'       => array(
				'type'  => 'font',
				'class' => 'bb-icons-rl bb-icons-rl-arrows-out-simple',
			),
			'help_url'   => bp_get_admin_url(
				add_query_arg(
					array(
						'page'    => 'bp-help',
						'article' => 125305,
					),
					'admin.php'
				)
			),
			'order'      => 30,
			'is_default' => false,
		)
	);

	// Side Panel 4: Member Directory.
	bb_register_side_panel(
		'members',
		'member_directory',
		array(
			'title'      => __( 'Member Directory', 'buddyboss' ),
			'icon'       => array(
				'type'  => 'font',
				'class' => 'bb-icons-rl bb-icons-rl-grid-nine',
			),
			'help_url'   => bp_get_admin_url(
				add_query_arg(
					array(
						'page'    => 'bp-help',
						'article' => 125311,
					),
					'admin.php'
				)
			),
			'order'      => 40,
			'is_default' => false,
		)
	);

	// Side Panel 5: Member Connection.
	// Always registered — the panel contains a toggle to enable/disable the friends component.
	bb_register_side_panel(
		'members',
		'member_connection',
		array(
			'title'      => __( 'Member Connection', 'buddyboss' ),
			'icon'       => array(
				'type'  => 'font',
				'class' => 'bb-icons-rl bb-icons-rl-handshake',
			),
			'help_url'   => bp_get_admin_url(
				add_query_arg(
					array(
						'page'    => 'bp-help',
						'article' => 62835,
					),
					'admin.php'
				)
			),
			'order'      => 50,
			'is_default' => false,
		)
	);

	// Divider before custom screen panels.

	// Side Panel 6: Profile Fields (custom screen).
	bb_register_side_panel(
		'members',
		'profile_fields',
		array(
			'title'      => __( 'Profile Fields', 'buddyboss' ),
			'icon'       => array(
				'type'  => 'font',
				'class' => 'bb-icons-rl bb-icons-rl-list-dashes',
			),
			'help_url'   => bp_get_admin_url(
				add_query_arg(
					array(
						'page'    => 'bp-help',
						'article' => 62796,
					),
					'admin.php'
				)
			),
			'divider'    => true,
			'order'      => 60,
			'is_default' => false,
		)
	);

	// Side Panel 7: Profile Types (custom screen).
	bb_register_side_panel(
		'members',
		'profile_types',
		array(
			'title'    => __( 'Profile Types', 'buddyboss' ),
			'icon'     => array(
				'type'  => 'font',
				'class' => 'bb-icons-rl bb-icons-rl-tag',
			),
			'help_url' => bp_get_admin_url(
				add_query_arg(
					array(
						'page'    => 'bp-help',
						'article' => 62816,
					),
					'admin.php'
				)
			),
			'order'    => 70,
		)
	);

	// Side Panel 8: Profile Search.
	bb_register_side_panel(
		'members',
		'profile_search',
		array(
			'title'      => __( 'Profile Search', 'buddyboss' ),
			'icon'       => array(
				'type'  => 'font',
				'class' => 'bb-icons-rl bb-icons-rl-magnifying-glass',
			),
			'help_url'   => bp_get_admin_url(
				add_query_arg(
					array(
						'page'    => 'bp-help',
						'article' => 62803,
					),
					'admin.php'
				)
			),
			'order'      => 80,
			'is_default' => false,
		)
	);

	// Side Panel 9: Profile Navigation.
	bb_register_side_panel(
		'members',
		'profile_navigation',
		array(
			'title'      => __( 'Profile Navigation', 'buddyboss' ),
			'icon'       => array(
				'type'  => 'font',
				'class' => 'bb-icons-rl bb-icons-rl-tabs',
			),
			'help_url'   => bp_get_admin_url(
				add_query_arg(
					array(
						'page'    => 'bp-help',
						'article' => 62851,
					),
					'admin.php'
				)
			),
			'order'      => 90,
			'is_default' => false,
		)
	);

	// =========================================================================
	// PANEL FIELDS
	// =========================================================================

	// Panel 1: Profile Name.
	bb_members_register_profile_name_panel_fields();

	// Panel 2: Profile Image.
	bb_members_register_profile_image_panel_fields();

	// Panel 3: Profile Headers.
	bb_members_register_profile_headers_panel_fields();

	// Panel 4: Member Directory.
	bb_members_register_member_directory_panel_fields();

	// Panel 5: Member Connection.
	// Always registered — toggle field inside controls friends component activation.
	bb_members_register_member_connection_panel_fields();

	// Access Controls (Connection Access) — follows same pattern as Groups/Activity.
	bb_members_register_access_control_fields();

	// Panel 6: Profile Types.
	bb_members_register_profile_types_panel_fields();

	// Panel 7: Profile Search.
	bb_members_register_profile_search_panel_fields();

	// Panel 8: Profile Navigation.
	bb_members_register_profile_navigation_panel_fields();

	/**
	 * Fires after all Members settings panels are registered.
	 * Allows third-party extensions to add more panels or fields.
	 *
	 * @since BuddyBoss [BBVERSION]
	 */
	do_action( 'bb_members_after_register_settings_fields' );
}

add_action( 'bb_register_features', 'bb_admin_settings_register_members_feature', 15 );
