<?php
/**
 * BuddyBoss Admin Settings - Media Feature Registration.
 *
 * Registers the Media feature in the Feature Registry and loads
 * all Media settings (side panels, sections, fields).
 *
 * The Media feature maps to the bp-media (photos) component only. The
 * bp-video (videos) and bp-document (documents) components are managed
 * independently — they are not activated or deactivated by the Media
 * toggle, and their settings panels are only registered when the
 * corresponding component is active and present in the build.
 *
 * @package BuddyBoss\Core\Administration
 * @since BuddyBoss 3.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Register Media feature and settings in Feature Registry.
 *
 * Registers the feature, side panels, and delegates field registration
 * to panel-specific functions.
 *
 * @since BuddyBoss 3.0.0
 */
function bb_admin_settings_register_media_feature() {

	// =========================================================================
	// REGISTER FEATURE
	// =========================================================================

	bb_register_feature(
		'media',
		array(
			'label'              => __( 'Media Uploading', 'buddyboss' ),
			'description'        => __( 'Allow members to upload photos, videos, documents, emojis, and GIFs, and organize them into albums or folders.', 'buddyboss' ),
			'icon'               => array(
				'type'  => 'font',
				'class' => 'bb-icons-rl bb-icons-rl-image',
			),
			'license_tier'       => 'free',
			'category'           => 'community',
			'standalone'         => true,
			'is_active_callback' => function () {
				return bp_is_active( 'media' );
			},
			'components'         => array( 'media' ),
			'settings_route'     => '/settings/media',
			'order'              => 110,
		)
	);

	// When media is disabled, only the feature card is needed (so admin can re-enable).
	// Side panels, sections, and fields depend on media functions that aren't loaded.
	if ( ! bp_is_active( 'media' ) ) {
		return;
	}

	// Load settings sub-files only when media is active.
	require_once __DIR__ . '/settings/media/callbacks.php';
	require_once __DIR__ . '/settings/media/settings-photos.php';
	require_once __DIR__ . '/settings/media/settings-emoji.php';
	require_once __DIR__ . '/settings/media/settings-security.php';
	require_once __DIR__ . '/settings/media/settings-access-controls.php';

	// Videos and Documents are independent components (paid-only); only load
	// their settings panels when the component is active in this build.
	if ( bp_is_active( 'video' ) ) {
		require_once __DIR__ . '/settings/media/settings-videos.php';
	}
	if ( bp_is_active( 'document' ) ) {
		require_once __DIR__ . '/settings/media/settings-documents.php';
	}

	// =========================================================================
	// SIDE PANELS
	// =========================================================================

	// Side Panel 1: Photos (default).
	bb_register_side_panel(
		'media',
		'photos',
		array(
			'title'      => __( 'Photos', 'buddyboss' ),
			'icon'       => array(
				'type'  => 'font',
				'class' => 'bb-icons-rl bb-icons-rl-image',
			),
			'help_url'   => bp_get_admin_url(
				add_query_arg(
					array(
						'page'    => 'bp-help',
						'article' => 62827,
					),
					'admin.php'
				)
			),
			'order'      => 10,
			'is_default' => true,
		)
	);

	// Side Panel 2: Videos (only when the video component is active).
	if ( bp_is_active( 'video' ) ) {
		bb_register_side_panel(
			'media',
			'videos',
			array(
				'title'    => __( 'Videos', 'buddyboss' ),
				'icon'     => array(
					'type'  => 'font',
					'class' => 'bb-icons-rl bb-icons-rl-video-camera',
				),
				'help_url' => bp_get_admin_url(
					add_query_arg(
						array(
							'page'    => 'bp-help',
							'article' => 124807,
						),
						'admin.php'
					)
				),
				'order'    => 20,
			)
		);
	} else {
		bb_media_register_addons_upsell_panel(
			'videos',
			__( 'Videos', 'buddyboss' ),
			'bb-icons-rl bb-icons-rl-video-camera',
			__( 'Let members upload and share videos on their profiles, in groups, messages and forums. Requires the BuddyBoss Add-ons plugin. Available on the Start plan and above.', 'buddyboss' ),
			20
		);
	}

	// Side Panel 3: Documents (only when the document component is active).
	if ( bp_is_active( 'document' ) ) {
		bb_register_side_panel(
			'media',
			'documents',
			array(
				'title'    => __( 'Documents', 'buddyboss' ),
				'icon'     => array(
					'type'  => 'font',
					'class' => 'bb-icons-rl bb-icons-rl-file-text',
				),
				'help_url' => bp_get_admin_url(
					add_query_arg(
						array(
							'page'    => 'bp-help',
							'article' => 87466,
						),
						'admin.php'
					)
				),
				'order'    => 30,
			)
		);
	} else {
		bb_media_register_addons_upsell_panel(
			'documents',
			__( 'Documents', 'buddyboss' ),
			'bb-icons-rl bb-icons-rl-file-text',
			__( 'Let members upload, organize and share documents and folders on their profiles, in groups, messages and forums. Requires the BuddyBoss Add-ons plugin. Available on the Start plan and above.', 'buddyboss' ),
			30
		);
	}

	// Side Panel 4: Emoji.
	bb_register_side_panel(
		'media',
		'emoji',
		array(
			'title'    => __( 'Emoji', 'buddyboss' ),
			'icon'     => array(
				'type'  => 'font',
				'class' => 'bb-icons-rl bb-icons-rl-smiley',
			),
			'help_url' => bp_get_admin_url(
				add_query_arg(
					array(
						'page'    => 'bp-help',
						'article' => 62828,
					),
					'admin.php'
				)
			),
			'order'    => 40,
		)
	);

	// Side Panel 5: Animated GIFs. The GIPHY feature ships in the BuddyBoss Add-ons
	// plugin, which registers the real panel on `bb_after_register_features`. When no
	// GIF provider is loaded, show an upsell instead so the feature stays discoverable.
	if ( ! bb_giphy_provider_available() ) {
		bb_media_register_addons_upsell_panel(
			'animated_gifs',
			__( 'Animated GIFs', 'buddyboss' ),
			'bb-icons-rl bb-icons-rl-gif',
			__( 'Let members search and post animated GIFs from GIPHY in activity posts, comments, messages and forums. Requires the BuddyBoss Add-ons plugin. Available on the Start plan and above.', 'buddyboss' ),
			50
		);
	}

	// Side Panel 6: Security & Performance.
	bb_register_side_panel(
		'media',
		'security_performance',
		array(
			'title' => __( 'Security & Performance', 'buddyboss' ),
			'icon'  => array(
				'type'  => 'font',
				'class' => 'bb-icons-rl bb-icons-rl-shield-check',
			),
			'order' => 60,
		)
	);

	// Side Panel 7: Access Controls.
	bb_register_side_panel(
		'media',
		'access_controls',
		array(
			'title' => __( 'Access Controls', 'buddyboss' ),
			'icon'  => array(
				'type'  => 'font',
				'class' => 'bb-icons-rl bb-icons-rl-lock',
			),
			'order' => 70,
		)
	);

	// Side Panel 8: Offload Media link-out (optional).
	//
	// Only registered when the Offload Media plugin is active. We detect the
	// plugin via its `BB_OM_FEATURE_ID` constant, defined at plugin bootstrap
	// (`buddyboss-offload-media.php`) before any hooks fire — so by the time
	// this function runs on `bb_register_features`, the constant is present
	// iff the plugin is loaded. No need to consult the feature registry, and
	// no extra `add_action` required.
	//
	// Rendered by `SideNavigation.js`'s external-link variant (anchor + trailing
	// up-right arrow icon) because the target lives under a separate feature
	// (`offload-media`), not a panel inside Media. The link resolves to the
	// Settings 2.0 URL via `bb_get_feature_settings_url()`, so legacy
	// ?page=bp-integrations&tab=bb-om bookmarks are also handled by that
	// helper's fallback logic on older Platform builds.
	if ( defined( 'BB_OM_FEATURE_ID' ) ) {
		bb_register_side_panel(
			'media',
			'offload_media_link',
			array(
				'title'        => __( 'Offload Media', 'buddyboss' ),
				'icon'         => array(
					'type'  => 'font',
					'class' => 'bb-icons-rl bb-icons-rl-cloud',
				),
				'order'        => 100, // After Access Controls (order 70); divider gives visual separation above.
				'divider'      => true,
				'external_url' => bb_get_feature_settings_url( BB_OM_FEATURE_ID ),
			)
		);
	}

	// =========================================================================
	// PANEL FIELDS
	// =========================================================================

	// Panel 1: Photos.
	bb_media_register_photos_panel_fields();

	// Panel 2: Videos (only when the video component is active).
	if ( bp_is_active( 'video' ) && function_exists( 'bb_media_register_videos_panel_fields' ) ) {
		bb_media_register_videos_panel_fields();
	}

	// Panel 3: Documents (only when the document component is active).
	if ( bp_is_active( 'document' ) && function_exists( 'bb_media_register_documents_panel_fields' ) ) {
		bb_media_register_documents_panel_fields();
	}

	// Panel 4: Emoji.
	bb_media_register_emoji_panel_fields();

	// Panel 6: Security & Performance.
	bb_media_register_security_panel_fields();

	// Panel 7: Access Controls.
	bb_media_register_access_controls_panel_fields();

	/**
	 * Fires after all Media settings panels are registered.
	 * Allows third-party extensions to add more panels or fields.
	 *
	 * @since BuddyBoss 3.0.0
	 */
	do_action( 'bb_media_after_register_settings_fields' );
}

add_action( 'bb_register_features', 'bb_admin_settings_register_media_feature', 20 );

/**
 * Register a Media side panel that advertises a feature from the BuddyBoss Add-ons plugin.
 *
 * Videos, Documents and Animated GIFs ship in the BuddyBoss Add-ons plugin.
 * When the feature is not available on this site, the panel still appears so
 * admins can discover it, showing an "Upgrade" empty state instead of
 * settings. The real panel replaces it as soon as the feature is active.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param string $panel_id     Side panel ID ('videos', 'documents' or 'animated_gifs').
 * @param string $title        Panel title.
 * @param string $icon_class   Icon font class.
 * @param string $description  Empty state description.
 * @param int    $order        Panel order.
 * @param string $button_label Optional. Button label. Default 'Upgrade to Start'.
 *
 * @return void
 */
function bb_media_register_addons_upsell_panel( $panel_id, $title, $icon_class, $description, $order, $button_label = '' ) {
	bb_register_side_panel(
		'media',
		$panel_id,
		array(
			'title' => $title,
			'icon'  => array(
				'type'  => 'font',
				'class' => $icon_class,
			),
			'order' => $order,
		)
	);

	bb_register_feature_section(
		'media',
		$panel_id,
		$panel_id,
		array(
			'title'      => $title,
			'order'      => 10,
			// Section header badge: the feature needs the Start plan (BuddyBoss Add-ons).
			// The popup content (label, title, description, image, URL) comes from the
			// field-upgrades catalog (S3 JSON) so it can change without a plugin release;
			// without a catalog entry the badge opens `link_url` instead.
			'pro_notice' => array(
				'show'       => true,
				'tier'       => 'start',
				'badge_text' => __( 'UPGRADE START', 'buddyboss' ),
				'badge_icon' => 'bb-icons-rl-crown-simple',
				'link_url'   => bb_media_get_addons_upgrade_url( $panel_id ),
			),
		)
	);

	bb_register_feature_field(
		'media',
		$panel_id,
		$panel_id,
		array(
			'name'                    => 'bb_media_' . $panel_id . '_addons_upsell',
			'label'                   => '',
			'type'                    => 'empty_state',
			'icon'                    => $icon_class,
			'empty_state_title'       => $title,
			'empty_state_description' => $description,
			'button_label'            => '' !== $button_label ? $button_label : __( 'Upgrade to Start', 'buddyboss' ),
			// Fallback only: with `upgrade_from_catalog` on, the AJAX formatter swaps in the
			// campaign-tagged URL from the field-upgrades catalog when it has an entry.
			'button_url'              => bb_media_get_addons_upgrade_url( $panel_id ),
			// Popup content and button label come from the catalog entry for this panel.
			'upgrade_from_catalog'    => true,
			'button_target'           => '_blank',
			'sanitize_callback'       => '__return_empty_string',
			'order'                   => 10,
		)
	);
}

/**
 * Get the pricing URL for a Media settings upsell panel.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param string $panel_id Side panel ID ('videos', 'documents' or 'animated_gifs').
 *
 * @return string
 */
function bb_media_get_addons_upgrade_url( $panel_id ) {
	// Share the notice's URL builder so the `bb_moved_media_features_upgrade_url` filter applies here too.
	if ( function_exists( 'bb_moved_media_features_get_upgrade_url' ) ) {
		return bb_moved_media_features_get_upgrade_url( 'settings-' . $panel_id );
	}

	$content = 'settings-' . sanitize_key( $panel_id );
	$url     = add_query_arg(
		array(
			'utm_source'   => 'product',
			'utm_medium'   => 'platform-plugin',
			'utm_campaign' => 'moved-media-features',
			'utm_content'  => $content,
		),
		'https://www.buddyboss.com/pricing/'
	);

	/** This filter is documented in bp-core/admin/bb-admin-moved-media-features-notice.php */
	return (string) apply_filters( 'bb_moved_media_features_upgrade_url', $url, $content );
}

/**
 * Get the BuddyBoss Add-ons module that provides a Media upsell panel's feature.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param string $panel_id Side panel ID ('videos', 'documents' or 'animated_gifs').
 *
 * @return string Module folder name, or an empty string for an unknown panel.
 */
function bb_media_get_addons_module_for_panel( $panel_id ) {
	$modules = array(
		'videos'        => 'video',
		'documents'     => 'documents',
		'animated_gifs' => 'giphy',
	);

	return isset( $modules[ $panel_id ] ) ? $modules[ $panel_id ] : '';
}

/**
 * Resolve what a Media upsell panel should offer.
 *
 * The plan decides first: a license whose plan does not carry BuddyBoss Add-ons
 * (no license, Free, Lite, or an unreachable catalog) keeps the upgrade prompt.
 * When the plan does carry it, the panel offers the step that makes the
 * feature available instead of asking a paying customer to upgrade:
 *
 * - 'install'     Add-ons is not on disk.
 * - 'activate'    Add-ons is on disk but inactive.
 * - 'update'      Add-ons is active but older than the version this Platform
 *                 requires, or the installed build does not ship the module.
 * - 'unavailable' Add-ons is active and current, yet the feature did not load
 *                 (for example the add-on's own license check locked it).
 * - 'upgrade'     Not entitled.
 *
 * REMOTE CALL on a cold add-ons cache (the entitlement lookup), so it is only
 * called from the settings AJAX formatters, never from feature registration.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param string $panel_id Side panel ID ('videos', 'documents' or 'animated_gifs').
 *
 * @return string One of 'upgrade', 'install', 'activate', 'update' or 'unavailable'.
 */
function bb_media_get_addons_panel_state( $panel_id ) {
	static $states = array();

	if ( isset( $states[ $panel_id ] ) ) {
		return $states[ $panel_id ];
	}

	$state = 'upgrade';

	if ( bb_is_entitled_to_addons() ) {
		if ( ! function_exists( 'get_plugins' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}

		$plugin_file = 'buddyboss-addons/buddyboss-addons.php';
		$plugin_path = trailingslashit( WP_PLUGIN_DIR ) . $plugin_file;

		if ( ! file_exists( $plugin_path ) ) {
			$state = 'install';
		} elseif ( ! is_plugin_active( $plugin_file ) ) {
			$state = 'activate';
		} else {
			$plugins   = get_plugins();
			$installed = isset( $plugins[ $plugin_file ]['Version'] ) ? (string) $plugins[ $plugin_file ]['Version'] : '';
			$module    = bb_media_get_addons_module_for_panel( $panel_id );
			$has_code  = '' === $module || file_exists( dirname( $plugin_path ) . '/addons/' . $module . '/loader.php' );

			$state = ( ! $has_code || ( '' !== $installed && version_compare( $installed, bb_get_addons_required_version(), '<' ) ) )
				? 'update'
				: 'unavailable';
		}
	}

	/**
	 * Filters the state of a Media upsell panel.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @param string $state    One of 'upgrade', 'install', 'activate', 'update' or 'unavailable'.
	 * @param string $panel_id Side panel ID ('videos', 'documents' or 'animated_gifs').
	 */
	$states[ $panel_id ] = (string) apply_filters( 'bb_media_addons_panel_state', $state, $panel_id );

	return $states[ $panel_id ];
}

/**
 * Whether a newer BuddyBoss Add-ons build than the installed one is available to the license.
 *
 * Reads the cached add-ons catalog. Fails closed: an unknown installed version or
 * release reads as "no update".
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @return bool
 */
function bb_media_addons_update_available() {
	if ( ! class_exists( '\BuddyBoss\Core\Admin\Mothership\BB_Addons_Manager' ) ) {
		return false;
	}

	if ( ! function_exists( 'get_plugins' ) ) {
		require_once ABSPATH . 'wp-admin/includes/plugin.php';
	}

	$plugins   = get_plugins();
	$installed = isset( $plugins['buddyboss-addons/buddyboss-addons.php']['Version'] ) ? (string) $plugins['buddyboss-addons/buddyboss-addons.php']['Version'] : '';
	$product   = \BuddyBoss\Core\Admin\Mothership\BB_Addons_Manager::checkProductBySlug( 'buddyboss-addons' );
	$release   = \BuddyBoss\Core\Admin\Mothership\BB_Addons_Manager::get_product_latest_version( $product );
	$latest    = ( null !== $release && ! empty( $release->number ) ) ? (string) $release->number : '';

	$available = '' !== $installed && '' !== $latest && version_compare( $latest, $installed, '>' );

	/**
	 * Filters whether a BuddyBoss Add-ons update is available for the Media upsell panels.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @param bool   $available Whether a newer build is available.
	 * @param string $installed Installed version, empty when unreadable.
	 * @param string $latest    Latest release version, empty when unresolved.
	 */
	return (bool) apply_filters( 'bb_media_addons_update_available', $available, $installed, $latest );
}

/**
 * Swap a Media upsell card for the step an entitled site needs.
 *
 * Hooked on the field formatter, which only runs while the Media settings are
 * loaded over AJAX, so the entitlement lookup never runs on ordinary admin
 * requests. The upgrade card registered by bb_media_register_addons_upsell_panel()
 * stays as it is for sites whose plan does not include BuddyBoss Add-ons.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param array  $field_data Formatted field data bound for React.
 * @param array  $field      Registered field args.
 * @param string $feature_id Feature the field belongs to.
 *
 * @return array
 */
function bb_media_format_addons_upsell_field( $field_data, $field, $feature_id ) {
	if ( 'media' !== $feature_id || empty( $field['name'] ) || ! preg_match( '/^bb_media_(videos|documents|animated_gifs)_addons_upsell$/', $field['name'], $matches ) ) {
		return $field_data;
	}

	$panel_id = $matches[1];
	$state    = bb_media_get_addons_panel_state( $panel_id );

	if ( 'upgrade' === $state ) {
		return $field_data;
	}

	$title   = isset( $field['empty_state_title'] ) ? (string) $field['empty_state_title'] : '';
	$actions = function_exists( 'bb_admin_settings_get_addon_ajax_actions' ) ? bb_admin_settings_get_addon_ajax_actions() : array();
	$card    = array(
		'button_target'    => '_self',
		'addon_action'     => null,
		'addon_slug'       => null,
		'addon_nonce_key'  => null,
		'addon_busy_label' => null,
		// The plan already includes the feature, so the upgrade popup must not take over the button.
		'upgrade_modal'    => null,
	);

	switch ( $state ) {
		case 'install':
			$card['empty_state_description'] = sprintf(
				/* translators: %s: Feature name, e.g. "Videos". */
				__( '%s is included in your plan. Install the BuddyBoss Add-ons plugin to use it.', 'buddyboss' ),
				$title
			);
			$card['button_label']     = __( 'Install & Activate', 'buddyboss' );
			$card['button_url']       = bp_get_admin_url( 'admin.php?page=buddyboss-addons' );
			$card['addon_action']     = isset( $actions['install'] ) ? $actions['install'] : null;
			$card['addon_slug']       = 'buddyboss-addons';
			$card['addon_busy_label'] = __( 'Installing…', 'buddyboss' );
			break;

		case 'activate':
			$card['empty_state_description'] = sprintf(
				/* translators: %s: Feature name, e.g. "Videos". */
				__( '%s is included in your plan. Activate the BuddyBoss Add-ons plugin to use it.', 'buddyboss' ),
				$title
			);
			$card['button_label']     = __( 'Activate', 'buddyboss' );
			$card['button_url']       = self_admin_url( 'plugins.php' );
			$card['addon_action']     = isset( $actions['activate'] ) ? $actions['activate'] : null;
			$card['addon_slug']       = 'buddyboss-addons';
			$card['addon_busy_label'] = __( 'Activating…', 'buddyboss' );
			break;

		case 'update':
			// The update handler only moves an add-on that is below the required version,
			// so offer the one-click update only then.
			$plugins     = get_plugins();
			$installed   = isset( $plugins['buddyboss-addons/buddyboss-addons.php']['Version'] ) ? (string) $plugins['buddyboss-addons/buddyboss-addons.php']['Version'] : '';
			$below_floor = '' !== $installed && version_compare( $installed, bb_get_addons_required_version(), '<' );

			if ( $below_floor && bb_media_addons_update_available() ) {
				$card['empty_state_description'] = sprintf(
					/* translators: %s: Feature name, e.g. "Videos". */
					__( '%s is included in your plan, but the installed version of the BuddyBoss Add-ons plugin does not provide it. Update the plugin to use it.', 'buddyboss' ),
					$title
				);
				$card['button_label']     = __( 'Update Plugin', 'buddyboss' );
				$card['button_url']       = self_admin_url( 'update-core.php' );
				$card['addon_action']     = 'bb_media_update_addons';
				$card['addon_slug']       = 'buddyboss-addons';
				$card['addon_nonce_key']  = 'ajaxNonce';
				$card['addon_busy_label'] = __( 'Updating…', 'buddyboss' );
			} else {
				// Nothing newer is known yet; the update check may be stale, and the
				// WordPress updates screen can re-run it.
				$card['empty_state_description'] = sprintf(
					/* translators: %s: Feature name, e.g. "Videos". */
					__( '%s is included in your plan, but the installed version of the BuddyBoss Add-ons plugin does not provide it. Check for a plugin update.', 'buddyboss' ),
					$title
				);
				$card['button_label'] = __( 'Go to Updates', 'buddyboss' );
				$card['button_url']   = self_admin_url( 'update-core.php' );
			}
			break;

		case 'unavailable':
		default:
			$card['empty_state_description'] = sprintf(
				/* translators: %s: Feature name, e.g. "Videos". */
				__( '%s is included in your plan, but the BuddyBoss Add-ons plugin has not enabled it. Check your license.', 'buddyboss' ),
				$title
			);
			$card['button_label'] = __( 'Manage License', 'buddyboss' );
			$card['button_url']   = bp_get_admin_url( 'admin.php?page=buddyboss-license' );
			break;
	}

	if ( ! empty( $card['button_url'] ) ) {
		$card['button_url'] = esc_url_raw( $card['button_url'] );
	}

	return array_merge( $field_data, $card );
}
add_filter( 'bb_admin_settings_format_field_data', 'bb_media_format_addons_upsell_field', 10, 3 );

/**
 * Drop the "UPGRADE START" header badge from a Media upsell panel the plan already covers.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param array  $section_data  Formatted section data bound for React.
 * @param array  $section       Registered section args.
 * @param string $feature_id    Feature ID.
 * @param string $side_panel_id Side panel ID.
 *
 * @return array
 */
function bb_media_format_addons_upsell_section( $section_data, $section, $feature_id, $side_panel_id ) {
	if (
		'media' !== $feature_id ||
		empty( $section_data['pro_notice'] ) ||
		'' === bb_media_get_addons_module_for_panel( $side_panel_id ) ||
		'upgrade' === bb_media_get_addons_panel_state( $side_panel_id )
	) {
		return $section_data;
	}

	unset( $section_data['pro_notice'] );

	return $section_data;
}
add_filter( 'bb_admin_settings_format_section_data', 'bb_media_format_addons_upsell_section', 10, 4 );

/**
 * AJAX: update BuddyBoss Add-ons so the Media panels it provides become available.
 *
 * Mothership has install/activate actions but no update action, so this is
 * Platform-owned and verifies the `bb_admin_settings` nonce (`ajaxNonce`).
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @return void
 */
function bb_media_ajax_update_addons() {
	check_ajax_referer( 'bb_admin_settings', '_ajax_nonce' );

	if ( ! bp_current_user_can( 'bp_moderate' ) || ! current_user_can( 'update_plugins' ) ) {
		wp_send_json_error( array( 'message' => __( 'Sorry, you are not allowed to update plugins.', 'buddyboss' ) ), 403 );
	}

	$result   = bb_update_addons_bundle_to_required_version();
	$messages = array(
		'inactive'     => __( 'Activate the BuddyBoss Add-ons plugin first.', 'buddyboss' ),
		'up_to_date'   => __( 'The BuddyBoss Add-ons plugin is already up to date.', 'buddyboss' ),
		'vcs_checkout' => __( 'The BuddyBoss Add-ons plugin is a version-control checkout. Update it manually.', 'buddyboss' ),
		'not_entitled' => __( 'The BuddyBoss Add-ons plugin is not available under your current license.', 'buddyboss' ),
		'no_package'   => __( 'No newer version of the BuddyBoss Add-ons plugin is available yet.', 'buddyboss' ),
		'failed'       => __( 'Plugin update failed. Please try again.', 'buddyboss' ),
	);

	if ( 'updated' !== $result ) {
		wp_send_json_error( array( 'message' => isset( $messages[ $result ] ) ? $messages[ $result ] : $messages['failed'] ) );
	}

	wp_send_json_success();
}
add_action( 'wp_ajax_bb_media_update_addons', 'bb_media_ajax_update_addons' );
