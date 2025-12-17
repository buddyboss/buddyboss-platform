<?php
/**
 * BuddyBoss Platform Topics Manager.
 *
 * Handles database schema creation and CRUD operations for topics.
 *
 * @since   BuddyBoss 2.8.80
 * @package BuddyBoss\Activity
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Manages Topics data storage and operations.
 *
 * @since BuddyBoss 2.8.80
 */
class BB_Topics_Manager {

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
	 * Table name for Topics.
	 *
	 * @since BuddyBoss 2.8.80
	 *
	 * @var string
	 */
	public $topics_table;

	/**
	 * Table name for Topic Relationships.
	 *
	 * @since BuddyBoss 2.8.80
	 *
	 * @var string
	 */
	public $topic_rel_table;

	/**
	 * Table name for Activity Topic Relationships.
	 *
	 * @since BuddyBoss 2.8.80
	 *
	 * @var string
	 */
	public $activity_topic_rel_table;

	/**
	 * Table name for Topic History.
	 *
	 * @since BuddyBoss 2.8.80
	 *
	 * @var string
	 */
	public $topic_history_table;

	/**
	 * Cache group for topics.
	 *
	 * @since BuddyBoss 2.8.80
	 *
	 * @var string
	 */
	public static $topic_cache_group = 'bb_topics';

	/**
	 * WordPress Database instance.
	 *
	 * @since BuddyBoss 2.8.80
	 *
	 * @var object
	 */
	private $wpdb;

	/**
	 * Get the singleton instance of this class.
	 *
	 * @since BuddyBoss 2.8.80
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
	 * @since BuddyBoss 2.8.80
	 */
	public function __construct() {
		global $wpdb;
		$this->wpdb = $wpdb;
		$prefix     = bp_core_get_table_prefix();

		$this->topics_table             = $prefix . 'bb_topics';
		$this->topic_rel_table          = $prefix . 'bb_topic_relationships';
		$this->activity_topic_rel_table = $prefix . 'bb_activity_topic_relationship';
		$this->topic_history_table      = $prefix . 'bb_topic_history';

		$this->setup_hooks();
	}

	/**
	 * Set up hooks for logging actions.
	 *
	 * @since BuddyBoss 2.8.80
	 */
	private function setup_hooks() {
		add_action( 'bp_admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
		add_action( 'bp_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
		add_action( 'wp_ajax_bb_add_topic', array( $this, 'bb_add_topic_ajax' ) );
		add_action( 'wp_ajax_bb_edit_topic', array( $this, 'bb_edit_topic_ajax' ) );
		add_action( 'wp_ajax_bb_delete_topic', array( $this, 'bb_delete_topic_ajax' ) );
		add_action( 'wp_ajax_bb_update_topics_order', array( $this, 'bb_update_topics_order_ajax' ) );
		add_action( 'bp_template_redirect', array( $this, 'bb_handle_topic_redirects' ) );
		add_action( 'wp_ajax_bb_migrate_topic', array( $this, 'bb_migrate_topic_ajax' ) );
	}

	/**
	 * Enqueue scripts.
	 *
	 * @since BuddyBoss 2.8.80
	 *
	 * @param string $hook_suffix The current admin page.
	 */
	public function enqueue_scripts( $hook_suffix ) {
		if (
			is_admin() &&
			(
				false === strpos( $hook_suffix, 'bp-groups' ) &&
				false === strpos( $hook_suffix, 'bp-settings' )
			)
		) {
			return;
		}

		if (
			! is_admin() &&
			$this->bb_load_topics_scripts()
		) {
			return;
		}

		$bp  = buddypress();
		$min = bp_core_get_minified_asset_suffix();
		wp_enqueue_script(
			'bb-topics-manager',
			$bp->plugin_url . "bp-core/js/bb-topics-manager{$min}.js",
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
				'create_topic_text'            => __( 'Create Topic', 'buddyboss' ),
				'edit_topic_text'              => __( 'Edit Topic', 'buddyboss' ),
				/* translators: %s: Topic name */
				'delete_topic_text'            => esc_html__( 'Deleting "%s"?', 'buddyboss' ),
			)
		);
		wp_localize_script(
			'bb-topics-manager',
			'bbTopicsManagerVars',
			$bb_topics_js_strings
		);

		// Add the JS templates for topics.
		bp_get_template_part( 'common/js-templates/bb-topic-lists' );
	}

	/**
	 * Create the necessary database tables.
	 *
	 * @since BuddyBoss 2.8.80
	 */
	public function create_tables() {
		$sql             = array();
		$wpdb            = $GLOBALS['wpdb'];
		$charset_collate = $wpdb->get_charset_collate();

		$topics_table             = $this->topics_table;
		$topic_rel_table          = $this->topic_rel_table;
		$activity_topic_rel_table = $this->activity_topic_rel_table;
		$topic_history_table      = $this->topic_history_table;

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
				KEY date_updated (date_updated),
				KEY idx_relationship_type_perm_item ( item_type, permission_type, item_id, topic_id )
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

		$has_topic_history_table = $wpdb->query( $wpdb->prepare( 'show tables like %s', $topic_history_table ) ); //phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		if ( empty( $has_topic_history_table ) ) {
			$sql[] = "CREATE TABLE {$topic_history_table} (
				id BIGINT(20) UNSIGNED AUTO_INCREMENT,
				item_id BIGINT(20) UNSIGNED NOT NULL,
				item_type VARCHAR(10) NOT NULL,
				old_topic_id BIGINT(20) UNSIGNED NOT NULL,
				old_topic_slug VARCHAR(255) NOT NULL,
				new_topic_id BIGINT(20) UNSIGNED NOT NULL,
				new_topic_slug VARCHAR(255) NOT NULL,
				action VARCHAR(20) NOT NULL,
				date_created DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
				PRIMARY KEY (id),
				KEY item_id (item_id),
				KEY item_type (item_type),
				KEY old_topic_id (old_topic_id),
				KEY old_topic_slug (old_topic_slug),
				KEY new_topic_id (new_topic_id),
				KEY new_topic_slug (new_topic_slug),
				KEY action (action),
				KEY date_created (date_created)
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
	 * @since BuddyBoss 2.8.80
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

		$name              = sanitize_text_field( wp_unslash( $_POST['name'] ) );
		$slug              = isset( $_POST['slug'] ) ? sanitize_title( wp_unslash( $_POST['slug'] ) ) : '';
		$permission_type   = isset( $_POST['permission_type'] ) ? sanitize_text_field( wp_unslash( $_POST['permission_type'] ) ) : 'anyone';
		$previous_topic_id = isset( $_POST['topic_id'] ) ? absint( wp_unslash( $_POST['topic_id'] ) ) : 0;
		$item_id           = isset( $_POST['item_id'] ) ? absint( wp_unslash( $_POST['item_id'] ) ) : 0;
		$item_type         = isset( $_POST['item_type'] ) ? sanitize_text_field( wp_unslash( $_POST['item_type'] ) ) : 'activity';

		if ( empty( $slug ) ) {
			$slug = sanitize_title( $name );
		} else {
			$slug = sanitize_title( $slug );
		}
		if ( empty( $slug ) ) {
			wp_send_json_error(
				array(
					'error' => __( 'Please enter a valid topic name.', 'buddyboss' ),
					'topic' => array(),
				)
			);
		}

		if ( empty( $previous_topic_id ) ) {
			$topic_data = $this->bb_add_topic(
				array(
					'name'            => $name,
					'slug'            => $slug,
					'permission_type' => $permission_type,
					'item_id'         => $item_id,
					'item_type'       => $item_type,
					'error_type'      => 'wp_error',
				)
			);
		} else {
			$topic_data = $this->bb_update_topic(
				array(
					'id'              => $previous_topic_id,
					'name'            => $name,
					'slug'            => $slug,
					'permission_type' => $permission_type,
					'item_type'       => $item_type,
					'item_id'         => $item_id,
					'error_type'      => 'wp_error',
				)
			);
		}

		if ( is_wp_error( $topic_data ) ) {
			wp_send_json_error( array( 'error' => $topic_data->get_error_message() ) );
		}

		// Add topic history when topic is updated.
		if ( ! empty( $previous_topic_id ) && ! empty( $topic_data ) ) {
			$previous_topic_data = $this->bb_get_topic_by( 'id', $previous_topic_id );
			$previous_topic_slug = ! empty( $previous_topic_data ) ? $previous_topic_data->slug : '';

			if ( $previous_topic_slug !== $topic_data->slug ) {
				$this->bb_add_topic_history(
					array(
						'item_id'        => $item_id,
						'item_type'      => $item_type,
						'old_topic_id'   => $previous_topic_id,
						'old_topic_slug' => $previous_topic_slug,
						'new_topic_id'   => $topic_data->topic_id,
						'new_topic_slug' => $topic_data->slug,
						'action'         => 'rename',
					)
				);
			}
		}

		if ( 'activity' === $item_type ) {
			$permission_type = function_exists( 'bb_activity_topics_manager_instance' ) ? bb_activity_topics_manager_instance()->bb_activity_topic_permission_type( $permission_type ) : array();
		} else {
			$permission_type = function_exists( 'bb_group_activity_topic_permission_type' ) ? bb_group_activity_topic_permission_type( $permission_type ) : array();
		}
		if ( ! empty( $permission_type ) && is_object( $topic_data ) ) {
			$permission_type_value       = current( $permission_type );
			$topic_data->permission_type = $permission_type_value;
		}

		wp_send_json_success(
			array(
				'content' => array(
					'topic'        => $topic_data,
					'edit_nonce'   => wp_create_nonce( 'bb_edit_topic' ),
					'delete_nonce' => wp_create_nonce( 'bb_delete_topic' ),
				),
			)
		);
	}

	/**
	 * Add a new global topic.
	 *
	 * @since BuddyBoss 2.8.80
	 *
	 * @param array $args {
	 * Array of arguments for adding a topic.
	 *
	 * @type string $name Required. The name of the topic.
	 * @type string $slug Optional. The slug for the topic. Auto-generated if empty.
	 * }
	 *
	 * @return bool|WP_Error|object Topic ID on success, WP_Error on failure.
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
			),
			'bb_add_topic'
		);

		if ( empty( $r['name'] ) ) {
			$error_message = __( 'Topic name is required.', 'buddyboss' );
			if ( 'wp_error' === $r['error_type'] ) {
				unset( $r );

				return new WP_Error( 'bb_topic_name_required', $error_message );
			}

			unset( $r );

			return false;
		}

		/**
		 * Fires before a topic has been added.
		 *
		 * @since BuddyBoss 2.8.80
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

			return false;
		}

		// Check if we've reached the maximum number of topics (20).
		if ( $this->bb_topics_limit_reached( $r ) ) {
			$error_message = esc_html__( 'Maximum number of topics (20) has been reached.', 'buddyboss' );
			if ( 'wp_error' === $r['error_type'] ) {
				unset( $r );

				return new WP_Error( 'bb_topic_limit_reached', esc_html( $error_message ) );
			}

			unset( $r );

			return false;
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

			if ( is_wp_error( $inserted ) ) {
				if ( 'wp_error' === $r['error_type'] ) {
					unset( $r );

					return new WP_Error( 'bb_topic_db_insert_error', $this->wpdb->last_error );
				}

				unset( $r );

				return false;
			}

			$topic_id = $this->wpdb->insert_id;
		} else {
			$topic_id = $existing_slug->id;
		}

		if ( empty( $r['topic_id'] ) && ! empty( $topic_id ) ) {
			$this->bb_add_topic_relationship(
				array(
					'topic_id'        => $topic_id,
					'permission_type' => isset( $r['permission_type'] ) ? $r['permission_type'] : 'anyone',
					'item_id'         => $r['item_id'],
					'item_type'       => $r['item_type'],
				)
			);
		} else {
			$this->bb_update_topic_relationship(
				array(
					'topic_id'        => $topic_id,
					'permission_type' => isset( $r['permission_type'] ) ? $r['permission_type'] : 'anyone',
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
						'component'   => $r['item_type'],
						'item_id'     => $r['item_id'],
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

		if ( empty( $get_topic_relationship ) ) {
			return new WP_Error(
				'bb_topic_relationship_not_found',
				esc_html__( 'Topic relationship not found.', 'buddyboss' )
			);
		}

		/**
		 * Fires after a topic has been added.
		 *
		 * @since BuddyBoss 2.8.80
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
	 * @since BuddyBoss 2.8.80
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
	 * @return object|bool|WP_Error Topic object on success, WP_Error on failure.
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
			),
			'bb_update_topic'
		);

		$previous_topic_id = $r['id'];
		$name              = $r['name'];
		$slug              = $r['slug'];
		$item_type         = $r['item_type'];
		$item_id           = $r['item_id'];
		$permission_type   = $r['permission_type'];

		// Check if the topic is a global activity.
		$previous_topic     = $this->bb_get_topic(
			array(
				'topic_id' => $previous_topic_id,
			)
		);
		$is_global_activity = $previous_topic && isset( $previous_topic->is_global_activity ) ? $previous_topic->is_global_activity : false;

		/**
		 * Fires before a topic has been updated.
		 *
		 * @since BuddyBoss 2.8.80
		 *
		 * @param array  $r The arguments used to update the topic.
		 * @param int    $previous_topic_id The ID of the previous topic.
		 * @param object $previous_topic The previous topic object.
		 */
		do_action( 'bb_topic_before_updated', $r, $previous_topic_id, $previous_topic );

		// First, check if the new name exists in the bb_topics table.
		$existing_topic = $this->bb_get_topic_by( 'slug', $slug );
		if ( ! $existing_topic ) { // NO.
			if ( $is_global_activity && 'groups' === $item_type ) {
				if ( 'wp_error' === $r['error_type'] ) {
					unset( $r, $existing_topic, $previous_topic, $is_global_activity, $item_type, $item_id, $permission_type );

					return new WP_Error(
						'bb_topic_global_activity_group',
						esc_html__( 'You cannot assign or update a global topic under a group.', 'buddyboss' )
					);
				}

				unset( $r );

				return false;
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
			// Fetch topic id from the bb_topics table.
			$new_topic_id = (int) $existing_topic->id;

			// Check if topic ID is different from the current topic ID.
			if ( $previous_topic_id !== $new_topic_id ) { // NO.
				// Check if a relationship already exists.
				$existing_relationship = $this->bb_get_topic(
					array(
						'topic_id'  => $new_topic_id,
						'item_type' => $item_type,
						'item_id'   => $item_id,
					)
				);

				if ( $existing_relationship ) {
					// Error: Topic already exists with this relationship.
					if ( 'wp_error' === $r['error_type'] ) {
						unset( $r, $existing_topic, $previous_topic, $is_global_activity, $item_type, $item_id, $permission_type );

						return new WP_Error(
							'bb_topic_duplicate_slug',
							esc_html__( 'This topic name is already in use. Please enter a unique topic name.', 'buddyboss' )
						);
					}

					unset( $r );

					return false;
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
						$get_previous_activity_relationship = bb_activity_topics_manager_instance()->bb_get_activity_topic_relationship(
							array(
								'topic_id'  => $previous_topic_id,
								'item_id'   => $item_id,
								'component' => $item_type,
								'fields'    => 'activity_id',
							)
						);
						if ( ! empty( $get_previous_activity_relationship ) ) {
							bb_activity_topics_manager_instance()->bb_update_activity_topic_relationship(
								array(
									'topic_id'    => $new_topic_id,
									'previous_id' => $previous_topic_id,
									'component'   => $item_type,
									'item_id'     => $item_id,
								)
							);
						}
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

			$topic_data = $this->bb_get_topic(
				array(
					'topic_id'  => $new_topic_id,
					'item_id'   => $item_id,
					'item_type' => $item_type,
				)
			);

			if ( empty( $topic_data ) ) {
				return new WP_Error(
					'bb_topic_relationship_not_found',
					esc_html__( 'Topic relationship not found.', 'buddyboss' )
				);
			}
		}

		/**
		 * Fires after a topic has been updated.
		 *
		 * @since BuddyBoss 2.8.80
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
	 * @since BuddyBoss 2.8.80
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
	 *
	 * @return int|bool|WP_Error The number of rows inserted on success, false on failure, WP_Error on error.
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
				'date_created'    => bp_core_current_time(),
				'date_updated'    => bp_core_current_time(),
				'error_type'      => 'bool',
			),
			'bb_add_topic_relationship'
		);

		if ( empty( $r['topic_id'] ) ) {
			return new WP_Error(
				'bb_topic_relationship_missing_topic_id',
				esc_html__( 'Topic ID is required.', 'buddyboss' )
			);
		}

		if ( empty( $r['item_type'] ) ) {
			return new WP_Error(
				'bb_topic_relationship_missing_item_type',
				esc_html__( 'Item type is required.', 'buddyboss' )
			);
		}

		$valid_permission_types = array();
		if ( 'groups' === $r['item_type'] ) {
			$valid_permission_types = array( 'admins', 'mods', 'members' );
		} elseif ( 'activity' === $r['item_type'] ) {
			$valid_permission_types = array( 'mods_admins', 'anyone' );
		} else {
			return new WP_Error(
				'bb_topic_relationship_invalid_item_type',
				esc_html__( 'Invalid item type.', 'buddyboss' )
			);
		}

		if ( ! in_array( $r['permission_type'], $valid_permission_types, true ) ) {
			return new WP_Error(
				'bb_topic_relationship_invalid_permission_type',
				esc_html__( 'Invalid permission type.', 'buddyboss' )
			);
		}

		// phpcs:ignore
		$menu_order      = $this->wpdb->get_var( $this->wpdb->prepare( "SELECT MAX(menu_order) FROM {$this->topic_rel_table} WHERE item_type = %s AND item_id = %d", $r['item_type'], $r['item_id'] ) );
		$r['menu_order'] = $menu_order + 1;

		/**
		 * Fires before a topic relationship has been added.
		 *
		 * @since BuddyBoss 2.8.80
		 *
		 * @param array $args The arguments used to add the topic relationship.
		 */
		do_action( 'bb_topic_relationship_before_added', $r );

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
				unset( $r );

				return new WP_Error(
					'bb_topic_relationship_db_insert_error',
					esc_html( $error_message )
				);
			}

			unset( $r );

			return false;
		}

		/**
		 * Fires after a topic relationship has been added.
		 *
		 * @since BuddyBoss 2.8.80
		 *
		 * @param int   $inserted The number of rows inserted.
		 * @param array $args     The arguments used to add the topic relationship.
		 */
		do_action( 'bb_topic_relationship_after_added', $inserted, $r );

		return $inserted;
	}

	/**
	 * Update a topic relationship.
	 *
	 * @since BuddyBoss 2.8.80
	 *
	 * @param array $args {
	 *     Array of arguments for updating a topic relationship.
	 *
	 *     @type int    $topic_id         The topic ID. (required)
	 *     @type int    $item_id          The item ID. (Optional, for more specific updates)
	 *     @type string $item_type        The item type. (optional)
	 *     @type string $permission_type  The new permission type. (optional)
	 *     @type array  $where            Additional WHERE conditions. (optional)
	 * }
	 * @return bool|WP_Error
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
			),
			'bb_update_topic_relationship'
		);

		/**
		 * Fires before a topic relationship has been updated.
		 *
		 * @since BuddyBoss 2.8.80
		 *
		 * @param array $args The arguments used to add the topic relationship.
		 */
		do_action( 'bb_topic_relationship_before_updated', $r );

		// Build data to update.
		$data        = array();
		$data_format = array();
		if ( ! empty( $r['permission_type'] ) ) {
			$data['permission_type'] = $r['permission_type'];
			$data_format[]           = '%s';
		}
		if ( ! empty( $r['item_id'] ) ) {
			$data['item_id'] = $r['item_id'];
			$data_format[]   = '%d';
		}
		if ( ! empty( $r['item_type'] ) ) {
			$data['item_type'] = $r['item_type'];
			$data_format[]     = '%s';
		}
		if ( ! empty( $r['topic_id'] ) ) {
			$data['topic_id'] = $r['topic_id'];
			$data_format[]    = '%d';
		}

		if ( empty( $data ) ) {
			return false;
		}

		// Build where clause and format specifiers.
		if ( ! empty( $r['where'] ) && is_array( $r['where'] ) ) {
			foreach ( $r['where'] as $key => $value ) {
				$where[ $key ] = $value;
				// Add appropriate format specifier based on field type.
				if ( in_array( $key, array( 'topic_id', 'item_id' ), true ) ) {
					$where_format[] = '%d';
				} else {
					$where_format[] = '%s';
				}
			}
		}

		$get_topic_relationship = $this->bb_get_topic(
			array(
				'topic_id'  => $where['topic_id'],
				'item_id'   => $where['item_id'],
				'item_type' => $where['item_type'],
			)
		);

		// If no existing data is found or data is the same, return early.
		if ( ! $get_topic_relationship ) {
			return false;
		}

		// Check if any values changed.
		$needs_update = false;
		foreach ( $data as $key => $value ) {
			// Cast both values to integers for numeric fields.
			if ( in_array( $key, array( 'topic_id', 'item_id' ), true ) ) {
				$current_value = (int) ( $get_topic_relationship->{$key} ?? 0 );
				$new_value     = (int) $value;
			} else {
				$current_value = $get_topic_relationship->{$key} ?? '';
				$new_value     = $value;
			}

			if ( $current_value !== $new_value ) {
				$needs_update = true;
				break;
			}
		}

		// If no changes needed, return success.
		if ( ! $needs_update ) {
			return true;
		}

		$updated = $this->wpdb->update(
			$this->topic_rel_table,
			$data,
			$where,
			$data_format,
			$where_format
		);

		if ( ! $updated ) {
			$error_message = esc_html__( 'Failed to update topic relationship.', 'buddyboss' );
			if ( 'wp_error' === $r['error_type'] ) {
				unset( $r );

				return new WP_Error(
					'bb_topic_relationship_db_updated_error',
					esc_html( $error_message )
				);
			}

			unset( $r );

			return false;
		}

		/**
		 * Fires after a topic relationship has been updated.
		 *
		 * @since BuddyBoss 2.8.80
		 *
		 * @param int   $updated The number of rows updated.
		 * @param array $args The arguments used to add the topic relationship.
		 */
		do_action( 'bb_topic_relationship_after_updated', $updated, $r );

		return $updated;
	}

	/**
	 * Get a single topic by field (id or slug).
	 *
	 * @since BuddyBoss 2.8.80
	 *
	 * @param string $field The field to query by ('id' or 'slug').
	 * @param mixed  $value The value to search for.
	 *
	 * @return object|bool Topic object on success, null on failure.
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

		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$sql = $this->wpdb->prepare( "SELECT * FROM {$this->topics_table} WHERE {$field} = %s", $value );

		$topic = $this->wpdb->get_row( $sql ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared

		if ( $topic ) {
			wp_cache_set( $cache_key, $topic, self::$topic_cache_group );
		}

		return $topic;
	}

	/**
	 * Get multiple topics based on arguments.
	 *
	 * @since BuddyBoss 2.8.80
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
				'topic_id'        => 0,
				'name'            => '',
				'slug'            => '',
				'per_page'        => -1, // Retrieve all by default.
				'paged'           => 1,
				'orderby'         => 'menu_order',
				'order'           => 'ASC',
				'search'          => '',
				'item_id'         => 0,
				'item_type'       => '',
				'permission_type' => '',
				'user_id'         => 0,
				'include'         => array(),
				'exclude'         => array(),
				'count_total'     => false,
				'fields'          => 'all', // Fields to include.
				'error_type'      => 'bool',
			),
			'bb_get_topics'
		);

		// Select conditions.
		$select_sql = 'SELECT DISTINCT tr.id, tr.menu_order';

		/**
		 * Filters the MySQL SELECT clause for the topic query.
		 *
		 * @since BuddyBoss 2.8.80
		 *
		 * @param string $select_sql Current SELECT MySQL statement.
		 * @param array  $r          Method parameters.
		 */
		$select_sql = apply_filters( 'bb_get_topics_select_sql', $select_sql, $r );

		$from_sql = ' FROM ' . $this->topic_rel_table . ' tr';

		$join_sql = ' LEFT JOIN ' . $this->topics_table . ' t ON t.id = tr.topic_id';

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

		if ( 'name' === $r['orderby'] ) {
			$order_by = 't.name';
		} else {
			$order_by = 'tr.' . $r['orderby'];
		}

		/**
		 * Filters the MySQL ORDER BY clause for the topic query.
		 *
		 * @since BuddyBoss 2.8.80
		 *
		 * @param string $order_by Current ORDER BY MySQL statement.
		 * @param array  $r        Method parameters.
		 */
		$order_by = apply_filters( 'bb_get_topics_order_by', $order_by, $r );

		if ( ! empty( $order_by ) && ! empty( $sort ) ) {
			$order_by = "ORDER BY {$order_by} {$sort}";
		}

		if ( ! empty( $r['item_id'] ) ) {
			$r['item_id'] = is_array( $r['item_id'] ) ? $r['item_id'] : array( $r['item_id'] );
			$item_ids     = wp_parse_id_list( $r['item_id'] );
			$placeholders = array_fill( 0, count( $item_ids ), '%d' );

			// phpcs:ignore
			$where_conditions[] = $this->wpdb->prepare( 'tr.item_id IN (' . implode( ',', $placeholders ) . ')', $item_ids );
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
			$r['item_type'] = is_array( $r['item_type'] ) ? $r['item_type'] : array( $r['item_type'] );
			$item_types     = array_map( 'sanitize_text_field', $r['item_type'] );
			$placeholders   = array_fill( 0, count( $item_types ), '%s' );

			// phpcs:ignore
			$where_conditions[] = $this->wpdb->prepare( 'tr.item_type IN (' . implode( ',', $placeholders ) . ')', $item_types );
		}

		// permission_type.
		if ( ! empty( $r['permission_type'] ) ) {
			$r['permission_type'] = is_array( $r['permission_type'] ) ? $r['permission_type'] : array( $r['permission_type'] );
			$permission_types     = array_map( 'sanitize_text_field', $r['permission_type'] );
			$placeholders         = array_fill( 0, count( $permission_types ), '%s' );

			// phpcs:ignore
			$where_conditions[] = $this->wpdb->prepare( 'tr.permission_type IN (' . implode( ',', $placeholders ) . ')', $permission_types );
		}

		// user_id.
		if ( ! empty( $r['user_id'] ) ) {
			$where_conditions[] = $this->wpdb->prepare( 'tr.user_id = %d', $r['user_id'] );
		}

		// include.
		if ( ! empty( $r['include'] ) ) {
			$include_ids        = implode( ',', wp_parse_id_list( $r['include'] ) );
			$where_conditions[] = $this->wpdb->prepare( 'tr.topic_id IN ( %s )', $include_ids );
		}

		// exclude.
		if ( ! empty( $r['exclude'] ) ) {
			$exclude_ids        = implode( ',', wp_parse_id_list( $r['exclude'] ) );
			$where_conditions[] = $this->wpdb->prepare( 'tr.topic_id NOT IN ( %s )', $exclude_ids );
		}

		/**
		 * Filters the MySQL WHERE conditions for the activity topics using the SQL method.
		 *
		 * @since BuddyBoss 2.8.80
		 *
		 * @param array  $where_conditions Current conditions for MySQL WHERE statement.
		 * @param array  $r                Parsed arguments passed into the method.
		 * @param string $select_sql       Current SELECT MySQL statement at the point of execution.
		 * @param string $from_sql         Current FROM MySQL statement at the point of execution.
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

		/**
		 * Filter the MySQL JOIN clause for the topic query.
		 *
		 * @since BuddyBoss 2.8.80
		 *
		 * @param string $join_sql   JOIN clause.
		 * @param array  $r          Method parameters.
		 * @param string $select_sql Current SELECT MySQL statement.
		 * @param string $from_sql   Current FROM MySQL statement.
		 * @param string $where_sql  Current WHERE MySQL statement.
		 */
		$join_sql = apply_filters( 'bb_get_topics_join_sql', $join_sql, $r, $select_sql, $from_sql, $where_sql );

		/**
		 * Filters the MySQL GROUP BY clause for the topic query.
		 *
		 * @since BuddyBoss 2.8.80
		 *
		 * @param string $group_by   Current GROUP BY MySQL statement.
		 * @param array  $r          Method parameters.
		 * @param string $select_sql Current SELECT MySQL statement.
		 * @param string $from_sql   Current FROM MySQL statement.
		 * @param string $where_sql  Current WHERE MySQL statement.
		 */
		$group_by = apply_filters( 'bb_get_topics_group_by', '', $r, $select_sql, $from_sql, $where_sql );

		// Query first for topic IDs.
		$topic_sql = "{$select_sql} {$from_sql} {$join_sql} {$where_sql} {$group_by} {$order_by} {$pagination}";

		$retval = array(
			'topics' => null,
			'total'  => null,
		);

		/**
		 * Filters the Topic MySQL statement.
		 *
		 * @since BuddyBoss 2.8.80
		 *
		 * @param string $topic_sql MySQL's statement used to query for topics.
		 * @param array  $r         Array of arguments passed into the method.
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
			$topic_data = wp_parse_id_list( $topic_ids );
		} else {
			$uncached_ids = bp_get_non_cached_ids( $topic_ids, self::$topic_cache_group );
			if ( ! empty( $uncached_ids ) ) {
				$uncached_ids_sql = implode( ',', wp_parse_id_list( $uncached_ids ) );

				$queried_data = $this->wpdb->get_results( 'SELECT t.*, tr.* FROM ' . $this->topics_table . ' t LEFT JOIN ' . $this->topic_rel_table . ' tr ON t.id = tr.topic_id WHERE tr.id IN (' . $uncached_ids_sql . ') ' . $order_by, ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
				foreach ( (array) $queried_data as $topic_data ) {
					if ( ! empty( $topic_data['id'] ) ) {
						// Create a unique cache key for each topic relationship.
						$item_id                = is_array( $r['item_id'] ) ? implode( '_', $r['item_id'] ) : $r['item_id'];
						$item_type              = is_array( $r['item_type'] ) ? implode( '_', $r['item_type'] ) : $r['item_type'];
						$relationship_cache_key = 'bb_topic_relationship_' . $topic_data['id'] . '_' . $item_id . '_' . $item_type;
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
					$topic_data[] = (object) $topic;
				}
			}

			if ( 'all' !== $r['fields'] ) {
				if ( false !== strpos( $r['fields'], ',' ) ) {
					$fields = explode( ',', $r['fields'] );
					$fields = array_map( 'trim', $fields );

					$filtered_data = array();
					foreach ( $topic_data as $topic ) {
						$filtered_topic = array();
						foreach ( $fields as $field ) {
							if ( isset( $topic->$field ) ) {
								$filtered_topic[ $field ] = $topic->$field;
							}
						}
						$filtered_data[] = $filtered_topic;
					}
					$topic_data = $filtered_data;
				} else {
					$topic_data = array_unique( array_column( $topic_data, $r['fields'] ) );
				}
			}
		}

		$retval['topics'] = $topic_data;

		if ( ! empty( $r['count_total'] ) ) {

			/**
			 * Filters the total activity topics in the MySQL statement.
			 *
			 * @since BuddyBoss 2.8.80
			 *
			 * @param string $value     MySQL's statement used to query for total activity topics.
			 * @param string $where_sql MySQL WHERE statement portion.
			 */
			$total_activity_topic_sql = apply_filters(
				'bb_total_topic_count_sql',
				'SELECT count(DISTINCT t.id) FROM ' . $this->topic_rel_table . ' tr'
				. ' ' . $join_sql
				. ' ' . $where_sql,
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
	 * Fetch an existing topic while the edit topic modal is open via AJAX.
	 *
	 * @since BuddyBoss 2.8.80
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

		$topic = $this->bb_get_topic( $args );

		if ( is_wp_error( $topic ) ) {
			wp_send_json_error( array( 'error' => $topic->get_error_message() ) );
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
	 * @since BuddyBoss 2.8.80
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
			),
			'bb_get_topic'
		);

		$topic      = $this->bb_get_topics( $r );
		$topic_data = is_array( $topic ) && ! empty( $topic['topics'] ) ? current( $topic['topics'] ) : null;
		if ( isset( $r['item_type'] ) && 'groups' === $r['item_type'] && $topic_data ) {
			$topic_data->is_global_activity = $this->bb_is_topic_global( $r['topic_id'] );
		}

		return $topic_data;
	}

	/**
	 * Delete an existing topic via AJAX.
	 *
	 * @since BuddyBoss 2.8.80
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

		$topic_id  = absint( sanitize_text_field( wp_unslash( $_POST['topic_id'] ) ) );
		$item_id   = isset( $_POST['item_id'] ) ? absint( sanitize_text_field( wp_unslash( $_POST['item_id'] ) ) ) : 0;
		$item_type = isset( $_POST['item_type'] ) ? sanitize_text_field( wp_unslash( $_POST['item_type'] ) ) : '';

		$topic_lists = $this->bb_get_topics(
			array(
				'item_id'   => $item_id,
				'item_type' => $item_type,
				'exclude'   => $topic_id,
			)
		);

		$existing_topic_name = $this->bb_get_topic_by( 'id', $topic_id );
		$header_text         = __( 'Deleting', 'buddyboss' );
		if ( ! empty( $existing_topic_name->name ) ) {
			$header_text = sprintf(
			/* translators: %s: topic name */
				__( 'Deleting "%s"?', 'buddyboss' ),
				$existing_topic_name->name
			);
		}

		wp_send_json_success(
			array(
				'topic_lists' => ! empty( $topic_lists['topics'] ) ? $topic_lists['topics'] : null,
				'topic_id'    => $topic_id,
				'header_text' => $header_text,
				'item_id'     => $item_id,
				'item_type'   => $item_type,
				'nonce'       => wp_create_nonce( 'bb_migrate_topic' ),
			)
		);
	}

	/**
	 * Delete a topic and its associated relationships.
	 *
	 * @since BuddyBoss 2.8.80
	 *
	 * @param array $args      {
	 *                         Array of arguments.
	 *
	 * @type int    $topic_id  The ID of the topic to delete.
	 * @type int    $item_id   The ID of the item.
	 * @type string $item_type The type of item.
	 *                         }
	 *
	 * @return bool|WP_Error True on success, WP_Error on failure.
	 */
	public function bb_delete_topic( $args ) {
		$r = bp_parse_args(
			$args,
			array(
				'topic_id'  => 0,
				'fields'    => 'id',
				'item_id'   => 0,
				'item_type' => 'activity',
			),
			'bb_delete_topic'
		);

		if ( empty( $r['topic_id'] ) ) {
			return false;
		}

		$topic_id = absint( $r['topic_id'] );
		if ( ! $this->bb_get_topic_by( 'id', $topic_id ) ) {
			return false;
		}

		// Delete the topic relationship.
		$get_topic_args = array(
			'topic_id'  => $topic_id,
			'fields'    => $r['fields'],
			'item_id'   => $r['item_id'],
			'item_type' => $r['item_type'],
		);

		$get_all_topic_relationships = $this->bb_get_topics( $get_topic_args );

		$relationships_ids = ! empty( $get_all_topic_relationships['topics'] ) ? $get_all_topic_relationships['topics'] : array();

		/**
		 * Fires before a topic is deleted.
		 *
		 * @since BuddyBoss 2.8.80
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

			/**
			 * Fires after a topic relationship has been deleted.
			 *
			 * @since BuddyBoss 2.8.80
			 *
			 * @param array $relationships_ids The IDs of the topic relationships that were deleted.
			 * @param int   $topic_id          The ID of the topic that was deleted.
			 */
			do_action( 'bb_topic_relationship_after_deleted', $relationships_ids, $topic_id );

			if ( function_exists( 'bb_activity_topics_manager_instance' ) ) {
				bb_activity_topics_manager_instance()->bb_delete_activity_topic_relationship( $r );
			}
		}

		/**
		 * Fires after a topic has been deleted.
		 *
		 * @since BuddyBoss 2.8.80
		 *
		 * @param array $relationships_ids The IDs of the topic relationships that were deleted.
		 * @param int   $topic_id          The ID of the topic that was deleted.
		 */
		do_action( 'bb_topic_deleted', $relationships_ids, $topic_id );

		unset( $topic_id, $deleted_rels, $deleted_topic, $get_all_topic_relationships, $relationships_ids );

		return true;
	}

	/**
	 * Migrate a topic via AJAX.
	 *
	 * @since BuddyBoss 2.8.80
	 */
	public function bb_migrate_topic_ajax() {
		if ( ! isset( $_POST['nonce'] ) ) {
			wp_send_json_error(
				array(
					'error' => __( 'Nonce is required.', 'buddyboss' ),
					'topic' => array(),
				)
			);
		}

		check_ajax_referer( 'bb_migrate_topic', 'nonce' );

		if ( ! isset( $_POST['old_topic_id'] ) ) {
			wp_send_json_error(
				array(
					'error' => __( 'Topic ID is required.', 'buddyboss' ),
					'topic' => array(),
				)
			);
		}

		$migrate_type = isset( $_POST['migrate_type'] ) ? sanitize_text_field( wp_unslash( $_POST['migrate_type'] ) ) : '';
		if ( 'migrate' === $migrate_type && ! isset( $_POST['new_topic_id'] ) ) {
			wp_send_json_error(
				array(
					'error' => __( 'Migrate topic ID is required.', 'buddyboss' ),
					'topic' => array(),
				)
			);
		}

		$old_topic_id = absint( sanitize_text_field( wp_unslash( $_POST['old_topic_id'] ) ) );
		$item_id      = isset( $_POST['item_id'] ) ? absint( sanitize_text_field( wp_unslash( $_POST['item_id'] ) ) ) : 0;
		$item_type    = isset( $_POST['item_type'] ) ? sanitize_text_field( wp_unslash( $_POST['item_type'] ) ) : '';
		$new_topic_id = 'migrate' === $migrate_type ? absint( sanitize_text_field( wp_unslash( $_POST['new_topic_id'] ) ) ) : 0;

		if ( 'migrate' === $migrate_type && $new_topic_id ) {
			$result = $this->bb_migrate_topic(
				array(
					'topic_id'     => $old_topic_id,
					'new_topic_id' => $new_topic_id,
					'item_id'      => $item_id,
					'item_type'    => $item_type,
					'migrate_type' => $migrate_type,
				)
			);

			if ( is_wp_error( $result ) ) {
				wp_send_json_error( array( 'error' => $result->get_error_message() ) );
			}

			// Add topic history when topic is migrated.
			if (
				! empty( $old_topic_id ) &&
				! empty( $new_topic_id ) &&
				! empty( $item_type )
			) {
				$old_topic_data = $this->bb_get_topic_by( 'id', $old_topic_id );
				$old_topic_slug = ! empty( $old_topic_data ) ? $old_topic_data->slug : '';
				$new_topic_data = $this->bb_get_topic_by( 'id', $new_topic_id );
				$new_topic_slug = ! empty( $new_topic_data ) ? $new_topic_data->slug : '';

				$this->bb_add_topic_history(
					array(
						'item_id'        => $item_id,
						'item_type'      => $item_type,
						'old_topic_id'   => $old_topic_id,
						'old_topic_slug' => $old_topic_slug,
						'new_topic_id'   => $new_topic_id,
						'new_topic_slug' => $new_topic_slug,
						'action'         => 'migrate',
					)
				);
			}
		}

		/**
		 * Fires before a topic is deleted.
		 *
		 * @since BuddyBoss 2.8.80
		 *
		 * @param int $topic_id The ID of the topic being deleted.
		 */
		do_action( 'bb_activity_topic_before_delete', $old_topic_id );

		$deleted_topic = $this->bb_delete_topic(
			array(
				'topic_id'  => $old_topic_id,
				'item_id'   => $item_id,
				'item_type' => $item_type,
			)
		);

		/**
		 * Fires after a topic is deleted.
		 *
		 * @since BuddyBoss 2.8.80
		 *
		 * @param int $topic_id The ID of the topic being deleted.
		 */
		do_action( 'bb_activity_topic_after_delete', $old_topic_id );

		if ( false === $deleted_topic ) {
			wp_send_json_error( array( 'error' => __( 'Failed to delete topic.', 'buddyboss' ) ) );
		}

		wp_send_json_success(
			array(
				'message' => __( 'Topic migrated successfully.', 'buddyboss' ),
				'topic'   => $result,
			)
		);
	}

	/**
	 * Migrate a topic.
	 *
	 * @since BuddyBoss 2.8.80
	 *
	 * @param array $args {
	 *     Array of arguments.
	 *     @type int $old_topic_id The ID of the old topic.
	 *     @type int $new_topic_id The ID of the new topic.
	 *     @type int $item_id The ID of the item.
	 *     @type string $item_type The type of item.
	 * }
	 */
	public function bb_migrate_topic( $args ) {
		$r = bp_parse_args(
			$args,
			array(
				'topic_id'     => 0,
				'new_topic_id' => 0,
				'item_id'      => 0,
				'item_type'    => 'activity',
				'error_type'   => 'bool',
			),
			'bb_migrate_topic'
		);

		if ( empty( $r['topic_id'] ) ) {
			return new WP_Error(
				'bb_migrate_topic_error',
				__( 'Topic ID is required.', 'buddyboss' ),
				array( 'status' => 400 )
			);
		}

		if ( empty( $r['new_topic_id'] ) ) {
			return new WP_Error(
				'bb_migrate_topic_error',
				__( 'New topic ID is required.', 'buddyboss' ),
				array( 'status' => 400 )
			);
		}

		if ( empty( $r['item_type'] ) ) {
			return new WP_Error(
				'bb_migrate_topic_error',
				__( 'Item type is required.', 'buddyboss' ),
				array( 'status' => 400 )
			);
		}

		if ( 'groups' === $r['item_type'] && empty( $r['item_id'] ) ) {
			return new WP_Error(
				'bb_migrate_topic_error',
				__( 'Item ID is required.', 'buddyboss' ),
				array( 'status' => 400 )
			);
		}

		$topic_id     = $r['topic_id'];
		$new_topic_id = $r['new_topic_id'];
		$item_id      = $r['item_id'];
		$item_type    = $r['item_type'];

		if ( ! in_array( $item_type, array( 'activity', 'groups' ), true ) ) {
			return new WP_Error(
				'bb_migrate_topic_error',
				__( 'Item type is invalid.', 'buddyboss' ),
				array( 'status' => 400 )
			);
		}

		$topic_id_exists = $this->bb_get_topic_by( 'id', $topic_id );
		if ( ! $topic_id_exists ) {
			return new WP_Error(
				'bb_migrate_topic_error',
				__( 'Old topic ID does not exist.', 'buddyboss' ),
				array( 'status' => 400 )
			);
		}

		$new_topic_id_exists = $this->bb_get_topic_by( 'id', $new_topic_id );
		if ( ! $new_topic_id_exists ) {
			return new WP_Error(
				'bb_migrate_topic_error',
				__( 'New topic ID does not exist.', 'buddyboss' ),
				array( 'status' => 400 )
			);
		}

		/**
		 * Fires before a topic is migrated.
		 *
		 * @since BuddyBoss 2.8.80
		 *
		 * @param int    $topic_id     The ID of the old topic.
		 * @param int    $new_topic_id The ID of the new topic.
		 * @param int    $item_id      The ID of the item.
		 * @param string $item_type    The type of item.
		 */
		do_action( 'bb_before_migrate_topic', $topic_id, $new_topic_id, $item_id, $item_type );

		$result = $this->wpdb->update(
			$this->activity_topic_rel_table,
			array( 'topic_id' => $new_topic_id ),
			array(
				'topic_id'  => $topic_id,
				'item_id'   => $item_id,
				'component' => $item_type,
			),
			array( '%d' ),
			array( '%d', '%d', '%s' )
		);

		if ( false === $result ) {
			wp_send_json_error( array( 'error' => __( 'Failed to migrate topic.', 'buddyboss' ) ) );
		}

		$get_topic = $this->bb_get_topic(
			array(
				'topic_id' => $new_topic_id,
				'fields'   => 'all',
			)
		);
		if ( empty( $get_topic ) ) {
			return new WP_Error(
				'bb_migrate_topic_error',
				__( 'Failed to get new topic.', 'buddyboss' ),
				array( 'status' => 400 )
			);
		}

		$migrated_activity_ids = bb_activity_topics_manager_instance()->bb_get_activity_topic_relationship(
			array(
				'topic_id'  => $new_topic_id,
				'item_id'   => $item_id,
				'component' => $item_type,
				'fields'    => 'activity_id',
			)
		);

		/**
		 * Fires after a topic is migrated.
		 *
		 * @since BuddyBoss 2.8.80
		 * @since BuddyBoss 2.8.90 Added $migrated_activity_ids parameter.
		 *
		 * @param array  $get_topic             The new topic.
		 * @param int    $topic_id              The ID of the old topic.
		 * @param int    $new_topic_id          The ID of the new topic.
		 * @param int    $item_id               The ID of the item.
		 * @param string $item_type             The type of item.
		 * @param array  $migrated_activity_ids The IDs of the activities.
		 */
		do_action( 'bb_after_migrate_topic', $get_topic, $topic_id, $new_topic_id, $item_id, $item_type, $migrated_activity_ids );

		return $get_topic;
	}

	/**
	 * Check if the maximum number of topics has been reached.
	 *
	 * @since BuddyBoss 2.8.80
	 *
	 * @param array $r Array of arguments.
	 *
	 * @return bool True if the maximum number of topics has been reached, false otherwise.
	 */
	public function bb_topics_limit_reached( $r ) {
		$args = array(
			'per_page'    => 1,
			'item_type'   => $r['item_type'],
			'item_id'     => $r['item_id'],
			'count_total' => true,
		);
		if ( ! empty( $r['topic_id'] ) ) {
			$args['exclude'] = array( $r['topic_id'] );
		}
		$topics_count = $this->bb_get_topics( $args );

		return is_array( $topics_count ) && isset( $topics_count['total'] ) && $topics_count['total'] >= $this->bb_topics_limit();
	}

	/**
	 * Limit the number of topics.
	 *
	 * @since BuddyBoss 2.8.80
	 *
	 * @return int The maximum number of topics.
	 */
	public function bb_topics_limit() {
		return 20;
	}

	/**
	 * Update the order of topics.
	 *
	 * @since BuddyBoss 2.8.80
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

		$topic_ids = wp_parse_id_list( wp_unslash( $_POST['topic_ids'] ) );

		$success = true;

		// Update each topic with its new order in the relationship table.
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
	 * @since BuddyBoss 2.8.80
	 *
	 * @param array $args {
	 *     Array of arguments.
	 *     @type int $topic_id The ID of the topic.
	 *     @type int $item_id The ID of the item.
	 *     @type string $item_type The type of item.
	 * }
	 *
	 * @return string The permission type for the topic.
	 */
	public function bb_get_topic_permission_type( $args ) {
		if ( empty( $args['topic_id'] ) ) {
			return 'anyone';
		}

		$topic_id  = $args['topic_id'];
		$item_id   = $args['item_id'] ?? 0;
		$item_type = $args['item_type'] ?? 'activity';

		// phpcs:ignore
		$topic_permission_type = $this->wpdb->get_var( $this->wpdb->prepare( "SELECT permission_type FROM {$this->topic_rel_table} WHERE topic_id = %d AND item_id = %d AND item_type = %s", $topic_id, $item_id, $item_type ) );

		if ( ! $topic_permission_type ) {
			return 'anyone';
		}

		return $topic_permission_type;
	}

	/**
	 * Allow loading scripts only when needed.
	 *
	 * @since BuddyBoss 2.8.80
	 */
	public function bb_load_topics_scripts() {
		$is_enabled_activity = bp_is_active( 'activity' );
		$is_enabled_groups   = bp_is_active( 'groups' );
		return (
			! $is_enabled_activity || // Activity component is not active.
			(
				$is_enabled_activity && // Activity is active.
				(
					// If the groups component is active.
					(
						$is_enabled_groups &&
						! bp_is_activity_directory() &&
						! bp_is_group_admin_page() &&
						! bp_is_group_create() &&
						! bp_is_group_activity() &&
						! bp_is_user_activity()
					) ||
					// If the groups component is not active.
					(
						! $is_enabled_groups &&
						! bp_is_activity_directory()
					)
				)
			)
		);
	}

	/**
	 * Check if a topic is global by looking for activity relationships.
	 *
	 * @since BuddyBoss 2.8.80
	 *
	 * @param int $topic_id The ID of the topic to check.
	 * @return bool True if the topic is global (has activity relationships), false otherwise.
	 */
	public function bb_is_topic_global( $topic_id ) {
		if ( empty( $topic_id ) ) {
			return false;
		}

		$topic_id = absint( $topic_id );

		// Check if topic has any activity relationships.
		$activity_relationships = $this->bb_get_topics(
			array(
				'topic_id'  => $topic_id,
				'item_type' => 'activity',
				'fields'    => 'id',
			)
		);

		return ! empty( $activity_relationships['topics'] );
	}

	/**
	 * Add a topic history.
	 *
	 * @since BuddyBoss 2.8.80
	 *
	 * @param array $args {
	 *     Array of arguments.
	 *     @type int $item_id The ID of the item.
	 *     @type string $item_type The type of item.
	 *     @type int $old_topic_id The ID of the old topic.
	 *     @type string $old_topic_slug The slug of the old topic.
	 *     @type int $new_topic_id The ID of the new topic.
	 *     @type string $new_topic_slug The slug of the new topic.
	 *     @type string $action The action.
	 *     @type bool $error_type The error type.
	 * }
	 *
	 * @return bool True on success, false on failure.
	 */
	public function bb_add_topic_history( $args ) {
		$r = bp_parse_args(
			$args,
			array(
				'item_id'        => 0,
				'item_type'      => 'activity',
				'old_topic_id'   => 0,
				'old_topic_slug' => '',
				'new_topic_id'   => 0,
				'new_topic_slug' => '',
				'action'         => '',
				'error_type'     => 'bool',
			),
			'bb_add_topic_history'
		);

		// Early validation - return immediately if required fields are missing.
		if (
			empty( $r['item_type'] ) ||
			( 'groups' === $r['item_type'] && empty( $r['item_id'] ) ) ||
			empty( $r['old_topic_id'] ) ||
			empty( $r['old_topic_slug'] ) ||
			empty( $r['new_topic_id'] ) ||
			empty( $r['new_topic_slug'] ) ||
			empty( $r['action'] )
		) {
			return false;
		}

		$result = $this->wpdb->insert(
			$this->topic_history_table,
			array(
				'item_id'        => absint( $r['item_id'] ),
				'item_type'      => sanitize_text_field( $r['item_type'] ),
				'old_topic_id'   => absint( $r['old_topic_id'] ),
				'old_topic_slug' => sanitize_title( $r['old_topic_slug'] ),
				'new_topic_id'   => absint( $r['new_topic_id'] ),
				'new_topic_slug' => sanitize_title( $r['new_topic_slug'] ),
				'action'         => sanitize_text_field( $r['action'] ),
				'date_created'   => bp_core_current_time(),
			),
			array( '%d', '%s', '%d', '%s', '%d', '%s', '%s', '%s' )
		);

		if ( false === $result ) {
			if ( 'wp_error' === $r['error_type'] ) {
				return new WP_Error(
					'bb_topic_history_add_failed',
					$this->wpdb->last_error
				);
			}
			return false;
		}

		/**
		 * Fires after topic history has been added.
		 *
		 * @since BuddyBoss 2.8.80
		 *
		 * @param array $r The arguments used to add the topic history.
		 */
		do_action( 'bb_topic_history_after_added', $r );

		return false !== $result;
	}

	/**
	 * Get the redirected topic slug from history.
	 *
	 * @since BuddyBoss 2.8.80
	 *
	 * @param array $args {
	 *     Array of arguments.
	 *     @type string $old_slug  The old topic slug to check.
	 *     @type string $item_type The item type (optional, defaults to 'activity').
	 *     @type int    $item_id   The item ID (optional, defaults to 0 for activity).
	 * }
	 *
	 * @return string|false The redirected topic slug if found, false otherwise.
	 */
	public function bb_get_redirected_topic_slug( $args ) {
		$r = bp_parse_args(
			$args,
			array(
				'old_slug'  => '',
				'item_type' => 'activity',
				'item_id'   => 0,
			),
			'bb_get_redirected_topic_slug'
		);

		if ( empty( $r['old_slug'] ) ) {
			return false;
		}

		$cache_key  = 'bb_topic_redirect_' . $r['old_slug'] . '_' . $r['item_type'] . '_' . $r['item_id'];
		$final_slug = wp_cache_get( $cache_key, self::$topic_cache_group );

		if ( ! $final_slug ) {
			$final_slug = $this->bb_get_latest_topic_slug_concise( $r['old_slug'], $r['item_id'], $r['item_type'] );
		}

		wp_cache_set( $cache_key, $final_slug, self::$topic_cache_group, HOUR_IN_SECONDS );

		return $final_slug;
	}

	/**
	 * Get the latest topic slug using concise query.
	 *
	 * @since BuddyBoss 2.8.80
	 *
	 * @param string $input_slug The input slug.
	 * @param int    $item_id The item ID.
	 * @param string $item_type The item type.
	 *
	 * @return string The latest topic slug.
	 */
	protected function bb_get_latest_topic_slug_concise( $input_slug, $item_id = 0, $item_type = 'activity' ) {
		// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$query = $this->wpdb->prepare(
			"SELECT @final_slug := IF(
				@next_slug IS NULL,
				@current_slug,
				@next_slug
			) as final_slug
			FROM (
				SELECT @current_slug := %s,
					   @next_slug := (
						   SELECT new_topic_slug
						   FROM {$this->topic_history_table}
						   WHERE BINARY old_topic_slug = @current_slug
						   AND item_id = %d
						   AND item_type = %s
						   AND action IN ( 'rename', 'migrate' )
						   ORDER BY date_created DESC
						   LIMIT 1
					   )
			) vars",
			$input_slug,
			$item_id,
			$item_type
		);
		// phpcs:enable WordPress.DB.PreparedSQL.InterpolatedNotPrepared

		$final_slug = $this->wpdb->get_var( $query ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- Query is prepared above.

		return ( false !== $final_slug && null !== $final_slug ) ? $final_slug : $input_slug;
	}

	/**
	 * Handle topic redirects for renamed topics.
	 *
	 * @since BuddyBoss 2.8.80
	 */
	public function bb_handle_topic_redirects() {
		$bb_topic = $this->bb_get_topic_slug_from_url();
		if ( empty( $bb_topic ) ) {
			return;
		}

		// First check if the topic exists with the current slug.
		$args = array(
			'slug' => $bb_topic,
		);
		if ( function_exists( 'bp_is_group' ) && bp_is_group() ) {
			$args['item_type'] = 'groups';
			$args['item_id']   = bp_get_current_group_id();
		} else {
			$args['item_type'] = 'activity';
			$args['item_id']   = 0;
		}

		$existing_topic = $this->bb_get_topic( $args );

		// If topic exists, no redirect needed.
		if ( $existing_topic ) {
			return;
		}

		// Check for redirect in topic history.
		$new_slug = $this->bb_get_redirected_topic_slug(
			array(
				'old_slug'  => $bb_topic,
				'item_type' => $args['item_type'],
				'item_id'   => $args['item_id'],
			)
		);

		if ( $new_slug ) {
			$new_slug_exists = $this->bb_get_topic_history( array( 'new_topic_slug' => $new_slug ) );

			// Build the redirect URL.
			$current_url = home_url( add_query_arg( array() ) );
			if ( ! $new_slug_exists ) {
				$redirect_url = remove_query_arg( 'bb-topic', $current_url );
			} else {
				$redirect_url = add_query_arg( 'bb-topic', $new_slug, remove_query_arg( 'bb-topic', $current_url ) );
			}

			/**
			 * Filters the topic redirect URL.
			 *
			 * @since BuddyBoss 2.8.80
			 *
			 * @param string $redirect_url The redirect URL.
			 * @param string $old_slug The old topic slug.
			 * @param string $new_slug The new topic slug.
			 * @param string $item_type The item type (activity or groups).
			 * @param int    $item_id The item ID.
			 */
			$redirect_url = apply_filters( 'bb_topic_redirect_url', $redirect_url, $bb_topic, $new_slug, $args['item_type'], $args['item_id'] );

			/**
			 * Fires before a topic redirect is performed.
			 *
			 * @since BuddyBoss 2.8.80
			 *
			 * @param string $old_slug The old topic slug.
			 * @param string $new_slug The new topic slug.
			 * @param string $item_type The item type (activity or groups).
			 * @param int    $item_id The item ID.
			 */
			do_action( 'bb_topic_before_redirect', $bb_topic, $new_slug, $args['item_type'], $args['item_id'] );

			wp_safe_redirect( $redirect_url, 301 );
			exit;
		}
	}

	/**
	 * Get the topic slug from the URL.
	 *
	 * @since BuddyBoss 2.8.80
	 *
	 * @return string The topic slug from URL parameters.
	 */
	public function bb_get_topic_slug_from_url() {
		// Get topic slug from GET/POST data - this is used for public filtering, no nonce needed.
		// Since this is used for public topic filtering and not form submission, nonce verification
		// is not required. The data is sanitized for security.
		$topic_slug = isset( $_REQUEST['bb-topic'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['bb-topic'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended

		return $topic_slug;
	}

	/**
	 * Get the topic history.
	 *
	 * @since BuddyBoss 2.8.80
	 *
	 * @param array $args The arguments.
	 *
	 * @return array The topic history.
	 */
	public function bb_get_topic_history( $args ) {
		$r = bp_parse_args(
			$args,
			array(
				'new_topic_slug' => '',
			),
			'bb_get_topic_history'
		);

		// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$query = $this->wpdb->prepare(
			"SELECT * FROM {$this->topic_history_table} WHERE new_topic_slug = %s",
			$r['new_topic_slug']
		);

		return $this->wpdb->get_var( $query ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- Query is prepared above.
	}
}
