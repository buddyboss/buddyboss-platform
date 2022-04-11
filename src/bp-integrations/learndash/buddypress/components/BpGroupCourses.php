<?php
/**
 * BuddyBoss LearnDash integration BpGroupCourses class.
 *
 * @package BuddyBoss\LearnDash
 * @since BuddyBoss 1.0.0
 */

namespace Buddyboss\LearndashIntegration\Buddypress\Components;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

use BP_Group_Extension;

/**
 * Exttends Buddypress Group Tab
 *
 * @since BuddyBoss 1.0.0
 */
class BpGroupCourses extends BP_Group_Extension {

	/**
	 * Constructor
	 *
	 * @since BuddyBoss 1.0.0
	 */
	public function __construct() {
		 parent::init( $this->prepareComponentOptions() );
	}

	/**
	 * Displays the settings for all views
	 *
	 * @since BuddyBoss 1.0.0
	 */
	public function settings_screen( $groupId = null ) {
		$groupId    = $groupId ?: bp_get_new_group_id();
		$hasLdGroup = bp_ld_sync( 'buddypress' )->sync->generator( $groupId )->hasLdGroup();
		$ldGroupId  = $hasLdGroup ? bp_ld_sync( 'buddypress' )->sync->generator( $groupId )->getLdGroupId() : 0;

		require bp_locate_template( 'groups/single/admin/edit-courses.php', false );
	}

	/**
	 * Saving the settings for all views
	 *
	 * @since BuddyBoss 1.0.0
	 */
	public function settings_screen_save( $groupId = null ) {
		$generator = bp_ld_sync( 'buddypress' )->sync->generator( $groupId );

		if ( ! bp_ld_sync()->getRequest( 'bp-ld-sync-enable' ) ) {
			return $generator->desyncFromLearndash();
		}

		$generator->associateToLearndash()->syncBpAdmins();
	}

	/**
	 * Display the tab content based on the selected sub tab
	 *
	 * @since BuddyBoss 1.0.0
	 */
	public function display( $groupId = null ) {
		$this->loadSubMenuTemplate( $groupId );

		$action = bp_action_variable() ?: 'courses';

		if ( ! $location = bp_locate_template( "groups/single/courses-{$action}.php", true ) ) {
			bp_locate_template( 'groups/single/courses-404.php', true );
		}
	}

	/**
	 * Display the tab sub menu before the tab content
	 *
	 * @since BuddyBoss 1.0.0
	 */
	protected function loadSubMenuTemplate( $groupId ) {
		$groupId     = $groupId ?: bp_get_new_group_id();
		$hasLdGroup  = bp_ld_sync( 'buddypress' )->sync->generator( $groupId )->hasLdGroup();
		$currentMenu = bp_action_variable();
		$subMenus    = array_map(
			function( $menu ) {
				$menu['url'] = bp_ld_sync( 'buddypress' )->subMenuLink( $menu['slug'] );
				return $menu;
			},
			bp_ld_sync( 'buddypress' )->coursesSubMenus()
		);

		require bp_locate_template( 'groups/single/courses-nav.php', false, false );
	}

	/**
	 * Arguments to pass into the buddypress group extension class
	 *
	 * @since BuddyBoss 1.0.0
	 */
	protected function prepareComponentOptions() {
		$tabName     = apply_filters( 'bp_ld_sync/courses_group_tab_name', $this->tabLabel() );
		$tabSlug     = apply_filters( 'bp_ld_sync/courses_group_tab_slug', 'courses' );
		$tabPosition = apply_filters( 'bp_ld_sync/courses_group_tab_position', 40 );
		// learndash_is_group_leader_user

		return array(
			'name'              => $tabName,
			'slug'              => $tabSlug,
			'nav_item_position' => $tabPosition,
			'access'            => apply_filters( 'bp_ld_sync/courses_group_tab_enabled', $this->showTabOnView() ),

			'screens'           => array(
				'create' => array(
					'enabled'  => apply_filters( 'bp_ld_sync/courses_group_tab_enabled/screen=create', $this->showTabOnCreate() ),
					'name'     => apply_filters( 'bp_ld_sync/courses_group_tab_name/screen=create', $tabName ),
					'slug'     => apply_filters( 'bp_ld_sync/courses_group_tab_slug/screen=create', $tabSlug ),
					'position' => apply_filters( 'bp_ld_sync/courses_group_tab_position/screen=create', $tabPosition ),
					// 'screen_callback' => '',
					// 'save_callback'   => '', // ??
				),

				'edit'   => array(
					'enabled'  => apply_filters( 'bp_ld_sync/courses_group_tab_enabled/screen=edit', true ),
					'name'     => apply_filters( 'bp_ld_sync/courses_group_tab_name/screen=edit', $tabName ),
					'slug'     => apply_filters( 'bp_ld_sync/courses_group_tab_slug/screen=edit', $tabSlug ),
					'position' => apply_filters( 'bp_ld_sync/courses_group_tab_position/screen=edit', $tabPosition ),
					// 'screen_callback' => '',
					// 'save_callback'   => '', // ??
					// 'submit_text' => ''
				),

				'admin'  => array(
					'metabox_context'  => 'normal',
					'metabox_priority' => 'core',
				),
			),
		);
	}

	/**
	 * Return the tab label with proper nonce
	 *
	 * @since BuddyBoss 1.0.0
	 */
	protected function tabLabel() {
		$default = sprintf( __( '%s', 'buddyboss' ), \LearnDash_Custom_Label::get_label( 'courses' ) );

		if ( ! $currentGroup = groups_get_current_group() ) {
			return $default;
		}

		$coursesCount = count( bp_learndash_get_group_courses( $currentGroup->id ) );

		return sprintf( _n( \LearnDash_Custom_Label::get_label( 'course' ) , \LearnDash_Custom_Label::get_label( 'courses' ) , $coursesCount, 'buddyboss' ), $coursesCount );
	}

	/**
	 * Determine who can see the tab
	 *
	 * @since BuddyBoss 1.0.0
	 */
	protected function showTabOnView() {
		if ( ! $currentGroup = groups_get_current_group() ) {
			return 'noone';
		}

		$generator = bp_ld_sync( 'buddypress' )->sync->generator( $currentGroup->id );
		if ( ! $generator->hasLdGroup() ) {
			return 'noone';
		}

		if ( ! learndash_group_enrolled_courses( $generator->getLdGroupId() ) ) {
			return 'noone';
		}

		return bp_ld_sync( 'settings' )->get( 'buddypress.tab_access', true );
	}

	/**
	 * Should tha tab be shown on the group create screen
	 *
	 * @since BuddyBoss 1.0.0
	 */
	protected function showTabOnCreate() {
		return bp_ld_sync( 'settings' )->get( 'buddypress.show_in_bp_create', true );
	}
}
