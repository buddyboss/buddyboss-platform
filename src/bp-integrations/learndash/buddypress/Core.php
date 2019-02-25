<?php

namespace Buddyboss\LearndashIntegration\Buddypress;

use Buddyboss\LearndashIntegration\Buddypress\Admin;
use Buddyboss\LearndashIntegration\Buddypress\Ajax;
use Buddyboss\LearndashIntegration\Buddypress\Components\BpGroupCourses;
use Buddyboss\LearndashIntegration\Buddypress\Components\BpGroupReports;
use Buddyboss\LearndashIntegration\Buddypress\Courses;
use Buddyboss\LearndashIntegration\Buddypress\Forum;
use Buddyboss\LearndashIntegration\Buddypress\Group;
use Buddyboss\LearndashIntegration\Buddypress\GroupBpComponent;
use Buddyboss\LearndashIntegration\Buddypress\Helpers;
use Buddyboss\LearndashIntegration\Buddypress\Hooks;
use Buddyboss\LearndashIntegration\Buddypress\Reports;
use Buddyboss\LearndashIntegration\Buddypress\Sync;

class Core
{
	public function __construct()
	{
		$this->helpers = new Helpers;
		$this->courses = new Courses;
		$this->reports = new Reports;
		$this->ajax    = new Ajax;
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

		if (bp_ld_sync('settings')->get('buddypress.enabled')) {
			require_once bp_ld_sync()->path('/buddypress/components/BpGroupCourses.php');
			$extension = new BpGroupCourses;
			add_action('bp_actions', [$extension, '_register'], 8);
			add_action('admin_init', [$extension, '_register']);
		}

		if (bp_ld_sync('settings')->get('reports.enabled')) {
			require_once bp_ld_sync()->path('/buddypress/components/BpGroupReports.php');
			$extension = new BpGroupReports;
			add_action('bp_actions', [$extension, '_register'], 8);
			add_action('admin_init', [$extension, '_register']);
		}
	}

	public function registerPluginTemplate()
	{
		return bp_learndash_path('/templates');
	}

    public function coursesSubMenus()
    {
    	return wp_list_sort(apply_filters('bp_ld_sync/courses_group_tab_subnavs', [
    		'courses' => [
				'name'     => __('Courses', 'buddyboss'),
				'slug'     => '',
				'position' => 10
    		],
    	]), 'position', 'ASC', true);
    }

    public function reportsSubMenus()
    {
    	return wp_list_sort(apply_filters('bp_ld_sync/reports_group_tab_subnavs', [
    		'reports' => [
				'name'     => __('Reports', 'buddyboss'),
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
