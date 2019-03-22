<?php
/**
 * BuddyBoss Media Privacy
 *
 * @package BuddyBoss\Media
 * @since BuddyBoss 1.0.0
 */

// Exit if accessed directly
if ( !defined( 'ABSPATH' ) ) exit;

/**
 * BuddyBoss Media Privacy.
 *
 * Handles media privacy information.
 *
 * @since BuddyBoss 1.0.0
 */

class BP_Media_Privacy {

	private function __construct() {}

	/**
	 * Get the instance of this class.
	 *
	 * @return Controller|null
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
	 * @param bool $friend
	 * @param bool $group
	 * @return array
	 */
	function get_visibility_options( $is_group = false ){

		$options = array(
			'public' => __( 'Everyone', 'buddyboss' ),
			'loggedin' => __( 'Logged In Users', 'buddyboss' ),
			'onlyme' => __('Only Me', 'buddyboss' ),
			'friends' => __('My Friends', 'buddyboss' ),
		);

		if( $is_group && bp_is_active( 'groups' ) ) {
			$options['grouponly'] = __( 'Group Members', 'buddyboss' );
		}

		return $options;
	}

	function get_visibility( $media_id ) {
		$media_model = new BP_Media();
		$media = $media_model::get( $media_id );

		if ( empty( $media ) || is_wp_error( $media ) ) {
			return new WP_Error( 'no_media', __( 'There is no media.', 'buddyboss' ), array( 'status' => 500 ) );
		}

		return $media->privacy;
	}

	function is_media_visible( $media_id = 0 ) {

		$media = new BP_Media( $media_id );

		if ( empty( $media->id ) ) {
			return new \WP_Error( 'no_media', __( 'There is no media.', 'buddyboss' ), array( 'status' => 500 ) );
		}

		$visibility = $media->privacy;
		$visible = true;

		if( bp_loggedin_user_id() != $media->user_id ) {

			switch ( $visibility ) {
				//Logged in users
				case 'loggedin' :
					if( !bp_loggedin_user_id() )
						$visible = false;
					break;

				//My friends
				case 'friends' :
					if ( bp_is_active( 'friends' ) ) {
						$is_friend = friends_check_friendship( bp_loggedin_user_id(), $media->user_id );
						if( !$is_friend )
							$visible = false;
					}
					break;

				//Only group members
				case 'grouponly' :
					$group_is_user_member = groups_is_user_member( bp_loggedin_user_id(), $media->activity_id );
					if( !$group_is_user_member )
						$visible = false;
					break;

				//Only Me
				case 'onlyme' :
					if( bp_loggedin_user_id() != $media->user_id )
						$visible = false;
					break;

				default:
					//public
					break;
			}
		}

		if ( is_super_admin() ) {
			$visible = true;
		}

		$visible = apply_filters( 'bp_media_is_media_visible', $visible, $visibility, $media_id );

		return $visible;
	}

	function get_visible_media_count( $user_id = 0 ) {

		if ( empty( $user_id ) ) {
			return 0;
		}

		$media_model = new BP_Media();

		if ( is_user_logged_in() ) {
			$visibility = array( 'public', 'loggedin' );
			if ( bp_is_active( 'friends' ) ) {
				$is_friend = friends_check_friendship( get_current_user_id(), $user_id );
				if( $is_friend ) {
					$visibility[] = 'friends';
				}
			}
		} else {
			$visibility = 'public';
		}

		if ( get_current_user_id() == $user_id || is_super_admin() ) {
			$visibility = array_keys( $this->get_visibility_options() );
		}

		return $media_model::rows( array( 'author_id' => $user_id, 'privacy' => $visibility ) );
	}

}