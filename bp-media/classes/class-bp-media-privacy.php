<?php
/**
 * BuddyBoss Media Privacy
 *
 * @package BuddyBoss\Media
 * @since BuddyBoss 1.0.0
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * BuddyBoss Media Privacy.
 *
 * Handles media privacy information.
 *
 * @since BuddyBoss 1.0.0
 * @deprecated BuddyBoss 1.2.3 No longer used by internal code and not recommended.
 */

class BP_Media_Privacy {

	private function __construct() {}

	/**
	 * Get the instance of this class.
	 *
	 * @return BP_Media_Privacy|null
	 */
	public static function instance() {
		static $instance = null;

		if ( null === $instance ) {
			$instance = new BP_Media_Privacy();
		}

		return $instance;
	}

	/**
	 * get options for visibility
	 *
	 * @since BuddyBoss 1.0.0
	 * @param bool $group
	 * @return array
	 */
	function get_visibility_options( $is_group = false ) {

		$options = array(
			'public'   => __( 'Public', 'buddyboss' ),
			'onlyme'   => __( 'Only Me', 'buddyboss' ),
			'loggedin' => __( 'All Members', 'buddyboss' ),
			'friends'  => __( 'My Connections', 'buddyboss' ),
		);

		if ( $is_group && bp_is_active( 'groups' ) ) {
			$options['grouponly'] = __( 'Group Members', 'buddyboss' );
		}

		return $options;
	}

	/**
	 * Get visibility of media
	 *
	 * @since BuddyBoss 1.0.0
	 * @param $media_id
	 *
	 * @return WP_Error
	 */
	function get_visibility( $media_id ) {
		$result = bp_media_get_specific( array( 'media_ids' => $media_id ) );

		if ( empty( $result['medias'] ) || empty( $result['medias'][0] ) ) {
			return new WP_Error( 'no_media', __( 'There is no media.', 'buddyboss' ), array( 'status' => 500 ) );
		}

		return $result['medias'][0]->privacy;
	}

	/**
	 * Check if media is visible or not to the logged in user
	 *
	 * @since BuddyBoss 1.0.0
	 * @param bool $media_id
	 *
	 * @return bool|mixed|WP_Error
	 */
	public function is_media_visible( $media_id = false ) {
		$result = bp_media_get_specific( array( 'media_ids' => $media_id ) );

		if ( empty( $result['medias'] ) || empty( $result['medias'][0] ) ) {
			return new WP_Error( 'no_media', __( 'There is no media.', 'buddyboss' ), array( 'status' => 500 ) );
		}

		$media      = $result['medias'][0];
		$visibility = $media->privacy;
		$visible    = true;

		if ( bp_loggedin_user_id() != $media->user_id ) {

			switch ( $visibility ) {
				// Logged in users
				case 'loggedin':
					if ( ! bp_loggedin_user_id() ) {
						$visible = false;
					}
					break;

				// My friends
				case 'friends':
					if ( bp_is_active( 'friends' ) ) {
						$is_friend = friends_check_friendship( bp_loggedin_user_id(), $media->user_id );
						if ( ! $is_friend ) {
							$visible = false;
						}
					}
					break;

				// Only group members
				case 'grouponly':
					$group_is_user_member = groups_is_user_member( bp_loggedin_user_id(), $media->activity_id );
					if ( ! $group_is_user_member ) {
						$visible = false;
					}
					break;

				// Only Me
				case 'onlyme':
					if ( bp_loggedin_user_id() != $media->user_id ) {
						$visible = false;
					}
					break;

				default:
					// public
					break;
			}
		}

		if ( is_super_admin() ) {
			$visible = true;
		}

		return apply_filters( 'bp_media_is_media_visible', $visible, $visibility, $media_id );
	}

}
