<?php
/**
 * BuddyBoss Suspend Notification Classes
 *
 * @since   BuddyBoss 2.0.3
 * @package BuddyBoss\Suspend
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Database interaction class for the BuddyBoss Suspend Notification.
 *
 * @since BuddyBoss 2.0.3
 */
class BP_Suspend_Notification extends BP_Suspend_Abstract {

	/**
	 * Item type
	 *
	 * @var string
	 */
	public static $type = 'notification';

	/**
	 * BP_Suspend_Notification constructor.
	 *
	 * @since BuddyBoss 2.0.3
	 */
	public function __construct() {

		$this->item_type = self::$type;
		/**
		 * Suspend code should not add for WordPress backend or IF component is not active or Bypass argument passed for admin
		 */
		if ( ( is_admin() && ! wp_doing_ajax() ) || self::admin_bypass_check() ) {
			return;
		}

		add_filter( 'bb_notifications_get_where_conditions', array( $this, 'update_where_sql' ), 10, 3 );
	}

	/**
	 * Get Notification's comment ids
	 *
	 * @since BuddyBoss 2.0.3
	 *
	 * @param int   $notification_id Notification id.
	 * @param array $args            Parent args.
	 *
	 * @return array
	 */
	protected function get_related_contents( $notification_id, $args = array() ) {
		return array();
	}

	/**
	 * Prepare notification Where SQL query to filter blocked users.
	 *
	 * @since BuddyBoss 2.0.3
	 *
	 * @param string $where_conditions Notification Where sql.
	 * @param string $tbl_alias        Table alias.
	 * @param array  $args             Query arguments.
	 *
	 * @return mixed Where SQL
	 */
	public function update_where_sql( $where_conditions, $tbl_alias, $args = array() ) {

		if ( isset( $args['moderation_query'] ) && false === $args['moderation_query'] ) {
			return $where_conditions;
		}

		/**
		 * Filters the hidden notification Where SQL statement.
		 *
		 * @since BuddyBoss 2.0.3
		 *
		 * @param string $where_conditions Query to hide suspended user's notification.
		 * @param array  $class            current class object.
		 */
		return apply_filters( 'bp_suspend_notification_get_where_conditions', $where_conditions, $this );
	}

}
