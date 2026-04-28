<?php
/**
 * BuddyBoss Admin Settings - Members Callbacks.
 *
 * Sanitize callback functions for Members feature settings.
 *
 * @package BuddyBoss\Core\Administration
 * @since BuddyBoss [BBVERSION]
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Sanitize display name format setting.
 *
 * Accepts only allowed display name format values.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param mixed $value The value to sanitize.
 *
 * @return string Sanitized display name format value.
 */
function bb_members_sanitize_display_name_format( $value ) {
	$value   = sanitize_text_field( $value );
	$allowed = array( 'first_name', 'first_last_name', 'nickname' );

	if ( ! in_array( $value, $allowed, true ) ) {
		return 'first_name';
	}

	return $value;
}

/**
 * Sanitize profile slug format setting.
 *
 * Accepts only 'username' or 'unique_id'.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param mixed $value The value to sanitize.
 *
 * @return string Sanitized slug format value.
 */
function bb_members_sanitize_slug_format( $value ) {
	$value   = sanitize_text_field( $value );
	$allowed = array( 'username', 'unique_identifier' );

	if ( ! in_array( $value, $allowed, true ) ) {
		return 'username';
	}

	return $value;
}

/**
 * Sanitize profile avatar type setting.
 *
 * Accepts only 'BuddyBoss' or 'WordPress'.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param mixed $value The value to sanitize.
 *
 * @return string Sanitized avatar type value.
 */
function bb_members_sanitize_avatar_type( $value ) {
	$value   = sanitize_text_field( $value );
	$allowed = array( 'BuddyBoss', 'WordPress' );

	if ( ! in_array( $value, $allowed, true ) ) {
		return 'BuddyBoss';
	}

	return $value;
}

/**
 * Sanitize default profile avatar type setting.
 *
 * Accepts only allowed default avatar type values.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param mixed $value The value to sanitize.
 *
 * @return string Sanitized default avatar type value.
 */
function bb_members_sanitize_default_avatar_type( $value ) {
	$value   = sanitize_text_field( $value );
	$allowed = array( 'buddyboss', 'display-name', 'custom' );

	if ( ! in_array( $value, $allowed, true ) ) {
		return 'buddyboss';
	}

	return $value;
}

/**
 * Sanitize default profile cover type setting.
 *
 * Accepts only allowed cover type values.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param mixed $value The value to sanitize.
 *
 * @return string Sanitized default cover type value.
 */
function bb_members_sanitize_default_cover_type( $value ) {
	$value   = sanitize_text_field( $value );
	$allowed = array( 'buddyboss', 'none', 'custom' );

	if ( ! in_array( $value, $allowed, true ) ) {
		return 'buddyboss';
	}

	return $value;
}

/**
 * Sanitize member layout format setting.
 *
 * Accepts only allowed layout format values.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param mixed $value The value to sanitize.
 *
 * @return string Sanitized layout format value.
 */
function bb_members_sanitize_layout_format( $value ) {
	$value   = sanitize_text_field( $value );
	$allowed = array( 'list_grid', 'grid', 'list' );

	if ( ! in_array( $value, $allowed, true ) ) {
		return 'list_grid';
	}

	return $value;
}

/**
 * Sanitize member layout default format setting.
 *
 * Accepts only 'grid' or 'list'.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param mixed $value The value to sanitize.
 *
 * @return string Sanitized default format value.
 */
function bb_members_sanitize_default_format( $value ) {
	$value   = sanitize_text_field( $value );
	$allowed = array( 'grid', 'list' );

	if ( ! in_array( $value, $allowed, true ) ) {
		return 'grid';
	}

	return $value;
}

/**
 * Sanitize member toggle list elements.
 *
 * Expects an associative array where keys are element slugs and values are 0/1.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param mixed $value The value to sanitize.
 *
 * @return array Sanitized array of element_slug => 0|1.
 */
function bb_members_sanitize_toggle_list( $value ) {
	if ( ! is_array( $value ) ) {
		return array();
	}

	$sanitized = array();
	foreach ( $value as $key => $val ) {
		$sanitized[ sanitize_key( $key ) ] = absint( $val ) ? 1 : 0;
	}

	return $sanitized;
}

/**
 * Sanitize profile header style setting.
 *
 * Accepts only 'left' or 'centered'.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param mixed $value The value to sanitize.
 *
 * @return string Sanitized header style value.
 */
function bb_members_sanitize_header_style( $value ) {
	$value   = sanitize_text_field( $value );
	$allowed = array( 'left', 'centered' );

	if ( ! in_array( $value, $allowed, true ) ) {
		return 'left';
	}

	return $value;
}

/**
 * Sanitize profile cover image width setting.
 *
 * Accepts only 'default' or 'full'.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param mixed $value The value to sanitize.
 *
 * @return string Sanitized cover width value.
 */
function bb_members_sanitize_cover_width( $value ) {
	$value   = sanitize_text_field( $value );
	$allowed = array( 'default', 'full' );

	if ( ! in_array( $value, $allowed, true ) ) {
		return 'default';
	}

	return $value;
}

/**
 * Sanitize profile cover image height setting.
 *
 * Accepts only 'small' or 'large'.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param mixed $value The value to sanitize.
 *
 * @return string Sanitized cover height value.
 */
function bb_members_sanitize_cover_height( $value ) {
	$value   = sanitize_text_field( $value );
	$allowed = array( 'small', 'large' );

	if ( ! in_array( $value, $allowed, true ) ) {
		return 'small';
	}

	return $value;
}

// =========================================================================
// POST-SAVE HOOKS (bb_admin_save_feature_settings_after)
//
// Priority ordering — must run in this order:
// 2  - bb_members_sync_connection_component          (sync friends to bp-active-components + bb-active-features)
// 3  - bb_members_handle_avatar_type_wordpress       (force-disable gravatar when avatar type is WordPress)
// 5  - bb_members_validate_image_settings_after_save (revert invalid avatar/cover selections)
// 10 - bb_members_handle_display_name_format_change  (update xprofile field meta on name format change)
// 10 - bb_members_handle_slug_format_change          (flush caches when profile slug format changes)
//
// PRE-SAVE HOOK (bb_admin_settings_before_get_feature):
// 10 - bb_members_capture_pre_save_state             (capture avatar/cover/slug values before save loop)
//
// RESPONSE FILTER (bb_admin_save_feature_settings_response):
// 10 - bb_members_sync_reverted_image_values         (sync reverted avatar/cover/gravatar values to AJAX response)
//
// PRO HOOKS (registered in buddyboss-platform-pro):
// 5  - bb_reset_primary_action_on_connection_toggle   (reset directory primary action when connections disabled)
// =========================================================================

/**
 * Sync friends component activation state when connection toggle is saved.
 *
 * Adds or removes 'friends' from bp-active-components based on the
 * bb_enable_member_connections toggle value. Runs at priority 2 so
 * component state is updated before other connection-related save hooks.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param string $feature_id Feature ID being saved.
 * @param array  $settings   Full submitted settings.
 * @param array  $saved      Keys and values saved by core.
 *
 * @return void
 */
function bb_members_sync_connection_component( $feature_id, $settings, $saved ) {
	if ( 'members' !== $feature_id || ! array_key_exists( 'bb_enable_member_connections', $settings ) ) {
		return;
	}

	$enable = ! empty( $settings['bb_enable_member_connections'] );

	// Sync to bp-active-components (legacy, so bp_is_active('friends') works).
	$active_components = bp_get_option( 'bp-active-components', array() );
	if ( ! is_array( $active_components ) ) {
		$active_components = array();
	}

	$was_active = ! empty( $active_components['friends'] );

	// Only run install/uninstall when the state actually changes.
	// bp_core_install() is expensive (creates DB tables, flushes cache, flushes rewrite rules).
	if ( $enable !== $was_active ) {
		$previously_active = $active_components;

		if ( $enable ) {
			$active_components['friends'] = 1;
		} else {
			unset( $active_components['friends'] );
		}

		// Replicate legacy component toggle: install pages, update option, uninstall removed.
		// Schema file is not auto-loaded in AJAX context, so require it explicitly.
		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		require_once buddypress()->plugin_dir . '/bp-core/admin/bp-core-admin-schema.php';

		bp_core_install( $active_components );
		bp_core_add_page_mappings( $active_components );
		bp_update_option( 'bp-active-components', $active_components );

		$uninstalled = array_diff_key( $previously_active, $active_components );
		if ( ! empty( $uninstalled ) ) {
			bp_core_uninstall( $uninstalled );
		}
	}

	// Sync to bb-active-features (primary storage for Settings 2.0).
	$active_features = bp_get_option( 'bb-active-features', array() );
	if ( ! is_array( $active_features ) ) {
		$active_features = array();
	}

	$active_features['friends'] = $enable ? 1 : 0;
	bp_update_option( 'bb-active-features', $active_features );
}

add_action( 'bb_admin_save_feature_settings_after', 'bb_members_sync_connection_component', 2, 3 );

/**
 * Get or set members pre-save state for post-save comparison.
 *
 * Uses a static variable instead of a PHP global for cross-hook state.
 * Call with no arguments to read, or pass an array to set.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param array|null $state Optional. Pass an array to set state. Omit or null to read.
 *
 * @return array The pre-save state array.
 */
function bb_members_get_pre_save_state( $state = null ) {
	static $saved_state = array();

	if ( null !== $state && is_array( $state ) ) {
		$saved_state = $state;
	}

	return $saved_state;
}

/**
 * Capture members settings state before save for post-save comparison.
 *
 * Captures the current value of settings that need before/after comparison
 * (e.g., avatar type, cover type, slug format) before the save loop updates them.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param string $feature_id Feature ID being saved.
 *
 * @return void
 */
function bb_members_capture_pre_save_state( $feature_id ) {
	if ( 'members' !== $feature_id ) {
		return;
	}

	bb_members_get_pre_save_state(
		array(
			'profile_avatar_type'         => bb_get_profile_avatar_type(),
			'disable_avatar_uploads'      => bp_disable_avatar_uploads(),
			'default_profile_avatar_type' => bb_get_default_profile_avatar_type(),
			'enable_profile_gravatar'     => bp_enable_profile_gravatar(),
			'default_profile_cover_type'  => bb_get_default_profile_cover_type(),
			'profile_slug_format'         => bb_get_profile_slug_format(),
			'display_name_format'         => bp_get_option( 'bp-display-name-format', 'first_name' ),
		)
	);
}

add_action( 'bb_admin_settings_before_save_feature', 'bb_members_capture_pre_save_state', 10, 1 );

/**
 * Handle display name format change after save.
 *
 * When the display name format changes, update xprofile field meta
 * for visibility and required status based on the selected format.
 * Replicates legacy BP_Admin_Setting_Xprofile::settings_save() logic.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param string $feature_id Feature ID.
 * @param array  $settings   Full submitted settings.
 * @param array  $saved      Keys and values saved by core.
 *
 * @return void
 */
function bb_members_handle_display_name_format_change( $feature_id, $settings, $saved ) {
	if ( 'members' !== $feature_id ) {
		return;
	}

	if ( ! array_key_exists( 'bp-display-name-format', $settings ) ) {
		return;
	}

	$display_name_format = bp_get_option( 'bp-display-name-format', 'first_name' );

	// Skip field meta updates if the format hasn't actually changed.
	$pre_save_state = bb_members_get_pre_save_state();
	$format_before  = ! empty( $pre_save_state['display_name_format'] ) ? $pre_save_state['display_name_format'] : '';
	if ( $format_before === $display_name_format ) {
		return;
	}

	if ( 'first_last_name' === $display_name_format || 'first_name' === $display_name_format ) {
		$firstname_field_id = bp_xprofile_firstname_field_id();
		bp_xprofile_update_field_meta( $firstname_field_id, 'default_visibility', 'public' );
		bp_xprofile_update_field_meta( $firstname_field_id, 'allow_custom_visibility', 'disabled' );

		// Make the first name field required if not already.
		$field = xprofile_get_field( $firstname_field_id );
		if ( $field ) {
			$field->is_required = true;
			$field->save();
		}

		if ( 'first_last_name' === $display_name_format ) {
			$lastname_field_id = bp_xprofile_lastname_field_id();
			bp_xprofile_update_field_meta( $lastname_field_id, 'default_visibility', 'public' );
		}
	} elseif ( 'nickname' === $display_name_format ) {
		$nickname_field_id = bp_xprofile_nickname_field_id();
		bp_xprofile_update_field_meta( $nickname_field_id, 'default_visibility', 'public' );
		bp_xprofile_update_field_meta( $nickname_field_id, 'allow_custom_visibility', 'disabled' );
	}
}

add_action( 'bb_admin_save_feature_settings_after', 'bb_members_handle_display_name_format_change', 10, 3 );

/**
 * Handle avatar type WordPress selection after save.
 *
 * When avatar type is set to WordPress, force-disable Gravatar setting.
 * Replicates legacy BP_Admin_Setting_Xprofile::settings_save() logic.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param string $feature_id Feature ID.
 * @param array  $settings   Full submitted settings.
 * @param array  $saved      Keys and values saved by core.
 *
 * @return void
 */
function bb_members_handle_avatar_type_wordpress( $feature_id, $settings, $saved ) {
	if ( 'members' !== $feature_id ) {
		return;
	}

	if ( ! array_key_exists( 'bp-profile-avatar-type', $settings ) ) {
		return;
	}

	$avatar_type = bb_get_profile_avatar_type();

	if ( 'WordPress' === $avatar_type ) {
		bp_update_option( 'bp-enable-profile-gravatar', '' );
	}
}

add_action( 'bb_admin_save_feature_settings_after', 'bb_members_handle_avatar_type_wordpress', 3, 3 );

/**
 * Validate profile avatar and cover image type selections after save.
 *
 * In the legacy admin, the Upload Custom Avatar block is always in the DOM; when the user
 * selects Custom they see it immediately and upload, then Save (hidden field has the URL).
 * In Settings 2.0 the upload is a separate step: user selects Custom → must Save first →
 * then the Upload section stays visible and they can upload. So we only revert when they
 * *had* Custom with an image and then removed the image. We do not revert when they just
 * selected Custom (no image yet), so the selection persists and they can upload after Save.
 *
 * - If avatar type is 'custom' and no custom avatar URL: revert only when previous type was
 *   also 'custom' (they removed the image). Otherwise keep 'custom' so Upload section stays open.
 * - If avatar type is 'display-name' but no image editor available, revert to 'buddyboss'.
 * - Same for cover: revert only when prev was 'custom' and image is now empty.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param string $feature_id Feature ID.
 * @param array  $settings   Full submitted settings.
 * @param array  $saved      Keys and values saved by core.
 *
 * @return void
 */
function bb_members_validate_image_settings_after_save( $feature_id, $settings, $saved ) {
	if ( 'members' !== $feature_id ) {
		return;
	}

	$has_avatar = array_key_exists( 'bp-default-profile-avatar-type', $settings );
	$has_cover  = array_key_exists( 'bp-default-profile-cover-type', $settings );

	if ( ! $has_avatar && ! $has_cover ) {
		return;
	}

	$pre_save_state = bb_members_get_pre_save_state();

	// --- Avatar type validation ---
	// Only validate avatar sub-options when avatar type is BuddyBoss.
	// When WordPress is selected, these sub-options are irrelevant.
	$current_avatar_type = isset( $settings['bp-profile-avatar-type'] ) ? $settings['bp-profile-avatar-type'] : bb_get_profile_avatar_type();

	if ( $has_avatar && 'BuddyBoss' === $current_avatar_type ) {
		$avatar_type = bb_get_default_profile_avatar_type();

		// Revert only when they had Custom and then removed the image. Do not revert when they
		// just selected Custom (so Save keeps Custom and Upload Custom Avatar section stays visible).
		if ( 'custom' === $avatar_type ) {
			$custom_avatar = bb_get_default_custom_upload_profile_avatar();
			$prev_type     = ! empty( $pre_save_state['default_profile_avatar_type'] ) ? $pre_save_state['default_profile_avatar_type'] : 'buddyboss';

			if ( empty( $custom_avatar ) && 'custom' === $prev_type ) {
				bp_update_option( 'bp-default-profile-avatar-type', 'buddyboss' );
			}
		}

		// If 'display-name' but no image editor available, revert.
		if ( 'display-name' === $avatar_type && ! wp_image_editor_supports() ) {
			bp_update_option( 'bp-default-profile-avatar-type', 'buddyboss' );
		}
	}
	// When avatar type is WordPress, no avatar sub-option validation is needed
	// because the UI hides these fields when WordPress is selected.

	// --- Cover type validation ---
	if ( $has_cover ) {
		$cover_type = bb_get_default_profile_cover_type();

		// Revert only when they had Custom and then removed the image.
		if ( 'custom' === $cover_type ) {
			$custom_cover = bb_get_default_custom_upload_profile_cover();
			$prev_type    = ! empty( $pre_save_state['default_profile_cover_type'] ) ? $pre_save_state['default_profile_cover_type'] : 'buddyboss';

			if ( empty( $custom_cover ) && 'custom' === $prev_type ) {
				bp_update_option( 'bp-default-profile-cover-type', 'buddyboss' );
			}
		}
	}
}

add_action( 'bb_admin_save_feature_settings_after', 'bb_members_validate_image_settings_after_save', 5, 3 );

/**
 * Sync reverted image setting values into the save response.
 *
 * After bb_members_validate_image_settings_after_save() may revert avatar/cover
 * type values, this filter updates only the affected keys in the response so
 * React stays in sync.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param array  $response_data Response data being sent.
 * @param string $feature_id    Feature ID.
 * @param array  $settings      Full submitted settings.
 * @param array  $saved         Keys and values saved by core.
 *
 * @return array Modified response data.
 */
function bb_members_sync_reverted_image_values( $response_data, $feature_id, $settings, $saved ) {
	if ( 'members' !== $feature_id ) {
		return $response_data;
	}

	// Only re-read the specific keys that post-save hooks may have modified:
	// - bp-default-profile-avatar-type / bp-default-profile-cover-type: reverted by bb_members_validate_image_settings_after_save() at priority 5.
	// - bp-enable-profile-gravatar: force-disabled by bb_members_handle_avatar_type_wordpress() at priority 3 when avatar type is WordPress.
	$revertable_keys = array(
		'bp-default-profile-avatar-type',
		'bp-default-profile-cover-type',
		'bp-enable-profile-gravatar',
	);

	foreach ( $revertable_keys as $key ) {
		if ( isset( $saved[ $key ] ) ) {
			$actual = bp_get_option( $key, $saved[ $key ] );
			if ( $actual !== $saved[ $key ] ) {
				$response_data['saved'][ $key ] = $actual;
			}
		}
	}

	return $response_data;
}

add_filter( 'bb_admin_save_feature_settings_response', 'bb_members_sync_reverted_image_values', 10, 4 );

/**
 * Handle slug format change after save.
 *
 * When the profile slug format changes, flush all caches including
 * Performance API cache. Replicates legacy settings_save() logic.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param string $feature_id Feature ID.
 * @param array  $settings   Full submitted settings.
 * @param array  $saved      Keys and values saved by core.
 *
 * @return void
 */
function bb_members_handle_slug_format_change( $feature_id, $settings, $saved ) {
	if ( 'members' !== $feature_id ) {
		return;
	}

	if ( ! array_key_exists( 'bb_profile_slug_format', $settings ) ) {
		return;
	}

	$pre_save_state = bb_members_get_pre_save_state();
	$slug_before    = ! empty( $pre_save_state['profile_slug_format'] ) ? $pre_save_state['profile_slug_format'] : '';
	$slug_after     = bb_get_profile_slug_format();

	if ( $slug_before !== $slug_after ) {
		// Flush all caches — slug format affects every member URL site-wide.
		// Legacy BP_Admin_Setting_Xprofile::settings_save() uses the same approach.
		wp_cache_flush();

		// Purge all the cache for API (member URLs changed).
		if ( class_exists( 'BuddyBoss\Performance\Cache' ) ) {
			BuddyBoss\Performance\Cache::instance()->purge_all();
		}
	}
}

add_action( 'bb_admin_save_feature_settings_after', 'bb_members_handle_slug_format_change', 10, 3 );

// =========================================================================
// AJAX-TIME FIELD ENRICHMENT
//
// These filters hook into bb_admin_settings_format_field_data and run
// during bb_admin_get_feature_settings AJAX requests — NOT at page load.
//
// Two reasons a field may need enrichment:
//
// 1. TIMING — The data source (e.g., xprofile fields, component state) is
// not available at registration time (bp_loaded priority 4). Options are
// registered as empty [] and populated here when everything is loaded.
//
// 2. PRO KEY MISMATCH — Several fields (header elements, directory elements,
// directory actions) are Pro-only features. Pro stores its enabled/disabled
// state under its own DB keys. Settings 2.0 registers new distinct DB keys.
// On first open, get_option() on the new key returns empty, so React would
// show all toggles as OFF even on a live site with a saved Pro config.
// The back-fill reads from Pro's getter functions (which read the Pro keys)
// and injects the correct { slug: 0|1 } map. Once the admin saves through
// Settings 2.0, the new key is written and the back-fill is skipped.
// =========================================================================

/**
 * Convert an element array to a Settings 2.0 option format.
 *
 * Public utility used by Pro and extensions to transform element arrays
 * (from bb_get_profile_header_elements(), bb_get_member_directory_elements(),
 * bb_get_member_directory_profile_actions()) into the Settings 2.0 options format.
 * Platform itself no longer calls this directly — it is kept as a public
 * API for Pro and third-party plugins that need to transform element arrays
 * into Settings 2.0 option format.
 *
 * @api
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param array $elements Array of elements with 'element_label', 'element_name', 'element_class' keys.
 *
 * @return array Options array with 'label', 'value', and optional 'disabled' keys.
 */
function bb_members_elements_to_options( $elements ) {
	$options = array();

	foreach ( $elements as $element ) {
		$option = array(
			'label' => $element['element_label'],
			'value' => $element['element_name'],
		);

		if ( ! empty( $element['element_class'] ) && false !== strpos( $element['element_class'], 'bp-hide' ) ) {
			$option['disabled'] = true;
		}

		$options[] = $option;
	}

	return $options;
}

/**
 * Inject dynamic cover image dimensions into the upload help text at AJAX time.
 *
 * At field registration time (bp_loaded priority 5), theme compat features are
 * not yet available (they register at priority 12). This filter runs when the
 * admin fetches settings via AJAX, by which time theme compat is fully loaded.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param array  $field_data Formatted field data.
 * @param array  $field      Original field registration data.
 * @param string $feature_id Feature ID.
 *
 * @return array Modified field data.
 */
function bb_members_enrich_cover_upload_help_text( $field_data, $field, $feature_id ) {
	if ( 'members' !== $feature_id || 'bp-default-profile-cover-type' !== ( isset( $field_data['name'] ) ? $field_data['name'] : '' ) ) {
		return $field_data;
	}

	if ( ! empty( $field_data['upload_config'] ) && empty( $field_data['upload_config']['help_text'] ) ) {
		$cover_dimensions                         = bb_attachments_get_default_custom_cover_image_dimensions( 'members' );
		$field_data['upload_config']['help_text'] = sprintf(
			/* translators: 1: width in pixels, 2: height in pixels. */
			__( 'Upload a default cover image (JPG or PNG, recommended size: %1$s×%2$s px).', 'buddyboss' ),
			(int) $cover_dimensions['width'],
			(int) $cover_dimensions['height']
		);
	}

	return $field_data;
}

add_filter( 'bb_admin_settings_format_field_data', 'bb_members_enrich_cover_upload_help_text', 10, 3 );

/**
 * Allow member profile url_getter functions in the image upload allowlist.
 *
 * The AJAX handler restricts which functions can be called as url_getters for
 * security. This filter adds the two member-profile-specific getters so that
 * saved custom avatar and cover image URLs are resolved and returned to React.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param array $allowed_url_getters List of allowed url_getter function names.
 *
 * @return array Extended list including member profile getter functions.
 */
function bb_members_allow_image_url_getters( $allowed_url_getters ) {
	$allowed_url_getters[] = 'bb_get_default_custom_upload_profile_avatar';
	$allowed_url_getters[] = 'bb_get_default_custom_upload_profile_cover';

	return $allowed_url_getters;
}

add_filter( 'bb_admin_settings_allowed_url_getters', 'bb_members_allow_image_url_getters' );

/**
 * Inject dynamic avatar dimensions into the upload help text at AJAX time.
 *
 * `bp_core_avatar_full_width`/`bp_core_avatar_full_height` return 0 at registration time (bp_loaded
 * priority 5) because avatar setup hasn't run yet. This filter runs during
 * the AJAX request when dimensions are fully available.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param array  $field_data Formatted field data.
 * @param array  $field      Original field registration data.
 * @param string $feature_id Feature ID.
 *
 * @return array Modified field data.
 */
function bb_members_enrich_avatar_upload_help_text( $field_data, $field, $feature_id ) {
	if ( 'members' !== $feature_id || 'bp-default-profile-avatar-type' !== ( isset( $field_data['name'] ) ? $field_data['name'] : '' ) ) {
		return $field_data;
	}

	if ( ! empty( $field_data['upload_config'] ) && empty( $field_data['upload_config']['help_text'] ) ) {
		$field_data['upload_config']['help_text'] = sprintf(
			/* translators: 1: width in pixels, 2: height in pixels. */
			__( 'Upload a default avatar image (JPG or PNG, recommended size: %1$s×%2$s px).', 'buddyboss' ),
			absint( bp_core_avatar_full_width() ),
			absint( bp_core_avatar_full_height() )
		);
	}

	return $field_data;
}

add_filter( 'bb_admin_settings_format_field_data', 'bb_members_enrich_avatar_upload_help_text', 10, 3 );

/**
 * Inject profile type options at AJAX time.
 *
 * The bp_get_active_member_types() runs a WP_Query for bp-member-type CPT which
 * should not execute on every admin page load. This filter populates the
 * Default Profile Type select options only when the admin fetches settings.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param array  $field_data Formatted field data.
 * @param array  $field      Original field registration data.
 * @param string $feature_id Feature ID.
 *
 * @return array Modified field data.
 */
function bb_members_enrich_profile_type_options( $field_data, $field, $feature_id ) {
	if ( 'members' !== $feature_id || 'bp-member-type-default-on-registration' !== ( isset( $field_data['name'] ) ? $field_data['name'] : '' ) ) {
		return $field_data;
	}

	if ( ! empty( $field_data['options'] ) ) {
		return $field_data;
	}

	$type_options = array(
		array(
			'label' => __( 'Select', 'buddyboss' ),
			'value' => '',
		),
	);

	$member_types = bp_get_active_member_types();

	// Prime post caches to avoid N+1 get_post_meta() calls in the loop.
	if ( ! empty( $member_types ) ) {
		_prime_post_caches( $member_types, false, true );
	}

	foreach ( $member_types as $member_type_id ) {
		$type_name        = bp_get_member_type_key( $member_type_id );
		$member_type_name = get_post_meta( $member_type_id, '_bp_member_type_label_name', true );

		if ( ! empty( $type_name ) ) {
			$type_options[] = array(
				'label' => $member_type_name,
				'value' => $type_name,
			);
		}
	}

	$field_data['options'] = $type_options;

	return $field_data;
}

add_filter( 'bb_admin_settings_format_field_data', 'bb_members_enrich_profile_type_options', 10, 3 );

/**
 * Push updated profile-types-enabled flag back to the React layer after save.
 *
 * The Profile Type Redirects section on the Login Redirects panel hides via a
 * section-level conditional that reads `window.bbAdminData.isProfileTypesEnabled`.
 * That flag is set on page load via wp_localize_script, but it must also stay
 * fresh whenever the `bp-member-type-enable-disable` toggle is saved — from
 * any feature page in the SPA. We attach the current value to every save
 * response that touched the toggle so the React layer can merge it into
 * window.bbAdminData and re-evaluate section conditionals without a reload.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param array  $response_data Response data being returned to the React app.
 * @param string $feature_id    Feature ID that initiated the save.
 * @param array  $settings      Full submitted settings.
 * @param array  $saved         Keys and values saved to options by core.
 *
 * @return array Filtered response data, possibly with bbAdminDataUpdates appended.
 */
function bb_members_push_profile_types_flag_after_save( $response_data, $feature_id, $settings, $saved ) {
	if ( ! is_array( $saved ) || ! array_key_exists( 'bp-member-type-enable-disable', $saved ) ) {
		return $response_data;
	}

	if ( ! isset( $response_data['bbAdminDataUpdates'] ) || ! is_array( $response_data['bbAdminDataUpdates'] ) ) {
		$response_data['bbAdminDataUpdates'] = array();
	}

	$response_data['bbAdminDataUpdates']['isProfileTypesEnabled'] = (
		bp_is_active( 'xprofile' )
		&& function_exists( 'bp_member_type_enable_disable' )
		&& bp_member_type_enable_disable()
	);

	return $response_data;
}

add_filter( 'bb_admin_save_feature_settings_response', 'bb_members_push_profile_types_flag_after_save', 10, 4 );
