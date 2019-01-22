<?php

namespace Buddyboss\LearndashIntegration\Library;

class SyncGenerator
{
	protected $syncingToLearndash = false;
	protected $syncingToBuddypress = false;
	protected $bpGroupId;
	protected $ldGroupId;
	protected $syncMetaKey = '_sync_group_id';

	public function __construct($bpGroupId = null, $ldGroupId = null)
	{
		$this->bpGroupId = $bpGroupId;
		$this->ldGroupId = $ldGroupId;

		$this->populateData();
		$this->verifyInputs();
	}

	public function hasLdGroup()
	{
		return !! $this->ldGroupId;
	}

	public function hasBpGroup()
	{
		return !! $this->bpGroupId;
	}

	public function getLdGroupId()
	{
		return $this->ldGroupId;
	}

	public function getBpGroupId()
	{
		return $this->bpGroupId;
	}

	public function syncToLearndash()
	{
		if ($this->ldGroupId) {
			return $this;
		}

		$this->createLearndashGroup();

		return $this;
	}

	public function desyncFromLearndash()
	{
		if (! $this->ldGroupId) {
			return $this;
		}

		$this->unsetSyncGropuIds();

		return $this;
	}

	public function deleteLdGroup($ldGroupId)
	{
		$this->syncingToBuddypress(function() use ($ldGroupId) {
			wp_delete_post($ldGroupId, true);
		});
	}

	public function syncToBuddypress()
	{

	}

	public function desyncFromBuddypress()
	{
		if (! $this->bpGroupId) {
			return $this;
		}

		$this->unsetSyncGropuIds();

		return $this;
	}

	public function syncBpAdmins()
	{
		$this->syncingToLearndash(function() {
			$adminIds = groups_get_group_admins($this->bpGroupId);

			foreach ($adminIds as $admin) {
				$this->syncBpAdmin($admin->user_id);
			}
		});

		$this->clearLdGroupCache();
	}

	public function syncBpMods()
	{
		$this->syncingToLearndash(function() {
			$modIds = groups_get_group_mods($this->bpGroupId);

			foreach ($modIds as $mod) {
				$this->syncBpMod($mod->user_id);
			}
		});

		$this->clearLdGroupCache();
	}

	public function syncBpUsers()
	{
		$this->syncingToLearndash(function() {
			$memberIds = groups_get_group_members($this->bpGroupId);

			foreach ($memberIds as $member) {
				$this->syncBpMember($member->user_id);
			}
		});

		$this->clearLdGroupCache();
	}

	public function syncLdAdmins()
	{
		// $admins = learndash_get_groups_administrators($groupId);
		// learndash_set_groups_administrators($groupId, [$bpGroup->creator_id]);
	}

	public function syncLdUsers()
	{

	}

	public function syncBpAdmin($userId, $remove = false)
	{
		$this->syncingToLearndash(function() use ($userId, $remove) {
			call_user_func_array($this->getBpSyncFunction('admin'), [$userId, $this->ldGroupId, $remove]);
			$this->maybeRemoveAsLdUser('admin', $userId);
		});

		$this->clearLdGroupCache();
	}

	public function syncBpMod($userId, $remove = false)
	{
		$this->syncingToLearndash(function() use ($userId, $remove) {
			call_user_func_array($this->getBpSyncFunction('mod'), [$userId, $this->ldGroupId, $remove]);
			$this->maybeRemoveAsLdUser('mod', $userId);
		});

		$this->clearLdGroupCache();
	}

	public function syncBpMember($userId, $remove = false)
	{
		$this->syncingToLearndash(function() use ($userId, $remove) {
			call_user_func_array($this->getBpSyncFunction('user'), [$userId, $this->ldGroupId, $remove]);
			call_user_func_array('ld_update_leader_group_access', [$userId, $this->ldGroupId, true]);
		});

		$this->clearLdGroupCache();
	}

	protected function verifyInputs()
	{
		if ($this->bpGroupId && ! groups_get_group($this->bpGroupId)->id) {
			$this->unsetBpGroupMeta();
		}

		if ($this->ldGroupId && ! get_post($this->ldGroupId)) {
			$this->unsetLdGroupMeta();
		}
	}

	protected function populateData()
	{
		if (! $this->bpGroupId) {
			$this->bpGroupId = $this->loadBpGroupId();
		}

		if (! $this->ldGroupId) {
			$this->ldGroupId = $this->loadLdGroupId();
		}
	}

	protected function loadBpGroupId()
	{
		return get_post_meta($this->ldGroupId, $this->syncMetaKey, true) ?: null;
	}

	protected function loadLdGroupId()
	{
		return groups_get_groupmeta($this->bpGroupId, $this->syncMetaKey, true) ?: null;
	}

	protected function setBpGroupId()
	{
		update_post_meta($this->ldGroupId, $this->syncMetaKey, $this->bpGroupId);
		return $this;
	}

	protected function setLdGroupId()
	{
		groups_update_groupmeta($this->bpGroupId, $this->syncMetaKey, $this->ldGroupId);
		return $this;
	}

	protected function setSyncGropuIds()
	{
		return $this->setLdGroupId()->setBpGroupId();
	}

	protected function unsetBpGroupMeta($removeProp = true)
	{
		if ($removeProp) {
			$this->bpGroupId = null;
		}

		delete_post_meta($this->ldGroupId, $this->syncMetaKey);
		return $this;
	}

	protected function unsetLdGroupMeta($removeProp = true)
	{
		if ($removeProp) {
			$this->ldGroupId = null;
		}

		groups_delete_groupmeta($this->bpGroupId, $this->syncMetaKey);
		return $this;
	}

	protected function unsetSyncGropuIds()
	{
		$this->unsetBpGroupMeta(false)->unsetLdGroupMeta(false);
		$this->bpGroupId = $this->ldGroupId = null;
		return $this;
	}

	protected function createLearndashGroup()
	{
		$this->syncingToLearndash(function() {
			$bpGroup = groups_get_group($this->bpGroupId);

	    	$this->ldGroupId = wp_insert_post([
				'post_title'   => $bpGroup->name,
				'post_author'  => $bpGroup->creator_id,
				'post_content' => $bpGroup->description,
				'post_status'  => 'publish',
				'post_type'    => learndash_get_post_type_slug('group')
	    	]);

	    	$this->setSyncGropuIds();
		});
	}

	protected function maybeRemoveAsLdUser($type, $userId)
	{
		if ('user' == $this->getBpSyncToRole($type)) {
			return;
		}

		// remove them as user, cause they are leader now
		ld_update_group_access($userId, $this->ldGroupId, true);
	}

	protected function getBpSyncToRole($type)
	{
		return bp_ld_sync('settings')->get("buddypress.default_{$type}_sync_to");
	}

	protected function getBpSyncFunction($type)
	{
		switch ($this->getBpSyncToRole($type)) {
			case 'admin':
				return 'ld_update_leader_group_access';
			default:
				return 'ld_update_group_access';
		}
	}

	protected function clearLdGroupCache()
	{
		delete_transient("learndash_group_leaders_{$this->ldGroupId}");
		delete_transient("learndash_group_users_{$this->ldGroupId}");
	}

	protected function syncingToLearndash($callback)
	{
		global $bp_ld_sync__syncing_to_learndash;

		$bp_ld_sync__syncing_to_learndash = true;
		$callback();
		$bp_ld_sync__syncing_to_learndash = false;

		return $this;
	}

	protected function syncingToBuddypress($callback)
	{
		global $bp_ld_sync__syncing_to_buddypress;

		$bp_ld_sync__syncing_to_buddypress = true;
		$callback();
		$bp_ld_sync__syncing_to_buddypress = false;

		return $this;
	}
}
