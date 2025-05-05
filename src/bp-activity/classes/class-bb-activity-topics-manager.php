<?php
/**
 * BuddyBoss Platform Activity Topics Manager.
 *
 * Handles database schema creation and CRUD operations for activity topics.
 *
 * @since   BuddyBoss [BBVERSION]
 * @package BuddyBoss\Activity
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Manages Activity Topics data storage and operations.
 *
 * @since BuddyBoss [BBVERSION]
 */
class BB_Activity_Topics_Manager {

	/**
	 * Instance of this class.
	 *
	 * @since  BuddyBoss [BBVERSION]
	 *
	 * @var object
	 *
	 * @access private
	 */
	private static $instance = null;

	/**
	 * Cache group for activity topics.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @var string
	 */
	public static $topic_cache_group = 'bb_activity_topics';

	/**
	 * WordPress Database instance.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @var wpdb
	 */
	private $wpdb;

	/**
	 * Get the singleton instance of this class.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @return BB_Activity_Topics_Manager The singleton instance.
	 */
	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Constructor.
	 *
	 * @since BuddyBoss [BBVERSION]
	 */
	public function __construct() {
		global $wpdb;
		$this->wpdb = $wpdb;

		$this->setup_hooks();
	}

	/**
	 * Setup hooks for logging actions.
	 *
	 * @since BuddyBoss [BBVERSION]
	 */
	private function setup_hooks() {
		add_action( 'load-buddyboss_page_bp-activity', array( $this, 'bb_activity_admin_edit_metabox_topic' ) );

		// Add custom column to activity admin list table.
		add_filter( 'bp_activity_list_table_get_columns', array( $this, 'bb_add_activity_admin_topic_column' ) );
		add_filter( 'bp_activity_admin_get_custom_column', array( $this, 'bb_activity_admin_topic_column_content' ), 10, 3 );
	}

	/**
	 * Get the permission type for the activity topic.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @param string $existing_permission_type The existing permission type.
	 *
	 * @return array Array of permission types.
	 */
	public function bb_activity_topic_permission_type( $existing_permission_type = '' ) {
		$permission_types = apply_filters(
			/**
			 * Filters the permission types for the activity topic.
			 *
			 * @since BuddyBoss [BBVERSION]
			 *
			 * @param array $permission_types Array of permission types.
			 */
			'bb_activity_topic_permission_type',
			array(
				'anyone'      => __( 'Anyone', 'buddyboss' ),
				'mods_admins' => __( 'Admin', 'buddyboss' ),
			)
		);

		// If an existing permission type is provided, return only that type.
		if ( ! empty( $existing_permission_type ) && isset( $permission_types[ $existing_permission_type ] ) ) {
			return array( $existing_permission_type => $permission_types[ $existing_permission_type ] );
		}

		// Otherwise return all permission types.
		return $permission_types;
	}

	/**
	 * Load the activity topics metabox in activity admin page.
	 *
	 * @since BuddyBoss [BBVERSION]
	 */
	public function bb_activity_admin_edit_metabox_topic() {
		add_meta_box( 'bp_activity_topic', __( 'Topic', 'buddyboss' ), array( $this, 'bb_activity_admin_edit_metabox_topic_content' ), 'buddyboss_page_bp-activity', 'normal', 'core' );
	}

	/**
	 * Display the activity topics metabox in activity admin page.
	 *
	 * @since BuddyBoss [BBVERSION]
	 */
	public function bb_activity_admin_edit_metabox_topic_content() {
		// Get all activity topics.
		$topics = bb_topics_manager_instance()->bb_get_topics();

		$current_topic_id = '';
		?>
		<div class="bb-activity-topic-container">
			<select name="activity_topic" id="activity_topic">
				<option value=""><?php esc_html_e( '-- Select Topic --', 'buddyboss' ); ?></option>
				<?php
				if ( ! empty( $topics['topics'] ) ) {
					foreach ( $topics['topics'] as $topic ) {
						?>
						<option value="<?php echo esc_attr( $topic->id ); ?>" <?php selected( $current_topic_id, $topic->id ); ?>>
							<?php echo esc_html( $topic->name ); ?>
						</option>
						<?php
					}
				}
				?>
			</select>
		</div>
		<?php
	}

	/**
	 * Add topic column to activity admin list table.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @param array $columns Array of column names and labels.
	 *
	 * @return array Modified array of column names and labels.
	 */
	public function bb_add_activity_admin_topic_column( $columns ) {
		$new_columns = array();
		foreach ( $columns as $key => $value ) {
			$new_columns[ $key ] = $value;
			if ( 'comment' === $key ) {
				$new_columns['activity_topic'] = __( 'Topic', 'buddyboss' );
			}
		}
		return $new_columns;
	}

	/**
	 * Display topic column content in activity admin list table.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @param string $content     The column content.
	 * @param string $column_name Column name.
	 *
	 * @return string
	 */
	public function bb_activity_admin_topic_column_content( $content, $column_name, $item ) {

		if ( 'activity_topic' === $column_name && ! empty( $item['id'] ) ) {
			return $this->bb_get_activity_topic_name( $item['id'] );
		}

		return $content;
	}

	/**
	 * Get the activity topic name.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @param int $activity_id The ID of the activity.
	 *
	 * @return string The topic name or empty string if not found.
	 */
	public function bb_get_activity_topic_name( $activity_id ) {
		// Validate activity ID.
		$activity_id = absint( $activity_id );
		if ( empty( $activity_id ) ) {
			return '';
		}

		$cache_key  = 'bb_activity_topic_name_' . $activity_id;
		$topic_name = wp_cache_get( $cache_key, self::$topic_cache_group );

		if ( false !== $topic_name ) {
			return $topic_name;
		}

		$bp_prefix = bp_core_get_table_prefix();

		$topic = $this->wpdb->get_row(
			// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
			$this->wpdb->prepare(
				"SELECT t.name 
				FROM {$bp_prefix}bb_activity_topic_relationship atr
				INNER JOIN {$bp_prefix}bb_topics t ON t.id = atr.topic_id
				WHERE atr.activity_id = %d
				LIMIT 1",
				$activity_id
			)
		);

		$topic_name = $topic ? $topic->name : '';

		// Cache the result.
		wp_cache_set( $cache_key, $topic_name, self::$topic_cache_group );

		return $topic_name;
	}

	/**
	 * Update the activity topic relationship.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @param array $args Array of args.
	 *
	 * @return bool True if the activity topic relationship was updated, false otherwise.
	 */
	public function bb_update_activity_topic_relationship( $args ) {
		$r = bp_parse_args(
			$args,
			array(
				'previous_id' => 0,
				'topic_id'    => 0,
			)
		);

		$prefix = bp_core_get_table_prefix();
		$table  = "{$prefix}bb_activity_topic_relationship";

		/**
		 * Fires before updating the activity topic relationship.
		 *
		 * @since BuddyBoss [BBVERSION]
		 *
		 * @param array $args Array of args. {
		 *     @type int $previous_id The ID of the previous topic.
		 *     @type int $topic_id    The ID of the topic.
		 * }
		 */
		do_action( 'bb_before_update_activity_topic_relationship', $args );

		$updated = $this->wpdb->update(
			$table,
			array( 'topic_id' => $r['topic_id'] ),
			array( 'topic_id' => $r['previous_id'] ),
			array( '%d' ),
			array( '%d' )
		);

		if ( false === $updated ) {
			return false;
		}

		/**
		 * Fires after updating the activity topic relationship.
		 *
		 * @since BuddyBoss [BBVERSION]
		 *
		 * @param array $args Array of args. {
		 *     @type int $previous_id The ID of the previous topic.
		 *     @type int $topic_id    The ID of the topic.
		 * }
		 */
		do_action( 'bb_after_update_activity_topic_relationship', $args );

		return $updated;
	}

	/**
	 * Delete the activity topic relationship.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @param array $args Array of args.
	 *
	 * @return bool True if the activity topic relationship was deleted, false otherwise.
	 */
	public function bb_delete_activity_topic_relationship( $args ) {
		$r = bp_parse_args(
			$args,
			array(
				'topic_id' => 0,
			)
		);

		$prefix = bp_core_get_table_prefix();
		$table  = "{$prefix}bb_activity_topic_relationship";

		/**
		 * Fires before deleting the activity topic relationship.
		 *
		 * @since BuddyBoss [BBVERSION]
		 *
		 * @param array $args Array of args. {
		 *     @type int $topic_id The ID of the topic.
		 * }
		 */
		do_action( 'bb_before_delete_activity_topic_relationship', $args );

		$deleted = $this->wpdb->delete(
			$table,
			array( 'topic_id' => $r['topic_id'] ),
			array( '%d' )
		);

		if ( false === $deleted ) {
			return false;
		}

		/**
		 * Fires after deleting the activity topic relationship.
		 *
		 * @since BuddyBoss [BBVERSION]
		 *
		 * @param array $args Array of args. {
		 *     @type int $topic_id The ID of the topic.
		 * }
		 */
		do_action( 'bb_after_delete_activity_topic_relationship', $args );

		return $deleted;
	}
}
