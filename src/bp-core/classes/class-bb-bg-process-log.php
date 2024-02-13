<?php
/**
 * Holds Background process log functionality.
 *
 * @package buddyboss/Classes
 */

class BB_BG_Process_Log {

	/**
	 * Class instance.
	 *
	 * @var $instance
	 */
	private static $instance;

	/**
	 * Table name.
	 *
	 * @var string $table_name
	 */
	public $table_name = '';

	/**
	 * Using Singleton, see instance()
	 */
	public function __construct() {
		// Using Singleton, see instance().
	}

	/**
	 * Get the instance of the class.
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
	 * @since BuddyBoss [BBVERSION]
	 */
	public function load() {
		global $wpdb;

		$this->table_name = "{$wpdb->prefix}bb_background_process_logs";

		$this->setup_hooks();
	}

	/**
	 * Setup hooks for logging actions.
	 *
	 * @since BuddyBoss [BBVERSION]
	 */
	private function setup_hooks() {
		add_filter( 'bb_bp_bg_process_start', array( $this, 'record_bp_bg_process' ), 10, 1 );
		add_action( 'bb_bp_bg_process_end', array( $this, 'update_bp_bg_process' ), 10, 1 );
	}

	/**
	 * Create log of bp background process.
	 *
	 * @since BuddyBoss [BBVERSION]
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

		$insert = $this->add_log(
			array(
				'component'              => $this->get_component_name( $bg_data['callback'] ),
				'bg_process_name'        => $bg_data['callback'],
				'callback_function'      => $bg_data['callback'],
				'data'                   => maybe_serialize( $bg_data['args'] ),
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
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @param array $args Array of arguments.
	 *
	 * @return void
	 */
	public function update_bp_bg_process( $args ) {
		$this->update_log( $args );
	}

	/**
	 * Add log.
	 *
	 * @since BuddyBoss [BBVERSION]
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
	 * @since BuddyBoss [BBVERSION]
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
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @param string $callback_function Type.
	 *
	 * @return mixed|void|null
	 */
	public function get_component_name( $callback_function ) {
		if ( empty( $callback_function ) ) {
			return;
		}

		switch ( $callback_function ) {
			case 'bb_update_group_member_count':
				$component = 'groups';
				break;
			default:
				$component = 'core';
		}

		/**
		 * Filter the component name.
		 *
		 * @since BuddyBoss [BBVERSION]
		 *
		 * @param string $callback_function Type.
		 * @param string $component         Component name.
		 */
		return apply_filters( 'bb_bg_process_component_name', $component, $callback_function );
	}


	/**
	 * Create the table.
	 *
	 * @since BuddyBoss [BBVERSION]
	 * @return void
	 */
	public function create_table() {
		global $wpdb;

		$log_table_name  = "{$wpdb->prefix}bb_background_process_logs";
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
}
