<?php
/**
 * BuddyBoss Platform Topics Manager.
 *
 * Handles database schema creation and CRUD operations for topics.
 *
 * @since   BuddyBoss [BBVERSION]
 * @package BuddyBoss\Activity
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Manages Topics data storage and operations.
 *
 * @since BuddyBoss [BBVERSION]
 */
class BB_Topics_Manager {

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
	 * Table name for Topics.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @var string
	 */
	public $topics_table;

	/**
	 * Table name for Topic Relationships.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @var string
	 */
	public $topic_rel_table;

	/**
	 * Table name for Activity Topic Relationships.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @var string
	 */
	public $activity_topic_rel_table;

	/**
	 * Cache group for topics.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @var string
	 */
	public static $topic_cache_group = 'bb_topics';

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
	 * @return BB_Topics_Manager The singleton instance.
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
		$prefix     = bp_core_get_table_prefix();

		$this->topics_table             = $prefix . 'bb_topics';
		$this->topic_rel_table          = $prefix . 'bb_topic_relationships';
		$this->activity_topic_rel_table = $prefix . 'bb_activity_topic_relationship';

		$this->setup_hooks();
	}

	/**
	 * Setup hooks for logging actions.
	 *
	 * @since BuddyBoss [BBVERSION]
	 */
	private function setup_hooks() {
		add_action( 'bp_admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
		add_action( 'bp_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
		add_action( 'wp_ajax_bb_add_topic', array( $this, 'bb_add_topic_ajax' ) );
		add_action( 'wp_ajax_bb_edit_topic', array( $this, 'bb_edit_topic_ajax' ) );
		add_action( 'wp_ajax_bb_delete_topic', array( $this, 'bb_delete_topic_ajax' ) );
		add_action( 'wp_ajax_bb_update_topics_order', array( $this, 'bb_update_topics_order_ajax' ) );
	}

	/**
	 * Enqueue scripts.
	 *
	 * @since BuddyBoss [BBVERSION]
	 */
	public function enqueue_scripts() {
		$bp  = buddypress();
		$min = bp_core_get_minified_asset_suffix();
		wp_enqueue_script(
			'bb-topics-manager',
			$bp->plugin_url . 'bp-core/js/bb-topics-manager' . $min . '.js',
			array(
				'jquery',
			),
			bp_get_version(),
			true
		);

		$bb_topics_js_strings = apply_filters(
			'bb_topics_js_strings',
			array(
				'ajax_url'                     => admin_url( 'admin-ajax.php' ),
				/* translators: %s: Topic name */
				'delete_topic_confirm'         => esc_html__( 'Are you sure you want to delete "%s"? This cannot be undone.', 'buddyboss' ),
				'bb_update_topics_order_nonce' => wp_create_nonce( 'bb_update_topics_order' ),
				'generic_error'                => esc_html__( 'An error occurred while updating topic order.', 'buddyboss' ),
			)
		);
		wp_localize_script(
			'bb-topics-manager',
			'bbTopicsManagerVars',
			$bb_topics_js_strings
		);
	}

	/**
	 * Create the necessary database tables.
	 *
	 * @since BuddyBoss [BBVERSION]
	 */
	public function create_tables() {
		$sql             = array();
		$wpdb            = $GLOBALS['wpdb'];
		$charset_collate = $wpdb->get_charset_collate();

		$topics_table             = $this->topics_table;
		$topic_rel_table          = $this->topic_rel_table;
		$activity_topic_rel_table = $this->activity_topic_rel_table;

		$has_topics_table = $wpdb->query( $wpdb->prepare( 'show tables like %s', $topics_table ) ); //phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		if ( empty( $has_topics_table ) ) {

			$sql[] = "CREATE TABLE {$topics_table} (
				id BIGINT(20) UNSIGNED AUTO_INCREMENT,
				name VARCHAR(255) NOT NULL,
				slug VARCHAR(255) NOT NULL,
				PRIMARY KEY (id),
				KEY name (name),
				KEY slug (slug)
			) $charset_collate;";
		}

		$has_topic_rel_table = $wpdb->query( $wpdb->prepare( 'show tables like %s', $topic_rel_table ) ); //phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		if ( empty( $has_topic_rel_table ) ) {

			$sql[] = "CREATE TABLE {$topic_rel_table} (
				id BIGINT(20) UNSIGNED AUTO_INCREMENT,
				item_id BIGINT(20) UNSIGNED NOT NULL,
				item_type VARCHAR(10) NOT NULL,
				topic_id BIGINT(20) UNSIGNED NOT NULL,
				user_id BIGINT(20) UNSIGNED NOT NULL,
				permission_type VARCHAR(20) NOT NULL,
				permission_data LONGTEXT NULL,
				menu_order INT NOT NULL DEFAULT 0,
				date_created DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
				date_updated DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
				PRIMARY KEY (id),
				KEY item_id (item_id),
				KEY item_type (item_type),
				KEY topic_id (topic_id),
				KEY user_id (user_id),
				KEY permission_type (permission_type),
				KEY date_created (date_created),
				KEY date_updated (date_updated)
			) $charset_collate;";
		}

		$has_activity_topic_rel_table = $wpdb->query( $wpdb->prepare( 'show tables like %s', $activity_topic_rel_table ) ); //phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		if ( empty( $has_activity_topic_rel_table ) ) {

			$sql[] = "CREATE TABLE {$activity_topic_rel_table} (
				id BIGINT(20) UNSIGNED AUTO_INCREMENT,
				topic_id BIGINT(20) UNSIGNED NOT NULL,
				activity_id BIGINT(20) UNSIGNED NOT NULL,
				component VARCHAR(20) NOT NULL,
				item_id BIGINT(20) UNSIGNED NOT NULL,
				date_created DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
				date_updated DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
				PRIMARY KEY (id),
				KEY topic_id (topic_id),
				KEY activity_id (activity_id),
				KEY component (component),
				KEY item_id (item_id),
				KEY date_created (date_created),
				KEY date_updated (date_updated)
			) $charset_collate;";
		}

		if ( ! empty( $sql ) ) {
			// Ensure that dbDelta() is defined.
			if ( ! function_exists( 'dbDelta' ) ) {
				require_once ABSPATH . 'wp-admin/includes/upgrade.php';
			}

			dbDelta( $sql );
		}
	}

	/**
	 * Add a new global topic via AJAX.
	 *
	 * @since BuddyBoss [BBVERSION]
	 */
	public function bb_add_topic_ajax() {
		if ( ! isset( $_POST['name'] ) ) {
			wp_send_json_error(
				array(
					'error' => __( 'Topic name is required.', 'buddyboss' ),
					'topic' => array(),
				)
			);
		}

		check_ajax_referer( 'bb_add_topic', 'nonce' );

		$name               = sanitize_text_field( wp_unslash( $_POST['name'] ) );
		$slug               = isset( $_POST['slug'] ) ? sanitize_title( wp_unslash( $_POST['slug'] ) ) : '';
		$permission_type    = isset( $_POST['permission_type'] ) ? sanitize_text_field( wp_unslash( $_POST['permission_type'] ) ) : 'anyone';
		$previous_topic_id  = isset( $_POST['topic_id'] ) ? absint( wp_unslash( $_POST['topic_id'] ) ) : 0;
		$item_id            = isset( $_POST['item_id'] ) ? absint( wp_unslash( $_POST['item_id'] ) ) : 0;
		$item_type          = isset( $_POST['item_type'] ) ? sanitize_text_field( wp_unslash( $_POST['item_type'] ) ) : 'activity';
		$action_from        = isset( $_POST['action_from'] ) ? sanitize_text_field( wp_unslash( $_POST['action_from'] ) ) : 'admin';
		$is_global_activity = false;
		if ( isset( $_POST['is_global_activity'] ) ) {
			$val = sanitize_text_field( wp_unslash( $_POST['is_global_activity'] ) );
			if ( '1' === $val || 'true' === $val ) {
				$is_global_activity = true;
			} else {
				$is_global_activity = false;
			}
		}

		if ( empty( $slug ) ) {
			$slug = sanitize_title( $name );
		} else {
			$slug = sanitize_title( $slug );
		}

		if ( empty( $previous_topic_id ) ) {
			$topic_data = $this->bb_add_topic(
				array(
					'name'            => $name,
					'slug'            => $slug,
					'permission_type' => $permission_type,
					'item_id'         => $item_id,
					'item_type'       => $item_type,
				)
			);
		} else {
			$topic_data = $this->bb_update_topic(
				array(
					'id'                 => $previous_topic_id,
					'name'               => $name,
					'slug'               => $slug,
					'permission_type'    => $permission_type,
					'item_type'          => $item_type,
					'item_id'            => $item_id,
					'is_global_activity' => $is_global_activity,
				)
			);
		}

		if ( ! $topic_data ) {
			wp_send_json_error( array( 'error' => __( 'Failed to add topic.', 'buddyboss' ) ) );
		}

		wp_send_json_success( array( 'topic_id' => $topic_data->id ) );
	}

	/**
	 * Add a new global topic.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @param array $args {
	 * Array of arguments for adding a topic.
	 *
	 * @type string $name Required. The name of the topic.
	 * @type string $slug Optional. The slug for the topic. Auto-generated if empty.
	 * }
	 *
	 * @return int|WP_Error Topic ID on success, WP_Error on failure.
	 */
	public function bb_add_topic( $args ) {
		$r = bp_parse_args(
			$args,
			array(
				'name'       => '',
				'slug'       => '',
				'item_type'  => 'activity',
				'item_id'    => 0,
				'error_type' => 'bool',
			)
		);

		if ( empty( $r['name'] ) ) {
			$error_message = __( 'Topic name is required.', 'buddyboss' );
			if ( 'wp_error' === $r['error_type'] ) {
				unset( $r );

				return new WP_Error( 'bb_topic_name_required', $error_message );
			}

			unset( $r );

			wp_send_json_error(
				array(
					'error' => $error_message,
				)
			);
		}

		/**
		 * Fires before a topic has been added.
		 *
		 * @since BuddyBoss [BBVERSION]
		 *
		 * @param array $r The arguments used to add the topic.
		 */
		do_action( 'bb_topic_before_added', $r );

		$r['slug'] = ! empty( $r['slug'] ) ? $r['slug'] : sanitize_title( $r['name'] );

		// Check if relationship already exists.
		$existing_relationship = $this->bb_get_topic(
			array(
				'slug'      => $r['slug'],
				'item_type' => $r['item_type'],
				'item_id'   => $r['item_id'],
			)
		);

		if ( $existing_relationship ) {
			$error_message = esc_html__( 'This topic name is already in use. Please enter a unique topic name.', 'buddyboss' );
			if ( 'wp_error' === $r['error_type'] ) {
				unset( $r );

				return new WP_Error( 'bb_topic_duplicate_slug', esc_html( $error_message ) );
			}

			unset( $r );

			wp_send_json_error(
				array(
					'error' => esc_html( $error_message ),
				)
			);
		}

		// Check if we've reached the maximum number of topics (20).
		if ( $this->bb_topics_limit_reached( $r ) ) {
			$error_message = esc_html__( 'Maximum number of topics (20) has been reached.', 'buddyboss' );
			if ( 'wp_error' === $r['error_type'] ) {
				unset( $r );

				return new WP_Error( 'bb_topic_limit_reached', esc_html( $error_message ) );
			}

			unset( $r );

			wp_send_json_error(
				array(
					'error' => esc_html( $error_message ),
				)
			);
		}

		$existing_slug = $this->bb_get_topic_by( 'slug', $r['slug'] );

		if ( ! $existing_slug ) {
			// Prepare data for insertion.
			$data   = array(
				'name' => sanitize_text_field( $r['name'] ),
				'slug' => $r['slug'],
			);
			$format = array( '%s', '%s' );

			// Use the updated table name property.
			$inserted = $this->wpdb->insert( $this->topics_table, $data, $format );

			if ( ! $inserted ) {
				return new WP_Error( 'bb_topic_db_insert_error', $this->wpdb->last_error );
			}

			$topic_id = $this->wpdb->insert_id;
		} else {
			$topic_id = $existing_slug->id;
		}

		if ( empty( $r['topic_id'] ) && ! empty( $topic_id ) ) {
			$this->bb_add_topic_relationship(
				array(
					'topic_id'        => $topic_id,
					'permission_type' => $r['permission_type'],
					'item_id'         => $r['item_id'],
					'item_type'       => $r['item_type'],
				)
			);
		} else {
			$this->bb_update_topic_relationship(
				array(
					'topic_id'        => $topic_id,
					'permission_type' => $r['permission_type'],
					'where'           => array(
						'topic_id'  => $r['topic_id'],
						'item_id'   => $r['item_id'],
						'item_type' => $r['item_type'],
					),
				)
			);

			if ( function_exists( 'bb_activity_topics_manager_instance' ) ) {
				bb_activity_topics_manager_instance()->bb_update_activity_topic_relationship(
					array(
						'topic_id'    => $topic_id,
						'previous_id' => $r['topic_id'],
					)
				);
			}
		}

		$get_topic_relationship = $this->bb_get_topic(
			array(
				'topic_id'  => $topic_id,
				'item_id'   => $r['item_id'],
				'item_type' => $r['item_type'],
			)
		);

		/**
		 * Fires after a topic has been added.
		 *
		 * @param object $get_topic_relationship The topic relationship object.
		 * @param array  $r The arguments used to add the topic.
		 */
		do_action( 'bb_topic_after_added', $get_topic_relationship, $r );

		unset( $r, $data, $format, $inserted );

		return $get_topic_relationship;
	}

	/**
	 * Update an existing topic.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @param array $args {
	 *     Array of arguments for updating a topic.
	 *
	 *     @type int    $previous_topic_id The ID of the topic to update.
	 *     @type string $name             The new name for the topic.
	 *     @type string $slug             The new slug for the topic.
	 *     @type string $permission_type  The permission type for the topic.
	 *     @type int    $item_id          The ID of the item.
	 *     @type string $item_type        The type of item.
	 * }
	 *
	 * @return object|WP_Error Topic object on success, WP_Error on failure.
	 */
	public function bb_update_topic( $args ) {
		$r = bp_parse_args(
			$args,
			array(
				'id'         => 0,
				'name'       => '',
				'slug'       => '',
				'item_type'  => 'activity',
				'item_id'    => 0,
				'error_type' => 'bool',
			)
		);

		$previous_topic_id  = $r['id'];
		$name               = $r['name'];
		$slug               = $r['slug'];
		$item_type          = $r['item_type'];
		$item_id            = $r['item_id'];
		$is_global_activity = isset( $r['is_global_activity'] ) ? $r['is_global_activity'] : false;
		$permission_type    = $r['permission_type'];

		// Check if the topic is global activity.
		$previous_topic = $this->bb_get_topic(
			array(
				'topic_id'           => $previous_topic_id,
				'is_global_activity' => true,
			)
		);
		if ( $previous_topic ) {
			$is_global_activity = $previous_topic->is_global_activity;
		}

		/**
		 * Fires before a topic has been updated.
		 *
		 * @param array  $r The arguments used to update the topic.
		 * @param int    $previous_topic_id The ID of the previous topic.
		 * @param object $previous_topic The previous topic object.
		 */
		do_action( 'bb_topic_before_updated', $r, $previous_topic_id, $previous_topic );

		// First check if new name exists in bb_topics table.
		$existing_topic = $this->bb_get_topic_by( 'slug', $slug );
		if ( ! $existing_topic ) { // NO.
			if ( $is_global_activity && 'group' === $item_type ) {
				wp_send_json_error(
					array(
						'error' => esc_html__( 'You cannot assign or update a global topic under a group.', 'buddyboss' ),
					)
				);
			}
			// Case: Name doesn't exist - create new topic.
			$topic_data = $this->bb_add_topic(
				array(
					'topic_id'        => $previous_topic_id,
					'name'            => $name,
					'slug'            => sanitize_title( $name ),
					'permission_type' => $permission_type,
					'item_id'         => $item_id,
					'item_type'       => $item_type,
				)
			);
		} else { // YES.
			// Case: Name already exists.
			// Fetch topic id from bb_topics table.
			$new_topic_id = (int) $existing_topic->id;
			$topic_data   = $this->bb_get_topic_by( 'id', $new_topic_id );

			// Check if topic id is different from current topic id.
			if ( $previous_topic_id !== $new_topic_id ) { // NO.
				// Check if relationship already exists.
				$existing_relationship = $this->bb_get_topic(
					array(
						'topic_id'  => $new_topic_id,
						'item_type' => $item_type,
						'item_id'   => $item_id,
					)
				);

				if ( $existing_relationship ) {
					// Error: Topic already exists with this relationship.
					wp_send_json_error(
						array(
							'error' => esc_html__( 'This topic name is already in use. Please enter a unique topic name.', 'buddyboss' ),
						)
					);
				} else {
					// Update relationships.
					$this->bb_update_topic_relationship(
						array(
							'topic_id' => $new_topic_id,
							'where'    => array(
								'topic_id'  => $previous_topic_id,
								'item_id'   => $item_id,
								'item_type' => $item_type,
							),
						)
					);

					if ( function_exists( 'bb_activity_topics_manager_instance' ) ) {
						bb_activity_topics_manager_instance()->bb_update_activity_topic_relationship(
							array(
								'topic_id'    => $new_topic_id,
								'previous_id' => $previous_topic_id,
							)
						);
					}
				}
			} else {
				// If just update permission_type for existing topic.
				$this->bb_update_topic_relationship(
					array(
						'item_id'         => $item_id,
						'item_type'       => $item_type,
						'permission_type' => $permission_type,
						'where'           => array(
							'topic_id'  => $new_topic_id,
							'item_id'   => $item_id,
							'item_type' => $item_type,
						),
					)
				);
			}
		}

		/**
		 * Fires after a topic has been updated.
		 *
		 * @param object $topic_data The topic data.
		 * @param array  $r The arguments used to update the topic.
		 * @param int    $previous_topic_id The ID of the previous topic.
		 * @param object $previous_topic The previous topic object.
		 */
		do_action( 'bb_topic_after_updated', $topic_data, $r, $previous_topic_id, $previous_topic );

		return $topic_data;
	}

	/**
	 * Add a new topic relationship.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @param array $args {
	 * Array of arguments for adding a topic relationship.
	 *
	 * @type int    $topic_id       The ID of the topic.
	 * @type string $permission_type The permission type.
	 * @type int    $item_id         The ID of the item.
	 * @type string $item_type       The type of item.
	 * @type int    $user_id         The ID of the user.
	 * @type array  $permission_data The permission data.
	 * @type int    $menu_order      The menu order.
	 * @type string $date_created    The date created.
	 * @type string $date_updated    The date updated.
	 * }
	 */
	public function bb_add_topic_relationship( $args ) {
		$r = bp_parse_args(
			$args,
			array(
				'topic_id'        => 0,
				'permission_type' => 'anyone',
				'item_id'         => 0,
				'item_type'       => 'activity',
				'user_id'         => bp_loggedin_user_id(),
				'permission_data' => null,
				'menu_order'      => 0,
				'date_created'    => current_time( 'mysql' ),
				'date_updated'    => current_time( 'mysql' ),
				'error_type'      => 'bool',
			)
		);

		$menu_order      = $this->wpdb->get_var( $this->wpdb->prepare( "SELECT MAX(menu_order) FROM {$this->topic_rel_table} WHERE item_type = %s AND item_id = %d", $r['item_type'], $r['item_id'] ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		$r['menu_order'] = $menu_order + 1;

		/**
		 * Fires before a topic relationship has been added.
		 *
		 * @since BuddyBoss [BBVERSION]
		 *
		 * @param array $args The arguments used to add the topic relationship.
		 */
		do_action( 'bb_topic_relationship_added', $r );

		$inserted = $this->wpdb->insert(
			$this->topic_rel_table,
			array(
				'topic_id'        => $r['topic_id'],
				'permission_type' => $r['permission_type'],
				'item_id'         => $r['item_id'],
				'item_type'       => $r['item_type'],
				'user_id'         => $r['user_id'],
				'permission_data' => $r['permission_data'],
				'menu_order'      => $r['menu_order'],
				'date_created'    => $r['date_created'],
				'date_updated'    => $r['date_updated'],
			),
			array( '%d', '%s', '%d', '%s', '%d', '%s', '%d', '%s', '%s' )
		);

		if ( ! $inserted ) {
			$error_message = esc_html__( 'Failed to add topic relationship.', 'buddyboss' );
			if ( 'wp_error' === $r['error_type'] ) {
				return new WP_Error( 'bb_topic_relationship_db_insert_error', esc_html( $error_message ) );
			}

			wp_send_json_error(
				array(
					'error' => esc_html( $error_message ),
				)
			);
		}

		/**
		 * Fires after a topic relationship has been added.
		 *
		 * @since BuddyBoss [BBVERSION]
		 *
		 * @param int   $inserted The number of rows inserted.
		 * @param array $args     The arguments used to add the topic relationship.
		 */
		do_action( 'bb_topic_relationship_added', $inserted, $r );
	}

	/**
	 * Update a topic relationship.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @param array $args {
	 *     Array of arguments for updating a topic relationship.
	 *     @type int    $topic_id         The topic ID. (required)
	 *     @type int    $item_id          The item ID. (optional, for more specific updates)
	 *     @type string $item_type        The item type. (optional)
	 *     @type string $permission_type  The new permission type. (optional)
	 *     @type array  $where            Additional WHERE conditions. (optional)
	 * }
	 * @return int|false Number of rows updated, or false on error.
	 */
	public function bb_update_topic_relationship( $args ) {
		$r = bp_parse_args(
			$args,
			array(
				'topic_id'        => 0,
				'item_id'         => null,
				'item_type'       => null,
				'permission_type' => null,
				'where'           => array(),
				'error_type'      => 'bool',
			)
		);

		/**
		 * Fires before a topic relationship has been updated.
		 *
		 * @since BuddyBoss [BBVERSION]
		 *
		 * @param array $args The arguments used to add the topic relationship.
		 */
		do_action( 'bb_topic_relationship_before_updated', $r );

		// Build data to update.
		$data = array();
		if ( ! empty( $r['permission_type'] ) ) {
			$data['permission_type'] = $r['permission_type'];
		}
		if ( ! empty( $r['item_id'] ) ) {
			$data['item_id'] = $r['item_id'];
		}
		if ( ! empty( $r['item_type'] ) ) {
			$data['item_type'] = $r['item_type'];
		}
		if ( ! empty( $r['topic_id'] ) ) {
			$data['topic_id'] = $r['topic_id'];
		}

		if ( empty( $data ) ) {
			return false;
		}

		$where = array();
		// Build where clause.
		if ( ! empty( $r['where'] ) && is_array( $r['where'] ) ) {
			$where = array_merge( $where, $r['where'] );
		}

		$updated = $this->wpdb->update(
			$this->topic_rel_table,
			$data,
			$where
		);

		if ( ! $updated ) {
			$error_message = esc_html__( 'Failed to update topic relationship.', 'buddyboss' );
			if ( 'wp_error' === $r['error_type'] ) {
				return new WP_Error( 'bb_topic_relationship_db_updated_error', esc_html( $error_message ) );
			}

			wp_send_json_error(
				array(
					'error' => esc_html( $error_message ),
				)
			);
		}

		/**
		 * Fires after a topic relationship has been updated.
		 *
		 * @since BuddyBoss [BBVERSION]
		 *
		 * @param int   $updated The number of rows updated.
		 * @param array $args The arguments used to add the topic relationship.
		 */
		do_action( 'bb_topic_relationship_after_updated', $updated, $r );
	}

	/**
	 * Get a single topic by field (id or slug).
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @param string $field The field to query by ('id' or 'slug').
	 * @param mixed  $value The value to search for.
	 *
	 * @return object|null Topic object on success, null on failure.
	 */
	public function bb_get_topic_by( $field, $value ) {

		if ( ! in_array( $field, array( 'id', 'slug', 'name' ), true ) ) {
			return null;
		}

		if ( 'id' === $field ) {
			$value     = absint( $value );
			$cache_key = 'bb_topic_id_' . $value;
			if ( ! $value ) {
				return false;
			}
		} elseif ( 'name' === $field ) {
			$cache_key = 'bb_topic_name_' . $value;
		} elseif ( 'slug' === $field ) {
			$value     = sanitize_title( $value );
			$cache_key = 'bb_topic_slug_' . $value;
			if ( empty( $value ) ) {
				return false;
			}
		}

		$topic = wp_cache_get( $cache_key, self::$topic_cache_group );
		if ( false !== $topic ) {
			return $topic;
		}

		$sql = $this->wpdb->prepare(
			"SELECT * FROM {$this->topics_table} WHERE {$field} = %s",
			$value
		);

		$topic = $this->wpdb->get_row( $sql ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared

		if ( $topic ) {
			wp_cache_set( $cache_key, $topic, self::$topic_cache_group );
		}

		return $topic;
	}

	/**
	 * Get multiple topics based on arguments.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @param array $args    {
	 *                       Array of query arguments.
	 *
	 * @type int    $number  Number of topics to retrieve. Default -1 (all).
	 * @type int    $offset  Number of topics to skip. Default 0.
	 * @type string $orderby Field to order by ('id', 'name', 'slug', 'menu_order', 'date_created'). Default 'menu_order'.
	 * @type string $order   Order direction ('ASC', 'DESC'). Default 'ASC'.
	 * @type string $search  Search term to match against name or slug.
	 * @type string $scope   Filter by topic_scope ('global').
	 * @type array  $include Array of topic IDs to include.
	 * @type array  $exclude Array of topic IDs to exclude.
	 *                       }
	 * @return array Array of topic objects.
	 */
	public function bb_get_topics( $args = array() ) {
		$r = bp_parse_args(
			$args,
			array(
				'topic_id'           => 0,
				'name'               => '',
				'slug'               => '',
				'per_page'           => -1, // Retrieve all by default.
				'paged'              => 1,
				'orderby'            => 'menu_order',
				'order'              => 'ASC',
				'search'             => '',
				'item_id'            => 0,
				'item_type'          => 'activity',
				'permission_type'    => '',
				'user_id'            => 0,
				'include'            => array(),
				'exclude'            => array(),
				'count_total'        => false,
				'fields'             => 'all', // Fields to include.
				'error_type'         => 'bool',
				'is_global_activity' => false,
			),
			'bb_get_topics'
		);

		// Select conditions.
		$select_sql = 'SELECT DISTINCT tr.id, tr.menu_order';

		// Add is_global_activity field if requested.
		$global_activity_sql = '';
		if ( $r['is_global_activity'] ) {
			$global_activity_sql = ', EXISTS (
				SELECT 1 
				FROM ' . $this->topic_rel_table . ' global_tr 
				WHERE global_tr.topic_id = tr.topic_id 
				AND global_tr.item_id = 0 
				AND global_tr.item_type = "activity"
				AND global_tr.id IS NOT NULL
			) as is_global_activity';
			$select_sql         .= $global_activity_sql;
		}

		$from_sql = ' FROM ' . $this->topic_rel_table . ' tr';

		$from_sql .= ' LEFT JOIN ' . $this->topics_table . ' t ON t.id = tr.topic_id';

		// Where conditions.
		$where_conditions = array();

		// Sorting.
		$sort = bp_esc_sql_order( $r['order'] );
		if ( 'ASC' !== $sort && 'DESC' !== $sort ) {
			$sort = 'DESC';
		}

		// Validate orderby parameter.
		$allowed_orderby = array( 'id', 'name', 'date_created', 'menu_order', 'date_updated' );
		if ( ! in_array( $r['orderby'], $allowed_orderby, true ) ) {
			$r['orderby'] = 'menu_order';
		}

		$order_by = 'tr.' . $r['orderby'];

		if ( ! empty( $r['item_id'] ) ) {
			$r['item_id']       = is_array( $r['item_id'] ) ? $r['item_id'] : array( $r['item_id'] );
			$item_ids           = array_map( 'absint', $r['item_id'] );
			$placeholders       = array_fill( 0, count( $item_ids ), '%d' );
			$where_conditions[] = $this->wpdb->prepare( 'tr.item_id IN (' . implode( ',', $placeholders ) . ')', $item_ids ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		}

		// topic_id.
		if ( ! empty( $r['topic_id'] ) ) {
			$where_conditions[] = $this->wpdb->prepare( 'tr.topic_id = %d', $r['topic_id'] );
		}

		// name.
		if ( ! empty( $r['name'] ) ) {
			$where_conditions[] = $this->wpdb->prepare( 't.name = %s', $r['name'] );
		}

		// slug.
		if ( ! empty( $r['slug'] ) ) {
			$where_conditions[] = $this->wpdb->prepare( 't.slug = %s', $r['slug'] );
		}

		// search.
		if ( ! empty( $r['search'] ) ) {
			$where_conditions[] = $this->wpdb->prepare( 't.name LIKE %s', '%' . $this->wpdb->esc_like( $r['search'] ) . '%' );
		}

		// item_type.
		if ( ! empty( $r['item_type'] ) ) {
			$r['item_type']     = is_array( $r['item_type'] ) ? $r['item_type'] : array( $r['item_type'] );
			$item_types         = array_map( 'sanitize_text_field', $r['item_type'] );
			$placeholders       = array_fill( 0, count( $item_types ), '%s' );
			$where_conditions[] = $this->wpdb->prepare( 'tr.item_type IN (' . implode( ',', $placeholders ) . ')', $item_types ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		}

		// permission_type.
		if ( ! empty( $r['permission_type'] ) ) {
			$r['permission_type'] = is_array( $r['permission_type'] ) ? $r['permission_type'] : array( $r['permission_type'] );
			$permission_types     = array_map( 'sanitize_text_field', $r['permission_type'] );
			$placeholders         = array_fill( 0, count( $permission_types ), '%s' );
			$where_conditions[]   = $this->wpdb->prepare( 'tr.permission_type IN (' . implode( ',', $placeholders ) . ')', $permission_types ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		}

		// user_id.
		if ( ! empty( $r['user_id'] ) ) {
			$where_conditions[] = $this->wpdb->prepare( 'tr.user_id = %d', $r['user_id'] );
		}

		// include.
		if ( ! empty( $r['include'] ) ) {
			$include_ids        = implode( ',', array_map( 'absint', $r['include'] ) );
			$where_conditions[] = $this->wpdb->prepare( 'tr.topic_id IN ( %s )', $include_ids );
		}

		// exclude.
		if ( ! empty( $r['exclude'] ) ) {
			$exclude_ids        = implode( ',', array_map( 'absint', $r['exclude'] ) );
			$where_conditions[] = $this->wpdb->prepare( 'tr.topic_id NOT IN ( %s )', $exclude_ids );
		}

		/**
		 * Filters the MySQL WHERE conditions for the activity topics get sql method.
		 *
		 * @since BuddyBoss [BBVERSION]
		 *
		 * @param array  $where_conditions Current conditions for MySQL WHERE statement.
		 * @param array  $r                Parsed arguments passed into method.
		 * @param string $select_sql       Current SELECT MySQL statement at the point of execution.
		 * @param string $from_sql         Current FROM MySQL statement at point of execution.
		 */
		$where_conditions = apply_filters( 'bb_get_topics_where_conditions', $where_conditions, $r, $select_sql, $from_sql );

		// Join the where conditions together.
		$where_sql = '';
		if ( ! empty( $where_conditions ) ) {
			$where_sql = 'WHERE ' . join( ' AND ', $where_conditions );
		}

		// Sanitize page and per_page parameters.
		$page       = absint( $r['paged'] );
		$per_page   = $r['per_page'];
		$pagination = '';
		if ( ! empty( $per_page ) && ! empty( $page ) && - 1 !== $per_page ) {
			$start_val = intval( ( $page - 1 ) * $per_page );
			if ( ! empty( $where_conditions['before'] ) ) {
				$start_val = 0;
				unset( $where_conditions['before'] );
			}
			$pagination = $this->wpdb->prepare( 'LIMIT %d, %d', $start_val, intval( $per_page ) );
		}

		// Query first for poll vote IDs.
		$topic_sql = "{$select_sql} {$from_sql} {$where_sql} ORDER BY {$order_by} {$sort} {$pagination}";

		$retval = array(
			'topics' => null,
			'total'  => null,
		);

		/**
		 * Filters the poll votes data MySQL statement.
		 *
		 * @since 2.6.00
		 *
		 * @param string $poll_votes_sql MySQL's statement used to query for poll votes.
		 * @param array  $r              Array of arguments passed into method.
		 */
		$topic_sql = apply_filters( 'bb_get_topics_sql', $topic_sql, $r );

		// Create a unique cache key based on the query parameters.
		$cache_key = 'bb_topics_query_' . md5( maybe_serialize( $r ) );

		// Get cached results.
		$cached = bp_core_get_incremented_cache( $cache_key, self::$topic_cache_group );
		if ( false === $cached ) {
			$topic_ids = $this->wpdb->get_col( $topic_sql ); // phpcs:ignore
			bp_core_set_incremented_cache( $cache_key, self::$topic_cache_group, $topic_ids );
		} else {
			$topic_ids = $cached;
		}

		if ( 'id' === $r['fields'] ) {
			// We only want the IDs.
			$topic_data = array_map( 'intval', $topic_ids );
		} else {
			$uncached_ids = bp_get_non_cached_ids( $topic_ids, self::$topic_cache_group );
			if ( ! empty( $uncached_ids ) ) {
				$uncached_ids_sql = implode( ',', wp_parse_id_list( $uncached_ids ) );

				$queried_data = $this->wpdb->get_results( 'SELECT t.*, tr.*' . $global_activity_sql . ' FROM ' . $this->topics_table . ' t LEFT JOIN ' . $this->topic_rel_table . ' tr ON t.id = tr.topic_id WHERE tr.id IN (' . $uncached_ids_sql . ')', ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
				foreach ( (array) $queried_data as $topic_data ) {
					if ( ! empty( $topic_data['id'] ) ) {
						// Create a unique cache key for each topic relationship.
						$relationship_cache_key = 'bb_topic_relationship_' . $topic_data['id'] . '_' . $topic_data['item_id'] . '_' . $topic_data['item_type'];
						wp_cache_set( $relationship_cache_key, $topic_data, self::$topic_cache_group );
					}
				}
			}

			$topic_data = array();
			foreach ( $topic_ids as $id ) {
				// Get the cached topic relationship using the unique key.
				$item_id                = is_array( $r['item_id'] ) ? implode( '_', $r['item_id'] ) : $r['item_id'];
				$item_type              = is_array( $r['item_type'] ) ? implode( '_', $r['item_type'] ) : $r['item_type'];
				$relationship_cache_key = 'bb_topic_relationship_' . $id . '_' . $item_id . '_' . $item_type;
				$topic                  = wp_cache_get( $relationship_cache_key, self::$topic_cache_group );

				if ( ! empty( $topic ) ) {
					$topic['is_global_activity'] = ! empty( $topic['is_global_activity'] ) ? (bool) $topic['is_global_activity'] : false;
					$topic_data[]                = (object) $topic;
				}
			}

			if ( 'all' !== $r['fields'] ) {
				$topic_data = array_unique( array_column( $topic_data, $r['fields'] ) );
			}
		}

		$retval['topics'] = $topic_data;

		if ( ! empty( $r['count_total'] ) ) {

			/**
			 * Filters the total activity topics MySQL statement.
			 *
			 * @since BuddyBoss [BBVERSION]
			 *
			 * @param string $value     MySQL's statement used to query for total activity topics.
			 * @param string $where_sql MySQL WHERE statement portion.
			 */
			$total_activity_topic_sql = apply_filters(
				'bb_total_topic_count_sql',
				'SELECT count(DISTINCT t.id)' . $global_activity_sql . ' FROM ' . $this->topic_rel_table . ' tr
				 LEFT JOIN ' . $this->topics_table . ' t ON t.id = tr.topic_id
				 ' . $where_sql,
				$where_sql
			);

			$total_cache_key = 'bb_topics_count_' . md5( maybe_serialize( $r ) );
			$cached          = bp_core_get_incremented_cache( $total_cache_key, self::$topic_cache_group );
			if ( false === $cached ) {
				$total_activity_topics = $this->wpdb->get_var( $total_activity_topic_sql ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
				bp_core_set_incremented_cache( $total_cache_key, self::$topic_cache_group, $total_activity_topics );
			} else {
				$total_activity_topics = $cached;
			}

			$retval['total'] = $total_activity_topics;
		}

		unset( $r, $select_sql, $from_sql, $where_conditions, $where_sql, $pagination, $topic_sql, $cached, $topic_ids, $uncached_ids, $uncached_ids_sql, $queried_data, $topic_data );

		return $retval;
	}

	/**
	 * Fetch an existing topic while edit topic modal is open via AJAX.
	 *
	 * @since BuddyBoss [BBVERSION]
	 */
	public function bb_edit_topic_ajax() {
		if ( ! isset( $_POST['topic_id'] ) ) {
			wp_send_json_error(
				array(
					'error' => __( 'Topic ID is required.', 'buddyboss' ),
					'topic' => array(),
				)
			);
		}

		check_ajax_referer( 'bb_edit_topic', 'nonce' );

		$topic_id  = absint( sanitize_text_field( wp_unslash( $_POST['topic_id'] ) ) );
		$item_id   = isset( $_POST['item_id'] ) ? absint( sanitize_text_field( wp_unslash( $_POST['item_id'] ) ) ) : 0;
		$item_type = isset( $_POST['item_type'] ) ? sanitize_text_field( wp_unslash( $_POST['item_type'] ) ) : '';

		$args = array(
			'topic_id'  => $topic_id,
			'item_id'   => $item_id,
			'item_type' => $item_type,
		);

		if ( 'group' === $item_type ) {
			$args['is_global_activity'] = true;
		}

		$topic = $this->bb_get_topic( $args );

		if ( ! $topic ) {
			wp_send_json_error( array( 'error' => __( 'Topic not found.', 'buddyboss' ) ) );
		}

		wp_send_json_success(
			array(
				'topic' => $topic,
			)
		);
	}

	/**
	 * Get a single topic by ID.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @param array $args {
	 *     Array of arguments.
	 *     @type int $id The ID of the topic to get.
	 * }
	 *
	 * @return object The topic data.
	 */
	public function bb_get_topic( $args ) {
		$r = bp_parse_args(
			$args,
			array(
				'topic_id' => 0,
			)
		);

		$topic = $this->bb_get_topics( $r );

		return is_array( $topic ) && ! empty( $topic['topics'] ) ? current( $topic['topics'] ) : null;
	}

	/**
	 * Delete an existing topic via AJAX.
	 *
	 * @since BuddyBoss [BBVERSION]
	 */
	public function bb_delete_topic_ajax() {
		if ( ! isset( $_POST['topic_id'] ) ) {
			wp_send_json_error(
				array(
					'error' => __( 'Topic ID is required.', 'buddyboss' ),
					'topic' => array(),
				)
			);
		}

		check_ajax_referer( 'bb_delete_topic', 'nonce' );

		$topic_id = absint( sanitize_text_field( wp_unslash( $_POST['topic_id'] ) ) );

		$deleted = $this->bb_delete_topic( $topic_id );

		if ( is_wp_error( $deleted ) ) {
			wp_send_json_error( array( 'error' => $deleted->get_error_message() ) );
		}

		wp_send_json_success();
	}

	/**
	 * Delete a topic and its associated relationships.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @param int $topic_id The ID of the topic to delete.
	 *
	 * @return bool|WP_Error True on success, WP_Error on failure.
	 */
	public function bb_delete_topic( $topic_id ) {

		if ( empty( $topic_id ) ) {
			return false;
		}

		$topic_id = absint( $topic_id );
		if ( ! $this->bb_get_topic_by( 'id', $topic_id ) ) {
			return false;
		}

		$get_all_topic_relationships = $this->bb_get_topics(
			array(
				'topic_id'  => $topic_id,
				'item_type' => array( 'activity', 'group' ),
				'fields'    => 'id',
			)
		);

		$relationships_ids = ! empty( $get_all_topic_relationships['topics'] ) ? $get_all_topic_relationships['topics'] : array();

		/**
		 * Fires before a topic is deleted.
		 *
		 * @since BuddyBoss [BBVERSION]
		 *
		 * @param int $topic_id The ID of the topic being deleted.
		 */
		do_action( 'bb_topic_relationship_before_delete', $topic_id );

		// 1. Delete activity relationships.
		if ( ! empty( $relationships_ids ) ) {
			$placeholders = array_fill( 0, count( $relationships_ids ), '%d' );
			$sql          = 'DELETE FROM ' . $this->topic_rel_table . ' WHERE id IN (' . implode( ',', $placeholders ) . ')';
			$deleted_rels = $this->wpdb->query( $this->wpdb->prepare( $sql, $relationships_ids ) ); // phpcs:ignore
			if ( false === $deleted_rels ) {
				return false;
			}

			if ( function_exists( 'bb_activity_topics_manager_instance' ) ) {
				bb_activity_topics_manager_instance()->bb_delete_activity_topic_relationship(
					array(
						'topic_id' => $topic_id,
					)
				);
			}
		}

		/**
		 * Fires after a topic relationship has been deleted.
		 *
		 * @since BuddyBoss [BBVERSION]
		 *
		 * @param array $relationships_ids The IDs of the topic relationships that were deleted.
		 * @param int   $topic_id          The ID of the topic that was deleted.
		 */
		do_action( 'bb_topic_relationship_after_deleted', $relationships_ids, $topic_id );

		/**
		 * Fires after a topic has been deleted.
		 *
		 * @since BuddyBoss [BBVERSION]
		 *
		 * @param array $relationships_ids The IDs of the topic relationships that were deleted.
		 * @param int   $topic_id          The ID of the topic that was deleted.
		 */
		do_action( 'bb_topic_deleted', $relationships_ids, $topic_id );

		unset( $topic_id, $deleted_rels, $deleted_topic, $get_all_topic_relationships, $relationships_ids );

		return true;
	}

	/**
	 * Check if the maximum number of topics has been reached.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @param array $r Array of arguments.
	 *
	 * @return bool True if the maximum number of topics has been reached, false otherwise.
	 */
	public function bb_topics_limit_reached( $r ) {
		$topics_count = $this->bb_get_topics(
			array(
				'per_page'    => 1,
				'item_type'   => $r['item_type'],
				'item_id'     => $r['item_id'],
				'count_total' => true,
			)
		);
		return is_array( $topics_count ) && isset( $topics_count['total'] ) ? $topics_count['total'] >= $this->bb_topics_limit() : false;
	}

	/**
	 * Limit the number of topics.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @return int The maximum number of topics.
	 */
	public function bb_topics_limit() {
		return 20;
	}

	/**
	 * Add or update an activity-topic relationship.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @param array $args {
	 *     Array of arguments for adding/updating an activity-topic relationship.
	 *
	 *     @type int    $topic_id    The ID of the topic.
	 *     @type int    $activity_id The ID of the activity.
	 *     @type string $component   The component name (e.g., 'activity', 'groups').
	 *     @type int    $item_id     The ID of the item (e.g., group ID if component is 'groups').
	 * }
	 *
	 * @return int|WP_Error The ID of the relationship or WP_Error on failure.
	 */
	public function bb_add_activity_topic_relationship( $args ) {
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
		if ( empty( $r['topic_id'] ) || empty( $r['activity_id'] ) ) {
			return new WP_Error( 'bb_activity_topic_relationship_missing_data', __( 'Topic ID and Activity ID are required.', 'buddyboss' ) );
		}

		/**
		 * Fires before an activity-topic relationship is added or updated.
		 *
		 * @since BuddyBoss [BBVERSION]
		 *
		 * @param array $r The arguments used to add/update the relationship.
		 */
		do_action( 'bb_activity_topic_relationship_before_add', $r );

		// Check if activity already has a topic assigned.
		$existing = $this->wpdb->get_row( $this->wpdb->prepare( "SELECT id, topic_id FROM {$this->activity_topic_rel_table} WHERE activity_id = %d", $r['activity_id'] ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared

		if ( $existing ) {
			// If the same topic is already assigned, return the existing relationship ID.
			if ( $existing->topic_id === $r['topic_id'] ) {
				return $existing->id;
			}

			// Update the existing relationship with the new topic.
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

			if ( false === $updated ) {
				$error_message = __( 'Failed to update activity-topic relationship.', 'buddyboss' );
				if ( 'wp_error' === $r['error_type'] ) {
					return new WP_Error( 'bb_activity_topic_relationship_db_update_error', $error_message );
				}

				wp_send_json_error( array( 'error' => $error_message ) );
			}

			/**
			 * Fires after an activity-topic relationship is updated.
			 *
			 * @since BuddyBoss [BBVERSION]
			 *
			 * @param int   $existing->id The ID of the updated relationship.
			 * @param array $r            The arguments used to update the relationship.
			 */
			do_action( 'bb_activity_topic_relationship_after_update', $existing->id, $r );

			return $existing->id;
		}

		// Insert new relationship.
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

		if ( ! $inserted ) {
			$error_message = __( 'Failed to add activity-topic relationship.', 'buddyboss' );
			if ( 'wp_error' === $r['error_type'] ) {
				return new WP_Error( 'bb_activity_topic_relationship_db_insert_error', $error_message );
			}

			wp_send_json_error( array( 'error' => $error_message ) );
		}

		$relationship_id = $this->wpdb->insert_id;

		/**
		 * Fires after an activity-topic relationship is added.
		 *
		 * @since BuddyBoss [BBVERSION]
		 *
		 * @param int   $relationship_id The ID of the inserted relationship.
		 * @param array $r               The arguments used to add the relationship.
		 */
		do_action( 'bb_activity_topic_relationship_after_add', $relationship_id, $r );

		return $relationship_id;
	}

	/**
	 * Get the activity topic information.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @param int    $activity_id The ID of the activity.
	 * @param string $return_type The type of data to return ('name' or 'id').
	 *
	 * @return string|int Topic name, ID, or empty string/0 if not found.
	 */
	public function bb_get_activity_topic( $activity_id, $return_type = 'id' ) {
		// Validate activity ID.
		$activity_id = absint( $activity_id );
		if ( empty( $activity_id ) ) {
			return 'name' === $return_type ? '' : 0;
		}

		$cache_key    = "bb_activity_topic_{$return_type}_{$activity_id}";
		$cached_value = wp_cache_get( $cache_key, self::$topic_cache_group );

		if ( false !== $cached_value ) {
			return $cached_value;
		}

		$bp_prefix     = bp_core_get_table_prefix();
		$column        = 'name' === $return_type ? 't.name' : 't.id';
		$default_value = 'name' === $return_type ? '' : 0;

		$result = $this->wpdb->get_var(
			$this->wpdb->prepare( "SELECT {$column} FROM {$bp_prefix}bb_activity_topic_relationship atr INNER JOIN {$bp_prefix}bb_topics t ON t.id = atr.topic_id WHERE atr.activity_id = %d LIMIT 1", $activity_id ) // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		);

		$return_value = null !== $result ? $result : $default_value;

		// Cache the result.
		wp_cache_set( $cache_key, $return_value, self::$topic_cache_group );

		return $return_value;
	}

	/**
	 * Update the order of topics.
	 *
	 * @since BuddyBoss [BBVERSION]
	 */
	public function bb_update_topics_order_ajax() {
		if ( ! isset( $_POST['topic_ids'] ) || ! is_array( $_POST['topic_ids'] ) ) {
			wp_send_json_error(
				array(
					'error' => __( 'No topic order provided.', 'buddyboss' ),
					'topic' => array(),
				)
			);
		}

		check_ajax_referer( 'bb_update_topics_order', 'nonce' );

		$topic_ids = array_map( 'absint', $_POST['topic_ids'] );

		$success = true;

		// Update each topic with its new order in the relationships table.
		foreach ( $topic_ids as $position => $topic_id ) {
			$result = $this->wpdb->update(
				$this->topic_rel_table,
				array( 'menu_order' => $position + 1 ),
				array( 'topic_id' => $topic_id ),
				array( '%d' ),
				array( '%d' )
			);

			if ( false === $result ) {
				$success = false;
				break;
			}
		}

		if ( $success ) {
			wp_send_json_success( array( 'message' => __( 'Topic order updated successfully.', 'buddyboss' ) ) );
		} else {
			wp_send_json_error( array( 'error' => __( 'Failed to update topic order.', 'buddyboss' ) ) );
		}
	}

	/**
	 * Get the permission type for a topic.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @param int $topic_id The ID of the topic.
	 *
	 * @return string The permission type for the topic.
	 */
	public function bb_get_topic_permission_type( $topic_id ) {
		$topic_permission_type = $this->wpdb->get_var( $this->wpdb->prepare( "SELECT permission_type FROM {$this->topic_rel_table} WHERE topic_id = %d", $topic_id ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared

		if ( ! $topic_permission_type ) {
			return 'anyone';
		}

		return $topic_permission_type;
	}
}
