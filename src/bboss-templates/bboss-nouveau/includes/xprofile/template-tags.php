<?php
/**
 * xProfile Template tags
 *
 * @since BuddyPress 3.0.0
 * @version 3.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Fire specific hooks into the single members xprofile templates.
 *
 * @since BuddyPress 3.0.0
 *
 * @param string $when   Optional. Either 'before' or 'after'.
 * @param string $suffix Optional. Use it to add terms at the end of the hook name.
 */
function bboss_nouveau_xprofile_hook( $when = '', $suffix = '' ) {
	$hook = array( 'bp' );

	if ( $when ) {
		$hook[] = $when;
	}

	// It's a xprofile hook
	$hook[] = 'profile';

	if ( $suffix ) {
		$hook[] = $suffix;
	}

	bboss_nouveau_hook( $hook );
}
// @bbwt-com-start compat-layer
// bbwt-rename:compat-aliases — the pre-rename name of the function above (decision 0013): declared right after it, guarded on it, so the old name exists exactly when the new one does.
if ( function_exists( 'bboss_nouveau_xprofile_hook' ) && ! function_exists( 'bp_nouveau_xprofile_hook' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_nouveau_xprofile_hook()}.
	 *
	 * @deprecated Use bboss_nouveau_xprofile_hook() instead.
	 */
	function bp_nouveau_xprofile_hook( $when = '', $suffix = '' ) {
		return bboss_nouveau_xprofile_hook( $when, $suffix );
	}
}
// @bbwt-com-end compat-layer


/**
 * Template tag to output the field visibility markup in edit and signup screens.
 *
 * @since BuddyPress 3.0.0
 */
function bboss_nouveau_xprofile_edit_visibilty() {
	/**
	 * Fires before the display of visibility options for the field.
	 *
	 * @since BuddyPress 1.7.0
	 */
	do_action( 'bp_custom_profile_edit_fields_pre_visibility' );

	bboss_get_template_part( 'members/single/parts/profile-visibility' );

	/**
	 * Fires after the visibility options for a field.
	 *
	 * @since BuddyPress 3.0.0
	 */
	do_action( 'bp_custom_profile_edit_fields' );
}
// @bbwt-com-start compat-layer
// bbwt-rename:compat-aliases — the pre-rename name of the function above (decision 0013): declared right after it, guarded on it, so the old name exists exactly when the new one does.
if ( function_exists( 'bboss_nouveau_xprofile_edit_visibilty' ) && ! function_exists( 'bp_nouveau_xprofile_edit_visibilty' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_nouveau_xprofile_edit_visibilty()}.
	 *
	 * @deprecated Use bboss_nouveau_xprofile_edit_visibilty() instead.
	 */
	function bp_nouveau_xprofile_edit_visibilty() {
		return bboss_nouveau_xprofile_edit_visibilty();
	}
}
// @bbwt-com-end compat-layer


/**
 * Return a bool check to see whether the base re group has had extended
 * profile fields added to it for the registration screen.
 *
 * @since BuddyPress 3.0.0
 */
function bboss_nouveau_base_account_has_xprofile() {
	$fields = $_POST['signup_profile_field_ids'] ?? array(); // phpcs:ignore WordPress.Security.NonceVerification.Missing, WordPress.Security.ValidatedSanitizedInput.MissingUnslash, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- read-only; the field ids are cast with intval() in BP_XProfile_Group::get().
	$args   = array(
		'profile_group_id' => bboss_xprofile_base_group_id(),
		'fetch_field_data' => false,
	);

	if ( ! empty( $fields ) ) {
		$args['include_fields'] = $fields;
	}

	return (bool) bboss_has_profile( $args );
}
// @bbwt-com-start compat-layer
// bbwt-rename:compat-aliases — the pre-rename name of the function above (decision 0013): declared right after it, guarded on it, so the old name exists exactly when the new one does.
if ( function_exists( 'bboss_nouveau_base_account_has_xprofile' ) && ! function_exists( 'bp_nouveau_base_account_has_xprofile' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_nouveau_base_account_has_xprofile()}.
	 *
	 * @deprecated Use bboss_nouveau_base_account_has_xprofile() instead.
	 */
	function bp_nouveau_base_account_has_xprofile() {
		return bboss_nouveau_base_account_has_xprofile();
	}
}
// @bbwt-com-end compat-layer

