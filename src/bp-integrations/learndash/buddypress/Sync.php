<?php
namespace Buddyboss\LearndashIntegration\Buddypress;

use Buddyboss\LearndashIntegration\Library\SyncGenerator;

class Sync
{
	// temporarily hold the synced learndash group id just before delete
	protected $deletingSyncedLdGroupId;

	public function __construct()
	{
		add_action('bp_ld_sync/init', [$this, 'init']);
	}

	public function init()
	{
		if (! bp_ld_sync('settings')->get('buddypress.enabled')) {
			return;
		}

		add_action('bp_ld_sync/buddypress_group_created', [$this, 'onGroupCreate']);
		// add_action('bp_ld_sync/buddypress_group_updated', [$this, 'onGroupUpdate']);
		add_action('bp_ld_sync/buddypress_group_deleting', [$this, 'onGroupDeleting']);
		add_action('bp_ld_sync/buddypress_group_deleted', [$this, 'onGroupDeleted']);

		add_action('bp_ld_sync/buddypress_group_admin_added', [$this, 'onAdminAdded'], 10, 3);
		add_action('bp_ld_sync/buddypress_group_mod_added', [$this, 'onModAdded'], 10, 3);
		add_action('bp_ld_sync/buddypress_group_member_added', [$this, 'onMemberAdded'], 10, 3);

		add_action('bp_ld_sync/buddypress_group_admin_removed', [$this, 'onAdminRemoved'], 10, 3);
		add_action('bp_ld_sync/buddypress_group_mod_removed', [$this, 'onModRemoved'], 10, 3);
		add_action('bp_ld_sync/buddypress_group_member_removed', [$this, 'onMemberRemoved'], 10, 3);
		add_action('bp_ld_sync/buddypress_group_member_banned', [$this, 'onMemberRemoved'], 10, 3);
	}

	public function generator($bpGroupId = null, $ldGroupId = null)
	{
		return new SyncGenerator($bpGroupId, $ldGroupId);
	}

	public function onGroupCreate($groupId)
	{
		global $bp_ld_sync__syncing_to_buddypress;

		// if it's group is created from learndash sync, don't need to sync back
		if ($bp_ld_sync__syncing_to_buddypress) {
			return false;
		}

		$settings = bp_ld_sync('settings');

		// on the group creation first step, and create tab is enabled, we create sync group in later step
		if ('group-details' == bp_get_groups_current_create_step() && $settings->get('buddypress.show_in_bp_create')) {
			return false;
		}

		// if auto sync is turn off
		if (! $settings->get('buddypress.default_auto_sync')) {
			return false;
		}

		// admin is added BEFORE this hook is called, so we need to manually sync admin
		// src/bp-groups/bp-groups-functions.php:194
		$this->generator($groupId)->syncToLearndash()->syncBpAdmins();
	}

	public function onGroupDeleting($groupId)
	{
		global $bp_ld_sync__syncing_to_buddypress;

		// if it's group is created from learndash sync, don't need to sync back
		if ($bp_ld_sync__syncing_to_buddypress) {
			return false;
		}

		$this->deletingSyncedLdGroupId = $this->generator($groupId)->getLdGroupId();
	}

	public function onGroupDeleted($groupId)
	{
		if (! $ldGroupId = $this->deletingSyncedLdGroupId) {
			return;
		}

		$this->deletingSyncedLdGroupId = null;

		if (! bp_ld_sync('settings')->get('buddypress.delete_ld_on_delete')) {
			$this->generator(null, $ldGroupId)->desyncFromBuddypress();
			return;
		}

		$this->generator()->deleteLdGroup($ldGroupId);
	}

	public function onAdminAdded($groupId, $memberId, $groupMemberObject)
	{
		if (! $generator = $this->groupUserEditCheck('admin', $groupId)) {
			return false;
		}

		$generator->syncBpAdmin($memberId);
	}

	public function onModAdded($groupId, $memberId, $groupMemberObject)
	{
		if (! $generator = $this->groupUserEditCheck('mod', $groupId)) {
			return false;
		}

		$generator->syncBpMod($memberId);
	}

	public function onMemberAdded($groupId, $memberId, $groupMemberObject)
	{
		if (! $generator = $this->groupUserEditCheck('user', $groupId)) {
			return false;
		}

		$generator->syncBpMember($memberId);
	}

	public function onAdminRemoved($groupId, $memberId, $groupMemberObject)
	{
		if (! $generator = $this->groupUserEditCheck('admin', $groupId)) {
			return false;
		}

		$generator->syncBpAdmin($memberId, true);
	}

	public function onModRemoved($groupId, $memberId, $groupMemberObject)
	{
		if (! $generator = $this->groupUserEditCheck('mod', $groupId)) {
			return false;
		}

		$generator->syncBpMod($memberId, true);
	}

	public function onMemberRemoved($groupId, $memberId, $groupMemberObject)
	{
		if (! $generator = $this->groupUserEditCheck('user', $groupId)) {
			return false;
		}

		$generator->syncBpMember($memberId, true);
	}

	protected function groupUserEditCheck($role, $groupId)
	{
		$settings = bp_ld_sync('settings');

		if (! $settings->get('buddypress.enabled')) {
			return false;
		}

		if ('none' == $settings->get("buddypress.default_{$role}_sync_to")) {
			return false;
		}

		$generator = $this->generator($groupId);

		if (! $generator->hasLdGroup()) {
			return false;
		}

		return $generator;
	}
}
