<?php
/**
 * BuddyBoss Admin Settings - Groups Callbacks.
 *
 * Sanitize callback functions for Groups feature settings.
 *
 * @package BuddyBoss\Core\Administration
 * @since BuddyBoss [BBVERSION]
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Sanitize group layout format setting.
 *
 * Accepts only allowed layout format values.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param mixed $value The value to sanitize.
 *
 * @return string Sanitized layout format value.
 */
function bb_groups_sanitize_layout_format( $value ) {
	$value   = sanitize_text_field( $value );
	$allowed = array( 'list_grid', 'grid', 'list' );

	if ( ! in_array( $value, $allowed, true ) ) {
		return 'grid';
	}

	return $value;
}

/**
 * Sanitize group layout default format setting.
 *
 * Accepts only 'grid' or 'list'.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param mixed $value The value to sanitize.
 *
 * @return string Sanitized default format value.
 */
function bb_groups_sanitize_default_format( $value ) {
	$value   = sanitize_text_field( $value );
	$allowed = array( 'grid', 'list' );

	if ( ! in_array( $value, $allowed, true ) ) {
		return 'grid';
	}

	return $value;
}

/**
 * Sanitize group avatar type setting.
 *
 * Accepts only allowed avatar type values.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param mixed $value The value to sanitize.
 *
 * @return string Sanitized avatar type value.
 */
function bb_groups_sanitize_avatar_type( $value ) {
	$value   = sanitize_text_field( $value );
	$allowed = array( 'buddyboss', 'group-name', 'custom' );

	if ( ! in_array( $value, $allowed, true ) ) {
		return 'buddyboss';
	}

	return $value;
}

/**
 * Sanitize group cover type setting.
 *
 * Accepts only allowed cover type values.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param mixed $value The value to sanitize.
 *
 * @return string Sanitized cover type value.
 */
function bb_groups_sanitize_cover_type( $value ) {
	$value   = sanitize_text_field( $value );
	$allowed = array( 'buddyboss', 'none', 'custom' );

	if ( ! in_array( $value, $allowed, true ) ) {
		return 'buddyboss';
	}

	return $value;
}

/**
 * Sanitize group header style setting.
 *
 * Accepts only 'left' or 'centered'.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param mixed $value The value to sanitize.
 *
 * @return string Sanitized header style value.
 */
function bb_groups_sanitize_header_style( $value ) {
	$value   = sanitize_text_field( $value );
	$allowed = array( 'left', 'centered' );

	if ( ! in_array( $value, $allowed, true ) ) {
		return 'left';
	}

	return $value;
}

/**
 * Sanitize group grid style setting.
 *
 * Accepts only 'left' or 'centered'.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param mixed $value The value to sanitize.
 *
 * @return string Sanitized grid style value.
 */
function bb_groups_sanitize_grid_style( $value ) {
	$value   = sanitize_text_field( $value );
	$allowed = array( 'left', 'centered' );

	if ( ! in_array( $value, $allowed, true ) ) {
		return 'left';
	}

	return $value;
}

/**
 * Sanitize group toggle list elements (headers, directory elements).
 *
 * Expects an associative array where keys are element slugs and values are 0/1.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param mixed $value The value to sanitize.
 *
 * @return array Sanitized array of element_slug => 0|1.
 */
function bb_groups_sanitize_toggle_list( $value ) {
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
 * Sanitize group cover image width setting.
 *
 * Accepts only 'default' or 'full'.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param mixed $value The value to sanitize.
 *
 * @return string Sanitized cover width value.
 */
function bb_groups_sanitize_cover_width( $value ) {
	$value   = sanitize_text_field( $value );
	$allowed = array( 'default', 'full' );

	if ( ! in_array( $value, $allowed, true ) ) {
		return 'default';
	}

	return $value;
}

/**
 * Sanitize group cover image height setting.
 *
 * Accepts only 'small' or 'large'.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param mixed $value The value to sanitize.
 *
 * @return string Sanitized cover height value.
 */
function bb_groups_sanitize_cover_height( $value ) {
	$value   = sanitize_text_field( $value );
	$allowed = array( 'small', 'large' );

	if ( ! in_array( $value, $allowed, true ) ) {
		return 'small';
	}

	return $value;
}

// =========================================================================
// POST-SAVE VALIDATION HOOKS
// =========================================================================

/**
 * Validate group avatar and cover image type selections after save.
 *
 * Replicates the legacy BP_Admin_Setting_Groups::settings_save() validation:
 * - If avatar type is 'custom' but no custom avatar URL exists, revert to previous type.
 * - If avatar type is 'group-name' but no image editor is available, revert to previous type.
 * - If cover type is 'custom' but no custom cover URL exists, revert to previous type.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param string $feature_id Feature ID.
 * @param array  $settings   Full submitted settings.
 * @param array  $saved      Keys and values saved by core.
 */
function bb_groups_validate_image_settings_after_save( $feature_id, $settings, $saved ) {
	if ( 'groups' !== $feature_id ) {
		return;
	}

	// Only validate if avatar type or cover type was submitted.
	$has_avatar = array_key_exists( 'bp-default-group-avatar-type', $settings );
	$has_cover  = array_key_exists( 'bp-default-group-cover-type', $settings );

	if ( ! $has_avatar && ! $has_cover ) {
		return;
	}

	// --- Avatar type validation ---
	if ( $has_avatar ) {
		$avatar_type = bb_get_default_group_avatar_type();

		// If 'custom' but no custom avatar uploaded, revert.
		if ( 'custom' === $avatar_type ) {
			$custom_avatar = function_exists( 'bb_get_default_custom_upload_group_avatar' )
				? bb_get_default_custom_upload_group_avatar()
				: '';

			if ( empty( $custom_avatar ) ) {
				bp_update_option( 'bp-default-group-avatar-type', 'buddyboss' );
			}
		}

		// If 'group-name' but no image editor available, revert.
		if ( 'group-name' === $avatar_type && empty( _wp_image_editor_choose() ) ) {
			bp_update_option( 'bp-default-group-avatar-type', 'buddyboss' );
		}
	}

	// --- Cover type validation ---
	if ( $has_cover ) {
		$cover_type = bb_get_default_group_cover_type();

		// If 'custom' but no custom cover uploaded, revert.
		if ( 'custom' === $cover_type ) {
			$custom_cover = function_exists( 'bb_get_default_custom_upload_group_cover' )
				? bb_get_default_custom_upload_group_cover()
				: '';

			if ( empty( $custom_cover ) ) {
				bp_update_option( 'bp-default-group-cover-type', 'buddyboss' );
			}
		}
	}
}

add_action( 'bb_admin_save_feature_settings_after', 'bb_groups_validate_image_settings_after_save', 5, 3 );

/**
 * Sync reverted image setting values into the save response.
 *
 * After bb_groups_validate_image_settings_after_save() may revert avatar/cover
 * type values (e.g., 'custom' -> 'buddyboss' when no image exists), this filter
 * updates only the affected keys in the response so React stays in sync.
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
function bb_groups_sync_reverted_image_values( $response_data, $feature_id, $settings, $saved ) {
	if ( 'groups' !== $feature_id ) {
		return $response_data;
	}

	// Only re-read the specific keys that the validation callback may have reverted.
	$revertable_keys = array( 'bp-default-group-avatar-type', 'bp-default-group-cover-type' );

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

add_filter( 'bb_admin_save_feature_settings_response', 'bb_groups_sync_reverted_image_values', 10, 4 );

/**
 * Capture group settings state before save for post-save comparison.
 *
 * Captures the current value of settings that need before/after comparison
 * (e.g., restrict invites) before the save loop updates them.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param string $feature_id Feature ID being saved.
 */
function bb_groups_capture_pre_save_state( $feature_id ) {
	if ( 'groups' !== $feature_id ) {
		return;
	}

	// Only capture during save, not during get (read).
	// phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce verified by bb_verify_request() in the calling AJAX handler.
	if ( ! isset( $_POST['action'] ) || 'bb_admin_save_feature_settings' !== sanitize_key( wp_unslash( $_POST['action'] ) ) ) {
		return;
	}

	// Store the pre-save state for restrict invites.
	global $bb_groups_pre_save_state;
	$bb_groups_pre_save_state = array(
		'restrict_invites' => bp_enable_group_restrict_invites(),
	);
}

add_action( 'bb_admin_settings_before_get_feature', 'bb_groups_capture_pre_save_state', 10, 1 );

/**
 * Trigger subgroup member migration when restrict invites is enabled.
 *
 * Replicates legacy BP_Admin_Setting_Groups::settings_save() logic:
 * When group hierarchies are enabled AND restrict invites changes from
 * disabled to enabled, migrate subgroup members who are not in the parent group.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param string $feature_id Feature ID.
 * @param array  $settings   Full submitted settings.
 * @param array  $saved      Keys and values saved by core.
 */
function bb_groups_maybe_migrate_subgroup_members( $feature_id, $settings, $saved ) {
	if ( 'groups' !== $feature_id ) {
		return;
	}

	// Only act if restrict invites was submitted.
	if ( ! array_key_exists( 'bp-enable-group-restrict-invites', $settings ) ) {
		return;
	}

	// Get pre-save state captured by bb_groups_capture_pre_save_state().
	global $bb_groups_pre_save_state;
	$restrict_invites_before = ! empty( $bb_groups_pre_save_state['restrict_invites'] ) ? $bb_groups_pre_save_state['restrict_invites'] : false;

	// Check: hierarchies ON, restrict invites was OFF, now ON.
	if (
		true === bp_enable_group_hierarchies() &&
		empty( $restrict_invites_before ) &&
		true === (bool) bp_enable_group_restrict_invites()
	) {
		if ( function_exists( 'bb_groups_migrate_subgroup_member' ) ) {
			bb_groups_migrate_subgroup_member();
		}
	}
}

add_action( 'bb_admin_save_feature_settings_after', 'bb_groups_maybe_migrate_subgroup_members', 10, 3 );
