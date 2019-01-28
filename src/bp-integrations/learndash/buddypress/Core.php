<?php

namespace Buddyboss\LearndashIntegration\Buddypress;

use Buddyboss\LearndashIntegration\Buddypress\Ajax;
use Buddyboss\LearndashIntegration\Buddypress\Courses;
use Buddyboss\LearndashIntegration\Buddypress\Sync;
use Buddyboss\LearndashIntegration\Buddypress\Hooks;
use Buddyboss\LearndashIntegration\Buddypress\Forum;
use Buddyboss\LearndashIntegration\Buddypress\GroupBpComponent;
use Buddyboss\LearndashIntegration\Buddypress\Helpers;
use Buddyboss\LearndashIntegration\Buddypress\Reports;
use Buddyboss\LearndashIntegration\Buddypress\Admin;
use Buddyboss\LearndashIntegration\Buddypress\Group;

class Core
{
	public function __construct()
	{
		$this->helpers = new Helpers;
		$this->courses = new Courses;
		$this->reports = new Reports;
		$this->ajax    = new Ajax;
		$this->forum   = new Forum;
		$this->sync    = new Sync;
		$this->hooks   = new Hooks;
		$this->admin   = new Admin;
		$this->group   = new Group;

		add_action('bp_ld_sync/init', [$this, 'init']);
	}

	public function init()
	{
		$this->registerTemplateStack();
		$this->registerGroupComponent();
	}

	protected function registerTemplateStack()
	{
		bp_register_template_stack([$this, 'registerPluginTemplate']);
	}

	protected function registerGroupComponent()
	{
		if (! bp_is_group() && ! bp_is_group_create()) {
			return;
		}

		if (! bp_ld_sync('settings')->get('buddypress.enabled')) {
			return;
		}

		// if (! bp_ld_sync('buddypress')->helpers->hasLearndashGroup(groups_get_current_group()->id)) {
		// 	return;
		// }

		require_once bp_ld_sync()->path('/buddypress/GroupBpComponent.php');
		$extension = new GroupBpComponent;
		add_action('bp_actions', [$extension, '_register'], 8);
		add_action('admin_init', [$extension, '_register']);
	}

	public function registerPluginTemplate()
	{
		return bp_learndash_path('/templates');
	}

    public function subMenus()
    {
    	return wp_list_sort(apply_filters('bp_ld_sync/group_tab_subnavs', [
    		'courses' => [
				'name'     => _x('Courses', 'Buddypress Group Subnev Name', 'buddyboss'),
				'slug'     => '',
				'position' => 10
    		],
    	]), 'position', 'ASC', true);
    }

    public function subMenuLink($slug)
    {
		$groupUrl = untrailingslashit(bp_get_group_permalink(groups_get_current_group()));
		$action   = bp_current_action();
    	return untrailingslashit("{$groupUrl}/{$action}/{$slug}");
    }
}
