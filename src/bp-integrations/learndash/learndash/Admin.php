<?php

namespace Buddyboss\LearndashIntegration\Learndash;

class Admin
{
	public function __construct()
	{
		add_action('bp_ld_sync/init', [$this, 'init']);
	}

	public function init()
	{
        add_action('add_meta_boxes', [$this, 'addGroupSyncMetaBox']);
	}

	public function addGroupSyncMetaBox()
	{
        add_meta_box(
            'bp_ld_sync-learndash-sync',
            __('Associated Social Group', 'buddyboss'),
            [$this, 'asyncMetaboxHtml'],
            'groups',
            'side'
        );
	}

    public function asyncMetaboxHtml()
    {
		$groupId    = get_the_ID();
		$generator  = bp_ld_sync('learndash')->sync->generator(null, $groupId);
		$hasBpGroup = $generator->hasBpGroup();
		$bpGroupId  = $hasBpGroup? $generator->getBpGroupId() : 0;
		$bpGroup    = groups_get_group($bpGroupId);

    	require bp_ld_sync()->template('/admin/learndash/sync-meta-box.php');
    }
}
