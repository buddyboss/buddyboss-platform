<?php
namespace Buddyboss\LearndashIntegration\Learndash;

use Buddyboss\LearndashIntegration\Library\SyncGenerator;

class Sync
{
	// temporarily hold the synced learndash group id just before delete
	protected $deletingSyncedBpGroupId;

	public function __construct()
	{
		add_action('bp_ld_sync/init', [$this, 'init']);
	}

	public function init()
	{
		if (! bp_ld_sync('settings')->get('learndash.enabled')) {
			return;
		}

		add_action('bp_ld_sync/learndash_group_updated', [$this, 'onGroupUpdated']);
		add_action('bp_ld_sync/learndash_group_deleting', [$this, 'onGroupDeleting']);
		add_action('bp_ld_sync/learndash_group_deleted', [$this, 'onGroupDeleted']);

		add_action('bp_ld_sync/learndash_group_admin_added', [$this, 'onAdminAdded'], 10, 2);
		add_action('bp_ld_sync/learndash_group_user_added', [$this, 'onUserAdded'], 10, 2);

		add_action('bp_ld_sync/learndash_group_admin_removed', [$this, 'onAdminRemoved'], 10, 2);
		add_action('bp_ld_sync/learndash_group_user_removed', [$this, 'onUserRemoved'], 10, 2);
	}

	public function generator($bpGroupId = null, $ldGroupId = null)
	{
		return new SyncGenerator($bpGroupId, $ldGroupId);
	}

	public function onGroupUpdated($groupId)
	{
		global $bp_ld_sync__syncing_to_learndash;

		// if it's group is created from buddypress sync, don't need to sync back
		if ($bp_ld_sync__syncing_to_learndash) {
			return false;
		}

		// created from backend
		if (bp_ld_sync()->isRequestExists('bp-ld-sync-enable') && ! bp_ld_sync()->getRequest('bp-ld-sync-enable')) {
			return false;
		}

		// created programatically
		if (! bp_ld_sync('settings')->get('learndash.default_auto_sync')) {
			return false;
		}

		$newGroup = bp_ld_sync()->getRequest('bp-ld-sync-id', null);
		$generator = $this->generator(null, $groupId);

		if ($generator->hasBpGroup() && $generator->getBpGroupId() == $newGroup) {
			return false;
		}

		$generator->associateToBuddypress($newGroup)
			->syncLdAdmins()
			->syncLdUsers();
	}

	public function onGroupDeleting($groupId)
	{
		global $bp_ld_sync__syncing_to_learndash;

		// if it's group is created from buddypress sync, don't need to sync back
		if ($bp_ld_sync__syncing_to_learndash) {
			return false;
		}

		$this->deletingSyncedBpGroupId = $this->generator(null, $groupId)->getBpGroupId();
	}

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

	public function onAdminAdded($groupId, $userId)
	{
		if (! $generator = $this->groupUserEditCheck('admin', $groupId)) {
			return false;
		}

		$generator->syncLdAdmin($userId);
	}

	public function onUserAdded($groupId, $userId)
	{
		if (! $generator = $this->groupUserEditCheck('user', $groupId)) {
			return false;
		}

		$generator->syncLdUser($userId);
	}

	public function onAdminRemoved($groupId, $userId)
	{
		if (! $generator = $this->groupUserEditCheck('admin', $groupId)) {
			return false;
		}

		$generator->syncLdAdmin($userId, true);
	}

	public function onUserRemoved($groupId, $userId)
	{
		if (! $generator = $this->groupUserEditCheck('user', $groupId)) {
			return false;
		}

		$generator->syncLdUser($userId, true);
	}

	protected function groupUserEditCheck($role, $groupId)
	{
		global $bp_ld_sync__syncing_to_learndash;

		// if it's group is created from buddypress sync, don't need to sync back
		if ($bp_ld_sync__syncing_to_learndash) {
			return false;
		}

		$settings = bp_ld_sync('settings');

		if (! $settings->get('learndash.enabled')) {
			return false;
		}

		if ('none' == $settings->get("learndash.default_{$role}_sync_to")) {
			return false;
		}

		$generator = $this->generator(null, $groupId);

		if (! $generator->hasBpGroup()) {
			return false;
		}

		return $generator;
	}
}
