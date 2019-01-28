<?php

namespace Buddyboss\LearndashIntegration\Buddypress;

class Admin
{
	public function __construct()
	{
		add_action('bp_ld_sync/init', [$this, 'init']);
	}

	public function init()
	{
		if (! bp_ld_sync('settings')->get('buddypress.enabled')) {
			return;
		}

        add_action('bp_group_admin_edit_after', [$this, 'saveGroupSyncMetaBox']);
        add_action('bp_groups_admin_meta_boxes', [$this, 'addGroupSyncMetaBox']);
	}

	public function saveGroupSyncMetaBox($groupId)
	{
		// created from backend
		if (bp_ld_sync()->isRequestExists('bp-ld-sync-enable') && ! bp_ld_sync()->getRequest('bp-ld-sync-enable')) {
			bp_ld_sync('buddypress')->sync->generator($groupId)->desyncFromLearndash();
			return false;
		}

		$newGroup = bp_ld_sync()->getRequest('bp-ld-sync-id', null);
		$generator = bp_ld_sync('buddypress')->sync->generator($groupId);

		if ($generator->hasLdGroup() && $generator->getLdGroupId() == $newGroup) {
			return false;
		}

		$generator->associateToLearndash($newGroup)
			->syncBpAdmins()
			->syncBpMods()
			->syncBpUsers();
	}

	public function addGroupSyncMetaBox()
	{
        add_meta_box(
            'bp_ld_sync-buddypress-sync',
            __('Associated LearnDash Group', 'buddyboss'),
            [$this, 'asyncMetaboxHtml'],
            get_current_screen()->id,
            'side'
        );
	}

    public function asyncMetaboxHtml()
    {
		$groupId    = bp_ld_sync()->getRequest('gid');
		$generator  = bp_ld_sync('buddypress')->sync->generator($groupId);
		$hasLdGroup = $generator->hasLdGroup();
		$ldGroupId  = $hasLdGroup? $generator->getLdGroupId() : 0;
		$ldGroup    = get_post($ldGroupId);
		$availableLdGroups = bp_ld_sync('learndash')->group->getUnassociatedGroups($groupId);

    	require bp_ld_sync()->template('/admin/buddypress/sync-meta-box.php');
    }
}
