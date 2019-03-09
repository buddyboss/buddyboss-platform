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
class Admin
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
		if (! bp_ld_sync('settings')->get('learndash.enabled')) {
			return;
		}

        add_action('add_meta_boxes', [$this, 'addGroupSyncMetaBox']);
	}

	/**
	 * 
	 *
	 * @since BuddyBoss 1.0.0
	 */
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

    /**
	 * 
	 *
	 * @since BuddyBoss 1.0.0
	 */
	public function asyncMetaboxHtml()
    {
		$groupId    = get_the_ID();
		$generator  = bp_ld_sync('learndash')->sync->generator(null, $groupId);
		$hasBpGroup = $generator->hasBpGroup();
		$bpGroupId  = $hasBpGroup? $generator->getBpGroupId() : 0;
		$bpGroup    = groups_get_group($bpGroupId);
		$availableBpGroups = bp_ld_sync('buddypress')->group->getUnassociatedGroups($groupId);
		$checked = get_current_screen()->action == 'add'?  bp_ld_sync('settings')->get('learndash.default_auto_sync') : $hasBpGroup;

    	require bp_ld_sync()->template('/admin/learndash/sync-meta-box.php');
    }
}
