<?php
/**
 * BuddyBoss LearnDash integration admin class.
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
		if (! bp_ld_sync('settings')->get('buddypress.enabled')) {
			return;
		}

        add_action('bp_group_admin_edit_after', [$this, 'saveGroupSyncMetaBox']);
        add_action('bp_groups_admin_meta_boxes', [$this, 'addGroupSyncMetaBox']);
	}

	/**
	 * 
	 *
	 * @since BuddyBoss 1.0.0
	 */
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

	/**
	 * 
	 *
	 * @since BuddyBoss 1.0.0
	 */
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

    /**
	 * 
	 *
	 * @since BuddyBoss 1.0.0
	 */
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
