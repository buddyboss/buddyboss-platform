<?php
/**
 * BuddyBoss Admin Settings - Profile Navigation Panel.
 *
 * Registers sections and fields for the Profile Navigation side panel.
 *
 * Values are stored in the nested `bp_nouveau_appearance` option
 * (keys: user_nav_display, user_default_tab, user_nav_order, user_nav_hide)
 * to maintain backward compatibility with the legacy Customizer.
 *
 * @package BuddyBoss\Core\Administration
 * @since BuddyBoss 3.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Register Profile Navigation panel sections and fields.
 *
 * @since BuddyBoss 3.0.0
 */
function bb_members_register_profile_navigation_panel_fields() {

	// -------------------------------------------------------------------------
	// SECTION: Profile Navigation Settings.
	// -------------------------------------------------------------------------
	bb_register_feature_section(
		'members',
		'profile_navigation',
		'profile_navigation_settings',
		array(
			'title' => __( 'Profile Navigation', 'buddyboss' ),
			'order' => 10,
		)
	);

	// FIELD: Layout — Display profile navigation vertically.
	bb_register_feature_field(
		'members',
		'profile_navigation',
		'profile_navigation_settings',
		array(
			'name'              => 'bb_user_nav_display',
			'label'             => __( 'Layout', 'buddyboss' ),
			'type'              => 'toggle',
			'description'       => __( 'Display the profile navigation vertically', 'buddyboss' ),
			'default'           => 0,
			'order'             => 10,
			'sanitize_callback' => 'absint',
		)
	);

	// FIELD: Default Tab.
	bb_register_feature_field(
		'members',
		'profile_navigation',
		'profile_navigation_settings',
		array(
			'name'              => 'bb_user_default_tab',
			'label'             => __( 'Default Tab', 'buddyboss' ),
			'description'       => __( 'Set the default navigation tab when viewing a member profile. The dropdown only shows tabs that are available to all members.', 'buddyboss' ),
			'type'              => 'select',
			'default'           => 'profile',
			'options'           => array(), // Populated at AJAX time via bb_member_navigation_enrich_field_data().
			'order'             => 20,
			'sanitize_callback' => 'sanitize_key',
		)
	);

	// -------------------------------------------------------------------------
	// SECTION: Navigation Order.
	// -------------------------------------------------------------------------
	bb_register_feature_section(
		'members',
		'profile_navigation',
		'profile_navigation_order',
		array(
			'title'    => __( 'Navigation Order', 'buddyboss' ),
			'order'    => 20,
			'help_url' => '638247',
		)
	);

	// FIELD: Navigation Order (drag-and-drop checkbox list).
	bb_register_feature_field(
		'members',
		'profile_navigation',
		'profile_navigation_order',
		array(
			'name'              => 'bb_user_nav_order',
			'label'             => '',
			'type'              => 'checkbox_list',
			'default'           => array(),
			'options'           => array(), // Populated at AJAX time via bb_member_navigation_enrich_field_data().
			'order'             => 10,
			'sanitize_callback' => 'bb_sanitize_member_nav_order',
		)
	);

	/**
	 * Fires after the Profile Navigation panel fields are registered.
	 *
	 * @since BuddyBoss 3.0.0
	 */
	do_action( 'bb_members_navigation_after_register_fields' );
}

// =========================================================================
// HELPERS
// =========================================================================

/**
 * Build the select options for the Default Tab dropdown.
 *
 * Reuses the same logic as the removed legacy Customizer and
 * preserves the `user_default_tab_options_list` filter for Pro extensibility.
 *
 * Note: Media-related keys ('media', 'document', 'video') are kept as-is
 * to maintain backward compatibility with the stored bp_nouveau_appearance
 * option. The members component maps them to nav slugs at render time.
 *
 * @since BuddyBoss 3.0.0
 *
 * @return array Array of [ 'value' => slug, 'label' => name ] items.
 */
function bb_get_member_default_tab_options() {
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

	if ( bp_is_active( 'media' ) && function_exists( 'bp_is_profile_document_support_enabled' ) && bp_is_profile_document_support_enabled() ) {
		$options['document'] = __( 'Documents', 'buddyboss' );
	}

	if ( bp_is_active( 'media' ) && function_exists( 'bp_is_profile_video_support_enabled' ) && bp_is_profile_video_support_enabled() ) {
		$options['video'] = __( 'Videos', 'buddyboss' );
	}

	/**
	 * Filters the list of available default tab options for member profiles.
	 *
	 * Matches the legacy Customizer filter for Pro extensibility.
	 *
	 * @since BuddyBoss 3.0.0
	 *
	 * @param array $options Associative array of slug => label.
	 */
	$options = apply_filters( 'user_default_tab_options_list', $options );

	// Convert to Settings 2.0 select format.
	$formatted = array();
	foreach ( $options as $slug => $label ) {
		$formatted[] = array(
			'value' => $slug,
			'label' => $label,
		);
	}

	return $formatted;
}

/**
 * Build the checkbox_list options for Navigation Order.
 *
 * Builds a static list of primary profile nav items based on active
 * components. This mirrors what the legacy Customizer renders via
 * bp_nouveau_member_customizer_nav(), which is not available in admin
 * AJAX context (requires a front-end user profile page to be loaded).
 *
 * Items whose parent feature is inactive get `disabled => true` and
 * `badge_label => 'Hidden'`.
 *
 * @since BuddyBoss 3.0.0
 *
 * @return array Array of checkbox_list option items.
 */
function bb_get_member_profile_nav_items_for_settings() {
	static $cached_options = null;
	if ( null !== $cached_options && ! doing_action( 'bb_admin_save_feature_settings_after' ) ) {
		return $cached_options;
	}

	// Core primary nav items for user profiles, in display order. Mirrors the
	// legacy Customizer's bp_nouveau_member_customizer_nav() output, which
	// excludes account-context items (messages, notifications, settings) —
	// those don't belong on a *visitor's view* of someone else's profile, see
	// bp-templates/bp-nouveau/includes/customizer-controls.php:127-130.
	// Other components (invites, etc.) add their items via the filter below.
	$all_items = array(
		'profile'   => array(
			'label'     => __( 'Profile', 'buddyboss' ),
			'component' => 'xprofile',
		),
		'activity'  => array(
			'label'     => __( 'Timeline', 'buddyboss' ),
			'component' => 'activity',
		),
		'friends'   => array(
			'label'     => __( 'Connections', 'buddyboss' ),
			'component' => 'friends',
		),
		'groups'    => array(
			'label'     => __( 'Groups', 'buddyboss' ),
			'component' => 'groups',
		),
		'forums'    => array(
			'label'     => __( 'Forums', 'buddyboss' ),
			'component' => 'forums',
		),
		'photos'    => array(
			'label'     => __( 'Photos', 'buddyboss' ),
			'component' => 'media',
		),
		'documents' => array(
			'label'     => __( 'Documents', 'buddyboss' ),
			'component' => 'media',
		),
		'videos'    => array(
			'label'     => __( 'Videos', 'buddyboss' ),
			'component' => 'video',
		),
	);

	// Merge in items registered by third-party plugins through the standard
	// BP `bp_core_new_nav_item()` flow. The legacy Customizer picks these up
	// automatically because its iframe loads the front-end (where `bp_setup_nav`
	// runs for the displayed user); admin AJAX has no displayed user, so we
	// bootstrap one and read the resulting nav registry. Account-context slugs
	// are filtered out to match the legacy display.
	foreach ( bb_get_third_party_profile_nav_items() as $slug => $item ) {
		if ( ! isset( $all_items[ $slug ] ) ) {
			$all_items[ $slug ] = $item;
		}
	}

	/**
	 * Filters the member profile nav items for Settings 2.0 Navigation Order.
	 *
	 * Components that own a profile nav tab should use this filter to register
	 * their item. Each item is keyed by its nav slug and contains:
	 *   - 'label'     (string) Display label.
	 *   - 'component' (string) Component ID used for active/inactive checks.
	 *
	 * @since BuddyBoss 3.0.0
	 *
	 * @param array $all_items Associative array of slug => item data.
	 */
	$all_items = apply_filters( 'bb_member_profile_nav_items', $all_items );

	$inactive_slugs = bb_get_inactive_member_nav_slugs();

	$options = array();
	foreach ( $all_items as $slug => $item ) {
		// Skip nav items whose parent component is inactive.
		// Legacy Customizer only shows nav from active components
		// (inactive components never register their nav items).
		if ( in_array( $slug, $inactive_slugs, true ) ) {
			continue;
		}

		$options[] = array(
			'value' => $slug,
			'label' => $item['label'],
		);
	}

	$cached_options = $options;

	return $cached_options;
}

/**
 * Get nav item slugs whose parent feature is inactive.
 *
 * Used to mark items as "Hidden" in the Navigation Order list.
 *
 * @since BuddyBoss 3.0.0
 *
 * @return array Array of nav item slugs that are inactive.
 */
function bb_get_inactive_member_nav_slugs() {
	$inactive = array();

	if ( ! bp_is_active( 'activity' ) ) {
		$inactive[] = 'activity';
	}

	if ( ! bp_is_active( 'friends' ) ) {
		$inactive[] = 'friends';
	}

	if ( ! bp_is_active( 'groups' ) ) {
		$inactive[] = 'groups';
	}

	if ( ! bp_is_active( 'forums' ) ) {
		$inactive[] = 'forums';
	}

	if ( ! bp_is_active( 'media' ) || ! function_exists( 'bp_is_profile_media_support_enabled' ) || ! bp_is_profile_media_support_enabled() ) {
		$inactive[] = 'photos';
	}

	if ( ! bp_is_active( 'media' ) || ! function_exists( 'bp_is_profile_document_support_enabled' ) || ! bp_is_profile_document_support_enabled() ) {
		$inactive[] = 'documents';
	}

	if ( ! bp_is_active( 'video' ) || ! function_exists( 'bp_is_profile_video_support_enabled' ) || ! bp_is_profile_video_support_enabled() ) {
		$inactive[] = 'videos';
	}

	/**
	 * Filters the list of inactive member profile nav slugs.
	 *
	 * Components that register nav items via `bb_member_profile_nav_items`
	 * should also hook here to mark their slug inactive when their component
	 * is disabled.
	 *
	 * @since BuddyBoss 3.0.0
	 *
	 * @param array $inactive Array of nav item slugs whose component is inactive.
	 */
	return apply_filters( 'bb_member_inactive_nav_slugs', $inactive );
}

// =========================================================================
// AJAX DATA ENRICHMENT — Read from bp_nouveau_appearance
// =========================================================================

/**
 * Populate profile navigation field values from bp_nouveau_appearance.
 *
 * The standard Settings 2.0 get loop reads from individual wp_options
 * via bp_get_option(). Profile navigation values live inside the nested
 * `bp_nouveau_appearance` option, so we intercept and override here.
 *
 * @since BuddyBoss 3.0.0
 *
 * @param array  $field_data Formatted field data.
 * @param array  $field      Original field registration data.
 * @param string $feature_id Feature ID.
 *
 * @return array Modified field data.
 */
function bb_member_navigation_enrich_field_data( $field_data, $field, $feature_id = '' ) {
	if ( 'members' !== $feature_id ) {
		return $field_data;
	}

	$field_name = isset( $field_data['name'] ) ? $field_data['name'] : '';

	// Only handle profile navigation fields.
	if ( ! in_array( $field_name, array( 'bb_user_nav_display', 'bb_user_default_tab', 'bb_user_nav_order' ), true ) ) {
		return $field_data;
	}

	// Fetch appearance settings once for all fields.
	$appearance = bp_nouveau_get_appearance_settings();

	switch ( $field_name ) {
		case 'bb_user_nav_display':
			$field_data['value'] = absint( isset( $appearance['user_nav_display'] ) ? $appearance['user_nav_display'] : 0 );
			break;

		case 'bb_user_default_tab':
			$field_data['options'] = bb_get_member_default_tab_options();
			$field_data['value']   = sanitize_key( isset( $appearance['user_default_tab'] ) ? $appearance['user_default_tab'] : 'profile' );
			break;

		case 'bb_user_nav_order':
			// Populate options at AJAX time (admin_init has fired).
			$options               = bb_get_member_profile_nav_items_for_settings();
			$field_data['options'] = $options;
			$field_data['value']   = bb_build_member_nav_order_value( $options, $appearance );
			break;
	}

	return $field_data;
}

add_filter( 'bb_admin_settings_format_field_data', 'bb_member_navigation_enrich_field_data', 10, 3 );

/**
 * Build the checkbox_list value for profile nav order.
 *
 * Reads saved order from bp_nouveau_appearance[user_nav_order]
 * and builds an ordered { slug: 1/0 } object for the React component.
 *
 * @since BuddyBoss 3.0.0
 *
 * @param array $options    Optional. Pre-fetched nav item options.
 * @param array $appearance Optional. Pre-fetched appearance settings.
 *
 * @return array Associative array of slug => 1/0 in saved order.
 */
function bb_build_member_nav_order_value( $options = array(), $appearance = array() ) {
	if ( empty( $options ) ) {
		$options = bb_get_member_profile_nav_items_for_settings();
	}

	if ( empty( $options ) ) {
		return array();
	}

	if ( empty( $appearance ) ) {
		$appearance = bp_nouveau_get_appearance_settings();
	}

	// Get saved order (array of slugs) and hidden items.
	$saved_order = isset( $appearance['user_nav_order'] ) ? $appearance['user_nav_order'] : array();
	$saved_hide  = isset( $appearance['user_nav_hide'] ) ? $appearance['user_nav_hide'] : array();

	if ( ! is_array( $saved_order ) ) {
		$saved_order = array();
	}
	if ( ! is_array( $saved_hide ) ) {
		$saved_hide = array();
	}

	// Build a map of all available nav items.
	$option_map = array();
	foreach ( $options as $opt ) {
		$option_map[ $opt['value'] ] = $opt;
	}

	// Start with saved order (preserving user's drag order).
	$ordered_value = array();
	foreach ( $saved_order as $slug ) {
		if ( isset( $option_map[ $slug ] ) ) {
			// Hidden (toggled off) if in the hide list.
			$ordered_value[ $slug ] = in_array( $slug, $saved_hide, true ) ? 0 : 1;
			unset( $option_map[ $slug ] );
		}
	}

	// Append remaining items not in saved order (newly added nav items).
	foreach ( $option_map as $slug => $opt ) {
		$ordered_value[ $slug ] = in_array( $slug, $saved_hide, true ) ? 0 : 1;
	}

	return $ordered_value;
}

// =========================================================================
// SAVE — Write back to bp_nouveau_appearance
// =========================================================================

/**
 * Save profile navigation settings back to bp_nouveau_appearance.
 *
 * The standard save loop writes each field as a separate wp_option.
 * We intercept after save to merge values back into the nested
 * `bp_nouveau_appearance` option and delete the temporary per-field options.
 *
 * @since BuddyBoss 3.0.0
 *
 * @param string $feature_id Feature ID.
 * @param array  $settings   Full submitted settings (JSON decoded).
 * @param array  $saved      Keys and values saved to options by core.
 */
function bb_member_navigation_save_settings( $feature_id, $settings, $saved ) {
	if ( 'members' !== $feature_id ) {
		return;
	}

	// Check if any profile navigation fields were submitted.
	$nav_fields = array( 'bb_user_nav_display', 'bb_user_default_tab', 'bb_user_nav_order' );
	$has_nav    = false;
	foreach ( $nav_fields as $field_name ) {
		if ( array_key_exists( $field_name, $settings ) ) {
			$has_nav = true;
			break;
		}
	}

	if ( ! $has_nav ) {
		return;
	}

	// Read current appearance option — use bp_nouveau_get_appearance_settings() so
	// defaults are merged in. Using bp_get_option() directly would lose defaults on
	// the first save if the option does not yet exist in the database.
	$appearance = bp_nouveau_get_appearance_settings();
	if ( ! is_array( $appearance ) ) {
		$appearance = array();
	}

	// Update user_nav_display.
	if ( array_key_exists( 'bb_user_nav_display', $settings ) ) {
		$appearance['user_nav_display'] = absint( $settings['bb_user_nav_display'] );
	}

	// Update user_nav_order and user_nav_hide (must be processed before default tab validation).
	$hide = isset( $appearance['user_nav_hide'] ) ? $appearance['user_nav_hide'] : array();
	if ( array_key_exists( 'bb_user_nav_order', $settings ) ) {
		$nav_order_data = $settings['bb_user_nav_order'];

		// JSON-decode if string.
		if ( is_string( $nav_order_data ) ) {
			$nav_order_data = json_decode( $nav_order_data, true );
		}

		if ( is_array( $nav_order_data ) ) {
			// Extract ordered slugs and hidden slugs.
			$order       = array();
			$hide        = array();
			$valid_slugs = wp_list_pluck( bb_get_member_profile_nav_items_for_settings(), 'value' );

			foreach ( $nav_order_data as $slug => $enabled ) {
				$slug = sanitize_key( $slug );

				// Only accept known nav item slugs.
				if ( ! in_array( $slug, $valid_slugs, true ) ) {
					continue;
				}

				$order[] = $slug;
				if ( ! absint( $enabled ) ) {
					$hide[] = $slug;
				}
			}

			$appearance['user_nav_order'] = $order;
			$appearance['user_nav_hide']  = $hide;
		}
	}

	// Update user_default_tab — validate against allowed options and hidden items.
	$default_tab = isset( $appearance['user_default_tab'] ) ? $appearance['user_default_tab'] : 'profile';
	if ( array_key_exists( 'bb_user_default_tab', $settings ) ) {
		$valid_tabs = wp_list_pluck( bb_get_member_default_tab_options(), 'value' );
		$tab        = sanitize_key( $settings['bb_user_default_tab'] );

		if ( in_array( $tab, $valid_tabs, true ) ) {
			$default_tab = $tab;
		}
	}

	// Map default tab keys to nav order slugs for hidden-tab validation.
	// Default tab uses legacy keys ('media', 'document', 'video') while
	// nav order uses display slugs ('photos', 'documents', 'videos').
	$default_tab_to_nav_slug = array(
		'media'    => 'photos',
		'document' => 'documents',
		'video'    => 'videos',
	);

	$nav_slug_for_check = isset( $default_tab_to_nav_slug[ $default_tab ] ) ? $default_tab_to_nav_slug[ $default_tab ] : $default_tab;

	// If the default tab is now hidden, reset to the first visible tab.
	if ( is_array( $hide ) && in_array( $nav_slug_for_check, $hide, true ) ) {
		$nav_order   = isset( $appearance['user_nav_order'] ) ? $appearance['user_nav_order'] : array();
		$default_tab = 'profile'; // Last resort fallback.

		// Reverse map: nav slug → legacy default-tab key.
		// user_default_tab stores legacy keys (media, document, video)
		// while user_nav_order stores display slugs (photos, documents, videos).
		$nav_slug_to_tab_key = array_flip( $default_tab_to_nav_slug );

		foreach ( $nav_order as $slug ) {
			if ( ! in_array( $slug, $hide, true ) ) {
				$default_tab = isset( $nav_slug_to_tab_key[ $slug ] ) ? $nav_slug_to_tab_key[ $slug ] : $slug;
				break;
			}
		}
	}
	$appearance['user_default_tab'] = $default_tab;

	// Save the merged appearance option.
	bp_update_option( 'bp_nouveau_appearance', $appearance );

	// Delete the temporary per-field options that the core save loop created.
	foreach ( $nav_fields as $field_name ) {
		if ( array_key_exists( $field_name, $saved ) ) {
			bp_delete_option( $field_name );
		}
	}
}

add_action( 'bb_admin_save_feature_settings_after', 'bb_member_navigation_save_settings', 10, 3 );

// =========================================================================
// SANITIZE CALLBACK
// =========================================================================

/**
 * Sanitize the profile nav order checkbox_list value.
 *
 * Expects an associative array { slug: 0|1, ... }.
 * Sanitizes keys and normalizes values to 0 or 1.
 *
 * @since BuddyBoss 3.0.0
 *
 * @param mixed $value The submitted value.
 *
 * @return array Sanitized array of slug => 0|1.
 */
function bb_sanitize_member_nav_order( $value ) {
	if ( is_string( $value ) ) {
		$value = json_decode( $value, true );
	}

	return bb_members_sanitize_toggle_list( $value );
}

/**
 * Read third-party-registered profile nav items from the live BP nav registry.
 *
 * The legacy Customizer at `customize.php?autofocus[section]=bp_nouveau_user_primary_nav`
 * iterates `buddypress()->members->nav->get_primary()`, which is populated when
 * `bp_setup_nav` fires for a displayed user — the customizer iframe loads the
 * front-end, so that registration runs automatically. Admin AJAX has no
 * displayed user, so plugins like "BuddyPress User Profile Tabs Creator Pro"
 * (which call `bp_core_new_nav_item()` from a `bp_setup_nav` hook) never get
 * a chance to register, and their tabs are silently missing from the React
 * Settings 2.0 Navigation Order list.
 *
 * This helper temporarily sets `displayed_user->id` to the current admin user,
 * fires `bp_setup_nav`, reads the resulting primary nav, then restores the
 * previous state. Items whose slug already exists in our canonical hardcoded
 * list are skipped; account-context slugs (`messages`, `notifications`,
 * `settings`) are filtered out to match the legacy Customizer's display.
 *
 * State restoration runs through `try/finally` so a fatal in a third-party
 * `bp_setup_nav` callback can't leave the admin request mid-impersonation.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @return array Associative array keyed by nav slug, each entry containing
 *               `label` and `component` keys (matching the shape of the
 *               canonical hardcoded list).
 */
function bb_get_third_party_profile_nav_items() {
	static $cached = null;
	if ( null !== $cached ) {
		return $cached;
	}
	$cached = array();

	$bp = buddypress();
	if ( empty( $bp->members ) || empty( $bp->members->nav ) ) {
		return $cached;
	}

	// Account-context items legacy excludes from the *profile* primary nav.
	// Filtering here also means an admin user who happens to have these set up
	// via `bp_setup_nav` doesn't see Messages/Notifications/Settings leak into
	// the Settings 2.0 list.
	$excluded_slugs = array( 'messages', 'notifications', 'settings' );

	$current_user_id = (int) get_current_user_id();
	if ( ! $current_user_id ) {
		return $cached;
	}

	// Snapshot pre-impersonation state so the finally block can fully restore
	// it — including the "displayed_user property didn't exist" case (the
	// default when admin AJAX runs without a profile context).
	$prev_displayed_existed = isset( $bp->displayed_user );
	$prev_displayed_id      = $prev_displayed_existed && isset( $bp->displayed_user->id )
		? (int) $bp->displayed_user->id
		: 0;

	try {
		// Bootstrap displayed_user so component setup_nav callbacks that gate on
		// `bp_displayed_user_id()` register their items. The members component's
		// guard at class-bp-members-component.php:setup_nav() requires
		// `is_user_logged_in() || bp_is_user()` — we already satisfy the first.
		if ( ! $prev_displayed_existed ) {
			$bp->displayed_user = new stdClass();
		}
		$bp->displayed_user->id = $current_user_id;

		/**
		 * Standard BP nav-registration action. Components and third-party
		 * plugins hook this and call `bp_core_new_nav_item()`. Firing it
		 * here in admin AJAX populates `buddypress()->members->nav` so we
		 * can read what plugins like BP User Profile Tabs Creator Pro
		 * registered.
		 */
		do_action( 'bp_setup_nav' );

		$primary = $bp->members->nav->get_primary();
		if ( empty( $primary ) ) {
			return $cached;
		}

		foreach ( $primary as $item ) {
			$slug = is_object( $item ) ? $item->slug : ( isset( $item['slug'] ) ? $item['slug'] : '' );
			$name = is_object( $item ) ? $item->name : ( isset( $item['name'] ) ? $item['name'] : '' );

			if ( '' === $slug || '' === $name || in_array( $slug, $excluded_slugs, true ) ) {
				continue;
			}

			$cached[ $slug ] = array(
				'label'     => wp_strip_all_tags( (string) $name ),
				// Use the slug as the component id when the nav object doesn't
				// expose a component — best-effort, only used by
				// bb_get_inactive_member_nav_slugs() which already short-circuits
				// for unknown slugs.
				'component' => $slug,
			);
		}
	} finally {
		// Always restore, even if a third-party callback fatal'd. Re-create
		// vs. unset based on the pre-call snapshot so we don't leave behind
		// an empty stdClass when the property was absent before.
		if ( $prev_displayed_existed ) {
			$bp->displayed_user->id = $prev_displayed_id;
		} else {
			unset( $bp->displayed_user );
		}
	}

	return $cached;
}
