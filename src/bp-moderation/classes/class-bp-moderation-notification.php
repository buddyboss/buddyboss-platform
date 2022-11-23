<?php
/**
 * BuddyBoss Moderation Notification Classes
 *
 * @since   BuddyBoss 2.0.3
 * @package BuddyBoss\Moderation
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Database interaction class for the BuddyBoss moderation Notification.
 *
 * @since BuddyBoss 2.0.3
 */
class BP_Moderation_Notification extends BP_Moderation_Abstract {

	/**
	 * Item type
	 *
	 * @var string
	 */
	public static $moderation_type = 'notification';

	/**
	 * BP_Moderation_Notification constructor.
	 *
	 * @since BuddyBoss 2.0.3
	 */
	public function __construct() {

		parent::$moderation[ self::$moderation_type ] = self::class;
		$this->item_type                              = self::$moderation_type;

		/**
		 * Moderation code should not add for WordPress backend and if Bypass argument passed for admin
		 */
		if ( ( is_admin() && ! wp_doing_ajax() ) || self::admin_bypass_check() ) {
			return;
		}

		/**
		 * If moderation setting enabled for this content then it'll filter hidden content.
		 * And IF moderation setting enabled for member then it'll filter blocked user content.
		 */
		add_filter( 'bb_notifications_get_where_conditions', array( $this, 'update_where_sql' ), 9999, 3 );

	}

	/**
	 * Get permalink
	 *
	 * @since BuddyBoss 2.0.3
	 *
	 * @param int $notification_id Notification id.
	 *
	 * @return string
	 */
	public static function get_permalink( $notification_id ) {
		return '';
	}

	/**
	 * Get Content owner id.
	 *
	 * @since BuddyBoss 2.0.3
	 *
	 * @param integer $notification_id Notification id.
	 *
	 * @return int
	 */
	public static function get_content_owner_id( $notification_id ) {
		return 0;
	}

	/**
	 * Remove hidden/blocked user's notifications.
	 *
	 * @since BuddyBoss 2.0.3
	 *
	 * @param string $sql_where Notifications Where sql.
	 * @param string $tbl_alias Table alias.
	 * @param object $args      Query arguments.
	 *
	 * @return string
	 */
	public function update_where_sql( $sql_where, $tbl_alias, $args = array() ) {
		global $wpdb;
		$bp = buddypress();

		if ( isset( $args['moderation_query'] ) && false === $args['moderation_query'] ) {
			return $sql_where;
		}

		if ( bp_is_moderation_member_blocking_enable( 0 ) ) {
			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$moderation_query = $wpdb->prepare( "SELECT item_id FROM {$bp->table_prefix}bp_suspend WHERE ( hide_parent = %d OR hide_sitewide = %d OR reported = %d ) AND item_type = %s", 1, 1, 1, 'user' );

			// phpcs:ignore Squiz.Strings.DoubleQuoteUsage.NotRequired
			$sql_where .= " AND {$tbl_alias}.secondary_item_id NOT IN ( " . $moderation_query . " )";

			$hidden_users_ids = bp_moderation_get_hidden_user_ids();
			if ( ! empty( $hidden_users_ids ) ) {
				// phpcs:ignore Squiz.Strings.DoubleQuoteUsage.NotRequired
				$sql_where .= " AND ( {$tbl_alias}.item_id NOT IN ( " . implode( ',', $hidden_users_ids ) . " ) )";
			}
		}

		/**
		 * Filters the hidden notification Where SQL statement.
		 *
		 * @since BuddyBoss 2.0.3
		 *
		 * @param string $sql_where Query to hide moderation user's notification.
		 * @param array  $class     current class object.
		 */
		return apply_filters( 'bp_moderation_notification_get_where_conditions', $sql_where, $this );
	}
}
