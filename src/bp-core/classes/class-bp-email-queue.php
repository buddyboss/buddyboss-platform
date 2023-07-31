<?php
/**
 * Email queue to send emails in background process.
 *
 * @package BuddyBoss\Core
 * @since   BuddyBoss 1.8.1
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Load Email Queue class
 *
 * @since BuddyBoss 1.8.1
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
	 * @since BuddyBoss 1.8.1
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
	 * @since BuddyBoss 1.8.1
	 */
	public function bb_email_background_process() {
		global $wpdb, $bb_email_background_updater;
		$table_name  = $wpdb->base_prefix . 'bb_email_queue';
		$get_records = $this->get_records();
		if ( ! empty( $get_records ) ) {
			$bb_email_background_updater->push_to_queue(
				array(
					'callback' => array( $this, 'bb_email_queue_cron_cb' ),
					'args'     => array( $get_records ),
				)
			);

			foreach ( $get_records as $record ) {
				//phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
				$wpdb->update( $table_name, array( 'scheduled' => true ), array( 'id' => $record['id'] ) );
			}

			$bb_email_background_updater->save()->dispatch();
		}
	}

	/**
	 * Email queue add record
	 *
	 * @since BuddyBoss 1.8.1
	 *
	 * @param string                   $email_type  Email type.
	 * @param string|array|int|WP_User $to          Either an email address, user ID, WP_User object,
	 *                                              or an array containing the address and name.
	 * @param array                    $args        Array of arguments.
	 *
	 * @return void
	 */
	public function add_record( $email_type, $to, $args = array() ) {
		global $wpdb;

		if ( is_int( $to ) && get_user_by( 'id', $to ) ) {
			$to = get_userdata( $to );
		}

		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$wpdb->query( $wpdb->prepare( "INSERT INTO {$wpdb->base_prefix}bb_email_queue ( email_type, recipient, arguments, date_created, scheduled ) VALUES ( %s, %s, %s, %s, %d )", $email_type, maybe_serialize( $to ), maybe_serialize( $args ), bp_core_current_time(), false ) );
	}

	/**
	 * Add multiple email queue records.
	 *
	 * @param array $data Array of data for emails.
	 */
	public function add_bulk_record( $data = array() ) {
		global $wpdb;
		if ( empty( $data ) ) {
			return;
		}

		$min_count = (int) apply_filters( 'bb_add_email_bulk_record_count', 200 );
		if ( count( $data ) > $min_count ) {
			$datas = array_chunk( $data, $min_count );
		} else {
			$datas = array( $data );
		}

		foreach ( $datas as $data ) {
			$place_holders = array();
			foreach ( $data as $item ) {
				// phpcs:ignore Squiz.Strings.DoubleQuoteUsage.NotRequired
				$place_holders[] = $wpdb->prepare( "( %s, %s, %s, %s, %d )", $item['email_type'], maybe_serialize( $item['recipient'] ), maybe_serialize( $item['arguments'] ), bp_core_current_time(), 0 );
			}

			$sql = "INSERT INTO {$wpdb->base_prefix}bb_email_queue ( email_type, recipient, arguments, date_created, scheduled ) VALUES " . implode( ', ', $place_holders );
			// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$wpdb->query( $sql );
		}
	}

	/**
	 * Email queue delete record
	 *
	 * @since BuddyBoss 1.8.1
	 *
	 * @param int $id Email record id.
	 *
	 * @return bool|int
	 */
	public function delete_record( $id ) {
		global $wpdb;

		return $wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->base_prefix}bb_email_queue WHERE id = %d", $id ) );
	}

	/**
	 * Email queue get record
	 *
	 * @since BuddyBoss 1.8.1
	 *
	 * @param int    $limit        Number of records needs to fetch.
	 * @param string $order_column Column name for order by.
	 * @param string $order        Fetch order asc/desc.
	 * @param string $scheduled    Scheduled or not.
	 *
	 * @return null|array|object
	 */
	public function get_records( $limit = 20, $order_column = 'id', $order = 'ASC', $scheduled = 0 ) {
		global $wpdb;

		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		return $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$wpdb->base_prefix}bb_email_queue WHERE scheduled = '0' ORDER BY {$order_column} {$order} LIMIT %d", $limit ), ARRAY_A );
	}

	/**
	 * Email queue get single record
	 *
	 * @since BuddyBoss 1.8.1
	 *
	 * @param int $id Email record id.
	 *
	 * @return array|object|void|null
	 */
	public function get_single_record( $id ) {
		global $wpdb;

		return $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->base_prefix}bb_email_queue  WHERE id = %d", $id ) );
	}

	/**
	 * Email queue cron callback.
	 *
	 * @param array $get_records Array of data for emails.
	 *
	 * @since BuddyBoss 1.8.1
	 */
	public function bb_email_queue_cron_cb( $get_records ) {
		if ( ! empty( $get_records ) ) {
			foreach ( $get_records as $single ) {
				$item_id    = ! empty( $single['id'] ) ? $single['id'] : 0;
				$email_type = ! empty( $single['email_type'] ) ? $single['email_type'] : '';
				$to         = ! empty( $single['recipient'] ) ? maybe_unserialize( $single['recipient'] ) : 0;
				$args       = ! empty( $single['arguments'] ) ? maybe_unserialize( $single['arguments'] ) : array();

				if ( $this->get_single_record( $item_id ) && ! empty( $email_type ) && ! empty( $to ) ) {
					bp_send_email( $email_type, $to, $args );
					$this->delete_record( $item_id );
				}
			}
		}

		$remain_records = $this->get_records();
		if ( ! empty( $remain_records ) ) {
			$this->bb_email_background_process();
		}
	}

	/**
	 * Create db table for eamil queue
	 *
	 * @since BuddyBoss 1.8.1
	 */
	public static function create_db_table() {
		global $wpdb;

		$charset_collate = $wpdb->get_charset_collate();

		if ( ! function_exists( 'dbDelta' ) ) {
			require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		}

		$sql = "CREATE TABLE {$wpdb->base_prefix}bb_email_queue (
			id bigint(20) NOT NULL AUTO_INCREMENT,
			email_type varchar(200) NOT NULL,
			recipient longtext NOT NULL,
			arguments longtext NOT NULL,
			date_created datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
			scheduled tinyint(1) NOT NULL DEFAULT '0',
			PRIMARY KEY  (id)
		) $charset_collate;";

		dbDelta( $sql );
	}
}
