<?php
/**
 * BuddyBoss Document Privacy
 *
 * @package BuddyBoss\Document
 * @since BuddyBoss 1.4.0
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * BuddyBoss Document Privacy.
 *
 * Handles document privacy information.
 *
 * @since BuddyBoss 1.4.0
 */

class BP_Document_Privacy {

	private function __construct() {}

	/**
	 * Get the instance of this class.
	 *
	 * @return BP_Document_Privacy|null
	 */
	public static function instance() {
		static $instance = null;

		if ( null === $instance ) {
			$instance = new BP_Document_Privacy();
		}

		return $instance;
	}

	/**
	 * get options for visibility.
	 *
	 * @since BuddyBoss 1.4.0
	 * @param bool $is_group
	 * @return array
	 */
	function get_visibility_options( $is_group = false ) {

		$options = array(
			'public'   => __( 'Everyone', 'buddyboss' ),
			'loggedin' => __( 'Logged In Users', 'buddyboss' ),
			'onlyme'   => __( 'Only Me', 'buddyboss' ),
			'friends'  => __( 'My Connections', 'buddyboss' ),
		);

		if ( $is_group && bp_is_active( 'groups' ) ) {
			$options['grouponly'] = __( 'Group Members', 'buddyboss' );
		}

		return $options;
	}

	/**
	 * Get visibility of document
	 *
	 * @since BuddyBoss 1.4.0
	 * @param $document_id
	 *
	 * @return WP_Error
	 */
	function get_visibility( $document_id ) {
		$result = bp_document_get_specific( array( 'document_ids' => $document_id ) );

		if ( empty( $result['documents'] ) || empty( $result['documents'][0] ) ) {
			return new WP_Error( 'no_document', __( 'There is no document.', 'buddyboss' ), array( 'status' => 500 ) );
		}

		return $result['documents'][0]->privacy;
	}

	/**
	 * Check if document is visible or not to the logged in user
	 *
	 * @since BuddyBoss 1.4.0
	 * @param bool $document_id
	 *
	 * @return bool|mixed|WP_Error
	 */
	public function is_document_visible( $document_id = false ) {
		$result = bp_document_get_specific( array( 'document_ids' => $document_id ) );

		if ( empty( $result['documents'] ) || empty( $result['documents'][0] ) ) {
			return new WP_Error( 'no_document', __( 'There is no document.', 'buddyboss' ), array( 'status' => 500 ) );
		}

		$document   = $result['documents'][0];
		$visibility = $document->privacy;
		$visible    = true;

		if ( bp_loggedin_user_id() != $document->user_id ) {

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
						$is_friend = friends_check_friendship( bp_loggedin_user_id(), $document->user_id );
						if ( ! $is_friend ) {
							$visible = false;
						}
					}
					break;

				// Only group members.
				case 'grouponly':
					if ( bp_is_active( 'groups' ) ) {
						$group_is_user_member = groups_is_user_member( bp_loggedin_user_id(), $document->activity_id );
						if ( ! $group_is_user_member ) {
							$visible = false;
						}
					}
					break;

				// Only Me.
				case 'onlyme':
					if ( bp_loggedin_user_id() != $document->user_id ) {
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

		return apply_filters( 'bp_document_is_document_visible', $visible, $visibility, $document_id );
	}

}
