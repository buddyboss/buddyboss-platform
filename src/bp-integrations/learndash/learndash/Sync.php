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

use Buddyboss\LearndashIntegration\Library\SyncGenerator;

/**
 * 
 * 
 * @since BuddyBoss 1.0.0
 */
class Sync
{
	// temporarily hold the synced learndash group id just before delete
	protected $deletingSyncedBpGroupId;

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
		add_action('bp_ld_sync/learndash_group_updated', [$this, 'onGroupUpdated']);
		add_action('bp_ld_sync/learndash_group_deleting', [$this, 'onGroupDeleting']);
		add_action('bp_ld_sync/learndash_group_deleted', [$this, 'onGroupDeleted']);

		add_action('bp_ld_sync/learndash_group_admin_added', [$this, 'onAdminAdded'], 10, 2);
		add_action('bp_ld_sync/learndash_group_user_added', [$this, 'onUserAdded'], 10, 2);

		add_action('bp_ld_sync/learndash_group_admin_removed', [$this, 'onAdminRemoved'], 10, 2);
		add_action('bp_ld_sync/learndash_group_user_removed', [$this, 'onUserRemoved'], 10, 2);
	}

	/**
	 * 
	 *
	 * @since BuddyBoss 1.0.0
	 */
	public function generator($bpGroupId = null, $ldGroupId = null)
	{
		return new SyncGenerator($bpGroupId, $ldGroupId);
	}

	/**
	 * 
	 *
	 * @since BuddyBoss 1.0.0
	 */
	public function onGroupUpdated( $groupId ) {
		if ( ! $this->preCheck() ) {
			return false;
		}

		// created from backend
		if ( bp_ld_sync()->isRequestExists( 'bp-ld-sync-enable' ) && ! bp_ld_sync()->getRequest( 'bp-ld-sync-enable' ) ) {
			$group_id = get_post_meta( $groupId, '_sync_group_id', true );
			if ( ! empty( $group_id ) ) {
				bp_ld_sync( 'buddypress' )->sync->generator( $group_id )->desyncFromLearndash();
			}

			return false;
		}

		// created programatically
		if ( ! bp_ld_sync( 'settings' )->get( 'learndash.default_auto_sync' ) ) {
			return false;
		}

		$newGroup  = bp_ld_sync()->getRequest( 'bp-ld-sync-id', null );
		$generator = $this->generator( null, $groupId );

		if ( $generator->hasBpGroup() && $generator->getBpGroupId() == $newGroup ) {
			$generator->fullSyncToBuddypress();

			return false;
		}

		$generator->associateToBuddypress( $newGroup )->syncLdAdmins()->syncLdUsers();
	}

	/**
	 * 
	 *
	 * @since BuddyBoss 1.0.0
	 */
	public function onGroupDeleting($groupId)
	{
		if (! $this->preCheck()) {
			return false;
		}

		$this->deletingSyncedBpGroupId = $this->generator(null, $groupId)->getBpGroupId();
	}

	/**
	 * 
	 *
	 * @since BuddyBoss 1.0.0
	 */
	public function onGroupDeleted($groupId)
	{
		if (! $bpGroupId = $this->deletingSyncedBpGroupId) {
			return;
		}

		$this->deletingSyncedBpGroupId = null;

		if (! bp_ld_sync('settings')->get('learndash.delete_bp_on_delete')) {
			$this->generator($bpGroupId)->desyncFromLearndash();
			return;
		}

		$this->generator()->deleteBpGroup($bpGroupId);
	}

	/**
	 * 
	 *
	 * @since BuddyBoss 1.0.0
	 */
	public function onAdminAdded($groupId, $userId)
	{
		if (! $generator = $this->groupUserEditCheck('admin', $groupId)) {
			return false;
		}

		$generator->syncLdAdmin($userId);
	}

	/**
	 * 
	 *
	 * @since BuddyBoss 1.0.0
	 */
	public function onUserAdded($groupId, $userId)
	{
		if (! $generator = $this->groupUserEditCheck('user', $groupId)) {
			return false;
		}

		$generator->syncLdUser($userId);
	}

	/**
	 * 
	 *
	 * @since BuddyBoss 1.0.0
	 */
	public function onAdminRemoved($groupId, $userId)
	{
		if (! $generator = $this->groupUserEditCheck('admin', $groupId)) {
			return false;
		}

		$generator->syncLdAdmin($userId, true);
	}

	/**
	 * 
	 *
	 * @since BuddyBoss 1.0.0
	 */
	public function onUserRemoved($groupId, $userId)
	{
		if (! $generator = $this->groupUserEditCheck('user', $groupId)) {
			return false;
		}

		$generator->syncLdUser($userId, true);
	}

	/**
	 * 
	 *
	 * @since BuddyBoss 1.0.0
	 */
	protected function groupUserEditCheck($role, $groupId)
	{
		if (! $this->preCheck()) {
			return false;
		}

		if ('none' == bp_ld_sync('settings')->get("learndash.default_{$role}_sync_to")) {
			return false;
		}

		$generator = $this->generator(null, $groupId);

		if (! $generator->hasBpGroup()) {
			return false;
		}

		return $generator;
	}

	/**
	 * 
	 *
	 * @since BuddyBoss 1.0.0
	 */
	protected function preCheck()
	{
		global $bp_ld_sync__syncing_to_learndash;

		// if it's group is created from buddypress sync, don't need to sync back
		if ($bp_ld_sync__syncing_to_learndash) {
			return false;
		}

		return bp_ld_sync('settings')->get('learndash.enabled');
	}
}
