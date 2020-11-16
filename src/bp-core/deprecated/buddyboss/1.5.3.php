<?php
/**
 * Deprecated functions.
 *
 * @deprecated BuddyBoss 1.5.3
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Function returns user progress data by checking if data already exists in transient first. IF NO then follow checking the progress logic.
 *
 * Clear transient when 1) Widget form settings update. 2) When Logged user profile updated. 3) When new profile fields added/updated/deleted.
 *
 * @param array $settings - set of fieldset selected to show in progress & profile or cover photo selected to show in progress.
 *
 * @return array $user_progress - user progress to render profile completion
 *
 * @since BuddyBoss 1.4.9
 */
function bp_xprofile_get_user_progress_data( $profile_groups, $profile_phototype, $widget_id   ) {

	_deprecated_function( __FUNCTION__, '1.5.3', 'bp_xprofile_get_user_profile_progress_data' );

	$settings                      = array();
	$settings['profile_groups']     = $profile_groups;
	$settings['profile_photo_type'] = $profile_phototype;

	return bp_xprofile_get_user_profile_progress_data( $settings );

}
