<?php
namespace Buddyboss\LearndashIntegration\Buddypress;

class Sync
{
	public $syncing = false;

	public function __construct()
	{
		add_action('bp_ld_sync/init', [$this, 'init']);
	}

	public function init()
	{
		if (! $this->isSyncEnabled()) {
			return;
		}

		add_action('groups_create_group', [$this, 'onGroupCreate']);
		add_action('groups_update_group', [$this, 'onGroupUpdate']);
	}

	public function onGroupCreate($groupId)
	{
		// create from frontend, don't do anything till later step
		if (bp_is_group_create()) {
			return;
		}

		if (! $this->shouldCreateSyncLearndashGroup()) {
			return false;
		}

		$this->syncing = true;
		$this->createSyncedLearndashGroup($groupId);
		$this->syncing = false;
	}

	public function syncOrCreateLearndashGroup($bpGroupId)
	{
    	if (bp_ld_sync('buddypress')->helpers->hasLearndashGroup($bpGroupId)) {
    		return;
    	}

		$bpGroup   = groups_get_group($bpGroupId);
		$ldGroupId = $this->createLdGroupFromBpGroup($bpGroup);
    	bp_ld_sync('buddypress')->helpers->setLearndashGroupId($bpGroupId, $ldGroupId);
	}

	protected function shouldCreateSyncLearndashGroup()
	{
		if (bp_ld_sync()->isRequestExists('sync_on_create')) {
			return !! bp_ld_sync()->getRequest('sync_on_create');
		}

		return bp_ld_sync('settings')->get('buddypress_sync_on_create');
	}

    protected function createSyncedLearndashGroup($groupId)
    {
		$bpGroup   = groups_get_group($groupId);

    	$groupId = wp_insert_post([
			'post_title'   => $bpGroup->name,
			'post_author'  => $bpGroup->creator_id,
			'post_content' => $bpGroup->description,
			'post_status'  => 'publish',
			'post_type'    => learndash_get_post_type_slug('group')
    	]);

		$admins = learndash_get_groups_administrators($groupId);
		$admins[] = $bpGroup->creator_id;

		learndash_set_groups_administrators($groupId, [$bpGroup->creator_id]);

		do_action('bp_ld_sync/ld_group_created_from_bp_group', $groupId, $bpGroup);

		return $groupId;
    }

	public function isSyncEnabled()
	{
		$settings = get_option('learndash_settings_buddypress_groups_sync');

		return $settings['auto_create_bp_group'];
	}
}
