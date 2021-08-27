<?php
/**
 * Email queue to send emails in background process.
 *
 * @package BuddyBoss\Core
 * @since   BuddyBoss 1.7.7
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Load Email Queue class
 *
 * @since BuddyBoss 1.7.7
 */
class BP_Email_Queue {

	/**
	 * Instance of this class.
	 *
	 * @var object
	 */
	private static $_instance = null;

	/**
	 * Return the instance of this class.
	 *
	 * @since BuddyBoss 1.7.7
	 */
	public static function instance() {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self();
		}

		return self::$_instance;
	}

	/**
	 * Background Process.
	 *
	 * @since BuddyBoss 1.7.7
	 */
	public function bb_email_background_process() {
		$get_records = $this->get_records();
		if ( ! empty( $get_records ) ) {
			global $bp_background_updater;
			$bp_background_updater->push_to_queue(
				array(
					'callback' => array( $this, 'bb_email_queue_cron_cb' ),
					'args'     => array( $get_records ),
				)
			);

			$bp_background_updater->save()->schedule_event();
		}
	}

	/**
	 * Email queue add record
	 *
	 * @since BuddyBoss 1.7.7
	 *
	 * @param string $email_type                    Email type.
	 * @param string|array|int|WP_User $to          Either an email address, user ID, WP_User object,
	 *                                              or an array containing the address and name.
	 * @param array  $args                          Array of arguments.
	 *
	 * @return bool|int
	 */
	public function add_record( $email_type, $to, $args = array() ) {
		global $wpdb;

		$wpdb->query( $wpdb->prepare( "INSERT INTO {$wpdb->prefix}bb_email_queue ( email_type, recipient, arguments, date_created ) VALUES ( %s, %s, %s, %s )", $email_type, maybe_serialize( $to ), maybe_serialize( $args ), bp_core_current_time() ) );
	}

	/**
	 * Email queue delete record
	 *
	 * @since BuddyBoss 1.7.7
	 *
	 * @param int $id Email record id.
	 *
	 * @return bool|int
	 */
	public function delete_record( $id ) {
		global $wpdb;

		return $wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->prefix}bb_email_queue WHERE id = %d", $id ) );
	}

	/**
	 * Email queue get record
	 *
	 * @since BuddyBoss 1.7.7
	 *
	 * @param int    $limit        Number of records needs to fetch.
	 * @param string $order_column Column name for order by.
	 * @param string $order        Fetch order asc/desc.
	 *
	 * @return null|array|object
	 */
	public function get_records( $limit = 20, $order_column = 'id', $order = 'ASC' ) {
		global $wpdb;

		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		return $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}bb_email_queue ORDER BY {$order_column} {$order} LIMIT %d", $limit ) );
	}

	/**
	 * Email queue cron callback.
	 *
	 * @since BuddyBoss 1.7.7
	 *
	 * @param array $get_records Array of email records.
	 */
	public function bb_email_queue_cron_cb( $get_records ) {
		if ( isset( $get_records ) && ! empty( $get_records ) ) {
			foreach ( $get_records as $single ) {
				$item_id    = ! empty( $single->id ) ? $single->id : 0;
				$email_type = ! empty( $single->email_type ) ? $single->email_type : '';
				$to         = ! empty( $single->recipient ) ? maybe_unserialize( $single->recipient ) : 0;
				$args       = ! empty( $single->arguments ) ? maybe_unserialize( $single->arguments ) : array();
				//if ( bp_send_email( $email_type, $to, $args ) ) {
					bp_send_email( $email_type, $to, $args );
					$this->delete_record( $item_id );
				//}
			}
		}
	}

	/**
	 * Create db table for eamil queue
	 *
	 * @since BuddyBoss 1.7.7
	 */
	public static function create_db_table() {
		global $wpdb;

		$charset_collate = $wpdb->get_charset_collate();

		if ( ! function_exists( 'dbDelta' ) ) {
			require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		}

		$sql = "CREATE TABLE {$wpdb->prefix}bb_email_queue (
			id bigint(20) NOT NULL AUTO_INCREMENT,
			email_type varchar(200) NOT NULL,
			recipient longtext DEFAULT NULL,
			arguments longtext DEFAULT NULL,
			date_created datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
			PRIMARY KEY  (id)
		) $charset_collate;";

		dbDelta( $sql );
	}
}
