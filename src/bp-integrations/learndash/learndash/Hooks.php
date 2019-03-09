<?php
/**
 * @todo add description
 * 
 * @package BuddyBoss\LearnDash
 * @since BuddyBoss 1.0.0
 */ 

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

namespace Buddyboss\LearndashIntegration\Learndash;

/**
 * 
 * 
 * @since BuddyBoss 1.0.0
 */
class Hooks
{
	/**
	 * 
	 *
	 * @since BuddyBoss 1.0.0
	 */
	public function __construct()
	{
		add_action('bp_ld_sync/init', [$this, 'init']);
	}

	/**
	 * 
	 *
	 * @since BuddyBoss 1.0.0
	 */
	public function init()
	{
		// add some helpful missing hooks
		add_action('ld_group_postdata_updated', [$this, 'groupUpdated']);
		add_action('before_delete_post', [$this, 'groupDeleting']);

		// backward compet, we check the meta instead of using hook (hook not consistant)
		add_action('update_user_meta', [$this, 'checkLearndashGroupUpdateMeta'], 10, 4);
		add_action('added_user_meta', [$this, 'checkLearndashGroupUpdateMeta'], 10, 4);
		add_action('deleted_user_meta', [$this, 'checkLearndashGroupDeleteMeta'], 10, 4);

		add_action('update_post_meta', [$this, 'checkLearndashCourseUpdateMeta'], 10, 4);
		add_action('added_post_meta', [$this, 'checkLearndashCourseUpdateMeta'], 10, 4);
		add_action('deleted_post_meta', [$this, 'checkLearndashCourseDeleteMeta'], 10, 4);
	}

	/**
	 * 
	 *
	 * @since BuddyBoss 1.0.0
	 */
	public function groupUpdated($groupId)
	{
		do_action('bp_ld_sync/learndash_group_updated', $groupId);
	}

	/**
	 * 
	 *
	 * @since BuddyBoss 1.0.0
	 */
	public function groupDeleting($groupId)
	{
		global $wpdb;

		$post = $wpdb->get_row($wpdb->prepare("SELECT * FROM $wpdb->posts WHERE ID = %d", $groupId));

		if ($post->post_type != 'groups') {
			return false;
		}

		do_action('bp_ld_sync/learndash_group_deleting', $groupId);
		add_action('delete_post', [$this, 'groupDeleted']);
	}

	/**
	 * 
	 *
	 * @since BuddyBoss 1.0.0
	 */
	public function groupDeleted($groupId)
	{
		remove_action('delete_post', [$this, 'groupDeleted']);
		do_action('bp_ld_sync/learndash_group_deleted', $groupId);
	}

	/**
	 * 
	 *
	 * @since BuddyBoss 1.0.0
	 */
	public function checkLearndashGroupUpdateMeta($metaId, $userId, $metaKey, $metaValue)
	{
		if ($this->isLearndashLeaderMeta($metaKey)) {
			$groupId = $this->getLeardashMetaGroupId($metaKey);
			return do_action('bp_ld_sync/learndash_group_admin_added', $groupId, $userId);
		}

		if ($this->isLearndashUserMeta($metaKey)) {
			$groupId = $this->getLeardashMetaGroupId($metaKey);
			return do_action('bp_ld_sync/learndash_group_user_added', $groupId, $userId);
		}
	}

	/**
	 * 
	 *
	 * @since BuddyBoss 1.0.0
	 */
	public function checkLearndashGroupDeleteMeta($metaId, $userId, $metaKey, $metaValue)
	{
		if ($this->isLearndashLeaderMeta($metaKey)) {
			$groupId = $this->getLeardashMetaGroupId($metaKey);
			return do_action('bp_ld_sync/learndash_group_admin_removed', $groupId, $userId);
		}

		if ($this->isLearndashUserMeta($metaKey)) {
			$groupId = $this->getLeardashMetaGroupId($metaKey);
			return do_action('bp_ld_sync/learndash_group_user_removed', $groupId, $userId);
		}
	}

	/**
	 * 
	 *
	 * @since BuddyBoss 1.0.0
	 */
	public function checkLearndashCourseUpdateMeta($metaId, $groupId, $metaKey, $metaValue)
	{
		if ($this->isLearndashCourseMeta($metaKey)) {
			$courseId = $this->getLeardashMetaGroupId($metaKey);
			return do_action('bp_ld_sync/learndash_group_course_added', $groupId, $courseId);
		}
	}

	/**
	 * 
	 *
	 * @since BuddyBoss 1.0.0
	 */
	public function checkLearndashCourseDeleteMeta($metaId, $groupId, $metaKey, $metaValue)
	{
		if ($this->isLearndashCourseMeta($metaKey)) {
			$courseId = $this->getLeardashMetaGroupId($metaKey);
			return do_action('bp_ld_sync/learndash_group_course_deleted', $groupId, $userId);
		}
	}

	/**
	 * 
	 *
	 * @since BuddyBoss 1.0.0
	 */
	protected function isLearndashLeaderMeta($key)
	{
		return strpos($key, 'learndash_group_leaders_') === 0;
	}

	/**
	 * 
	 *
	 * @since BuddyBoss 1.0.0
	 */
	protected function isLearndashUserMeta($key)
	{
		return strpos($key, 'learndash_group_users_') === 0;
	}

	/**
	 * 
	 *
	 * @since BuddyBoss 1.0.0
	 */
	protected function isLearndashCourseMeta($key)
	{
		return strpos($key, 'learndash_group_enrolled_') === 0;
	}

	/**
	 * 
	 *
	 * @since BuddyBoss 1.0.0
	 */
	protected function getLeardashMetaGroupId($key)
	{
		$segments = explode('_', $key);
		return array_pop($segments);
	}
}
