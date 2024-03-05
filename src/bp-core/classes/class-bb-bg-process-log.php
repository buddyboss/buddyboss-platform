<?php
/**
 * Holds Background process log functionality.
 *
 * @package BuddyBoss/Core
 *
 * @since BuddyBoss 2.5.60
 */

defined( 'ABSPATH' ) || exit;

/**
 * BB_BG_Process_Log class.
 */
class BB_BG_Process_Log {

	/**
	 * Class instance.
	 *
	 * @since BuddyBoss 2.5.60
	 *
	 * @var $instance
	 */
	private static $instance;

	/**
	 * Table name.
	 *
	 * @since BuddyBoss 2.5.60
	 *
	 * @var string $table_name
	 */
	public $table_name = '';

	/**
	 * Using Singleton, see instance().
	 *
	 * @since BuddyBoss 2.5.60
	 */
	public function __construct() {
		// Using Singleton, see instance().
	}

	/**
	 * Get the instance of the class.
	 *
	 * @since BuddyBoss 2.5.60
	 *
	 * @return object
	 */
	public static function instance() {
		if ( null === self::$instance ) {
			$class_name     = __CLASS__;
			self::$instance = new $class_name();
			self::$instance->load();
		}

		return self::$instance;
	}

	/**
	 * Initialize the logger by setting up hooks and loading necessary data.
	 *
	 * @since BuddyBoss 2.5.60
	 * @throws Exception
	 */
	public function load() {
		$bp_prefix = bp_core_get_table_prefix();

		$this->table_name = $bp_prefix . 'bb_background_process_logs';

		$this->setup_hooks();
		$this->schedule_log_event();
	}

	/**
	 * Setup hooks for logging actions.
	 *
	 * @since BuddyBoss 2.5.60
	 */
	private function setup_hooks() {
		// Old background jobs.
		add_filter( 'bb_bp_bg_process_start', array( $this, 'record_bp_bg_process' ) );
		add_action( 'bb_bp_bg_process_end', array( $this, 'update_bp_bg_process' ), 10, 1 );

		// New background jobs.
		add_filter( 'bb_bg_process_start', array( $this, 'record_bg_process' ) );
		add_action( 'bb_bg_process_end', array( $this, 'update_bg_process' ), 10, 1 );

		// Schedule an event to clear logs.
		add_action( 'bb_bg_log_clear', array( $this, 'clear_logs' ) );

		// Re-schedule when update the timezone.
		add_action( 'update_option', array( $this, 'reschedule_event' ), 10, 3 );
	}

	/**
	 * Create log of bp background process.
	 *
	 * @since BuddyBoss 2.5.60
	 *
	 * @param array $args Array of arguments.
	 *
	 * @return array
	 */
	public function record_bp_bg_process( $args ) {
		global $wpdb;

		$bg_data = ( ! empty( $args->data ) && is_array( $args->data ) ) ? current( $args->data ) : array();

		if ( empty( $bg_data ) ) {
			return $args;
		}

		if ( is_string( $bg_data ) ) {
			$bg_data = array( 'callback' => $bg_data );
		} else {
			$bg_data['callback'] = is_array( $bg_data['callback'] ) ? maybe_serialize( $bg_data['callback'] ) : $bg_data['callback'];
		}

		$insert = $this->add_log(
			array(
				'component'         => self::get_component_name( $bg_data['callback'] ),
				'bg_process_from'   => self::action_from( $bg_data['callback'] ),
				'bg_process_name'   => $bg_data['callback'],
				'callback_function' => $bg_data['callback'],
				'data'              => ! empty( $bg_data['args'] ) ? maybe_serialize( $bg_data['args'] ) : '',
			)
		);

		if ( $insert ) {
			$args->bg_process_log_id = $wpdb->insert_id;
		}

		return $args;
	}

	/**
	 * Update log of bp background process.
	 *
	 * @since BuddyBoss 2.5.60
	 *
	 * @param array $args Array of arguments.
	 *
	 * @return void
	 */
	public function update_bp_bg_process( $args ) {
		$this->update_log( $args );
	}

	/**
	 * Create log of bp background process.
	 *
	 * @since BuddyBoss 2.5.60
	 *
	 * @param array|object $args Array of arguments.
	 *
	 * @return array
	 */
	public function record_bg_process( $args ) {
		global $wpdb;

		$bg_data = ! empty( $args->data ) ? $args->data : array();

		if ( empty( $bg_data ) ) {
			return $args;
		}
		if ( is_string( $bg_data ) ) {
			$bg_data = array( 'callback' => $bg_data );
		} else {
			$bg_data['callback'] = is_array( $bg_data['callback'] ) ? maybe_serialize( $bg_data['callback'] ) : $bg_data['callback'];
		}
		$insert = $this->add_log(
			array(
				'process_id'        => $args->key,
				'component'         => self::get_component_name( $bg_data['callback'] ),
				'bg_process_from'   => self::action_from( $bg_data['callback'] ),
				'bg_process_name'   => $bg_data['callback'],
				'callback_function' => $bg_data['callback'],
				'blog_id'           => $args->blog_id,
				'priority'          => $args->priority,
				'data'              => ! empty( $bg_data['args'] ) ? maybe_serialize( $bg_data['args'] ) : '',
			)
		);

		if ( $insert ) {
			$args->bg_process_log_id = $wpdb->insert_id;
		}

		return $args;
	}

	/**
	 * Update log of bp background process.
	 *
	 * @since BuddyBoss 2.5.60
	 *
	 * @param array $args Array of arguments.
	 *
	 * @return void
	 */
	public function update_bg_process( $args ) {
		$this->update_log( $args );
	}

	/**
	 * Add log.
	 *
	 * @since BuddyBoss 2.5.60
	 *
	 * @param array $args Array of arguments.
	 *
	 * @return bool|int|mysqli_result|null
	 */
	public function add_log( $args ) {
		global $wpdb;

		$start_date_gmt = current_time( 'mysql', 1 );
		$start_date     = get_date_from_gmt( $start_date_gmt );

		$defaults = array(
			'process_id'             => 0,
			'parent'                 => 0,
			'component'              => 'core',
			'bg_process_from'        => 'platform',
			'blog_id'                => get_current_blog_id(),
			'priority'               => 0,
			'process_start_date_gmt' => $start_date_gmt,
			'process_start_date'     => $start_date,
			'process_end_date_gmt'   => '0000-00-00 00:00:00',
			'process_end_date'       => '0000-00-00 00:00:00',
		);

		$args = bp_parse_args( $args, $defaults );

		if ( empty( $args['bg_process_name'] ) || empty( $args['bg_process_from'] ) || empty( $args['callback_function'] ) ) {
			return false;
		}

		return $wpdb->insert( $this->table_name, $args ); //phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
	}

	/**
	 * Update log by ID.
	 *
	 * @since BuddyBoss 2.5.60
	 *
	 * @param array $args Array of arguments.
	 *
	 * @return bool|int|mysqli_result|null
	 */
	public function update_log( $args ) {
		global $wpdb;

		if ( empty( $args->bg_process_log_id ) ) {
			return false;
		}

		$end_date_gmt = current_time( 'mysql', 1 );
		$end_date     = get_date_from_gmt( $end_date_gmt );

		return $wpdb->update( //phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$this->table_name,
			array(
				'process_end_date_gmt' => $end_date_gmt,
				'process_end_date'     => $end_date,
			),
			array( 'id' => (int) $args->bg_process_log_id ),
			array(
				'%s',
				'%s',
			),
			array( '%d' )
		);
	}

	/**
	 * Get the component name.
	 *
	 * @since BuddyBoss 2.5.60
	 *
	 * @param string $callback_function Type.
	 *
	 * @return mixed|void|null
	 */
	public static function get_component_name( $callback_function ) {
		if ( empty( $callback_function ) ) {
			return;
		}

		$callback_function = maybe_unserialize( $callback_function );
		if ( is_array( $callback_function ) ) {
			$callback_function = end( $callback_function );
		}

		switch ( $callback_function ) {
			case 'bb_pro_migrate_reactions_callback':
				$component = 'activity';
				break;
			case 'bb_remove_duplicate_member_slug' :
			case 'bb_set_bulk_user_profile_slug' :
			case 'bb_core_update_repair_member_slug' :
			case 'bb_core_removed_orphaned_member_slug' :
				$component = 'member';
				break;
			case 'bb_migrate_member_friends_count_callback' :
				$component = 'friends';
				break;
			case 'bb_update_group_member_count':
			case 'bb_create_group_member_subscriptions' :
			case 'bb_update_groups_discussion_subscriptions_background_process' :
			case 'bb_update_groups_subgroup_membership_background_process' :
			case 'bb_update_groups_invitation_background_process' :
			case 'bb_update_groups_members_background_process' :
				$component = 'groups';
				break;
			case 'bb_migrate_users_forum_topic_subscriptions' :
			case 'bb_migrate_bbpress_users_post_subscriptions' :
			case 'bb_migrate_users_topic_favorites' :
				$component = 'forums';
				break;
			case 'bb_moderation_bg_update_moderation_data' :
			case 'hide_related_content' :
			case 'unhide_related_content' :
				$component = 'moderation';
				break;
			case 'migrate_notification_preferences' :
			case 'bb_add_background_notifications' :
			case 'send_notifications_to_subscribers' :
			case 'bb_activity_following_post_notification' :
			case 'bb_email_queue_cron_cb' :
				$component = 'notifications';
				break;
			case 'bb_migrate_message_media_document' :
			case 'bb_render_digest_messages_template' :
			case 'bb_render_messages_recipients' :
			case 'bb_send_group_message_background' :
				$component = 'messages';
				break;
			case 'bp_video_background_create_thumbnail' :
				$component = 'video';
				break;
			case 'bb_xprofile_mapping_simple_to_repeater_fields_data' :
			case 'bb_remove_google_plus_fields' :
				$component = 'xprofile';
				break;
			case 'zoom_groups_meeting_details' :
			case 'zoom_groups_webinar_details' :
				$component = 'zoom';
				break;
			default:
				$component = 'core';
		}

		/**
		 * Filter the component name.
		 *
		 * @since BuddyBoss 2.5.60
		 *
		 * @param string $callback_function Type.
		 * @param string $component         Component name.
		 */
		return apply_filters( 'bb_bg_process_component_name', $component, $callback_function );
	}

	/**
	 * Get the action from name.
	 *
	 * @since BuddyBoss 2.5.60
	 *
	 * @param string $callback_function Type.
	 *
	 * @return mixed|void|null
	 */
	public static function action_from( $callback_function ) {
		if ( empty( $callback_function ) ) {
			return;
		}

		switch ( $callback_function ) {
			case 'zoom_groups_meeting_details' :
			case 'zoom_groups_webinar_details' :
			case 'bb_pro_migrate_reactions_callback' :
				$platform = 'pro';
				break;
			default:
				$platform = 'platform';
		}

		/**
		 * Filter the Platform name.
		 *
		 * @since BuddyBoss 2.5.60
		 *
		 * @param string $callback_function Type.
		 * @param string $platform          Platform name.
		 */
		return apply_filters( 'bb_bg_process_from_name', $platform, $callback_function );
	}

	/**
	 * Create the table.
	 *
	 * @since BuddyBoss 2.5.60
	 * @return void
	 */
	public function create_table() {
		global $wpdb;

		$bp_prefix       = bp_core_get_table_prefix();
		$log_table_name  = $bp_prefix . 'bb_background_process_logs';
		$charset_collate = $wpdb->get_charset_collate();
		$has_table       = $wpdb->query( $wpdb->prepare( 'show tables like %s', $log_table_name ) ); //phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching

		if ( ! empty( $has_table ) ) {
			return;
		}

		$sql = "CREATE TABLE $log_table_name (
            id bigint(20) NOT NULL AUTO_INCREMENT PRIMARY KEY,
            process_id bigint(20) NOT NULL,
            parent bigint(20) NULL,
            component varchar(55) NOT NULL,
            bg_process_name varchar(255) NOT NULL,
            bg_process_from varchar(255) NOT NULL,
            callback_function varchar(255) NULL,
            blog_id bigint(20) NOT NULL,
            data longtext NULL,
            priority bigint(10) NULL,
            process_start_date_gmt datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
            process_start_date datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
            process_end_date_gmt datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
            process_end_date datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
            KEY  process_id (process_id),
            KEY  parent (parent),
            KEY  component (component),
            KEY  bg_process_name (bg_process_name),
            KEY  bg_process_from (bg_process_from),
            KEY  callback_function (callback_function),
            KEY  blog_id (blog_id),
            KEY  priority (priority),
            KEY  process_start_date_gmt (process_start_date_gmt),
            KEY  process_start_date (process_start_date),
            KEY  process_end_date_gmt (process_end_date_gmt),
            KEY  process_end_date (process_end_date)
            ) $charset_collate";

		dbDelta( $sql );
	}

	/**
	 * Schedule event to clear the logs.
	 *
	 * @since BuddyBoss 2.5.60
	 * @return void
	 * @throws Exception
	 */
	private function schedule_log_event() {
		// Check if the cron job is not already scheduled.
		$is_scheduled = wp_next_scheduled( 'bb_bg_log_clear' );

		// WP datetime.
		$final_date         = date_i18n( 'Y-m-d', strtotime( 'next Sunday' ) ) . ' 23:59:59';
		$local_datetime     = date_create( $final_date, wp_timezone() );
		$schedule_timestamp = $local_datetime->getTimestamp();

		// Schedule the cron job to run every Sunday at 12 AM.
		if ( ! $is_scheduled ) {
			wp_schedule_event( $schedule_timestamp, 'weekly', 'bb_bg_log_clear' );
		} else if ( $is_scheduled !== $schedule_timestamp ) {
			wp_clear_scheduled_hook( 'bb_bg_log_clear' );
			wp_schedule_event( $schedule_timestamp, 'weekly', 'bb_bg_log_clear' );
		}
	}

	/**
	 * Clear the logs.
	 *
	 * @since BuddyBoss 2.5.60
	 * @return void
	 */
	public function clear_logs() {
		global $wpdb;

		$results = $wpdb->get_results( "SELECT id FROM $this->table_name WHERE process_start_date_gmt <= NOW() - INTERVAL 30 DAY;", ARRAY_A ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared

		/**
		 * Filter the limit of rows to delete from the background process log table.
		 *
		 * @since BuddyBoss 2.5.60
		 *
		 * @param int $limit Limit.
		 */
		$limit = apply_filters( 'bb_bg_process_log_delete_limit', 1000 );

		$pluck_ids = ! empty( $results ) ? wp_list_pluck( $results, 'id' ) : array();
		$count     = count( $pluck_ids );

		if ( $count > $limit ) {
			global $bb_background_updater;

			$chunked_data = array_chunk( $pluck_ids, $limit );

			foreach ( $chunked_data as $chunked_ids ) {
				$bb_background_updater->data(
					array(
						'type'     => 'bb_bg_remove_old_logs',
						'group'    => 'bb_bg_remove_old_logs',
						'callback' => array( $this, 'remove_logs' ),
						'args'     => array( $chunked_ids ),
					),
				);

				$bb_background_updater->save();
			}

			$bb_background_updater->schedule_event();
		} else {
			$this->remove_logs( $pluck_ids );
		}
	}

	/**
	 * Removed the old background job records.
	 *
	 * @since BuddyBoss 2.5.60
	 *
	 * @param array $bg_ids IDs of bg jobs.
	 *
	 * @return void
	 */
	public function remove_logs( $bg_ids ) {
		global $wpdb;

		if ( empty( $bg_ids ) ) {
			return;
		}

		$ids    = wp_parse_id_list( $bg_ids );
		$ids_in = "'" . implode( "','", $ids ) . "'";
		$wpdb->query( "DELETE FROM $this->table_name WHERE id IN ($ids_in)" ); //phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
	}

	/**
	 * Re-Schedule event to clear the logs.
	 *
	 * @since BuddyBoss 2.5.60
	 * @return void
	 * @throws Exception
	 */
	public function reschedule_event( $option, $old_value = '', $new_value = '' ) {
		static $is_reschedule = false; // Avoid clearing multiple time.

		// Check if the updated option is 'timezone_string'.
		if (
			(
				'timezone_string' === $option ||
				'gmt_offset' === $option
			) &&
			$old_value !== $new_value &&
			! $is_reschedule
		) {
			wp_clear_scheduled_hook( 'bb_bg_log_clear' );
			$this->schedule_log_event();
			$is_reschedule = true;
		}
	}
}
