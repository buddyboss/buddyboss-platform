<?php
/**
 * BuddyBoss Admin Settings Page.
 *
 * @package BuddyBoss\Core\Administration
 * @since BuddyBoss [BBVERSION]
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Register admin menu pages for Settings.
 *
 * @since BuddyBoss [BBVERSION]
 */
function bb_admin_settings_register_menu() {
	// Add "Settings" as a separate submenu item under BuddyBoss.
	// This keeps the old bp-components intact for comparison.
	add_submenu_page(
		'buddyboss-platform',
		__( 'Settings 2.0', 'buddyboss' ),
		__( 'Settings 2.0', 'buddyboss' ),
		'manage_options',
		'bb-settings',
		'bb_admin_settings_page'
	);
}
add_action( 'admin_menu', 'bb_admin_settings_register_menu', 999 ); // Late priority.


/**
 * Render the New Settings page.
 *
 * @since BuddyBoss [BBVERSION]
 */
function bb_admin_settings_page() {
	// Get build directory.
	$build_dir = buddypress()->plugin_dir . 'bp-core/admin/bb-settings/settings-2.0/build';
	$build_url = buddypress()->plugin_url . 'bp-core/admin/bb-settings/settings-2.0/build';

	// Load asset file.
	$asset_file = $build_dir . '/index.asset.php';
	if ( ! file_exists( $asset_file ) ) {
		?>
		<div class="wrap">
			<div class="notice notice-error">
				<p>
					<?php
					esc_html_e(
						'BuddyBoss Admin Settings 2.0 assets not found. Please run: npm run build:admin:settings-2.0',
						'buddyboss'
					);
					?>
				</p>
			</div>
		</div>
		<?php
		return;
	}

	$asset = require $asset_file;

	// Enqueue WordPress components style explicitly (needed for ToggleControl, Button, etc.).
	wp_enqueue_style( 'wp-components' );

	// Enqueue BuddyBoss icons CSS.
	$min             = bp_core_get_minified_asset_suffix();
	$bb_icon_version = function_exists( 'bb_icon_font_map_data' ) ? bb_icon_font_map_data( 'version' ) : bp_get_version();
	wp_enqueue_style(
		'bb-icons',
		buddypress()->plugin_url . "bp-templates/bp-nouveau/icons/css/bb-icons{$min}.css",
		array(),
		$bb_icon_version
	);
	wp_enqueue_style(
		'bb-icons-rl-css',
		buddypress()->plugin_url . "bp-templates/bp-nouveau/readylaunch/icons/css/bb-icons-rl{$min}.css",
		array(),
		$bb_icon_version
	);

	// Conditionally enqueue WordPress editor (~250KB TinyMCE) only when richtext fields exist.
	// Check both Feature Registry (settings fields) and Meta Field Registry (edit modal fields).
	$has_rich_text = false;

	// Check Feature Registry for richtext settings fields.
	if ( function_exists( 'bb_feature_registry' ) ) {
		$all_features = bb_feature_registry()->bb_get_features();
		foreach ( $all_features as $fid => $f ) {
			$all_fields = bb_feature_registry()->bb_get_all_fields( $fid );
			foreach ( $all_fields as $field ) {
				if ( ! empty( $field['type'] ) && 'richtext' === $field['type'] ) {
					$has_rich_text = true;
					break 2;
				}
			}
		}
	}

	// Check Meta Field Registry (Activity/Groups edit modals use richtext fields).
	if ( ! $has_rich_text && function_exists( 'bb_admin_meta_field_registry' ) ) {
		$meta_components = array( 'activity', 'groups' );
		foreach ( $meta_components as $component ) {
			$meta_fields = bb_admin_meta_field_registry()->get_fields( $component );
			foreach ( $meta_fields as $field ) {
				if ( ! empty( $field['type'] ) && 'richtext' === $field['type'] ) {
					$has_rich_text = true;
					break 2;
				}
			}
		}
	}

	if ( $has_rich_text ) {
		wp_enqueue_editor();
	}

	// Enqueue scripts and styles.
	wp_enqueue_script(
		'bb-admin-settings-2-0',
		$build_url . '/index.js',
		$asset['dependencies'],
		$asset['version'],
		true
	);

	// Check if CSS file exists (try multiple possible locations).
	$css_paths = array(
		$build_dir . '/styles/admin.css',
		$build_dir . '/admin.css',
	);

	foreach ( $css_paths as $css_file ) {
		if ( file_exists( $css_file ) ) {
			$css_url = str_replace( buddypress()->plugin_dir, buddypress()->plugin_url, $css_file );
			wp_enqueue_style(
				'bb-admin-settings-2-0',
				$css_url,
				array( 'wp-components' ), // Add wp-components as dependency.
				$asset['version']
			);
			break;
		}
	}

	// Localize script with admin data.
	$groups_per_page_option = bp_core_do_network_admin() ? 'buddyboss_page_bp_groups_network_per_page' : 'buddyboss_page_bp_groups_per_page';
	$groups_per_page        = absint( get_user_option( $groups_per_page_option, get_current_user_id() ) );
	if ( 0 === $groups_per_page ) {
		$groups_per_page = 20;
	}
	$localize_data = array(
		'apiUrl'         => rest_url( bp_rest_namespace() . '/' . bp_rest_version() . '/' ),
		'nonce'          => wp_create_nonce( 'wp_rest' ),
		'ajaxUrl'        => esc_url( admin_url( 'admin-ajax.php' ) ),
		'ajaxNonce'      => wp_create_nonce( 'bb_admin_settings' ),
		'logoUrl'        => buddypress()->plugin_url . 'bp-core/images/admin/BBLogo.png',
		'isReadyLaunch'  => function_exists( 'bb_is_readylaunch_enabled' ) && bb_is_readylaunch_enabled(),
		'currentUser'    => array(
			'id'   => get_current_user_id(),
			'name' => wp_get_current_user()->display_name,
		),
		// Pass the user's legacy groups-per-page screen option so GroupsListScreen
		// can honour the preference set in the old WP admin list table.
		'groupsPerPage' => $groups_per_page,
	);

	// Component active status for conditional UI in React.
	$localize_data['isSearchActive']                     = bp_is_active( 'search' );
	$localize_data['showMessagingWithoutConnectionFlag'] = bp_is_active( 'messages' ) && bp_is_active( 'friends' ) && (bool) bp_get_option( 'bp-force-friendship-to-message', false );
	$localize_data['isGroupCreationAllowed']             = bp_is_active( 'groups' ) && ! bp_restrict_group_creation();
	$localize_data['isGroupTypeCreationEnabled']         = bp_is_active( 'groups' ) && bp_disable_group_type_creation();
	$localize_data['isGroupAutoJoinEnabled']             = bp_is_active( 'groups' ) && bp_disable_group_type_creation() && bp_enable_group_auto_join();
	// Upload nonces for image upload fields (avatar/cover).
	// Only expose when the user has capability to manage group settings.
	if ( bp_current_user_can( 'bp_moderate' ) ) {
		$localize_data['uploadNonces'] = array(
			'uploader'          => wp_create_nonce( 'bp-uploader' ),
			'avatarCropstore'   => wp_create_nonce( 'bp_avatar_cropstore' ),
			'avatarDelete'      => wp_create_nonce( 'bp_delete_avatar_link' ),
			'avatarDeleteGroup' => wp_create_nonce( 'bp_group_avatar_delete' ),
			'coverDelete'       => wp_create_nonce( 'bp_delete_cover_image' ),
		);
	}

	// Reported Content: pass content types for the filter dropdown.
	if ( bp_is_active( 'moderation' ) ) {
		$content_types = bp_moderation_content_types();

		// Exclude member types (shown in separate Flagged Members panel).
		unset( $content_types[ BP_Moderation_Members::$moderation_type ] );
		if ( isset( $content_types[ BP_Moderation_Members::$moderation_type_report ] ) ) {
			unset( $content_types[ BP_Moderation_Members::$moderation_type_report ] );
		}

		$localize_data['reportedContentTypes'] = $content_types;
	}

	// Only expose debug data when WP_DEBUG is enabled.
	if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
		$registry               = bb_feature_registry();
		$all_features           = $registry->bb_get_features( array( 'status' => 'all' ) );
		$localize_data['debug'] = array(
			'featureCount' => count( $all_features ),
			'featureIds'   => array_keys( $all_features ),
		);
	}

	wp_localize_script( 'bb-admin-settings-2-0', 'bbAdminData', $localize_data );

	/**
	 * Deprecated: bp_activity_admin_enqueue_scripts.
	 *
	 * Legacy hook used by third-party plugins to enqueue CSS/JS on the
	 * activity admin screen. Fired here when the Settings 2.0 page loads,
	 * so existing plugins that hook here can still enqueue their assets.
	 *
	 * @since BuddyPress 1.6.0
	 * @deprecated BuddyBoss [BBVERSION] Use standard WordPress enqueue hooks instead.
	 */
	if ( bp_is_active( 'activity' ) ) {
		do_action_deprecated(
			'bp_activity_admin_enqueue_scripts',
			array(),
			'[BBVERSION]',
			'',
			'Enqueue scripts using standard WordPress admin_enqueue_scripts hooks instead.'
		);
	}

	// Render mount point.
	?>
	<div class="wrap bb-admin-settings-2-0-wrap">
		<div id="bb-admin-settings-2-0"></div>
	</div>
	<?php
}
