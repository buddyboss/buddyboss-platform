<?php
/**
 * BuddyBoss LearnDash integration gelper class.
 * 
 * @package BuddyBoss\LearnDash
 * @since BuddyBoss 1.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

namespace Buddyboss\LearndashIntegration\Buddypress;

/**
 * 
 * 
 * @since BuddyBoss 1.0.0
 */
class Helpers
{
	protected $ldGroupMetaKey = '_ld_group_id';

	/**
	 * 
	 *
	 * @since BuddyBoss 1.0.0
	 */
	public function hasLearndashGroup($groupId = null)
	{
		if (! $groupId) {
			return false;
		}

		if (! $ldGroupId = $this->getLearndashGroupId($groupId)) {
			return false;
		}

		if ('publish' !== get_post_status($ldGroupId)) {
			return false;
		}

		return true;
	}

	/**
	 * 
	 *
	 * @since BuddyBoss 1.0.0
	 */
	public function getLearndashGroupId($groupId)
	{
		return bp_ld_sync('buddypress')->sync->generator($groupId)->getLdGroupId();
		return bp_learndash_groups_sync_get_associated_ld_group($groupId)->ID;
		return groups_get_groupmeta($groupId, $this->ldGroupMetaKey, true);
	}

	/**
	 * 
	 *
	 * @since BuddyBoss 1.0.0
	 */
	public function setLearndashGroupId($groupId, $ldGroupId)
	{
		return groups_update_groupmeta($groupId, $this->ldGroupMetaKey, $ldGroupId);
	}

	/**
	 * 
	 *
	 * @since BuddyBoss 1.0.0
	 */
	public function deleteLearndashGroupId($groupId)
	{
		return groups_delete_groupmeta($groupId, $this->ldGroupMetaKey);
	}
}
