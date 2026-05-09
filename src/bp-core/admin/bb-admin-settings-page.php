<?php
/**
 * BuddyBoss Admin Settings Page.
 *
 * @package BuddyBoss\Core\Administration
 * @since BuddyBoss [BBVERSION]
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

// NOTE: The Settings submenu is registered by BP_Admin in class-bp-admin.php
// at the legacy bp-settings menu position (now using the bb-settings slug).
// The standalone add_submenu_page() call that used to live here is removed
// so Settings appears once in the correct position with label "Settings".
// `bb_admin_settings_page()` below remains the render callback and is
// invoked from BP_Admin's submenu registration.


/**
 * Render the New Settings page.
 *
 * @since BuddyBoss [BBVERSION]
 */
function bb_admin_settings_page() {
	// Get build directory.
	$build_dir = buddypress()->plugin_dir . 'bp-core/admin/bb-settings/settings/build';
	$build_url = buddypress()->plugin_url . 'bp-core/admin/bb-settings/settings/build';

	// Load asset file.
	$asset_file = $build_dir . '/index.asset.php';
	if ( ! file_exists( $asset_file ) ) {
		?>
		<div class="wrap">
			<div class="notice notice-error">
				<p>
					<?php
					esc_html_e(
						'BuddyBoss Admin Settings assets not found. Please run: npm run build:admin:settings',
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

	// Conditionally enqueue WordPress editor (~250KB TinyMCE) and the WordPress
	// media library (~200-400ms TTI) only when a feature actually exposes the
	// matching field type. Mirrors a single registry scan over the union of
	// the four field types we care about, instead of two full traversals over
	// every feature × every field × every option (the previous shape did
	// `bb_get_all_fields()` per feature TWICE, which scales multiplicatively
	// with feature count and runs on every Settings 2.0 page render).
	//
	// Result is memoized request-scoped via the `static` cache below; we don't
	// promote to wp_cache because feature registration runs at request boot,
	// so the value is request-stable and a static is enough.
	$has_rich_text   = false;
	$has_media_field = false;

	if ( function_exists( 'bb_feature_registry' ) ) {
		static $bb_settings_field_type_flags = null;

		if ( null === $bb_settings_field_type_flags ) {
			$bb_settings_field_type_flags = array(
				'richtext'    => false,
				'media_field' => false,
			);
			$media_types                  = array( 'media_picker', 'image_upload', 'image_radio' );
			$all_features                 = bb_feature_registry()->bb_get_features();
			foreach ( $all_features as $fid => $f ) {
				$all_fields = bb_feature_registry()->bb_get_all_fields( $fid );
				foreach ( $all_fields as $field ) {
					$type = $field['type'] ?? '';
					if ( 'richtext' === $type ) {
						$bb_settings_field_type_flags['richtext'] = true;
					} elseif ( in_array( $type, $media_types, true ) ) {
						$bb_settings_field_type_flags['media_field'] = true;
					}

					// Short-circuit once both flags are set.
					if ( $bb_settings_field_type_flags['richtext'] && $bb_settings_field_type_flags['media_field'] ) {
						break 2;
					}
				}
			}
		}

		$has_rich_text   = $bb_settings_field_type_flags['richtext'];
		$has_media_field = $bb_settings_field_type_flags['media_field'];
	}

	// Check Meta Field Registry (Activity/Groups edit modals use richtext fields).
	// Separate scan because meta fields are a different registry.
	if ( ! $has_rich_text && function_exists( 'bb_admin_meta_field_registry' ) ) {
		$meta_components = array( 'activity', 'groups', 'forums', 'discussions', 'replies', 'emails' );
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

	if ( $has_media_field ) {
		wp_enqueue_media();
	}

	// Enqueue scripts and styles.
	wp_enqueue_script(
		'bb-admin-settings',
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
				'bb-admin-settings',
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
	// Resolve the Mothership IPN root element ID. The prefix is derived from
	// the active plugin_id (free / plus / pro / per-site editions all produce
	// different IDs), so we ask the IPN View service for the actual ID it
	// will render. The React Header reads this via bbAdminData.ipnRootId to
	// locate and relocate the live IPN node.
	$ipn_root_id = '';
	if (
		class_exists( '\BuddyBoss\Core\Admin\Mothership\BB_Mothership_Loader' ) &&
		class_exists( '\BuddyBossPlatform\GroundLevel\InProductNotifications\Services\View' )
	) {
		try {
			$container   = \BuddyBoss\Core\Admin\Mothership\BB_Mothership_Loader::instance()->get_container();
			$ipn_view    = $container->get( \BuddyBossPlatform\GroundLevel\InProductNotifications\Services\View::class );
			$ipn_root_id = $ipn_view->getRootElementId();
		} catch ( Throwable $e ) {
			// IPN service not loaded for some reason — leave $ipn_root_id empty,
			// JS will fall back to a structural [id$="_ipn_root"] selector.
			unset( $e );
		}
	}

	$localize_data = array(
		'apiUrl'                    => rest_url( bp_rest_namespace() . '/' . bp_rest_version() . '/' ),
		'nonce'                     => wp_create_nonce( 'wp_rest' ),
		'ajaxUrl'                   => esc_url( admin_url( 'admin-ajax.php' ) ),
		'ajaxNonce'                 => wp_create_nonce( 'bb_admin_settings' ),
		'addonNonce'                => wp_create_nonce( 'mosh_addons' ),
		'logoUrl'                   => buddypress()->plugin_url . 'bp-core/images/admin/BBLogo.png',
		'isReadyLaunch'             => function_exists( 'bb_is_readylaunch_enabled' ) && bb_is_readylaunch_enabled(),
		// BuddyBoss Theme state for the Appearance welcome banner CTA logic.
		// `get_template()` over `get_stylesheet()` so child themes of
		// buddyboss-theme count as active. `canSwitchThemes` lets the
		// React side disable the Activate button on multisite where site
		// admins lack the cap.
		'isBuddyBossThemeActive'    => 'buddyboss-theme' === get_template(),
		'isBuddyBossThemeInstalled' => wp_get_theme( 'buddyboss-theme' )->exists(),
		'canSwitchThemes'           => current_user_can( 'switch_themes' ),
		'currentUser'               => array(
			'id'   => get_current_user_id(),
			'name' => wp_get_current_user()->display_name,
		),
		'siteUrl'                   => untrailingslashit( home_url() ),
		// Pass the user's legacy groups-per-page screen option so GroupsListScreen
		// can honour the preference set in the old WP admin list table.
		'groupsPerPage'             => $groups_per_page,
		// Mothership IPN root element ID — prefix is dynamic per plugin_id.
		'ipnRootId'                 => $ipn_root_id,
	);

	// Component active status for conditional UI in React.
	$localize_data['isSearchActive']                     = bp_is_active( 'search' );
	$localize_data['showMessagingWithoutConnectionFlag'] = bp_is_active( 'messages' ) && bp_is_active( 'friends' ) && (bool) bp_get_option( 'bp-force-friendship-to-message', false );
	$localize_data['isGroupCreationAllowed']             = bp_is_active( 'groups' ) && ! bp_restrict_group_creation();
	$localize_data['isGroupTypeCreationEnabled']         = bp_is_active( 'groups' ) && bp_disable_group_type_creation();
	$localize_data['isGroupAutoJoinEnabled']             = bp_is_active( 'groups' ) && bp_disable_group_type_creation() && bp_enable_group_auto_join();
	$localize_data['isEmailInviteEnabled']               = bp_is_active( 'invites' ) && function_exists( 'bp_disable_invite_member_type' ) && bp_disable_invite_member_type();
	$localize_data['isProfileTypesEnabled']              = bp_is_active( 'xprofile' ) && function_exists( 'bp_member_type_enable_disable' ) && bp_member_type_enable_disable();
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

	// Repair tools nonce — used by Email Missing modal to call existing
	// bp_admin_repair_tools_wrapper_function AJAX action.
	$localize_data['repairNonce'] = wp_create_nonce( 'bp-do-counts' );

	// One-shot help-content cache flush signal.
	//
	// `bb_maybe_clear_placeholder_features_cache()` (admin_init handler in
	// bb-admin-placeholder-features.php) raises this transient when an admin
	// hits `?bb_clear_placeholder_cache=1`. Reading + deleting it here means
	// the React app sees the signal exactly once on its next mount and then
	// the flag is gone — even if the page reloads, the localStorage flush
	// fires only for the trigger that set it, not on every subsequent visit.
	if ( get_transient( 'bb_help_content_cache_flush_signal' ) ) {
		$localize_data['helpContentCacheFlushSignal'] = true;
		delete_transient( 'bb_help_content_cache_flush_signal' );
	}

	// Bootstrap payload for the Appearance → General "Setup Wizard" button.
	// Allows the rl-onboarding React bundle to be lazy-loaded and mounted on
	// click without navigating away from Settings 2.0. Always localized — the
	// Setup Wizard button is always visible (its first step is the BuddyBoss
	// Theme vs ReadyLaunch layout choice, so admins may revisit it after
	// completion to switch). Without this payload the click handler falls
	// back to a full-page redirect, which still works but is slower.
	if ( class_exists( 'BB_ReadyLaunch_Onboarding' ) ) {
		$localize_data['rlOnboardingBootstrap'] = BB_ReadyLaunch_Onboarding::instance()->get_bootstrap_data();
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

	wp_localize_script( 'bb-admin-settings', 'bbAdminData', $localize_data );

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
	<div class="wrap bb-admin-settings-wrap">
		<div id="bb-admin-settings"></div>
		<!-- test -->
		<?php
		/*
		 * Mothership IPN inbox — render outside the React tree.
		 *
		 * The IPN service (Caseproof GroundLevel) attaches a Shadow DOM to
		 * its root <div> synchronously when ipn-inbox.js loads. If we render
		 * the root inside the React Header tree, React mounts asynchronously
		 * AFTER the bundle runs, so getElementById() returns null and the
		 * IPN throws "Cannot read properties of null (reading 'attachShadow')".
		 *
		 * Instead, fire bb_admin_header_actions outside the React tree (here,
		 * inside .wrap but next to the React mount). This emits the standard
		 * IPN <div id="bb-web-plus_ipn_root"> + <script>. The IPN attaches
		 * cleanly. Then the React Header relocates the live IPN node into
		 * its bell slot via appendChild — preserving the Shadow DOM.
		 */
		do_action( 'bb_admin_header_actions' );
		?>
		<noscript>
			<p style="padding: 20px; font-size: 14px;">
				<?php esc_html_e( 'JavaScript is required for BuddyBoss Settings. Please enable JavaScript in your browser.', 'buddyboss' ); ?>
			</p>
		</noscript>
	</div>
	<?php
}
