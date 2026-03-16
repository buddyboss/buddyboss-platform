<?php
/**
 * BuddyBoss Events Group Extension.
 *
 * Adds an Events tab to every BuddyBoss group nav.
 *
 * @package BuddyBoss\Events
 * @since BuddyBoss Events 1.0.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * BP_Events_Group_Extension class.
 *
 * Registers an Events tab on every BuddyBoss group navigation and renders
 * a FullCalendar calendar scoped to the group's events. Privacy for
 * private/hidden groups is enforced by the platform's BP_Group_Extension
 * base class — this class intentionally does NOT override user_can_visit().
 *
 * @since BuddyBoss Events 1.0.0
 */
class BP_Events_Group_Extension extends BP_Group_Extension {

	/**
	 * Constructor.
	 *
	 * Calls parent::init() with the Events tab configuration. BP_Group_Extension
	 * automatically restricts the tab to members of private/hidden groups.
	 *
	 * @since BuddyBoss Events 1.0.0
	 */
	public function __construct() {
		parent::init( array(
			'slug'              => 'events',
			'name'              => __( 'Events', 'buddyboss' ),
			'nav_item_name'     => __( 'Events', 'buddyboss' ),
			'nav_item_position' => 25,
			'enable_nav_item'   => true,
			'visibility'        => 'public',
			// 'access' and 'show_tab' default to 'member' automatically for
			// private/hidden groups — the platform enforces this.
		) );
	}

	/**
	 * Render the group events calendar tab.
	 *
	 * Stores the current group ID in a global so the template can read it
	 * without polluting the BP global object, consistent with how bp-forums
	 * exposes its context.
	 *
	 * @since BuddyBoss Events 1.0.0
	 *
	 * @param int|null $group_id Current group ID (passed since BP 2.2).
	 */
	public function display( $group_id = null ) {
		// Always prefer the parameter; fall back to global context.
		$group_id = $group_id ?: bp_get_current_group_id();

		// Expose to template via a clean global — avoids polluting the
		// BP global and is consistent with how bp-forums does it.
		$GLOBALS['bp_events_current_group_id'] = (int) $group_id;

		bp_get_template_part( 'events/group-events' );
	}
}
