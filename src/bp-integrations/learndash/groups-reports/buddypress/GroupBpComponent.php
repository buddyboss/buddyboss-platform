<?php

namespace Buddyboss\LearndashIntegration\Buddypress;

use BP_Group_Extension;

class GroupBpComponent extends BP_Group_Extension
{
	public function __construct()
	{
        parent::init($this->prepareComponentOptions());
	}

	public function settings_screen($groupId = null)
	{
		$groupId = $groupId ?: bp_get_new_group_id();
		$hasLdGroup = bp_ld_sync('buddypress')->helpers->hasLearndashGroup($groupId);
		require bp_ld_sync()->template('/groups/single/admin/edit-courses.php');
    }

    public function settings_screen_save($groupId = null)
    {
    	if (! isset($_POST['bp-ld-sync-group-ld-group']) || ! $_POST['bp-ld-sync-group-ld-group']) {
    		return;
    	}
    }

    public function display($groupId = null)
	{
		$this->loadSubMenuTemplate($groupId);

		$action = bp_action_variable() ?: 'courses';

		if (! $location = bp_locate_template("groups/single/courses-{$action}.php", true)) {
			bp_locate_template('groups/single/courses-404.php', true);
		}
    }

    protected function loadSubMenuTemplate($groupId)
    {
		$groupId     = $groupId ?: bp_get_new_group_id();
		$hasLdGroup  = bp_ld_sync('buddypress')->helpers->hasLearndashGroup($groupId);
		$currentMenu = bp_action_variable();
		$subMenus    = array_map(function($menu) {
			$menu['url'] = bp_ld_sync('buddypress')->subMenuLink($menu['slug']);
			return $menu;
		}, bp_ld_sync('buddypress')->subMenus());

		require bp_locate_template('groups/single/courses-nav.php', false, false);
    }

    protected function prepareComponentOptions()
    {
		$tabName     = apply_filters('bp_ld_sync/group_tab_name', __('Courses', 'buddyboss'));
		$tabSlug     = apply_filters('bp_ld_sync/group_tab_slug', 'courses');
		$tabPosition = apply_filters('bp_ld_sync/group_tab_position', 20);
		// learndash_is_group_leader_user

    	return [
			'name' => $tabName, // name (use for acf's location label)
			'slug' => $tabSlug, // slug (use for acf's location slug)

			'screens' => [
				'view' => [
					'enabled'         => apply_filters('bp_ld_sync/group_tab_enabled/screen=view', true),
					'name'            => apply_filters('bp_ld_sync/group_tab_name/screen=view', $tabName),
					'slug'            => apply_filters('bp_ld_sync/group_tab_slug/screen=view', $tabSlug),
					'position'        => apply_filters('bp_ld_sync/group_tab_position/screen=view', $tabPosition),
					// 'screen_callback' => '',
					// 'save_callback'   => '', // ??
				],

				'create' => [
					'enabled'         => apply_filters('bp_ld_sync/group_tab_enabled/screen=create', true),
					'name'            => apply_filters('bp_ld_sync/group_tab_name/screen=create', $tabName),
					'slug'            => apply_filters('bp_ld_sync/group_tab_slug/screen=create', $tabSlug),
					'position'        => apply_filters('bp_ld_sync/group_tab_position/screen=create', $tabPosition),
					// 'screen_callback' => '',
					// 'save_callback'   => '', // ??
				],

				'edit' => [
					'enabled'         => apply_filters('bp_ld_sync/group_tab_enabled/screen=edit', true),
					'name'            => apply_filters('bp_ld_sync/group_tab_name/screen=edit', $tabName),
					'slug'            => apply_filters('bp_ld_sync/group_tab_slug/screen=edit', $tabSlug),
					'position'        => apply_filters('bp_ld_sync/group_tab_position/screen=edit', $tabPosition),
					// 'screen_callback' => '',
					// 'save_callback'   => '', // ??
					// 'submit_text' => ''
				],
			]
		];
    }
}
