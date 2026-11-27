<?php
/**
 * XProfile: User's "Profile > Change Avatar" screen handler
 *
 * @package BuddyBoss\XProfileScreens
 * @since BuddyPress 3.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Handles the uploading and cropping of a user avatar. Displays the change avatar page.
 *
 * @since BuddyPress 1.0.0
 */
function bboss_xprofile_screen_change_avatar() {

	// Bail if not the correct screen.
	if ( ! bboss_is_my_profile() && ! bboss_current_user_can( 'bp_moderate' ) ) {
		return false;
	}

	// Bail if there are action variables.
	if ( bboss_action_variables() ) {
		bboss_do_404();
		return;
	}

	$bp = bboss();

	if ( ! isset( $bp->avatar_admin ) ) {
		$bp->avatar_admin = new stdClass();
	}

	$bp->avatar_admin->step = 'upload-image';

	if ( ! empty( $_FILES ) ) {

		// Check the nonce.
		check_admin_referer( 'bp_avatar_upload' );

		// Pass the file to the avatar upload handler.
		if ( bboss_core_avatar_handle_upload( $_FILES, 'bboss_xprofile_avatar_upload_dir' ) ) {
			$bp->avatar_admin->step = 'crop-image';

			// Make sure we include the jQuery jCrop file for image cropping.
			add_action( 'wp_print_scripts', 'bp_core_add_jquery_cropper' );
		}
	}

	// If the image cropping is done, crop the image and save a full/thumb version.
	if ( isset( $_POST['avatar-crop-submit'] ) ) {

		// Check the nonce.
		check_admin_referer( 'bp_avatar_cropstore' );

		$args = array(
			'item_id'       => bboss_displayed_user_id(),
			'original_file' => $_POST['image_src'], // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotValidated, WordPress.Security.ValidatedSanitizedInput.MissingUnslash, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- posted by the crop form; BP_Attachment_Avatar::crop() bails on an empty value, reduces it with basename() and requires the file to exist in the avatar folder.
			'crop_x'        => $_POST['x'], // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotValidated, WordPress.Security.ValidatedSanitizedInput.MissingUnslash, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- posted by the crop form; cast with (int) in BP_Attachment::crop() before wp_crop_image().
			'crop_y'        => $_POST['y'], // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotValidated, WordPress.Security.ValidatedSanitizedInput.MissingUnslash, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- posted by the crop form; cast with (int) in BP_Attachment::crop() before wp_crop_image().
			'crop_w'        => $_POST['w'], // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotValidated, WordPress.Security.ValidatedSanitizedInput.MissingUnslash, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- posted by the crop form; BP_Attachment_Avatar::crop() falls back to the full avatar size when empty and BP_Attachment::crop() casts it with (int).
			'crop_h'        => $_POST['h'], // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotValidated, WordPress.Security.ValidatedSanitizedInput.MissingUnslash, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- posted by the crop form; BP_Attachment_Avatar::crop() falls back to the full avatar size when empty and BP_Attachment::crop() casts it with (int).
		);

		if ( ! bboss_core_avatar_handle_crop( $args ) ) {
			bboss_core_add_message( __( 'There was a problem cropping your profile photo.', 'buddyboss' ), 'error' );
		} else {

			/**
			 * Fires right before the redirect, after processing a new avatar.
			 *
			 * @since BuddyPress 1.1.0
			 * @since BuddyPress 2.3.4 Add two new parameters to inform about the user id and
			 *              about the way the avatar was set (eg: 'crop' or 'camera').
			 *
			 * @param string $item_id Inform about the user id the avatar was set for.
			 * @param string $value   Inform about the way the avatar was set ('crop').
			 */
			do_action( 'xprofile_avatar_uploaded', (int) $args['item_id'], 'crop' );
			bboss_core_add_message( __( 'Your new profile photo was uploaded successfully.', 'buddyboss' ) );
			bboss_core_redirect( bboss_displayed_user_domain() );
		}
	}

	/**
	 * Fires right before the loading of the XProfile change avatar screen template file.
	 *
	 * @since BuddyPress 1.0.0
	 */
	do_action( 'xprofile_screen_change_avatar' );

	/**
	 * Filters the template to load for the XProfile change avatar screen.
	 *
	 * @since BuddyPress 1.0.0
	 *
	 * @param string $template Path to the XProfile change avatar template to load.
	 */
	bboss_core_load_template( apply_filters( 'xprofile_template_change_avatar', 'members/single/home' ) );
}
// @bbwt-com-start compat-layer
// bbwt-rename:compat-aliases — the pre-rename name of the function above (decision 0013): declared right after it, guarded on it, so the old name exists exactly when the new one does.
if ( function_exists( 'bboss_xprofile_screen_change_avatar' ) && ! function_exists( 'xprofile_screen_change_avatar' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_xprofile_screen_change_avatar()}.
	 *
	 * @deprecated Use bboss_xprofile_screen_change_avatar() instead.
	 */
	function xprofile_screen_change_avatar() {
		return bboss_xprofile_screen_change_avatar();
	}
}
// @bbwt-com-end compat-layer

