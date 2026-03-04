<?php
/**
 * BuddyBoss Admin Settings - Groups Navigation Panel.
 *
 * Registers the Group Navigation side panel section and fields
 * for the Groups feature in Settings 2.0.
 *
 * Values are stored in the nested `bp_nouveau_appearance` option
 * (keys: group_nav_display, group_default_tab, group_nav_order, group_nav_hide)
 * to maintain backward compatibility with the legacy Customizer.
 *
 * @package BuddyBoss\Core\Administration
 * @since BuddyBoss [BBVERSION]
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Register Group Navigation panel section and fields.
 *
 * @since BuddyBoss [BBVERSION]
 */
function bb_groups_register_navigation_panel_fields() {

	// -------------------------------------------------------------------------
	// SECTION: Group Navigation Settings.
	// -------------------------------------------------------------------------
	bb_register_feature_section(
		'groups',
		'group_navigation',
		'group_navigation_settings',
		array(
			'title' => __( 'Group Navigation', 'buddyboss' ),
			'order' => 10,
		)
	);

	// FIELD: Layout — Display group navigation vertically.
	bb_register_feature_field(
		'groups',
		'group_navigation',
		'group_navigation_settings',
		array(
			'name'              => 'bb_group_nav_display',
			'label'             => __( 'Layout', 'buddyboss' ),
			'type'              => 'toggle',
			'description'       => __( 'Display the group navigation vertically', 'buddyboss' ),
			'default'           => 0,
			'order'             => 10,
			'sanitize_callback' => 'absint',
		)
	);

	// FIELD: Default Tab.
	bb_register_feature_field(
		'groups',
		'group_navigation',
		'group_navigation_settings',
		array(
			'name'              => 'bb_group_default_tab',
			'label'             => __( 'Default Tab', 'buddyboss' ),
			'description'       => __( 'The dropdown only shows tabs that are available to all groups.', 'buddyboss' ),
			'type'              => 'select',
			'default'           => 'members',
			'options'           => array(), // Populated at AJAX time via bb_group_navigation_enrich_field_data() so options reflect current component state.
			'order'             => 20,
			'sanitize_callback' => 'sanitize_key',
		)
	);

	// -------------------------------------------------------------------------
	// SECTION: Navigation Order.
	// -------------------------------------------------------------------------
	bb_register_feature_section(
		'groups',
		'group_navigation',
		'group_navigation_order',
		array(
			'title' => __( 'Navigation Order', 'buddyboss' ),
			'order' => 20,
		)
	);

	// FIELD: Navigation Order (drag-and-drop checkbox list).
	bb_register_feature_field(
		'groups',
		'group_navigation',
		'group_navigation_order',
		array(
			'name'              => 'bb_group_nav_order',
			'label'             => '',
			'type'              => 'checkbox_list',
			'default'           => array(),
			'options'           => array(), // Populated at AJAX time via bb_group_navigation_enrich_field_data().
			'order'             => 10,
			'sanitize_callback' => 'bb_sanitize_group_nav_order',
		)
	);

	/**
	 * Fires after the Group Navigation panel fields are registered.
	 *
	 * @since BuddyBoss [BBVERSION]
	 */
	do_action( 'bb_groups_navigation_after_register_fields' );
}

// =========================================================================
// HELPERS
// =========================================================================

/**
 * Build the select options for the Default Tab dropdown.
 *
 * Reuses the same logic as the removed legacy Customizer and
 * preserves the `group_default_tab_options_list` filter for Pro extensibility.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @return array Array of [ 'value' => slug, 'label' => name ] items.
 */
function bb_get_group_default_tab_options() {
	// Base options — same logic as the removed legacy Customizer controls.
	if ( bp_is_active( 'activity' ) ) {
		$options = apply_filters(
			'group_default_tab_options_list',
			array(
				'members'  => __( 'Members', 'buddyboss' ),
				'activity' => __( 'Feed', 'buddyboss' ),
			)
		);
	} else {
		$options = apply_filters(
			'group_default_tab_options_list',
			array(
				'members' => __( 'Members', 'buddyboss' ),
			)
		);
	}

	if ( bp_is_active( 'media' ) && bp_is_group_media_support_enabled() ) {
		$options['photos'] = __( 'Photos', 'buddyboss' );
	}

	if ( bp_is_active( 'media' ) && bp_is_group_albums_support_enabled() ) {
		$options['albums'] = __( 'Albums', 'buddyboss' );
	}

	if ( bp_is_active( 'media' ) && bp_is_group_document_support_enabled() ) {
		$options['documents'] = __( 'Documents', 'buddyboss' );
	}

	if ( bp_is_active( 'media' ) && bp_is_group_video_support_enabled() ) {
		$options['videos'] = __( 'Videos', 'buddyboss' );
	}

	// Convert to Settings 2.0 select format.
	// Note: Hidden nav items are NOT filtered here — the React UI handles
	// live filtering based on Navigation Order toggles so options can be
	// restored immediately when an item is toggled back on.
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
 * Uses BP_Nouveau_Customizer_Group_Nav to get all registered group
 * nav items (including third-party BP_Group_Extension items and items
 * added via the `bp_nouveau_customizer_group_nav_items` filter).
 *
 * Items whose parent feature is inactive get `disabled => true` and
 * `badge_label => 'Hidden'`.
 *
 * Note: The "Send Messages" nav item depends on the current admin user's
 * membership in the fetched group (inherited from BP_Nouveau_Customizer_Group_Nav).
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @return array Array of checkbox_list option items.
 */
function bb_get_group_nav_items_for_settings() {
	static $cached_options = null;
	if ( null !== $cached_options ) {
		return $cached_options;
	}

	// Try persistent transient cache. Cleared by bb_clear_group_nav_items_cache()
	// on feature activation/deactivation and when the first group is created.
	$transient_key = 'bb_group_nav_items_for_settings';
	$transient     = get_transient( $transient_key );
	if ( false !== $transient ) {
		$cached_options = $transient;
		return $cached_options;
	}

	if ( ! class_exists( 'BP_Nouveau_Customizer_Group_Nav' ) ) {
		return array();
	}

	// We need a real group to build nav items. Use the most recently created group.
	// BP_Nouveau_Customizer_Group_Nav only needs any valid group_id to instantiate its
	// nav items — the actual nav tabs are determined by registered extensions, not
	// per-group content. The most recent group is used as a proxy; this may miss
	// group-specific extensions but covers the standard use-case.
	$groups = groups_get_groups(
		array(
			'per_page'    => 1,
			'orderby'     => 'date_created',
			'order'       => 'DESC',
			'show_hidden' => true,
		)
	);

	if ( empty( $groups['groups'] ) ) {
		// @todo: On fresh installs with no groups, this returns an empty list, leaving the
		// Navigation Order field blank until a group is created. Consider falling back to
		// the default BP_Nouveau_Customizer_Group_Nav slug list so the field is useful immediately.
		return array();
	}

	$group_id = $groups['groups'][0]->id;

	// Build nav via the same class the Customizer uses.
	$group_nav = new BP_Nouveau_Customizer_Group_Nav( $group_id );

	// Get nav items in saved order.
	// Note: If the constructor failed (e.g., admin_init not fired), this returns empty.
	$nav_items = $group_nav->get_group_nav();

	if ( empty( $nav_items ) ) {
		return array();
	}

	// Determine which features are inactive for "Hidden" badge.
	$inactive_slugs = bb_get_inactive_group_nav_slugs();

	$options = array();
	foreach ( $nav_items as $nav_item ) {
		$slug      = $nav_item->slug;
		$is_hidden = in_array( $slug, $inactive_slugs, true );

		$options[] = array(
			'value'       => $slug,
			'label'       => $nav_item->name,
			'disabled'    => $is_hidden,
			'badge_label' => $is_hidden ? __( 'Hidden', 'buddyboss' ) : '',
		);
	}

	$cached_options = $options;

	// Persist to transient so the groups_get_groups() + BP_Nouveau_Customizer_Group_Nav
	// instantiation is not repeated on every AJAX settings request.
	// Empty results are not cached so a fresh install picks up the first group immediately.
	if ( ! empty( $cached_options ) ) {
		set_transient( $transient_key, $cached_options, DAY_IN_SECONDS );
	}

	return $cached_options;
}

/**
 * Clear the group nav items settings transient cache.
 *
 * Called when a feature is activated/deactivated (which changes the list of
 * available nav items) and when the first group is created on a fresh install.
 *
 * @since BuddyBoss [BBVERSION]
 */
function bb_clear_group_nav_items_cache() {
	delete_transient( 'bb_group_nav_items_for_settings' );
}
add_action( 'bb_feature_activated', 'bb_clear_group_nav_items_cache' );
add_action( 'bb_feature_deactivated', 'bb_clear_group_nav_items_cache' );
add_action( 'groups_group_create_complete', 'bb_clear_group_nav_items_cache' );

/**
 * Get nav item slugs whose parent feature is inactive.
 *
 * Used to mark items as "Hidden" in the Navigation Order list.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @return array Array of nav item slugs that are inactive.
 */
function bb_get_inactive_group_nav_slugs() {
	$inactive = array();

	if ( ! bp_is_active( 'activity' ) ) {
		$inactive[] = 'activity';
	}

	if ( ! bp_is_active( 'media' ) || ! bp_is_group_media_support_enabled() ) {
		$inactive[] = 'photos';
	}

	if ( ! bp_is_active( 'media' ) || ! bp_is_group_video_support_enabled() ) {
		$inactive[] = 'videos';
	}

	if ( ! bp_is_active( 'media' ) || ! bp_is_group_albums_support_enabled() ) {
		$inactive[] = 'albums';
	}

	if ( ! bp_is_active( 'media' ) || ! bp_is_group_document_support_enabled() ) {
		$inactive[] = 'documents';
	}

	if ( ! bp_is_active( 'forums' ) || ! function_exists( 'bbp_is_group_forums_active' ) || ! bbp_is_group_forums_active() ) {
		$inactive[] = get_option( '_bbp_forum_slug', 'forum' );
	}

	// bp_disable_group_messages() returns true when group messages are ENABLED.
	if ( ! bp_is_active( 'messages' ) || ! bp_disable_group_messages() ) {
		$inactive[] = 'messages';
	}

	if ( ! bp_enable_group_hierarchies() ) {
		$inactive[] = 'subgroups';
	}

	return $inactive;
}

// =========================================================================
// AJAX DATA ENRICHMENT — Read from bp_nouveau_appearance
// =========================================================================

/**
 * Populate group navigation field values from bp_nouveau_appearance.
 *
 * The standard Settings 2.0 get loop reads from individual wp_options
 * via bp_get_option(). Group navigation values live inside the nested
 * `bp_nouveau_appearance` option, so we intercept and override here.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param array  $field_data Formatted field data.
 * @param array  $field      Original field registration data.
 * @param string $feature_id Feature ID.
 *
 * @return array Modified field data.
 */
function bb_group_navigation_enrich_field_data( $field_data, $field, $feature_id = '' ) {
	if ( 'groups' !== $feature_id ) {
		return $field_data;
	}

	$field_name = isset( $field_data['name'] ) ? $field_data['name'] : '';

	// Only handle group navigation fields.
	if ( ! in_array( $field_name, array( 'bb_group_nav_display', 'bb_group_default_tab', 'bb_group_nav_order' ), true ) ) {
		return $field_data;
	}

	// Fetch appearance settings once for all fields.
	$appearance = bp_nouveau_get_appearance_settings();

	switch ( $field_name ) {
		case 'bb_group_nav_display':
			$field_data['value'] = absint( isset( $appearance['group_nav_display'] ) ? $appearance['group_nav_display'] : 0 );
			break;

		case 'bb_group_default_tab':
			// Populate options at AJAX time so they reflect current component activation state.
			$field_data['options'] = bb_get_group_default_tab_options();
			$field_data['value']   = sanitize_key( isset( $appearance['group_default_tab'] ) ? $appearance['group_default_tab'] : 'members' );
			break;

		case 'bb_group_nav_order':
			// Populate options at AJAX time (admin_init has fired, so BP_Nouveau_Customizer_Group_Nav works).
			$options               = bb_get_group_nav_items_for_settings();
			$field_data['options'] = $options;
			$field_data['value']   = bb_build_group_nav_order_value( $options, $appearance );
			break;
	}

	return $field_data;
}

add_filter( 'bb_admin_settings_format_field_data', 'bb_group_navigation_enrich_field_data', 10, 3 );

/**
 * Build the checkbox_list value for group nav order.
 *
 * Reads saved order from bp_nouveau_appearance[group_nav_order]
 * and builds an ordered { slug: 1/0 } object for the React component.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param array $options    Optional. Pre-fetched nav item options from bb_get_group_nav_items_for_settings().
 * @param array $appearance Optional. Pre-fetched appearance settings from bp_nouveau_get_appearance_settings().
 *
 * @return array Associative array of slug => 1/0 in saved order.
 */
function bb_build_group_nav_order_value( $options = array(), $appearance = array() ) {
	if ( empty( $options ) ) {
		$options = bb_get_group_nav_items_for_settings();
	}

	if ( empty( $options ) ) {
		return array();
	}

	if ( empty( $appearance ) ) {
		$appearance = bp_nouveau_get_appearance_settings();
	}

	// Get saved order (array of slugs) and hidden items.
	$saved_order = isset( $appearance['group_nav_order'] ) ? $appearance['group_nav_order'] : array();
	$saved_hide  = isset( $appearance['group_nav_hide'] ) ? $appearance['group_nav_hide'] : array();

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
 * Save group navigation settings back to bp_nouveau_appearance.
 *
 * The standard save loop writes each field as a separate wp_option.
 * We intercept after save to merge values back into the nested
 * `bp_nouveau_appearance` option and delete the temporary per-field options.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param string $feature_id Feature ID.
 * @param array  $settings   Full submitted settings (JSON decoded).
 * @param array  $saved      Keys and values saved to options by core.
 */
function bb_group_navigation_save_settings( $feature_id, $settings, $saved ) {
	if ( 'groups' !== $feature_id ) {
		return;
	}

	// Check if any group navigation fields were submitted.
	$nav_fields = array( 'bb_group_nav_display', 'bb_group_default_tab', 'bb_group_nav_order' );
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

	// Update group_nav_display.
	if ( array_key_exists( 'bb_group_nav_display', $settings ) ) {
		$appearance['group_nav_display'] = absint( $settings['bb_group_nav_display'] );
	}

	// Update group_nav_order and group_nav_hide (must be processed before default tab validation).
	$hide = isset( $appearance['group_nav_hide'] ) ? $appearance['group_nav_hide'] : array();
	if ( array_key_exists( 'bb_group_nav_order', $settings ) ) {
		$nav_order_data = $settings['bb_group_nav_order'];

		// JSON-decode if string.
		if ( is_string( $nav_order_data ) ) {
			$nav_order_data = json_decode( $nav_order_data, true );
		}

		if ( is_array( $nav_order_data ) ) {
			// Extract ordered slugs and hidden slugs.
			$order = array();
			$hide  = array();

			foreach ( $nav_order_data as $slug => $enabled ) {
				$slug    = sanitize_key( $slug );
				$order[] = $slug;
				if ( ! absint( $enabled ) ) {
					$hide[] = $slug;
				}
			}

			$appearance['group_nav_order'] = $order;
			$appearance['group_nav_hide']  = $hide;
		}
	}

	// Update group_default_tab — validate against allowed options and hidden items.
	$default_tab = isset( $appearance['group_default_tab'] ) ? $appearance['group_default_tab'] : 'members';
	if ( array_key_exists( 'bb_group_default_tab', $settings ) ) {
		$valid_tabs = wp_list_pluck( bb_get_group_default_tab_options(), 'value' );
		$tab        = sanitize_key( $settings['bb_group_default_tab'] );

		if ( in_array( $tab, $valid_tabs, true ) ) {
			$default_tab = $tab;
		}
	}

	// If the default tab is now hidden, reset to the first visible tab.
	if ( is_array( $hide ) && in_array( $default_tab, $hide, true ) ) {
		$nav_order   = isset( $appearance['group_nav_order'] ) ? $appearance['group_nav_order'] : array();
		$default_tab = 'members'; // Last resort fallback.
		foreach ( $nav_order as $slug ) {
			if ( ! in_array( $slug, $hide, true ) ) {
				$default_tab = $slug;
				break;
			}
		}
	}
	$appearance['group_default_tab'] = $default_tab;

	// Save the merged appearance option.
	bp_update_option( 'bp_nouveau_appearance', $appearance );

	// Delete the temporary per-field options that the core save loop created.
	foreach ( $nav_fields as $field_name ) {
		if ( array_key_exists( $field_name, $saved ) ) {
			bp_delete_option( $field_name );
		}
	}
}

// bb_sanitize_group_nav_order() is defined in callbacks.php alongside other sanitize callbacks.
add_action( 'bb_admin_save_feature_settings_after', 'bb_group_navigation_save_settings', 10, 3 );
