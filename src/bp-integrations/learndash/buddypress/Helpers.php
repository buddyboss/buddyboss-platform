<?php
/**
 * BuddyBoss LearnDash integration gelper class.
 *
 * @package BuddyBoss\LearnDash
 * @since BuddyBoss 1.0.0
 */

namespace Buddyboss\LearndashIntegration\Buddypress;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Class misc helper functions
 *
 * @since BuddyBoss 1.0.0
 */
class Helpers {

	protected $ldGroupMetaKey = '_ld_group_id';

	/**
	 * Determine whether a group has connected ld group
	 *
	 * @since BuddyBoss 1.0.0
	 */
	public function hasLearndashGroup( $groupId = null ) {
		if ( ! $groupId ) {
			return false;
		}

		if ( ! $ldGroupId = $this->getLearndashGroupId( $groupId ) ) {
			return false;
		}

		if ( 'publish' !== get_post_status( $ldGroupId ) ) {
			return false;
		}

		return true;
	}

	/**
	 * Get the connected ld group id
	 *
	 * @since BuddyBoss 1.0.0
	 */
	public function getLearndashGroupId( $groupId ) {
		return bp_ld_sync( 'buddypress' )->sync->generator( $groupId )->getLdGroupId();
	}

	/**
	 * Set the ld group id on a bp grouop
	 *
	 * @since BuddyBoss 1.0.0
	 */
	public function setLearndashGroupId( $groupId, $ldGroupId ) {
		return groups_update_groupmeta( $groupId, $this->ldGroupMetaKey, $ldGroupId );
	}

	/**
	 * Remove ld group connection from a bp group
	 *
	 * @since BuddyBoss 1.0.0
	 */
	public function deleteLearndashGroupId( $groupId ) {
		return groups_delete_groupmeta( $groupId, $this->ldGroupMetaKey );
	}
}
