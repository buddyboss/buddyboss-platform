<?php
/**
 * BuddyBoss Platform Activity Topics Manager.
 *
 * Handles database schema creation and CRUD operations for activity topics.
 *
 * @since   BuddyBoss 2.8.80
 * @package BuddyBoss\Activity
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Manages Activity Topics data storage and operations.
 *
 * @since BuddyBoss 2.8.80
 */
#[\AllowDynamicProperties]
class BB_Activity_Topics_Manager {

	/**
	 * Instance of this class.
	 *
	 * @since  BuddyBoss 2.8.80
	 *
	 * @var object
	 *
	 * @access private
	 */
	private static $instance = null;

	/**
	 * Table name for Activity Topic Relationships.
	 *
	 * @since BuddyBoss 2.8.80
	 *
	 * @var string
	 */
	public $activity_topic_rel_table;

	/**
	 * Cache key for activity topics.
	 *
	 * @since BuddyBoss 2.8.80
	 *
	 * @var string
	 */
	public $activity_topics_cache_key = 'bb_activity_topics';

	/**
	 * WordPress Database instance.
	 *
	 * @since BuddyBoss 2.8.80
	 *
	 * @var wpdb
	 */
	private $wpdb;

	/**
	 * Get the singleton instance of this class.
	 *
	 * @since BuddyBoss 2.8.80
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
	 * @since BuddyBoss 2.8.80
	 */
	public function __construct() {
		global $wpdb;
		$this->wpdb = $wpdb;
		$prefix     = bp_core_get_table_prefix();

		$this->activity_topic_rel_table = bb_topics_manager_instance()->activity_topic_rel_table;

		$this->setup_hooks();
	}

	/**
	 * Setup hooks for logging actions.
	 *
	 * @since BuddyBoss 2.8.80
	 */
	private function setup_hooks() {
		add_action( 'load-buddyboss_page_bp-activity', array( $this, 'bb_activity_admin_edit_metabox_topic' ) );

		// Add custom column to activity admin list table.
		add_filter( 'bp_activity_list_table_get_columns', array( $this, 'bb_add_activity_admin_topic_column' ) );
		add_filter( 'bp_activity_admin_get_custom_column', array( $this, 'bb_activity_admin_topic_column_content' ), 10, 3 );
		add_action( 'bp_activity_admin_edit_after', array( $this, 'bb_save_activity_topic_metabox' ), 10, 1 );

		add_action( 'bp_activity_get_edit_data', array( $this, 'bb_activity_get_edit_topic_data' ), 10, 1 );

		add_action( 'bb_topic_before_added', array( $this, 'bb_validate_activity_topic_before_added' ) );
		add_filter( 'bp_ajax_querystring', array( $this, 'bb_activity_directory_set_topic_id' ), 20, 2 );
		add_filter( 'bp_activity_get_join_sql', array( $this, 'bb_activity_topic_get_join_sql' ), 10, 2 );
		add_filter( 'bp_activity_get_where_conditions', array( $this, 'bb_activity_topic_get_where_conditions' ), 10, 2 );
		add_filter( 'bp_core_get_js_strings', array( $this, 'bb_activity_topic_get_js_strings' ), 10, 1 );
		add_action( 'bp_after_member_activity_post_form', array( $this, 'bb_user_activity_topics_after_post_form' ), 10, 1 );
		add_filter( 'bp_before_has_activities_parse_args', array( $this, 'bb_get_activity_topic_from_url' ) );
	}

	/**
	 * Get the permission type for the activity topic.
	 *
	 * @since BuddyBoss 2.8.80
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
			 * @since BuddyBoss 2.8.80
			 *
			 * @param array $permission_types Array of permission types.
			 */
			'bb_activity_topic_permission_type',
			array(
				'anyone'      => __( 'Anyone', 'buddyboss' ),
				'mods_admins' => __( 'Admins', 'buddyboss' ),
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
	 * @since BuddyBoss 2.8.80
	 */
	public function bb_activity_admin_edit_metabox_topic() {
		add_meta_box( 'bp_activity_topic', __( 'Topic', 'buddyboss' ), array( $this, 'bb_activity_admin_edit_metabox_topic_content' ), 'buddyboss_page_bp-activity', 'normal', 'core' );
	}

	/**
	 * Display the activity topics metabox in activity admin page.
	 *
	 * @since BuddyBoss 2.8.80
	 *
	 * @param object $item The activity item.
	 */
	public function bb_activity_admin_edit_metabox_topic_content( $item ) {

		if ( ! isset( $item->id ) || ! function_exists( 'bb_topics_manager_instance' ) ) {
			return;
		}

		$topics = array();
		if ( 'groups' === $item->component && function_exists( 'bb_get_group_activity_topics' ) ) {
			$topics = bb_get_group_activity_topics(
				array(
					'item_id'   => $item->item_id,
					'item_type' => 'groups',
				)
			);
		} else {
			$topics = $this->bb_get_activity_topics();
		}
		$item->id         = (int) $item->id;
		$current_topic_id = (int) $this->bb_get_activity_topic( $item->id, 'id' );
		?>
		<div class="bb-activity-topic-container">
			<?php
			if ( ! empty( $topics ) ) {
				wp_nonce_field( 'save_activity_topic', 'activity_topic_nonce' );
				?>
				<select name="activity_topic" id="activity_topic">
					<?php
					foreach ( $topics as $topic ) {
						?>
							<option value="<?php echo esc_attr( $topic['topic_id'] ); ?>" <?php selected( $current_topic_id, $topic['topic_id'] ); ?>>
							<?php echo esc_html( $topic['name'] ); ?>
							</option>
							<?php
					}
					?>
				</select>
				<?php
			} else {
				?>
				<p><?php esc_html_e( 'No topics found.', 'buddyboss' ); ?></p>
				<?php
			}
			?>
		</div>
		<?php
	}

	/**
	 * Save the activity topic metabox.
	 *
	 * @since BuddyBoss 2.8.80
	 *
	 * @param object $activity The activity object.
	 */
	public function bb_save_activity_topic_metabox( $activity ) {
		if ( ! isset( $activity->id ) || ! isset( $_POST['activity_topic'] ) ) {
			return;
		}

		// Check nonce for security.
		if ( ! isset( $_POST['activity_topic_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['activity_topic_nonce'] ) ), 'save_activity_topic' ) ) {
			return;
		}

		/**
		 * Fires before saving the activity topic metabox.
		 *
		 * @since BuddyBoss 2.8.80
		 *
		 * @param object $activity The activity object.
		 */
		do_action( 'bb_before_save_activity_topic_metabox', $activity );

		// Get the topic ID from the form.
		$topic_id    = isset( $_POST['activity_topic'] ) ? absint( $_POST['activity_topic'] ) : 0;
		$activity_id = isset( $activity->id ) ? absint( $activity->id ) : 0;

		if ( $activity_id && $topic_id && function_exists( 'bb_topics_manager_instance' ) ) {
			// Save or update the activity-topic relationship.
			$args = array(
				'topic_id'    => $topic_id,
				'activity_id' => $activity_id,
				'component'   => $activity->component,
				'item_id'     => 0,
			);
			if ( 'groups' === $activity->component ) {
				$args['item_id'] = $activity->item_id;
			}
			$this->bb_add_activity_topic_relationship( $args );
		}

		/**
		 * Fires after saving the activity topic metabox.
		 *
		 * @since BuddyBoss 2.8.80
		 *
		 * @param int $activity_id The ID of the activity.
		 * @param int $topic_id    The ID of the topic.
		 */
		do_action( 'bb_after_save_activity_topic_metabox', $activity_id, $topic_id );
	}

	/**
	 * Add topic column to activity admin list table.
	 *
	 * @since BuddyBoss 2.8.80
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
				$new_columns['activity_topic'] = __( 'Topics', 'buddyboss' );
			}
		}
		return $new_columns;
	}

	/**
	 * Display topic column content in activity admin list table.
	 *
	 * @since BuddyBoss 2.8.80
	 *
	 * @param string $content     The column content.
	 * @param string $column_name Column name.
	 * @param array  $item        The activity item.
	 *
	 * @return string
	 */
	public function bb_activity_admin_topic_column_content( $content, $column_name, $item ) {

		if ( 'activity_topic' === $column_name && ! empty( $item['id'] ) ) {
			return $this->bb_get_activity_topic_url(
				array(
					'activity_id' => $item['id'],
					'html'        => true,
					'target'      => true,
				)
			);
		}

		return $content;
	}

	/**
	 * Add the activity topic relationship.
	 *
	 * @since BuddyBoss 2.8.80
	 *
	 * @param array $args Array of args. {
	 *     @type int    $topic_id    The ID of the topic.
	 *     @type int    $activity_id The ID of the activity.
	 *     @type string $component   The component (default: 'activity').
	 *     @type int    $item_id     The ID of the item (default: 0).
	 *     @type string $error_type  The error type ('wp_error' or other, default: 'wp_error').
	 * }
	 *
	 * @return int|WP_Error The ID of the inserted relationship on success, WP_Error on failure.
	 */
	public function bb_add_activity_topic_relationship( $args ) {
		// Parse and validate arguments with defaults.
		$r = bp_parse_args(
			$args,
			array(
				'topic_id'    => 0,
				'activity_id' => 0,
				'component'   => 'activity',
				'item_id'     => 0,
				'error_type'  => 'wp_error',
			)
		);

		// Validate required fields.
		if ( empty( $r['activity_id'] ) ) {
			return new WP_Error( 'bb_activity_topic_relationship_missing_data', __( 'Topic ID and Activity ID are required.', 'buddyboss' ) );
		}

		if ( bb_is_activity_topic_required() && empty( $r['topic_id'] ) ) {
			return new WP_Error( 'bb_activity_topic_relationship_missing_data', __( 'Topic ID is required.', 'buddyboss' ) );
		}

		/**
		 * Fires before an activity-topic relationship is added or updated.
		 *
		 * @since BuddyBoss 2.8.80
		 *
		 * @param array $r The arguments used to add the relationship.
		 */
		do_action( 'bb_activity_topic_relationship_before_add', $r );

		// Check if activity already has a topic assigned to prevent duplicates.
		$table_name = $this->activity_topic_rel_table;
		$existing   = $this->wpdb->get_row( $this->wpdb->prepare( "SELECT id, topic_id FROM `$table_name` WHERE activity_id = %d", $r['activity_id'] ) ); // phpcs:ignore WordPress.DB

		if ( $existing ) {
			// If the same topic is already assigned, return the existing relationship ID.
			if ( $existing->topic_id === $r['topic_id'] ) {
				return $existing->id;
			}

			// Update the existing relationship with the new topic instead of creating duplicate.
			$updated = $this->wpdb->update(
				$this->activity_topic_rel_table,
				array(
					'topic_id'     => $r['topic_id'],
					'component'    => $r['component'],
					'item_id'      => $r['item_id'],
					'date_updated' => current_time( 'mysql' ),
				),
				array( 'id' => $existing->id ),
				array( '%d', '%s', '%d', '%s' ),
				array( '%d' )
			);

			// Handle update errors.
			if ( false === $updated ) {
				$error_message = __( 'Failed to update activity-topic relationship.', 'buddyboss' );
				if ( 'wp_error' === $r['error_type'] ) {
					return new WP_Error( 'bb_activity_topic_relationship_db_update_error', $error_message );
				}

				wp_send_json_error( array( 'error' => $error_message ) );
			}

			// Get the updated relationship for hooks.
			$get_activity_relationship = $this->bb_get_activity_topic_relationship( array( 'id' => $existing->id ) );

			/**
			 * Fires after an activity-topic relationship is updated.
			 *
			 * @since BuddyBoss 2.8.80
			 *
			 * @param int   $existing_id               The ID of the updated relationship.
			 * @param array $get_activity_relationship The activity topic relationship.
			 * @param array $r                         The arguments used to update the relationship.
			 */
			do_action( 'bb_activity_topic_relationship_after_update', $existing->id, $get_activity_relationship, $r );

			return $existing->id;
		}

		// Insert new relationship since none exists for this activity.
		$inserted = $this->wpdb->insert(
			$this->activity_topic_rel_table,
			array(
				'topic_id'     => $r['topic_id'],
				'activity_id'  => $r['activity_id'],
				'component'    => $r['component'],
				'item_id'      => $r['item_id'],
				'date_created' => current_time( 'mysql' ),
				'date_updated' => current_time( 'mysql' ),
			),
			array( '%d', '%d', '%s', '%d', '%s', '%s' )
		);

		// Handle insert errors.
		if ( ! $inserted ) {
			$error_message = __( 'Failed to add activity-topic relationship.', 'buddyboss' );
			if ( 'wp_error' === $r['error_type'] ) {
				return new WP_Error( 'bb_activity_topic_relationship_db_insert_error', $error_message );
			}

			wp_send_json_error( array( 'error' => $error_message ) );
		}

		// Get the ID of the newly inserted relationship.
		$relationship_id = $this->wpdb->insert_id;

		// Retrieve the complete relationship data for hooks.
		$get_activity_relationship = $this->bb_get_activity_topic_relationship( array( 'id' => $relationship_id ) );

		/**
		 * Fires after an activity-topic relationship is added.
		 *
		 * @since BuddyBoss 2.8.80
		 *
		 * @param int   $relationship_id           The ID of the inserted relationship.
		 * @param array $get_activity_relationship The activity topic relationship.
		 * @param array $r                         The arguments used to add the relationship.
		 */
		do_action( 'bb_activity_topic_relationship_after_add', $relationship_id, $get_activity_relationship, $r );

		return $relationship_id;
	}

	/**
	 * Update the activity topic relationship.
	 *
	 * @since BuddyBoss 2.8.80
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
				'component'   => 'activity',
				'item_id'     => 0,
				'error_type'  => 'wp_error',
			)
		);

		/**
		 * Fires before updating the activity topic relationship.
		 *
		 * @since BuddyBoss 2.8.80
		 *
		 * @param array $args Array of args. {
		 *     @type int $previous_id The ID of the previous topic.
		 *     @type int $topic_id    The ID of the topic.
		 * }
		 */
		do_action( 'bb_before_update_activity_topic_relationship', $args );

		$where        = array();
		$where_format = array();
		// Build where clause and format specifiers.
		if ( ! empty( $r['previous_id'] ) ) {
			$where['topic_id'] = $r['previous_id'];
			$where_format[]    = '%d';
		}

		if ( ! empty( $r['item_id'] ) ) {
			$where['item_id'] = $r['item_id'];
			$where_format[]   = '%d';
		}

		if ( ! empty( $r['component'] ) ) {
			$where['component'] = $r['component'];
			$where_format[]     = '%s';
		}

		$updated = $this->wpdb->update(
			$this->activity_topic_rel_table,
			array( 'topic_id' => $r['topic_id'] ),
			$where,
			array( '%d' ),
			$where_format
		);

		if ( false === $updated ) {
			return false;
		}

		$get_activity_relationship = $this->bb_get_activity_topic_relationship( array( 'id' => $updated ) );

		/**
		 * Fires after updating the activity topic relationship.
		 *
		 * @since BuddyBoss 2.8.80
		 *
		 * @param int   $updated                   The ID of the updated relationship.
		 * @param array $get_activity_relationship The activity topic relationship.
		 * @param array $args                      Array of args. {
		 *      @type int $previous_id The ID of the previous topic.
		 *      @type int $topic_id    The ID of the topic.
		 * }
		 */
		do_action( 'bb_after_update_activity_topic_relationship', $updated, $get_activity_relationship, $args );

		return $updated;
	}

	/**
	 * Delete the activity topic relationship.
	 *
	 * @since BuddyBoss 2.8.80
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

		/**
		 * Fires before deleting the activity topic relationship.
		 *
		 * @since BuddyBoss 2.8.80
		 *
		 * @param array $args Array of args. {
		 *     @type int $topic_id The ID of the topic.
		 * }
		 */
		do_action( 'bb_before_delete_activity_topic_relationship', $args );

		$get_activity_relationship = $this->bb_get_activity_topic_relationship(
			array(
				'topic_id'  => $r['topic_id'],
				'item_id'   => $r['item_id'],
				'component' => $r['item_type'],
			)
		);

		if ( empty( $get_activity_relationship ) ) {
			return false;
		}

		$deleted = $this->wpdb->delete(
			$this->activity_topic_rel_table,
			array(
				'topic_id'  => $r['topic_id'],
				'item_id'   => $r['item_id'],
				'component' => $r['item_type'],
			),
			array( '%d', '%d', '%s' )
		);

		if ( false === $deleted ) {
			return false;
		}

		$get_activity_relationship = $this->bb_get_activity_topic_relationship( array( 'id' => $deleted ) );

		/**
		 * Fires after deleting the activity topic relationship.
		 *
		 * @since BuddyBoss 2.8.80
		 *
		 * @param int $deleted The ID of the deleted relationship.
		 * @param array $get_activity_relationship The activity topic relationship.
		 * @param array $args Array of args. {
		 *     @type int $topic_id The ID of the topic.
		 * }
		 */
		do_action( 'bb_after_delete_activity_topic_relationship', $deleted, $get_activity_relationship, $args );

		return $deleted;
	}

	/**
	 * Function to get activity topic relationship.
	 *
	 * @since BuddyBoss 2.8.80
	 *
	 * @param array $args Array of args. {
	 *     @type int $id The ID of the activity topic relationship.
	 *     @type int $topic_id The ID of the topic.
	 *     @type int $item_id The ID of the item.
	 *     @type string $component The component.
	 * }
	 *
	 * @return array Array of activity topic relationship.
	 */
	public function bb_get_activity_topic_relationship( $args ) {
		$r = bp_parse_args(
			$args,
			array(
				'id' => 0,
			)
		);

		$where_clauses = array();
		$where_values  = array();

		if ( ! empty( $r['id'] ) ) {
			$where_clauses[] = 'id = %d';
			$where_values[]  = $r['id'];
		}

		if ( ! empty( $r['topic_id'] ) ) {
			$where_clauses[] = 'topic_id = %d';
			$where_values[]  = $r['topic_id'];
		}

		if ( ! empty( $r['item_id'] ) ) {
			$where_clauses[] = 'item_id = %d';
			$where_values[]  = $r['item_id'];
		}

		if ( ! empty( $r['component'] ) ) {
			$where_clauses[] = 'component = %s';
			$where_values[]  = $r['component'];
		}

		if ( ! empty( $r['activity_id'] ) ) {
			$where_clauses[] = 'activity_id = %d';
			$where_values[]  = $r['activity_id'];
		}

		if ( empty( $where_clauses ) ) {
			return null;
		}

		$where_sql  = implode( ' AND ', $where_clauses );
		$table_name = $this->activity_topic_rel_table;

		// Handle dynamic fields.
		if ( ! empty( $r['fields'] ) ) {
			// Validate and sanitize fields.
			$allowed_fields   = array( 'id', 'topic_id', 'activity_id', 'component', 'item_id', 'date_created', 'date_updated' );
			$requested_fields = explode( ',', $r['fields'] );
			$valid_fields     = array();

			if ( ! empty( $requested_fields ) ) {
				foreach ( $requested_fields as $field ) {
					$field = trim( $field );
					if ( in_array( $field, $allowed_fields, true ) ) {
						$valid_fields[] = $field;
					}
				}
			}

			if ( ! empty( $valid_fields ) ) {
				$select_fields = implode( ', ', $valid_fields );
				$query         = "SELECT $select_fields FROM `$table_name` WHERE $where_sql";

				// If only one field requested, return as column.
				if ( 1 === count( $valid_fields ) ) {
					return $this->wpdb->get_col( $this->wpdb->prepare( $query, $where_values ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
				}

				// Multiple fields, return as results.
				return $this->wpdb->get_results( $this->wpdb->prepare( $query, $where_values ), ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
			}
		}

		$query = "SELECT * FROM `$table_name` WHERE $where_sql";
		return $this->wpdb->get_row( $this->wpdb->prepare( $query, $where_values ), ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
	}

	/**
	 * Get the activity topic data.
	 *
	 * @since BuddyBoss 2.8.80
	 *
	 * @param array $args Array of args.
	 *
	 * @return array Array of args with topic id.
	 */
	public function bb_activity_get_edit_topic_data( $args ) {
		if ( ! isset( $args['id'] ) ) {
			return $args;
		}

		$topic_id                   = $this->bb_get_activity_topic( (int) $args['id'], 'all' );
		$args['topics']['topic_id'] = isset( $topic_id->topic_id ) ? $topic_id->topic_id : 0;
		if ( ! empty( $args['topics']['topic_id'] ) ) {
			$args['topics']['topic_slug'] = isset( $topic_id->slug ) ? $topic_id->slug : '';
			$args['topics']['topic_name'] = isset( $topic_id->name ) ? $topic_id->name : '';
		}

		return $args;
	}

	/**
	 * Set the topic id in the querystring.
	 *
	 * @since BuddyBoss 2.8.80
	 *
	 * @param string $querystring The querystring.
	 * @param string $object_type The object type.
	 *
	 * @return string Modified querystring.
	 */
	public function bb_activity_directory_set_topic_id( $querystring, $object_type ) {
		if ( 'activity' !== $object_type || bp_is_single_activity() ) {
			return $querystring;
		}

		if ( empty( $_POST['topic_id'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
			return $querystring;
		}

		// Add topic id to querystring if it exists.
		$querystring             = bp_parse_args( $querystring );
		$querystring['topic_id'] = (int) sanitize_text_field( wp_unslash( $_POST['topic_id'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Missing

		return http_build_query( $querystring );
	}

	/**
	 * Validate the activity topic before it is added.
	 *
	 * @since BuddyBoss 2.8.80
	 *
	 * @param array $args The arguments array.
	 */
	public function bb_validate_activity_topic_before_added( $args ) {
		if (
			'activity' === $args['item_type'] &&
			! bp_current_user_can( 'administrator' )
		) {
			$error_message = __( 'You are not allowed to add a topic.', 'buddyboss' );
			if ( 'wp_error' === $args['error_type'] ) {
				return new WP_Error( 'bb_topic_not_allowed', $error_message );
			}

			wp_send_json_error( array( 'error' => $error_message ) );
		}
	}

	/**
	 * Add join SQL for activity topic filtering.
	 *
	 * @since BuddyBoss 2.8.80
	 *
	 * @param string $join_sql Join SQL statement.
	 * @param array  $args     Query arguments.
	 *
	 * @return string Modified join SQL statement.
	 */
	public function bb_activity_topic_get_join_sql( $join_sql, $args = array() ) {

		if ( empty( $args['topic_id'] ) ) {
			return $join_sql;
		}

		$bp_prefix = bp_core_get_table_prefix();
		$join_sql .= " LEFT JOIN {$bp_prefix}bb_activity_topic_relationship AS atr ON a.id = atr.activity_id";

		return $join_sql;
	}

	/**
	 * Get the where conditions for the activity topic.
	 *
	 * @since BuddyBoss 2.8.80
	 *
	 * @param array $where_conditions Array of where conditions.
	 * @param array $args             Query arguments.
	 *
	 * @return array Modified array of where conditions.
	 */
	public function bb_activity_topic_get_where_conditions( $where_conditions, $args ) {

		if ( empty( $args['topic_id'] ) ) {
			return $where_conditions;
		}

		$topic_id  = (int) $args['topic_id'];
		$pinned_id = isset( $args['pinned_id'] ) ? (int) $args['pinned_id'] : 0;

		if ( ! empty( $pinned_id ) ) {
			$where_conditions['topic_filter'] = "( atr.topic_id = {$topic_id} OR a.id = {$pinned_id})";
		} else {
			$where_conditions['topic_filter'] = "atr.topic_id = {$topic_id}";
		}

		return $where_conditions;
	}

	/**
	 * Process activity topic ID parameter for activity queries.
	 *
	 * @since BuddyBoss 2.8.80
	 *
	 * @param string $qs The activity query string.
	 *
	 * @return string Modified query string.
	 */
	public function bb_activity_add_topic_id_to_query_string( $qs ) {
		if ( empty( $_POST['topic_id'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
			return $qs;
		}

		$topic_id = (int) sanitize_text_field( wp_unslash( $_POST['topic_id'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Missing
		if ( $topic_id ) {
			$qs .= '&topic_id=' . $topic_id;
		}

		return $qs;
	}

	/**
	 * Get the JS strings for the activity topic.
	 *
	 * @since BuddyBoss 2.8.80
	 *
	 * @param array $strings Array of strings.
	 *
	 * @return array Modified array of strings.
	 */
	public function bb_activity_topic_get_js_strings( $strings ) {
		$topic_lists = $this->bb_get_activity_topics(
			array(
				'item_type' => 'activity',
				'can_post'  => true,
			)
		);
		// If group activity topics is not enabled, then don't show the topic lists.
		if ( bp_is_group() ) {
			$topic_lists = array();
			if (
				bp_is_active( 'groups' ) &&
				function_exists( 'bb_is_enabled_group_activity_topics' ) &&
				bb_is_enabled_group_activity_topics() &&
				function_exists( 'bb_get_group_activity_topics' )
			) {
				$topic_lists = bb_get_group_activity_topics( array( 'can_post' => true ) );
			}
		}

		$strings['activity']['params']['topics']['bb_is_enabled_group_activity_topics'] = function_exists( 'bb_is_enabled_group_activity_topics' ) && bb_is_enabled_group_activity_topics();
		$strings['activity']['params']['topics']['bb_is_enabled_activity_topics']       = function_exists( 'bb_is_enabled_activity_topics' ) ? bb_is_enabled_activity_topics() : false;
		$strings['activity']['params']['topics']['bb_is_activity_topic_required']       = function_exists( 'bb_is_activity_topic_required' ) ? bb_is_activity_topic_required() : false;
		$strings['activity']['params']['topics']['topic_lists']                         = ! empty( $topic_lists ) ? $topic_lists : array();
		$strings['activity']['params']['topics']['topic_tooltip_error']                 = esc_html__( 'Please select a topic', 'buddyboss' );
		$strings['activity']['params']['topics']['is_activity_directory']               = bp_is_activity_directory();
		return $strings;
	}

	/**
	 * Function to get the activity topics.
	 *
	 * @since BuddyBoss 2.8.80
	 *
	 * @param array $args The arguments array.
	 *
	 * @return array Array of activity topics.
	 */
	public function bb_get_activity_topics( $args = array() ) {
		$r = bp_parse_args(
			$args,
			array(
				'item_id'   => 0,
				'item_type' => 'activity',
				'fields'    => 'name,slug,topic_id',
			)
		);

		$r['item_type'] = ! empty( $r['item_type'] ) ? ( is_array( $r['item_type'] ) ? $r['item_type'] : array( $r['item_type'] ) ) : array( 'activity' );
		if ( ! empty( $r['can_post'] ) && in_array( 'activity', $r['item_type'], true ) ) {
			if ( bp_current_user_can( 'administrator' ) ) {
				$r['permission_type'] = array( 'mods_admins', 'anyone' );
			} else {
				$r['permission_type'] = 'anyone';
			}
		}

		$cache_key   = 'bb_activity_topics_' . md5( maybe_serialize( $r ) );
		$topic_cache = wp_cache_get( $cache_key, $this->activity_topics_cache_key );
		if ( false !== $topic_cache ) {
			return $topic_cache;
		}

		$topic_lists = bb_topics_manager_instance()->bb_get_topics( $r );

		if ( ! empty( $r['count_total'] ) ) {
			$topic_lists = ! empty( $topic_lists ) ? $topic_lists : array();
		} else {
			$topic_lists = ! empty( $topic_lists['topics'] ) ? $topic_lists['topics'] : array();
		}

		wp_cache_set( $cache_key, $topic_lists, $this->activity_topics_cache_key );

		return $topic_lists;
	}

	/**
	 * Check if a user can post to a activity topic.
	 *
	 * @since BuddyBoss 2.8.80
	 *
	 * @param int $topic_id The topic ID.
	 *
	 * @return bool True if the user can post to the topic, false otherwise.
	 */
	public function bb_can_user_post_to_activity_topic( $topic_id ) {
		$get_permission_type = bb_topics_manager_instance()->bb_get_topic_permission_type(
			array(
				'topic_id'  => $topic_id,
				'item_id'   => 0,
				'item_type' => 'activity',
			)
		);

		if ( 'mods_admins' === $get_permission_type && ! bp_current_user_can( 'administrator' ) ) {
			return false;
		}
		return true;
	}

	/**
	 * Get the activity topic information.
	 *
	 * @since BuddyBoss 2.8.80
	 *
	 * @param int    $activity_id The ID of the activity.
	 * @param string $return_type The type of data to return ('id', 'name', 'slug', 'all').
	 *
	 * @return mixed Topic data based on return type.
	 */
	public function bb_get_activity_topic( $activity_id, $return_type = 'id' ) {
		// Validate activity ID.
		$activity_id = absint( $activity_id );
		if ( empty( $activity_id ) ) {
			return 'all' === $return_type ? null : ( 'name' === $return_type ? '' : 0 );
		}

		// Define allowed return types and their default values.
		$allowed_types = array(
			'id'   => '',
			'name' => '',
			'slug' => '',
			'all'  => null,
		);

		// Validate return type.
		if ( ! array_key_exists( $return_type, $allowed_types ) ) {
			$return_type = 'id';
		}

		// Get the default value for the return type.
		$default_value = $allowed_types[ $return_type ];

		// Prepare the query based on return type.
		if ( 'all' === $return_type ) {
			$select = 't.name, t.slug, atr.*';
			$method = 'get_row';
		} else {
			$select = 't.' . sanitize_sql_orderby( $return_type );
			$method = 'get_var';
		}

		// Build and execute the query.
		$activity_topic_table = $this->activity_topic_rel_table;
		$topics_table         = $this->wpdb->prefix . 'bb_topics';

		// Prepare the base query with proper table names.
		$base_query = "SELECT %s FROM `$activity_topic_table` atr LEFT JOIN `$topics_table` t ON t.id = atr.topic_id WHERE atr.activity_id = %d LIMIT 1";

		$query  = sprintf( $base_query, $select, $activity_id );
		$result = $this->wpdb->$method( $query ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared

		/**
		 * Filters the activity topic data before returning.
		 *
		 * @since BuddyBoss 2.8.80
		 *
		 * @param mixed  $result      The topic data.
		 * @param int    $activity_id The activity ID.
		 * @param string $return_type The requested return type.
		 */
		$result = apply_filters( 'bb_get_activity_topic_data', $result, $activity_id, $return_type );

		return ! empty( $result ) ? $result : $default_value;
	}

	/**
	 * Get the activity topic URL.
	 *
	 * @since BuddyBoss 2.8.80
	 *
	 * @param array $args Array of args. {
	 *     @type int    $activity_id The ID of the activity.
	 *     @type bool   $html        Whether to return the URL as HTML link.
	 *     @type bool   $target      Whether to add target="_blank".
	 *     @type string $class       CSS class for the link.
	 *     @type string $link_text   The link text (defaults to topic name).
	 * }
	 *
	 * @return string The activity topic URL or HTML link.
	 */
	public function bb_get_activity_topic_url( $args = array() ) {
		$args = wp_parse_args(
			$args,
			array(
				'activity_id' => 0,
				'html'        => false,
				'target'      => false,
				'link_text'   => '',
				'class'       => '',
			)
		);

		$activity_id   = (int) $args['activity_id'];
		$html          = (bool) $args['html'];
		$target        = (bool) $args['target'];
		$default_class = 'bb-topic-url';
		if ( empty( $args['class'] ) ) {
			$class = 'bb-topic-url';
		} else {
			$classes = array_filter( array_map( 'sanitize_html_class', array_merge( array( $default_class ), explode( ' ', $args['class'] ) ) ) );
			$class   = implode( ' ', array_unique( $classes ) );
		}
		$link_text = $args['link_text'];

		$topic = $this->bb_get_activity_topic( $activity_id, 'all' );
		if ( ! $topic || ! is_object( $topic ) || empty( $topic->slug ) ) {
			return '';
		}

		// Get the appropriate directory permalink based on component.
		$directory_permalink = '';
		if (
			bp_is_active( 'groups' ) &&
			isset( $topic->component ) &&
			'groups' === $topic->component &&
			! empty( $topic->item_id )
		) {
			$group               = groups_get_group( $topic->item_id );
			$directory_permalink = bp_get_group_permalink( $group ) . bp_get_activity_slug();
		} else {
			$directory_permalink = bp_get_activity_directory_permalink();
		}

		if ( empty( $directory_permalink ) ) {
			return '';
		}

		$url = add_query_arg( 'bb-topic', rawurlencode( $topic->slug ), trailingslashit( $directory_permalink ) );

		/**
		 * Filters the activity topic URL before returning.
		 *
		 * @since BuddyBoss 2.8.80
		 *
		 * @param string $url         The topic URL.
		 * @param object $topic       The topic object.
		 * @param int    $activity_id The activity ID.
		 * @param bool   $html        Whether to return HTML.
		 */
		$url = apply_filters( 'bb_activity_topic_url', $url, $topic, $activity_id, $html );

		$output = '';

		if ( $html ) {
			$link_text   = $link_text ? $link_text : $topic->name;
			$html_output = sprintf(
				'<a href="%s" class="%s"%s data-topic-id="%s">%s</a>',
				esc_url( $url ),
				esc_attr( $class ),
				$target ? ' target="_blank"' : '',
				esc_attr( $topic->topic_id ),
				esc_html( $link_text )
			);

			/**
			 * Filter the HTML output for the activity topic URL.
			 *
			 * @since BuddyBoss 2.8.80
			 *
			 * @param string $html_output The HTML output.
			 * @param string $url         The topic URL.
			 * @param object $topic       The topic object.
			 * @param int    $activity_id The activity ID.
			 * @param bool   $html        Whether HTML output is requested.
			 * @param array  $args        The original arguments array.
			 */
			$output = apply_filters( 'bb_activity_topic_url_html', $html_output, $url, $topic, $activity_id, $html, $args );
		} else {
			$output = esc_url( $url );
		}

		return $output;
	}

	/**
	 * Add the activity topic selector after the post form in user activity.
	 *
	 * @since BuddyBoss 2.8.80
	 */
	public function bb_user_activity_topics_after_post_form() {
		$current_slug = function_exists( 'bb_topics_manager_instance' ) ? bb_topics_manager_instance()->bb_get_topic_slug_from_url() : '';
		$topics       = $this->bb_get_activity_topics();
		if ( ! empty( $topics ) ) {
			?>
			<div class="activity-topic-selector">
				<ul>
					<li>
						<a href="<?php echo esc_url( bp_get_activity_directory_permalink() ); ?>"><?php esc_html_e( 'All', 'buddyboss' ); ?></a>
					</li>
					<?php
					foreach ( $topics as $topic ) {
						$li_class = '';
						$a_class  = '';
						if ( ! empty( $current_slug ) && $current_slug === $topic['slug'] ) {
							$li_class = 'selected';
							$a_class  = 'selected active';
						}
						echo '<li class="bb-topic-selector-item ' . esc_attr( $li_class ) . '"><a href="' . esc_url( add_query_arg( 'bb-topic', $topic['slug'] ) ) . '" data-topic-id="' . esc_attr( $topic['topic_id'] ) . '" class="bb-topic-selector-link ' . esc_attr( $a_class ) . '">' . esc_html( $topic['name'] ) . '</a></li>';
					}
					?>
				</ul>
			</div>
			<?php
		}
	}

	/**
	 * Get the activity topic from the URL.
	 *
	 * @since BuddyBoss 2.8.80
	 *
	 * @param array $args The arguments.
	 *
	 * @return array The arguments.
	 */
	public function bb_get_activity_topic_from_url( $args ) {
		$topic_slug = bb_topics_manager_instance()->bb_get_topic_slug_from_url();
		if ( ! empty( $topic_slug ) ) {
			$topic_data = bb_topics_manager_instance()->bb_get_topic_by( 'slug', urldecode( $topic_slug ) );
			if ( ! empty( $topic_data ) && isset( $topic_data->id ) ) {
				$args['topic_id'] = $topic_data->id;
			}
		}
		return $args;
	}
}
