<?php
/**
 * @todo add description
 * 
 * @package BuddyBoss\LearnDash
 * @since BuddyBoss 1.0.0
 */

namespace Buddyboss\LearndashIntegration\Buddypress\Components;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

use BP_Group_Extension;

/**
 * @todo add title/description
 * 
 * @since BuddyBoss 1.0.0
 */
class BpGroupCourses extends BP_Group_Extension
{
	/**
	 * @todo add title/description
	 *
	 * @since BuddyBoss 1.0.0
	 */
	public function __construct()
	{
        parent::init($this->prepareComponentOptions());
	}

	/**
	 * @todo add title/description
	 *
	 * @since BuddyBoss 1.0.0
	 */
	public function settings_screen($groupId = null)
	{
		$groupId    = $groupId ?: bp_get_new_group_id();
		$hasLdGroup = bp_ld_sync('buddypress')->sync->generator($groupId)->hasLdGroup();
		$ldGroupId  = $hasLdGroup? bp_ld_sync('buddypress')->sync->generator($groupId)->getLdGroupId() : 0;

		require bp_locate_template('groups/single/admin/edit-courses.php', false);
    }

    /**
	 * @todo add title/description
	 *
	 * @since BuddyBoss 1.0.0
	 */
	public function settings_screen_save($groupId = null)
    {
    	$generator = bp_ld_sync('buddypress')->sync->generator($groupId);

    	if (! bp_ld_sync()->getRequest('bp-ld-sync-enable')) {
    		return $generator->desyncFromLearndash();
    	}

    	$generator->associateToLearndash()->syncBpAdmins();
    }

    /**
	 * @todo add title/description
	 *
	 * @since BuddyBoss 1.0.0
	 */
	public function display($groupId = null)
	{
		$this->loadSubMenuTemplate($groupId);

		$action = bp_action_variable() ?: 'courses';

		if (! $location = bp_locate_template("groups/single/courses-{$action}.php", true)) {
			bp_locate_template('groups/single/courses-404.php', true);
		}
    }

    /**
	 * @todo add title/description
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
		}, bp_ld_sync('buddypress')->coursesSubMenus());

		require bp_locate_template('groups/single/courses-nav.php', false, false);
    }

    /**
	 * @todo add title/description
	 *
	 * @since BuddyBoss 1.0.0
	 */
	protected function prepareComponentOptions()
    {
		$tabName     = apply_filters('bp_ld_sync/courses_group_tab_name', $this->tabLabel());
		$tabSlug     = apply_filters('bp_ld_sync/courses_group_tab_slug', 'courses');
		$tabPosition = apply_filters('bp_ld_sync/courses_group_tab_position', 15);
		// learndash_is_group_leader_user

    	return [
			'name' => $tabName,
			'slug' => $tabSlug,
			'nav_item_position' => $tabPosition,
			'access' => apply_filters('bp_ld_sync/courses_group_tab_enabled', $this->showTabOnView()),

			'screens' => [
				'create' => [
					'enabled'         => apply_filters('bp_ld_sync/courses_group_tab_enabled/screen=create', $this->showTabOnCreate()),
					'name'            => apply_filters('bp_ld_sync/courses_group_tab_name/screen=create', $tabName),
					'slug'            => apply_filters('bp_ld_sync/courses_group_tab_slug/screen=create', $tabSlug),
					'position'        => apply_filters('bp_ld_sync/courses_group_tab_position/screen=create', $tabPosition),
					// 'screen_callback' => '',
					// 'save_callback'   => '', // ??
				],

				'edit' => [
					'enabled'         => apply_filters('bp_ld_sync/courses_group_tab_enabled/screen=edit', true),
					'name'            => apply_filters('bp_ld_sync/courses_group_tab_name/screen=edit', $tabName),
					'slug'            => apply_filters('bp_ld_sync/courses_group_tab_slug/screen=edit', $tabSlug),
					'position'        => apply_filters('bp_ld_sync/courses_group_tab_position/screen=edit', $tabPosition),
					// 'screen_callback' => '',
					// 'save_callback'   => '', // ??
					// 'submit_text' => ''
				],

				'admin'  => array(
					'metabox_context'  => 'normal',
					'metabox_priority' => 'core',
				),
			]
		];
    }

    /**
	 * @todo add title/description
	 *
	 * @since BuddyBoss 1.0.0
	 */
	protected function tabLabel()
    {
    	$default = __('Courses', 'buddyboss');

    	if (! $currentGroup = groups_get_current_group()) {
    		return $default;
    	}

    	$coursesCount = count(bp_learndash_get_group_courses($currentGroup->id));

    	return _nx('Course', 'Courses', $coursesCount, 'bp group tab name', 'buddyboss');
    }

    /**
	 * @todo add title/description
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

    	return bp_ld_sync('settings')->get('buddypress.tab_access', true);
    }

    /**
	 * @todo add title/description
	 *
	 * @since BuddyBoss 1.0.0
	 */
	protected function showTabOnCreate()
    {
    	return bp_ld_sync('settings')->get('buddypress.show_in_bp_create', true);
    }
}
