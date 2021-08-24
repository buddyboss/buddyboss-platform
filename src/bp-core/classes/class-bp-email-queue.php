<?php
/**
 * Email queue to send emails in background process.
 *
 * @since BuddyBoss 1.7.6
*/

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'BP_Email_Queue' ) ) :

	/**
	 * Load Email queue class
	 *
	 * @since BuddyBoss 1.7.6
	 */
	class BP_Email_Queue {

		/**
		 * Constructor.
		 *
		 * @since BuddyBoss 1.7.6
		 */
		function __construct() {
			$this->create_db_table();
			$this->setup_cron();
		}

		/**
		 * Set up Cron.
		 *
		 * @since BuddyBoss 1.7.6
		 */
		function setup_cron() {
			if ( ! wp_next_scheduled( 'bp_email_queue_cron_hook' ) ) {
				wp_schedule_event( time(), 'bb_schedule_1min', 'bp_email_queue_cron_hook' );
			}

			add_action( 'bp_email_queue_cron_hook', array( $this, 'email_queue_cron_cb' ) );
		}

		/**
		 * Email queue add record
		 *
		 * @since BuddyBoss 1.7.6
		 */
		public function add_record( $email_type, $to, $args = array() ) {
			global $wpdb;

			return $wpdb->query( $wpdb->prepare( "INSERT INTO {$wpdb->prefix}bb_email_queue ( email_type, recipient_id, arguments, date_created ) VALUES ( %s, %d, %s, %s )", $email_type, $to, maybe_serialize( $args ), bp_core_current_time() ) );
		}

		/**
		 * Email queue delete record
		 *
		 * @since BuddyBoss 1.7.6
		 */
		public function delete_record( $id ) {
			global $wpdb;

			return $wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->prefix}bb_email_queue WHERE id = %d", $id ) );
		}

		/**
		 * Email queue get record
		 *
		 * @since BuddyBoss 1.7.6
		 */
		public function get_records() {
			global $wpdb;

			return $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}bb_email_queue LIMIT 0, 20" ) );
		}

		/**
		 * email queue cron callback.
		 *
		 * @since BuddyBoss 1.7.6
		 */
		function email_queue_cron_cb() {
			$get_records = $this->get_records();
			if ( isset ( $get_records ) && ! empty ( $get_records ) ) {
				foreach ( $get_records as $single ) {
					$item_id    = ! empty( $single->id ) ? $single->id : 0;
					$email_type = ! empty( $single->email_type ) ? $single->email_type : '';
					$to         = ! empty( $single->recipient_id ) ? get_userdata( $single->recipient_id ) : 0;
					$args       = ! empty( $single->arguments ) ? maybe_unserialize( $single->arguments ) : '';
					bp_send_email( $email_type, $to, $args );
					$this->delete_record( $item_id );
				}
			}
		}

		/**
		 * Create db table for eamil queue
		 *
		 * @since BuddyBoss 1.7.6
		 */
		public function create_db_table() {
			global $wpdb;

			$charset_collate = $wpdb->get_charset_collate();

			if ( ! function_exists( 'dbDelta' ) ) {
				require_once ABSPATH . 'wp-admin/includes/upgrade.php';
			}

			$sql = "CREATE TABLE {$wpdb->prefix}bb_email_queue (
	            id bigint(20) NOT NULL AUTO_INCREMENT,
	            email_type varchar(200) NOT NULL,
	            recipient_id bigint(20) NOT NULL DEFAULT 0,
	            arguments mediumtext DEFAULT NULL,
	            date_created datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
	            PRIMARY KEY  (id)
	        ) $charset_collate;";

			dbDelta( $sql );
		}
	}

endif; // End class_exists check.

