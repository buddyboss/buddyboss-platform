<?php

namespace Buddyboss\LearndashIntegration\Library;

use BP_Groups_Member;

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

	public function associateToLearndash($ldGroupId = null)
	{
		if ($this->ldGroupId && ! $ldGroupId) {
			return $this;
		}

		$this->syncingToLearndash(function() use ($ldGroupId) {
			$ldGroup = get_post($ldGroupId);

			if (! $ldGroupId || ! $ldGroup) {
				$this->createLearndashGroup();
			} else {
				$this->unsetBpGroupMeta(false)->unsetLdGroupMeta(false);
				$this->ldGroupId = $ldGroupId;
			}

    		$this->setSyncGropuIds();
		});

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

	public function deleteBpGroup($bpGroupId)
	{
		$this->syncingToBuddypress(function() use ($bpGroupId) {
			groups_delete_group($bpGroupId);
		});
	}

	public function deleteLdGroup($ldGroupId)
	{
		$this->syncingToLearndash(function() use ($ldGroupId) {
			wp_delete_post($ldGroupId, true);
		});
	}

	public function associateToBuddypress($bpGroupId = null)
	{
		if ($this->bpGroupId && ! $bpGroupId) {
			return $this;
		}

		$this->syncingToBuddypress(function() use ($bpGroupId) {
			$bpGroup = groups_get_group($bpGroupId);

			if (! $bpGroupId || ! $bpGroup->id) {
				$this->createBuddypressGroup();
			} else {
				$this->unsetBpGroupMeta(false)->unsetLdGroupMeta(false);
				$this->bpGroupId = $bpGroupId;
			}

    		$this->setSyncGropuIds();
		});

		return $this;
	}

	public function desyncFromBuddypress()
	{
		if (! $this->bpGroupId) {
			return $this;
		}

		$this->unsetSyncGropuIds();

		return $this;
	}

	public function fullSyncToLearndash()
	{
		$lastSynced = groups_get_groupmeta($this->bpGroupId, '_last_sync', true) ?: 0;

		if ($lastSynced > $this->getLastSyncTimestamp('bp')) {
			return;
		}

		$this->syncBpUsers()->syncBpMods()->syncBpAdmins();
		groups_update_groupmeta($this->bpGroupId, '_last_sync', time());
	}

	public function fullSyncToBuddypress()
	{
		$lastSynced = groups_get_groupmeta($this->ldGroupId, '_last_sync', true) ?: 0;

		if ($lastSynced > $this->getLastSyncTimestamp('ld')) {
			return;
		}

		$this->syncLdAdmins()->syncLdUsers();
		update_post_meta($this->ldGroupId, '_last_sync', time());
	}

	public function syncBpAdmins()
	{
		$this->syncingToLearndash(function() {
			$adminIds = groups_get_group_admins($this->bpGroupId);

			foreach ($adminIds as $admin) {
				$this->syncBpAdmin($admin->user_id, false, false);
			}
		});

		$this->clearLdGroupCache();

		return $this;
	}

	public function syncBpMods()
	{
		$this->syncingToLearndash(function() {
			$modIds = groups_get_group_mods($this->bpGroupId);

			foreach ($modIds as $mod) {
				$this->syncBpMod($mod->user_id, false, false);
			}
		});

		$this->clearLdGroupCache();

		return $this;
	}

	public function syncBpUsers()
	{
		$this->syncingToLearndash(function() {
			$members = groups_get_group_members([
				'group_id' => $this->bpGroupId
			])['members'];

			foreach ($members as $member) {
				$this->syncBpMember($member->ID, false, false);
			}
		});

		$this->clearLdGroupCache();

		return $this;
	}

	public function syncLdAdmins()
	{
		$this->syncingToBuddypress(function() {
			$adminIds = learndash_get_groups_administrator_ids($this->ldGroupId);

			foreach ($adminIds as $adminId) {
				$this->syncLdAdmin($adminId);
			}
		});

		return $this;
	}

	public function syncLdUsers()
	{
		$this->syncingToBuddypress(function() {
			$userIds = learndash_get_groups_user_ids($this->ldGroupId);

			foreach ($userIds as $userId) {
				$this->syncLdUser($userId);
			}
		});

		return $this;
	}

	public function syncBpAdmin($userId, $remove = false, $clearCache = true)
	{
		$this->syncingToLearndash(function() use ($userId, $remove) {
			call_user_func_array($this->getBpSyncFunction('admin'), [$userId, $this->ldGroupId, $remove]);
			$this->maybeRemoveAsLdUser('admin', $userId);
		});

		if ($clearCache) {
			$this->clearLdGroupCache();
		}

		return $this;
	}

	public function syncBpMod($userId, $remove = false, $clearCache = true)
	{
		$this->syncingToLearndash(function() use ($userId, $remove) {
			call_user_func_array($this->getBpSyncFunction('mod'), [$userId, $this->ldGroupId, $remove]);
			$this->maybeRemoveAsLdUser('mod', $userId);
		});

		if ($clearCache) {
			$this->clearLdGroupCache();
		}

		return $this;
	}

	public function syncBpMember($userId, $remove = false, $clearCache = true)
	{
		$this->syncingToLearndash(function() use ($userId, $remove) {
			call_user_func_array($this->getBpSyncFunction('user'), [$userId, $this->ldGroupId, $remove]);

			// if sync to user, we need to remove previous admin
			if ('user' == $this->getBpSyncToRole('user')) {
				call_user_func_array('ld_update_leader_group_access', [$userId, $this->ldGroupId, true]);
			}
		});

		if ($clearCache) {
			$this->clearLdGroupCache();
		}

		return $this;
	}

	public function syncLdAdmin($userId, $remove = false)
	{
		$this->syncingToBuddypress(function() use ($userId, $remove) {
			$this->addUserToBpGroup($userId, 'admin', $remove);
		});

		return $this;
	}

	public function syncLdUser($userId, $remove = false)
	{
		$ldGroupAdmins = learndash_get_groups_administrator_ids($this->ldGroupid);

		// if this user is learndash leader, we don't want to downgrad them (bp only allow 1 user)
		if (in_array($userId, $ldGroupAdmins)) {
			return $this;
		}

		$this->syncingToBuddypress(function() use ($userId, $remove) {
			$this->addUserToBpGroup($userId, 'user', $remove);
		});

		return $this;
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
		$bpGroup = groups_get_group($this->bpGroupId);

    	$this->ldGroupId = wp_insert_post([
			'post_title'   => $bpGroup->name,
			'post_author'  => $bpGroup->creator_id,
			'post_content' => $bpGroup->description,
			'post_status'  => 'publish',
			'post_type'    => learndash_get_post_type_slug('group')
    	]);
	}

	protected function createBuddypressGroup()
	{
		$ldGroup = get_post($this->ldGroupId);
		$settings = bp_ld_sync('settings');

    	$this->bpGroupId = groups_create_group([
			'name'   => $ldGroup->post_title ?: "For Social Group: {$this->ldGroupId}",
			'status' => $settings->get('learndash.default_bp_privacy'),
		]);

		groups_update_groupmeta($this->bpGroupId, 'invite_status', $settings->get('learndash.default_bp_invite_status'));

    	$this->setSyncGropuIds();
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

	protected function getLdSyncToRole($type)
	{
		return bp_ld_sync('settings')->get("learndash.default_{$type}_sync_to");
	}

	protected function addUserToBpGroup($userId, $type, $remove)
	{
		$groupMember = new BP_Groups_Member($userId, $this->bpGroupId);
		$syncTo = $this->getLdSyncToRole($type);

		if ($remove) {
			return $groupMember->remove();
		}

		$groupMember->group_id     = $this->bpGroupId;
		$groupMember->user_id      = $userId;
		$groupMember->is_admin     = 0;
		$groupMember->is_mod       = 0;
		$groupMember->is_confirmed = 1;

		if ('user' != $syncTo) {
			$var = "is_{$syncTo}";
			$groupMember->$var = 1;
		}

		$groupMember->save();
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

	protected function getLastSyncTimestamp($type = 'bp')
	{
		if (! $lastSync = bp_get_option("bp_ld_sync/{$type}_last_synced")) {
			$lastSync = time();
			bp_update_option("bp_ld_sync/{$type}_last_synced", $lastSync);
		}

		return $lastSync;
	}
}
