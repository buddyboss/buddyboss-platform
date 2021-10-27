<?php
/**
 * BuddyBoss LearnDash integration BpGroupReports class.
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
class BpGroupReports extends BP_Group_Extension {

	/**
	 * Constructor
	 *
	 * @since BuddyBoss 1.0.0
	 */
	public function __construct() {
		 parent::init( $this->prepareComponentOptions() );
	}

	/**
	 * Display the tab content based on the selected sub tab
	 *
	 * @since BuddyBoss 1.0.0
	 */
	public function display( $groupId = null ) {
		$this->loadSubMenuTemplate( $groupId );

		$action = bp_action_variable() ?: 'reports';

		if ( ! $location = bp_locate_template( "groups/single/reports-{$action}.php", true ) ) {
			bp_locate_template( 'groups/single/reports-404.php', true );
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
			bp_ld_sync( 'buddypress' )->reportsSubMenus()
		);

		require bp_locate_template( 'groups/single/reports-nav.php', false, false );
	}

	/**
	 * Arguments to pass into the buddypress group extension class
	 *
	 * @since BuddyBoss 1.0.0
	 */
	protected function prepareComponentOptions() {
		$tabName     = apply_filters( 'bp_ld_sync/reports_group_tab_name', __( 'Reports', 'buddyboss' ) );
		$tabSlug     = apply_filters( 'bp_ld_sync/reports_group_tab_slug', 'reports' );
		$tabPosition = apply_filters( 'bp_ld_sync/reports_group_tab_position', 40 );

		return array(
			'name'              => $tabName,
			'slug'              => $tabSlug,
			'nav_item_position' => $tabPosition,
			'access'            => apply_filters( 'bp_ld_sync/reports_group_tab_enabled', $this->showTabOnView() ),

			'screens'           => array(
				'create' => array(
					'enabled' => false,
				),
				'edit'   => array(
					'enabled' => false,
				),
				'admin'  => array(
					'enabled' => false,
				),
			),
		);
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

		// admin can always view
		if ( learndash_is_admin_user() ) {
			return true;
		}

		foreach ( bp_ld_sync( 'settings' )->get( 'reports.access', array() ) as $type ) {
			$function = "groups_is_user_{$type}";
			$callback = call_user_func_array( $function, array( bp_loggedin_user_id(), $currentGroup->id ) );
			if ( function_exists( $function ) && $callback ) {
				return true;
			}
		}

		return 'noone';
	}
}
