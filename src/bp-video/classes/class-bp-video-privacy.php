<?php
/**
 * BuddyBoss Video Privacy
 *
 * @package BuddyBoss\Video
 * @since BuddyBoss 1.5.7
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * BuddyBoss Video Privacy.
 *
 * Handles video privacy information.
 *
 * @since BuddyBoss 1.5.7
 * @deprecated BuddyBoss 1.2.3 No longer used by internal code and not recommended.
 */

class BP_Video_Privacy {

	private function __construct() {}

	/**
	 * Get the instance of this class.
	 *
	 * @return BP_Video_Privacy|null
	 */
	public static function instance() {
		static $instance = null;

		if ( null === $instance ) {
			$instance = new BP_Video_Privacy();
		}

		return $instance;
	}

	/**
	 * Get options for visibility.
	 *
	 * @since BuddyBoss 1.5.7
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
	 * Get visibility of video
	 *
	 * @since BuddyBoss 1.5.7
	 * @param $video_id
	 *
	 * @return WP_Error
	 */
	function get_visibility( $video_id ) {
		$result = bp_video_get_specific( array( 'video_ids' => $video_id ) );

		if ( empty( $result['videos'] ) || empty( $result['videos'][0] ) ) {
			return new WP_Error( 'no_video', __( 'There is no video.', 'buddyboss' ), array( 'status' => 500 ) );
		}

		return $result['videos'][0]->privacy;
	}

	/**
	 * Check if video is visible or not to the logged in user
	 *
	 * @since BuddyBoss 1.5.7
	 * @param bool $video_id
	 *
	 * @return bool|mixed|WP_Error
	 */
	public function is_video_visible( $video_id = false ) {
		$result = bp_video_get_specific( array( 'video_ids' => $video_id ) );

		if ( empty( $result['videos'] ) || empty( $result['videos'][0] ) ) {
			return new WP_Error( 'no_video', __( 'There is no video.', 'buddyboss' ), array( 'status' => 500 ) );
		}

		$video      = $result['videos'][0];
		$visibility = $video->privacy;
		$visible    = true;

		if ( bp_loggedin_user_id() != $video->user_id ) {

			switch ( $visibility ) {
				// Logged in users.
				case 'loggedin':
					if ( ! bp_loggedin_user_id() ) {
						$visible = false;
					}
					break;

				// My friends.
				case 'friends':
					if ( bp_is_active( 'friends' ) ) {
						$is_friend = friends_check_friendship( bp_loggedin_user_id(), $video->user_id );
						if ( ! $is_friend ) {
							$visible = false;
						}
					}
					break;

				// Only group members.
				case 'grouponly':
					$group_is_user_member = groups_is_user_member( bp_loggedin_user_id(), $video->activity_id );
					if ( ! $group_is_user_member ) {
						$visible = false;
					}
					break;

				// Only Me.
				case 'onlyme':
					if ( bp_loggedin_user_id() != $video->user_id ) {
						$visible = false;
					}
					break;

				default:
					// public.
					break;
			}
		}

		if ( is_super_admin() ) {
			$visible = true;
		}

		return apply_filters( 'bp_video_is_video_visible', $visible, $visibility, $video_id );
	}

}
