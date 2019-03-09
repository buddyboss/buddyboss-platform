<?php
/**
 * @todo add description
 * 
 * @package BuddyBoss\LearnDash
 * @since BuddyBoss 1.0.0
 */ 

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

namespace Buddyboss\LearndashIntegration\Buddypress\Components;

use BP_Group_Extension;

/**
 * 
 * 
 * @since BuddyBoss 1.0.0
 */
class BpGroupReports extends BP_Group_Extension
{
	/**
	 * 
	 *
	 * @since BuddyBoss 1.0.0
	 */
	public function __construct()
	{
        parent::init($this->prepareComponentOptions());
	}

    /**
	 * 
	 *
	 * @since BuddyBoss 1.0.0
	 */
	public function display($groupId = null)
	{
		$this->loadSubMenuTemplate($groupId);

		$action = bp_action_variable() ?: 'reports';

		if (! $location = bp_locate_template("groups/single/reports-{$action}.php", true)) {
			bp_locate_template('groups/single/reports-404.php', true);
		}
    }

    /**
	 * 
	 *
	 * @since BuddyBoss 1.0.0
	 */
	protected function loadSubMenuTemplate($groupId)
    {
		$groupId     = $groupId ?: bp_get_new_group_id();
		$hasLdGroup  = bp_ld_sync('buddypress')->sync->generator($groupId)->hasLdGroup();
		$currentMenu = bp_action_variable();
		$subMenus    = array_map(function($menu) {
			$menu['url'] = bp_ld_sync('buddypress')->subMenuLink($menu['slug']);
			return $menu;
		}, bp_ld_sync('buddypress')->reportsSubMenus());

		require bp_locate_template('groups/single/reports-nav.php', false, false);
    }

    /**
	 * 
	 *
	 * @since BuddyBoss 1.0.0
	 */
	protected function prepareComponentOptions()
    {
		$tabName     = apply_filters('bp_ld_sync/reports_group_tab_name', __('Reports', 'buddyboss'));
		$tabSlug     = apply_filters('bp_ld_sync/reports_group_tab_slug', 'reports');
		$tabPosition = apply_filters('bp_ld_sync/reports_group_tab_position', 15);

    	return [
			'name' => $tabName,
			'slug' => $tabSlug,
			'nav_item_position' => $tabPosition,
			'access' => apply_filters('bp_ld_sync/reports_group_tab_enabled', $this->showTabOnView()),

			'screens' => [
				'create' => [
					'enabled' => false,
				],
				'edit' => [
					'enabled' => false,
				],
				'admin'  => [
					'enabled' => false,
				],
			]
		];
    }

    /**
	 * 
	 *
	 * @since BuddyBoss 1.0.0
	 */
	protected function showTabOnView()
    {
    	if (! $currentGroup = groups_get_current_group()) {
    		return 'noone';
    	}

    	$generator = bp_ld_sync('buddypress')->sync->generator($currentGroup->id);
    	if (! $generator->hasLdGroup()) {
    		return 'noone';
    	}

    	if (! learndash_group_enrolled_courses($generator->getLdGroupId())) {
    		return 'noone';
    	}

		// admin can always view
		if (learndash_is_admin_user()) {
			return true;
		}

		foreach (bp_ld_sync('settings')->get('reports.access', []) as $type) {
			$function = "groups_is_user_{$type}";
			if (function_exists($function) && call_user_func_array($function, [bp_loggedin_user_id(), $currentGroup->id])) {
				return true;
			}
		}

		return 'noone';
    }
}
