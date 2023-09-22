<?php
/**
 * Reaction class.
 *
 * @package BuddyBoss\Core
 * @since BuddyBoss 2.4.30
 */

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'BB_Reaction' ) ) {

	/**
	 * BuddyBoss Reaction object.
	 *
	 * @since BuddyBoss 2.4.30
	 */
	#[\AllowDynamicProperties]
	class BB_Reaction {

		/**
		 * The single instance of the class.
		 *
		 * @since BuddyBoss 2.4.30
		 *
		 * @access private
		 * @var self
		 */
		private static $instance = null;

		/**
		 * Post type.
		 *
		 * @since BuddyBoss 2.4.30
		 *
		 * @access private
		 * @var mixed|null
		 */
		private static $post_type;

		/**
		 * User reaction table name.
		 *
		 * @since BuddyBoss 2.4.30
		 *
		 * @access public
		 * @var string
		 */
		public static $user_reaction_table = '';

		/**
		 * Reaction item types.
		 *
		 * @since BuddyBoss 2.4.30
		 *
		 * @var array
		 */
		private $registered_reaction_types = array();

		/**
		 * Reaction data table name.
		 *
		 * @since BuddyBoss 2.4.30
		 *
		 * @access public
		 * @var string
		 */
		public static $reaction_data_table = '';

		/**
		 * Cache group for user reaction.
		 *
		 * @since BuddyBoss 2.4.30
		 *
		 * @access public
		 * @var string
		 */
		public static $cache_group = 'bb_reactions';

		/**
		 * Cache group for reaction data.
		 *
		 * @since BuddyBoss 2.4.30
		 *
		 * @access public
		 * @var string
		 */
		public static $rd_cache_group = 'bb_reaction_data';

		/**
		 * Check initialize action status.
		 *
		 * @since  BuddyBoss 2.4.30
		 *
		 * @access private
		 * @var bool
		 */
		public static $status = false;

		/**
		 * Get the instance of this class.
		 *
		 * @since BuddyBoss 2.4.30
		 *
		 * @return Controller|BB_Reaction|null
		 */
		public static function instance() {

			if ( null === self::$instance ) {
				$class_name     = __CLASS__;
				self::$instance = new $class_name();
			}

			return self::$instance;
		}

		/**
		 * Constructor method.
		 *
		 * @since BuddyBoss 2.4.30
		 */
		public function __construct() {
			$bp_prefix = bp_core_get_table_prefix();
			// User reaction table.
			$bb_user_reactions         = $bp_prefix . 'bb_user_reactions';
			self::$user_reaction_table = $bb_user_reactions;
			// Reaction data table.
			$bb_reactions_data         = $bp_prefix . 'bb_reactions_data';
			self::$reaction_data_table = $bb_reactions_data;

			self::$post_type = 'bb_reaction';

			$this->bb_register_post_type();

			// Register activity reaction item type.
			$this->bb_register_reaction_item_type(
				'activity',
				array(
					'validate_callback' => array( $this, 'bb_validate_activity_reaction_request' ),
				)
			);

			add_action( 'bb_reaction_after_add_user_item_reaction', array( $this, 'bb_add_activity_reaction_data' ), 10, 2 );
			add_action( 'bb_reaction_after_remove_user_item_reaction', array( $this, 'bb_remove_activity_reaction_data' ), 10, 3 );

		}

		/******************* Required functions ******************/

		/**
		 * Created custom table for reactions.
		 *
		 * @since BuddyBoss 2.4.30
		 *
		 * @return void
		 */
		public function create_table() {
			$sql             = array();
			$wpdb            = $GLOBALS['wpdb'];
			$charset_collate = $wpdb->get_charset_collate();

			// User reaction table.
			$bb_user_reactions = self::$user_reaction_table;

			// Table already exists, so maybe upgrade instead?
			$user_reactions_table_exists = $wpdb->query( "SHOW TABLES LIKE '{$bb_user_reactions}';" ); // phpcs:ignore
			if ( ! $user_reactions_table_exists ) {
				$sql[] = "CREATE TABLE {$bb_user_reactions} (
					id bigint(20) NOT NULL AUTO_INCREMENT,
					user_id bigint(20) NOT NULL,
					reaction_id bigint(20) NOT NULL,
					item_type varchar(20) NOT NULL,
					item_id bigint(20) NOT NULL,
					date_created datetime NOT NULL,
					PRIMARY KEY (id),
					KEY user_id (user_id),
					KEY reaction_id (reaction_id),
					KEY item_type (item_type),
					KEY item_id (item_id),
					KEY date_created (date_created)
				) {$charset_collate};";
			}

			// Reaction data table.
			$bb_reactions_data = self::$reaction_data_table;

			// Table already exists, so maybe upgrade instead?
			$reactions_data_table_exists = $wpdb->query( "SHOW TABLES LIKE '{$bb_reactions_data}';" ); // phpcs:ignore
			if ( ! $reactions_data_table_exists ) {
				$sql[] = "CREATE TABLE {$bb_reactions_data} (
					id bigint(20) NOT NULL AUTO_INCREMENT,
					`name` varchar(255)  NOT NULL,
					`value` longtext DEFAULT NULL,
					rel1 varchar(20) NOT NULL,
					rel2 varchar(20) NOT NULL,
    				rel3 varchar(20) NOT NULL,
					`date` datetime NOT NULL,
					PRIMARY KEY (id),
					KEY `name` (`name`),
					KEY rel1 (rel1),
					KEY rel2 (rel2),
					KEY rel3 (rel3),
					KEY `date` (`date`)
				) {$charset_collate};";
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
		 * Register post type.
		 *
		 * @since BuddyBoss 2.4.30
		 *
		 * @return void
		 */
		public function bb_register_post_type() {
			if ( bp_is_root_blog() && ! is_network_admin() && function_exists( 'register_post_type' ) ) {
				register_post_type(
					self::$post_type,
					array(
						'description'         => __( 'Reactions', 'buddyboss' ),
						'labels'              => $this->bb_get_reaction_post_type_labels(),
						'menu_icon'           => 'dashicons-reaction-alt',
						'public'              => false,
						'show_ui'             => false,
						'show_in_rest'        => false,
						'exclude_from_search' => true,
						'show_in_admin_bar'   => false,
						'show_in_nav_menus'   => false,
					)
				);
			}
		}

		/**
		 * Return labels used by the reaction post type.
		 *
		 * @since BuddyBoss 2.4.30
		 *
		 * @return array
		 */
		public function bb_get_reaction_post_type_labels() {
			return array(
				'add_new'               => __( 'New Reaction', 'buddyboss' ),
				'add_new_item'          => __( 'Add New Reaction', 'buddyboss' ),
				'all_items'             => __( 'All Reactions', 'buddyboss' ),
				'edit_item'             => __( 'Edit Reaction', 'buddyboss' ),
				'filter_items_list'     => __( 'Filter Reaction list', 'buddyboss' ),
				'items_list'            => __( 'Reaction list', 'buddyboss' ),
				'items_list_navigation' => __( 'Reaction list navigation', 'buddyboss' ),
				'menu_name'             => __( 'Reactions', 'buddyboss' ),
				'name'                  => __( 'Reactions', 'buddyboss' ),
				'new_item'              => __( 'New Reaction', 'buddyboss' ),
				'not_found'             => __( 'No reactions found', 'buddyboss' ),
				'not_found_in_trash'    => __( 'No reactions found in trash', 'buddyboss' ),
				'search_items'          => __( 'Search Reactions', 'buddyboss' ),
				'singular_name'         => __( 'Reaction', 'buddyboss' ),
				'uploaded_to_this_item' => __( 'Uploaded to this reaction', 'buddyboss' ),
				'view_item'             => __( 'View Reaction', 'buddyboss' ),
			);
		}

		/******************* Reaction functions ******************/

		/**
		 * Add new reaction.
		 *
		 * @since BuddyBoss 2.4.30
		 *
		 * @param array $args {
		 *                    Reaction arguments.
		 * @type string $name Name of the reaction.
		 * @type string $icon Icon filename or uploaded file.
		 *                    }
		 *
		 * @return int|void|WP_Error
		 */
		private function bb_add_reaction( $args ) {
			$r = bp_parse_args(
				$args,
				array(
					'name' => '',
					'icon' => null,
				)
			);

			$post_title = ! empty( $r['name'] ) ? sanitize_title( $r['name'] ) : '';
			if ( empty( $post_title ) ) {
				return;
			}

			// Validate if a duplicate name exists before adding.
			$existing_reaction = get_page_by_path( $post_title, OBJECT, self::$post_type );
			if ( $existing_reaction ) {
				return;
			}

			$post_content = array(
				'name' => $r['name'],
				'icon' => $r['icon'],
			);

			// Prepare reaction data.
			$reaction_data = array(
				'post_title'   => $r['name'],
				'post_name'    => $post_title,
				'post_type'    => self::$post_type,
				'post_status'  => 'publish',
				'post_content' => maybe_serialize( $post_content ),
				'post_author'  => bp_loggedin_user_id(),
			);

			// Insert the new reaction.
			$reaction_id = wp_insert_post( $reaction_data );

			// If the reaction was successfully added, update the transient.
			if ( ! is_wp_error( $reaction_id ) && ! empty( $reaction_id ) ) {
				// Update bb_reactions transient.
				$this->bb_update_reactions_transient();
			}

			return $reaction_id;
		}

		/**
		 * Remove reaction.
		 *
		 * @since BuddyBoss 2.4.30
		 *
		 * @param int $reaction_id Reaction id.
		 *
		 * @return void
		 */
		private function bb_remove_reaction( $reaction_id ) {
			if ( empty( $reaction_id ) ) {
				return;
			}

			// Check if the reaction post exists.
			$reaction = get_post( $reaction_id );
			if ( ! isset( $reaction->post_type ) || self::$post_type !== $reaction->post_type ) {
				return;
			}

			$success = wp_delete_post( $reaction_id, true );

			if ( ! empty( $success ) && ! is_wp_error( $success ) ) {
				$this->bb_update_reactions_transient();
			}
		}

		/**
		 * Get all reaction data.
		 *
		 * @since BuddyBoss 2.4.30
		 *
		 * @return array
		 */
		public function bb_get_reactions() {
			$reactions = get_transient( 'bb_reactions', '' );
			if ( ! empty( $reactions ) ) {
				return maybe_unserialize( $reactions );
			}

			return array();
		}

		/**
		 * Update the bb_reactions transient.
		 *
		 * @since BuddyBoss 2.4.30
		 */
		private function bb_update_reactions_transient() {
			// Get all reactions.
			$args = array(
				'fields'                 => array( 'ids', 'post_title', 'post_content' ),
				'post_type'              => self::$post_type,
				'posts_per_page'         => - 1,
				'orderby'                => 'menu_order',
				'post_status'            => 'publish',
				'suppress_filters'       => false,
				'update_post_meta_cache' => false,
				'update_post_term_cache' => false,
			);

			$all_reactions = get_posts( $args );

			$reactions_data = array();
			if ( ! empty( $all_reactions ) ) {
				foreach ( $all_reactions as $reaction ) {
					$reaction_data = ! empty( $reaction->post_content ) ? maybe_unserialize( $reaction->post_content ) : '';
					if (
						! empty( $reaction_data ) &&
						is_array( $reaction_data ) &&
						isset( $reaction_data['name'] )
					) {
						$reactions_data[] = array(
							'id'   => $reaction->ID,
							'name' => $reaction_data['name'],
							'icon' => $reaction_data['icon'],
						);
					}
				}
			}

			$reactions_data = ! empty( $reactions_data ) ? maybe_serialize( $reactions_data ) : '';
			// Update the transient.
			set_transient( 'bb_reactions', $reactions_data );
		}

		/**
		 * Register reaction item type.
		 *
		 * @since BuddyBoss 2.4.30
		 *
		 * @param string $type Item Type.
		 * @param array  $args Array of arguments.
		 *
		 * @return void
		 */
		public function bb_register_reaction_item_type( $type, $args ) {
			$r = bp_parse_args(
				$args,
				array(
					'reaction_type'     => $type,
					'validate_callback' => '',
				)
			);

			if (
				empty( $r['reaction_type'] ) ||
				empty( $r['validate_callback'] ) ||
				isset( $this->registered_reaction_types[ $r['reaction_type'] ] ) ||
				! preg_match( '/^[a-zA-Z0-9_-]+$/', $r['reaction_type'] )
			) {
				return;
			}

			$this->registered_reaction_types[ $r['reaction_type'] ] = array(
				'reaction_type'     => $r['reaction_type'],
				'validate_callback' => $r['validate_callback'],
			);
		}

		/******************* Reaction user functions ******************/

		/**
		 * Function to add user reaction.
		 *
		 * @since BuddyBoss 2.4.30
		 *
		 * @param array $args Arguments of user reaction.
		 *
		 * @return false|int|WP_Error|object
		 */
		public function bb_add_user_item_reaction( $args ) {
			global $wpdb;

			$r = bp_parse_args(
				$args,
				array(
					'reaction_id'  => 0,
					'item_type'    => '',
					'item_id'      => 0,
					'user_id'      => bp_loggedin_user_id(),
					'date_created' => bp_core_current_time(),
					'error_type'   => 'bool',
				)
			);

			if ( true === self::$status ) { // Use self::$status to check.
				return false;
			}

			$all_registered_reaction_types = $this->bb_get_registered_reaction_item_types();

			if (
				empty( $all_registered_reaction_types ) ||
				! isset( $all_registered_reaction_types[ $r['item_type'] ] ) ||
				empty( $all_registered_reaction_types[ $r['item_type'] ]['validate_callback'] ) ||
				! is_callable( $all_registered_reaction_types[ $r['item_type'] ]['validate_callback'] )
			) {
				if ( 'wp_error' === $r['error_type'] ) {
					return new WP_Error(
						'bb_user_reactions_invalid_item_type',
						__( 'The item type is invalid.', 'buddyboss' )
					);
				}

				return false;
			} else {
				$validate_callback = $all_registered_reaction_types[ $r['item_type'] ]['validate_callback'];
				$validate_callback = call_user_func( $validate_callback, $r );
				if ( empty( $validate_callback ) ) {
					$r['item_id'] = 0;
				} else {
					$r['item_id'] = current( $validate_callback );
				}
			}

			// Reaction need reaction ID.
			if ( empty( $r['reaction_id'] ) ) {
				if ( 'wp_error' === $r['error_type'] ) {
					return new WP_Error( 'bb_user_reactions_empty_reaction_id', __( 'The reaction ID is required to add reaction.', 'buddyboss' ) );
				}

				return false;
				// Reaction need item type.
			} elseif ( empty( $r['item_type'] ) ) {
				if ( 'wp_error' === $r['error_type'] ) {
					return new WP_Error( 'bb_user_reactions_empty_item_type', __( 'The item type is required to add reaction.', 'buddyboss' ) );
				}

				return false;
				// Reaction need item id.
			} elseif ( empty( $r['item_id'] ) ) {
				if ( 'wp_error' === $r['error_type'] ) {
					return new WP_Error( 'bb_user_reactions_empty_item_id', __( 'The item id is required to add reaction.', 'buddyboss' ) );
				}

				return false;
			} elseif ( empty( $r['user_id'] ) ) {
				if ( 'wp_error' === $r['error_type'] ) {
					return new WP_Error( 'bb_user_reactions_empty_user_id', __( 'Invalid User ID.', 'buddyboss' ) );
				}

				return false;
			}

			/**
			 * Fires before the add user item reaction in DB.
			 *
			 * @since BuddyBoss 2.4.30
			 *
			 * @param array $r Args of user item reactions.
			 */
			do_action( 'bb_reaction_before_add_user_item_reaction', $r );

			$sql          = 'SELECT * FROM ' . self::$user_reaction_table . ' WHERE item_type = %s AND item_id = %d AND user_id = %d';
			$get_reaction = $wpdb->get_row( $wpdb->prepare( $sql, $r['item_type'], (int) $r['item_id'], (int) $r['user_id'] ) );  // phpcs:ignore
			if ( $get_reaction ) {
				$sql = $wpdb->prepare(
				// phpcs:ignore
					'UPDATE ' . self::$user_reaction_table . ' SET
						reaction_id = %d,
						date_created = %s
					WHERE
						id = %d
					',
					$r['reaction_id'],
					$r['date_created'],
					$get_reaction->id
				);
			} else {
				$sql = $wpdb->prepare(
					// phpcs:ignore
					'INSERT INTO ' . self::$user_reaction_table . ' (
						user_id,
						reaction_id,
						item_type,
						item_id,
						date_created
					) VALUES (
						%d, %d, %s, %d, %s
					)',
					(int) $r['user_id'],
					(int) $r['reaction_id'],
					$r['item_type'],
					(int) $r['item_id'],
					$r['date_created']
				);
			}

			// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			if ( false === $wpdb->query( $sql ) ) {
				if ( 'wp_error' === $r['error_type'] ) {
					return new WP_Error( 'bb_reaction_cannot_add', __( 'There is an error while adding the reaction.', 'buddyboss' ) );
				} else {
					return false;
				}
			}

			$user_reaction_id = $wpdb->insert_id;
			if ( $get_reaction ) {
				$user_reaction_id = $get_reaction->id;
			}

			/**
			 * Fires after the add user item reaction in DB.
			 *
			 * @since BuddyBoss 2.4.30
			 *
			 * @param int   $user_reaction_id User reaction id.
			 * @param array $r                Array of parsed arguments.
			 */
			do_action( 'bb_reaction_after_add_user_item_reaction', $user_reaction_id, $r );

			$this->bb_prepare_reaction_summary_data( $r );

			return $this->bb_get_user_reaction( $user_reaction_id );
		}

		/**
		 * Remove single user reaction based on reaction id.
		 *
		 * @since BuddyBoss 2.4.30
		 *
		 * @param int $user_reaction_id ID of the user reaction.
		 *
		 * @return int|false True on success, false on failure or if no user reaction
		 *                   is found for the user.
		 */
		public function bb_remove_user_item_reaction( $user_reaction_id ) {
			global $wpdb;

			if ( empty( $user_reaction_id ) || true === self::$status ) { // Use self::$status to check.
				return false;
			}

			/**
			 * Fires before the remove user item reaction.
			 *
			 * @since BuddyBoss 2.4.30
			 *
			 * @param int $user_reaction_id User reaction id.
			 */
			do_action( 'bb_reaction_before_remove_user_item_reaction', $user_reaction_id );

			$get = $this->bb_get_user_reaction( $user_reaction_id );

			if ( empty( $get->id ) ) {
				return false;
			}

			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$deleted = $wpdb->delete(
				self::$user_reaction_table,
				array(
					'id' => $get->id,
				)
			);

			/**
			 * Fires after the remove user item reaction.
			 *
			 * @since BuddyBoss 2.4.30
			 *
			 * @param int       $user_reaction_id User reaction id.
			 * @param int|false $deleted          The number of rows deleted, or false on error.
			 * @param object    $get              Reaction data.
			 */
			do_action( 'bb_reaction_after_remove_user_item_reaction', $user_reaction_id, $deleted, $get );

			$this->bb_prepare_reaction_summary_data(
				array(
					'item_id'     => $get->item_id,
					'item_type'   => $get->item_type,
					'user_id'     => $get->user_id,
					'reaction_id' => $get->reaction_id,
				)
			);

			return $deleted;
		}

		/**
		 * Remove user reactions based on args.
		 *
		 * @since BuddyBoss 2.4.30
		 *
		 * @param array $args Args of user reactions.
		 *
		 * @return bool|int|mysqli_result|resource
		 */
		public function bb_remove_user_item_reactions( $args ) {
			global $wpdb;

			$r = bp_parse_args(
				$args,
				array(
					'reaction_id' => 0,
					'item_id'     => 0,
					'item_type'   => '',
					'user_id'     => bp_loggedin_user_id(),
					'error_type'  => 'bool',
				)
			);

			if ( true === self::$status ) { // Use self::$status to check.
				return false;
			}

			/**
			 * Fires before the remove user item reactions.
			 *
			 * @since BuddyBoss 2.4.30
			 *
			 * @param array $r Args of user item reactions.
			 */
			do_action( 'bb_reaction_before_remove_user_item_reactions', $r );

			$where_args = array();

			if ( ! empty( $r['reaction_id'] ) ) {
				$where_args['reaction_id'] = $wpdb->prepare( 'reaction_id = %d', $r['reaction_id'] );
			}

			if ( ! empty( $r['item_id'] ) ) {
				$in                    = implode( ',', wp_parse_id_list( $r['item_id'] ) );
				$where_args['item_id'] = "item_id IN ({$in})";
			}

			if ( ! empty( $r['item_type'] ) ) {
				$where_args['item_type'] = $wpdb->prepare( 'item_type = %s', $r['item_type'] );
			}

			// User ID.
			if ( ! empty( $r['user_id'] ) ) {
				$where_args[] = $wpdb->prepare( 'user_id = %d', $r['user_id'] );
			}

			if ( empty( $where_args ) ) {
				return false;
			}

			// Join the where arguments for querying.
			$where_sql = ' WHERE ' . join( ' AND ', $where_args );

			// Fetch all reactions being deleted so we can perform more actions.
			// phpcs:ignore
			$get_reaction = $wpdb->get_results( 'SELECT * FROM ' . self::$user_reaction_table . " {$where_sql}" );

			// Attempt to delete reactions from the database.
			$deleted = $wpdb->query( 'DELETE FROM ' . self::$user_reaction_table . " {$where_sql}" ); // phpcs:ignore

			// Bail if nothing was deleted.
			if ( empty( $deleted ) ) {
				return false;
			}

			/**
			 * Fires after the remove user item reactions.
			 *
			 * @since BuddyBoss 2.4.30
			 *
			 * @param int|false $deleted      The number of rows deleted, or false on error.
			 * @param array     $r            Args of user item reactions.
			 * @param object    $get_reaction Reaction data.
			 */
			do_action( 'bb_reaction_after_remove_user_item_reactions', $deleted, $r, $get_reaction );

			$this->bb_prepare_reaction_summary_data( $r );

			return $deleted;
		}

		/**
		 * Query for user reactions.
		 *
		 * @since BuddyBoss 2.4.30
		 *
		 * @param array $args {
		 * An array of arguments. All items are optional.
		 *
		 * @type int         $reaction_id Reaction id.
		 * @type string      $item_type   Item type ( i.e - activity,activity_comment ).
		 * @type int         $item_id     Item id.
		 * @type int         $user_id     User id.
		 * @type int|bool    $per_page    Number of results per page. Default: 20.
		 * @type int         $paged       Which page of results to fetch. Using page=1 without per_page will result
		 *                                in no pagination. Default: 1.
		 * @type string      $order       ASC or DESC. Default: 'DESC'.
		 * @type string      $order_by    Column to order results by.
		 * @type string|bool $count_total If true, an additional DB query is run to count the total video items
		 *                                for the query. Default: false.
		 * @type string      $fields      Which fields to return. Specify 'id' to fetch a list of IDs.
		 *                                Default: 'all' (return BP_Subscription objects).
		 * }
		 *
		 * @return WP_Error
		 */
		public function bb_get_user_reactions( $args = array() ) {
			global $wpdb;

			$r = bp_parse_args(
				$args,
				array(
					'id'          => 0,      // User reaction id.
					'reaction_id' => 0,      // Reaction id.
					'item_type'   => '',     // Item type ( i.e - activity ).
					'item_id'     => 0,      // Item id ( i.e - activity_id ).
					'user_id'     => 0,      // User Id.
					'per_page'    => 20,     // Results per page.
					'paged'       => 1,      // Page 1 without a per_page will result in no pagination.
					'order'       => 'DESC', // Order ASC or DESC.
					'order_by'    => 'id',   // Column to order by.
					'count_total' => false,  // Whether to use count_total.
					'fields'      => 'all',  // Fields to include.
					'error_type'  => 'bool',
				),
				'bb_reactions_get_reaction'
			);

			$all_registered_reaction_types = $this->bb_get_registered_reaction_item_types();

			if ( ! empty( $r['item_type'] ) ) {
				if (
					empty( $all_registered_reaction_types ) ||
					! isset( $all_registered_reaction_types[ $r['item_type'] ] ) ||
					empty( $all_registered_reaction_types[ $r['item_type'] ]['validate_callback'] ) ||
					! is_callable( $all_registered_reaction_types[ $r['item_type'] ]['validate_callback'] )
				) {
					if ( 'wp_error' === $r['error_type'] ) {
						return new WP_Error(
							'bb_user_reactions_invalid_item_type',
							__( 'The item type is invalid.', 'buddyboss' )
						);
					}

					return false;
				} elseif ( ! empty( $r['item_id'] ) ) {
					$validate_callback = $all_registered_reaction_types[ $r['item_type'] ]['validate_callback'];
					$validate_callback = call_user_func( $validate_callback, $r );
					if ( empty( $validate_callback ) ) {
						return $validate_callback;
					} else {
						$r['item_id'] = $validate_callback;
					}
				}
			}

			// Select conditions.
			$select_sql = 'SELECT DISTINCT ur.id';

			$from_sql = ' FROM ' . self::$user_reaction_table . ' ur';

			$join_sql = '';

			// Where conditions.
			$where_conditions = array();

			// Sorting.
			$sort = bp_esc_sql_order( $r['order'] );
			if ( 'ASC' !== $sort && 'DESC' !== $sort ) {
				$sort = 'DESC';
			}

			switch ( $r['order_by'] ) {
				case 'date_created':
					$r['order_by'] = 'date_created';
					break;

				default:
					$r['order_by'] = 'id';
					break;
			}
			$order_by = 'ur.' . $r['order_by'];

			// id.
			if ( ! empty( $r['id'] ) ) {
				$id_in                  = implode( ',', wp_parse_id_list( $r['id'] ) );
				$where_conditions['id'] = "ur.id IN ({$id_in})";
			}

			// user_id.
			if ( ! empty( $r['user_id'] ) ) {
				$user_id_in                  = implode( ',', wp_parse_id_list( $r['user_id'] ) );
				$where_conditions['user_id'] = "ur.user_id IN ({$user_id_in})";
			}

			// reaction_id.
			if ( ! empty( $r['reaction_id'] ) ) {
				$reaction_id_in                  = implode( ',', wp_parse_id_list( $r['reaction_id'] ) );
				$where_conditions['reaction_id'] = "ur.reaction_id IN ({$reaction_id_in})";
			}

			// item_id.
			if ( ! empty( $r['item_id'] ) ) {
				$item_id_in                  = implode( ',', wp_parse_id_list( $r['item_id'] ) );
				$where_conditions['item_id'] = "ur.item_id IN ({$item_id_in})";
			}

			if ( ! empty( $r['item_type'] ) ) {
				$where_conditions['item_type'] = "ur.item_type = '" . $r['item_type'] . "'";
			}

			/**
			 * Filters the MySQL WHERE conditions for the user reaction get sql method.
			 *
			 * @since BuddyBoss 2.4.30
			 *
			 * @param array  $where_conditions Current conditions for MySQL WHERE statement.
			 * @param array  $r                Parsed arguments passed into method.
			 * @param string $select_sql       Current SELECT MySQL statement at point of execution.
			 * @param string $from_sql         Current FROM MySQL statement at point of execution.
			 * @param string $join_sql         Current INNER JOIN MySQL statement at point of execution.
			 */
			$where_conditions = apply_filters( 'bb_get_user_reactions_where_conditions', $where_conditions, $r, $select_sql, $from_sql, $join_sql );

			if ( empty( $where_conditions ) ) {
				$where_conditions[] = '1 = 1';
			}

			// Join the where conditions together.
			$where_sql = 'WHERE ' . join( ' AND ', $where_conditions );

			/**
			 * Filter the MySQL JOIN clause for the main user reaction query.
			 *
			 * @since BuddyBoss 2.4.30
			 *
			 * @param string $join_sql   JOIN clause.
			 * @param array  $r          Method parameters.
			 * @param string $select_sql Current SELECT MySQL statement.
			 * @param string $from_sql   Current FROM MySQL statement.
			 * @param string $where_sql  Current WHERE MySQL statement.
			 */
			$join_sql = apply_filters( 'bb_get_user_reactions_join_sql', $join_sql, $r, $select_sql, $from_sql, $where_sql );

			$retval = array(
				'reactions' => null,
				'total'     => null,
			);

			// Sanitize page and per_page parameters.
			$page       = absint( $r['paged'] );
			$per_page   = absint( $r['per_page'] );
			$pagination = '';
			if ( ! empty( $per_page ) && ! empty( $page ) && - 1 !== $per_page ) {
				$pagination = $wpdb->prepare( 'LIMIT %d, %d', intval( ( $page - 1 ) * $per_page ), intval( $per_page ) );
			}

			// Query first for user_reaction IDs.
			$paged_user_reactions_sql = "{$select_sql} {$from_sql} {$join_sql} {$where_sql} ORDER BY {$order_by} {$sort} {$pagination}";

			/**
			 * Filters the paged user reaction MySQL statement.
			 *
			 * @since BuddyBoss 2.4.30
			 *
			 * @param string $user_reaction_ids_sql MySQL's statement used to query for Reaction IDs.
			 * @param array  $r                     Array of arguments passed into method.
			 */
			$paged_user_reactions_sql = apply_filters( 'bb_get_user_reactions_paged_sql', $paged_user_reactions_sql, $r );

			$cached = bp_core_get_incremented_cache( $paged_user_reactions_sql, self::$cache_group );
			if ( false === $cached ) {
				$paged_user_reactions_ids = $wpdb->get_col( $paged_user_reactions_sql ); // phpcs:ignore
				bp_core_set_incremented_cache( $paged_user_reactions_sql, self::$cache_group, $paged_user_reactions_ids );
			} else {
				$paged_user_reactions_ids = $cached;
			}

			if ( 'id' === $r['fields'] ) {
				// We only want the IDs.
				$paged_user_reactions = array_map( 'intval', $paged_user_reactions_ids );
			} else {
				$uncached_ids = bp_get_non_cached_ids( $paged_user_reactions_ids, self::$cache_group );
				if ( ! empty( $uncached_ids ) ) {
					$uncached_ids_sql = implode( ',', wp_parse_id_list( $uncached_ids ) );

					// phpcs:ignore
					$queried_data = $wpdb->get_results( 'SELECT * FROM ' . self::$user_reaction_table . " WHERE id IN ({$uncached_ids_sql})" );

					foreach ( (array) $queried_data as $urdata ) {
						wp_cache_set( $urdata->id, $urdata, self::$cache_group );
					}
				}

				$paged_user_reactions = array();
				foreach ( $paged_user_reactions_ids as $id ) {
					$user_reaction = wp_cache_get( $id, self::$cache_group );
					if ( ! empty( $user_reaction ) ) {
						$paged_user_reactions[] = $user_reaction;
					}
				}

				if ( 'all' !== $r['fields'] ) {
					$paged_user_reactions = array_unique( array_column( $paged_user_reactions, $r['fields'] ) );
				}
			}

			$retval['reactions'] = $paged_user_reactions;

			if ( ! empty( $r['count_total'] ) ) {
				/**
				 * Filters the total user reaction MySQL statement.
				 *
				 * @since BuddyBoss 2.4.30
				 *
				 * @param string $sql       MySQL statement used to query for total videos.
				 * @param string $where_sql MySQL WHERE statement portion.
				 * @param string $sort      Sort direction for query.
				 */
				$sql                      = 'SELECT count(DISTINCT ur.id) FROM ' . self::$user_reaction_table . " ur {$join_sql} {$where_sql}";
				$total_user_reactions_sql = apply_filters( 'bb_get_user_reactions_total_sql', $sql, $where_sql, $sort );
				$cached                   = bp_core_get_incremented_cache( $total_user_reactions_sql, self::$cache_group );
				if ( false === $cached ) {
					$total_user_reactions = $wpdb->get_var( $total_user_reactions_sql ); // phpcs:ignore
					bp_core_set_incremented_cache( $total_user_reactions_sql, self::$cache_group, $total_user_reactions );
				} else {
					$total_user_reactions = $cached;
				}

				$retval['total'] = $total_user_reactions;
			}

			return $retval;
		}

		/**
		 * Fetch single user reaction.
		 *
		 * @since BuddyBoss 2.4.30
		 *
		 * @param int $user_reaction_id User reaction id.
		 *
		 * @return array|null
		 */
		public function bb_get_user_reaction( $user_reaction_id ) {
			global $wpdb;

			if ( empty( $user_reaction_id ) ) {
				return false;
			}

			$get_reaction = wp_cache_get( $user_reaction_id, self::$cache_group );

			if ( false !== $get_reaction ) {
				return $get_reaction;
			}

			$sql          = 'SELECT * FROM ' . self::$user_reaction_table . ' WHERE id = %d';
			$get_reaction = $wpdb->get_row( $wpdb->prepare( $sql, $user_reaction_id ) ); // phpcs:ignore

			return $get_reaction;
		}

		/**
		 * Get user reactions count.
		 *
		 * @since BuddyBoss 2.4.30
		 *
		 * @param array $args Args of the user reactions count.
		 *
		 * @return int
		 */
		public function bb_get_user_reactions_count( $args = array() ) {
			$r = bp_parse_args(
				$args,
				array(
					'reaction_id' => 0,      // Reaction id.
					'item_type'   => '',     // Item type ( i.e - Activity, Activity Comment ).
					'item_id'     => 0,      // Item id ( i.e - activity_id, activity_comment_id ).
					'user_id'     => 0,      // User Id.
					'per_page'    => 1,      // Pe Page 1.
					'paged'       => 1,      // Page 1 without a per_page will result in no pagination.
					'count_total' => true,   // Whether to use count_total.
				),
				'bb_get_user_reactions_count'
			);

			$total_count = $this->bb_get_user_reactions( $r );
			$total_count = ! empty( $total_count ) && ! empty( $total_count['total'] ) ? $total_count['total'] : 0;

			return $total_count;
		}

		/**
		 * Get current user reactions count.
		 *
		 * @since BuddyBoss 2.4.30
		 *
		 * @param array $args Args of the user reactions count.
		 *
		 * @return int
		 */
		public function bb_get_current_user_reactions_count( $args = array() ) {
			$r = bp_parse_args(
				$args,
				array(
					'user_id' => bp_loggedin_user_id(), // User Id.
				),
				'bb_get_current_user_reactions_count'
			);

			return $this->bb_get_user_reactions_count( $r );
		}

		/******************* Reaction data functions ******************/

		/**
		 * Get reactions data.
		 *
		 * @since BuddyBoss 2.4.30
		 *
		 * @param array $args Args of reaction data.
		 *
		 * @return array
		 */
		private function bb_get_reactions_data( $args = array() ) {
			global $wpdb;

			$r = bp_parse_args(
				$args,
				array(
					'name'        => '',       // Item Summary.
					'rel1'        => '',       // Item type ( i.e - activity, activity_comment ).
					'rel2'        => '',       // Item id.
					'rel3'        => '',       // Reaction id.
					'name_in'     => array(),  // Include name as array ( i.e - array( 'item_summary', 'total_reactions_count' ) ).
					'rel1_in'     => array(),  // Include rel1 as array ( i.e - array( 'activity', 'activity_comment' ) ).
					'rel2_in'     => array(),  // Include rel1 as array ( i.e - array( '123', '456' ) ).
					'per_page'    => 20,       // Results per page.
					'paged'       => 1,        // Page 1 without a per_page will result in no pagination.
					'order'       => 'DESC',   // Order ASC or DESC.
					'order_by'    => 'id',     // Column to order by.
					'count_total' => false,    // Whether to use count_total.
					'fields'      => 'all',    // Fields to include.
				),
				'bb_get_reactions_data'
			);

			// Select conditions.
			$select_sql = 'SELECT rd.id';

			$from_sql = ' FROM ' . self::$reaction_data_table . ' rd';

			$join_sql = '';

			// Where conditions.
			$where_conditions = array();

			// Sorting.
			$sort = bp_esc_sql_order( $r['order'] );
			if ( 'ASC' !== $sort && 'DESC' !== $sort ) {
				$sort = 'DESC';
			}

			switch ( $r['order_by'] ) {
				case 'name':
				case 'rel1':
				case 'rel2':
				case 'rel3':
					break;

				default:
					$r['order_by'] = 'id';
					break;
			}
			$order_by = 'rd.' . $r['order_by'];

			// id.
			if ( ! empty( $r['id'] ) ) {
				$id_in                  = implode( ',', wp_parse_id_list( $r['id'] ) );
				$where_conditions['id'] = "rd.id IN ({$id_in})";
			}

			// rel1.
			if ( ! empty( $r['rel1'] ) ) {
				$where_conditions['rel1'] = $wpdb->prepare( 'rd.rel1 = %s', $r['rel1'] );
			}

			// rel2.
			if ( ! empty( $r['rel2'] ) ) {
				$where_conditions['rel2'] = $wpdb->prepare( 'rd.rel2 = %s', $r['rel2'] );
			}

			// rel3.
			if ( ! empty( $r['rel3'] ) ) {
				$where_conditions['rel3'] = $wpdb->prepare( 'rd.rel3 = %s', $r['rel3'] );
			}

			// name.
			if ( ! empty( $r['name'] ) ) {
				$where_conditions['name'] = $wpdb->prepare( 'rd.name = %s', $r['name'] );
			}

			// name_in.
			if ( ! empty( $r['name_in'] ) ) {
				$name_in_string              = implode( "','", wp_parse_slug_list( $r['name_in'] ) );
				$where_conditions['name_in'] = "rd.name IN ('{$name_in_string}')";
			}

			// rel1_in.
			if ( ! empty( $r['rel1_in'] ) ) {
				$rel1_in                     = implode( ',', wp_parse_id_list( $r['rel1_in'] ) );
				$where_conditions['rel1_in'] = "rd.rel1 IN ({$rel1_in})";
			}

			// rel2_in.
			if ( ! empty( $r['rel2_in'] ) ) {
				$rel2_in                     = implode( ',', wp_parse_id_list( $r['rel2_in'] ) );
				$where_conditions['rel2_in'] = "rd.rel2 IN ({$rel2_in})";
			}

			/**
			 * Filters the MySQL WHERE conditions for get reaction data sql method.
			 *
			 * @since BuddyBoss 2.4.30
			 *
			 * @param array  $where_conditions Current conditions for MySQL WHERE statement.
			 * @param array  $r                Parsed arguments passed into method.
			 * @param string $select_sql       Current SELECT MySQL statement at point of execution.
			 * @param string $from_sql         Current FROM MySQL statement at point of execution.
			 * @param string $join_sql         Current INNER JOIN MySQL statement at point of execution.
			 */
			$where_conditions = apply_filters( 'bb_get_reactions_data_where_conditions', $where_conditions, $r, $select_sql, $from_sql, $join_sql );

			if ( empty( $where_conditions ) ) {
				$where_conditions['1'] = '1 = 1';
			}

			// Join the where conditions together.
			$where_sql = 'WHERE ' . join( ' AND ', $where_conditions );

			/**
			 * Filter the MySQL JOIN clause for the main reaction data query.
			 *
			 * @since BuddyBoss 2.4.30
			 *
			 * @param string $join_sql   JOIN clause.
			 * @param array  $r          Method parameters.
			 * @param string $select_sql Current SELECT MySQL statement.
			 * @param string $from_sql   Current FROM MySQL statement.
			 * @param string $where_sql  Current WHERE MySQL statement.
			 */
			$join_sql = apply_filters( 'bb_get_reactions_data_join_sql', $join_sql, $r, $select_sql, $from_sql, $where_sql );

			$retval = array(
				'reaction_data' => null,
				'total'         => null,
			);

			// Sanitize page and per_page parameters.
			$page       = absint( $r['paged'] );
			$per_page   = absint( $r['per_page'] );
			$pagination = '';
			if ( ! empty( $per_page ) && ! empty( $page ) && - 1 !== $per_page ) {
				$pagination = $wpdb->prepare( 'LIMIT %d, %d', intval( ( $page - 1 ) * $per_page ), intval( $per_page ) );
			}

			// Query first for user_reaction IDs.
			$paged_reaction_data_sql = "{$select_sql} {$from_sql} {$join_sql} {$where_sql} ORDER BY {$order_by} {$sort} {$pagination}";

			/**
			 * Filters the paged reaction data MySQL statement.
			 *
			 * @since BuddyBoss 2.4.30
			 *
			 * @param string $user_reaction_ids_sql MySQL's statement used to query for Reaction IDs.
			 * @param array  $r                     Array of arguments passed into method.
			 */
			$paged_reaction_data_sql = apply_filters( 'bb_get_reactions_data_paged_sql', $paged_reaction_data_sql, $r );

			$cached = bp_core_get_incremented_cache( $paged_reaction_data_sql, self::$rd_cache_group );
			if ( false === $cached ) {
				$paged_reaction_data_ids = $wpdb->get_col( $paged_reaction_data_sql ); // phpcs:ignore
				bp_core_set_incremented_cache( $paged_reaction_data_sql, self::$rd_cache_group, $paged_reaction_data_ids );
			} else {
				$paged_reaction_data_ids = $cached;
			}

			if ( 'id' === $r['fields'] ) {
				// We only want the IDs.
				$paged_reaction_data = array_map( 'intval', $paged_reaction_data_ids );
			} else {
				$uncached_ids = bp_get_non_cached_ids( $paged_reaction_data_ids, self::$rd_cache_group );
				if ( ! empty( $uncached_ids ) ) {
					$uncached_ids_sql = implode( ',', wp_parse_id_list( $uncached_ids ) );

					// phpcs:ignore
					$queried_data = $wpdb->get_results( 'SELECT * FROM ' . self::$reaction_data_table . " WHERE id IN ({$uncached_ids_sql})" );

					foreach ( (array) $queried_data as $rddata ) {
						wp_cache_set( $rddata->id, $rddata, self::$rd_cache_group );
					}
				}

				$paged_reaction_data = array();
				foreach ( $paged_reaction_data_ids as $id ) {
					$user_reaction = wp_cache_get( $id, self::$rd_cache_group );
					if ( ! empty( $user_reaction ) ) {
						$paged_reaction_data[] = $user_reaction;
					}
				}

				if ( 'all' !== $r['fields'] ) {
					$paged_reaction_data = array_unique( array_column( $paged_reaction_data, $r['fields'] ) );
				}
			}

			$retval['reaction_data'] = $paged_reaction_data;

			if ( ! empty( $r['count_total'] ) ) {
				/**
				 * Filters the total reaction data MySQL statement.
				 *
				 * @since BuddyBoss 2.4.30
				 *
				 * @param string $sql       MySQL statement used to query for total videos.
				 * @param string $where_sql MySQL WHERE statement portion.
				 * @param string $sort      Sort direction for query.
				 */
				$sql                     = 'SELECT count(DISTINCT rd.id) FROM ' . self::$reaction_data_table . " rd {$join_sql} {$where_sql}";
				$total_reaction_data_sql = apply_filters( 'bb_get_reactions_data_total_sql', $sql, $where_sql, $sort );
				$cached                  = bp_core_get_incremented_cache( $total_reaction_data_sql, self::$rd_cache_group );
				if ( false === $cached ) {
					$total_reaction_data = $wpdb->get_var( $total_reaction_data_sql ); // phpcs:ignore
					bp_core_set_incremented_cache( $total_reaction_data_sql, self::$rd_cache_group, $total_reaction_data );
				} else {
					$total_reaction_data = $cached;
				}

				$retval['total'] = $total_reaction_data;
			}

			return $retval;
		}

		/**
		 * Add reaction data.
		 *
		 * @since BuddyBoss 2.4.30
		 *
		 * @param array $args Args of reaction data.
		 *
		 * @return false|int|WP_Error
		 */
		private function bb_add_reactions_data( $args ) {
			global $wpdb;

			$r = bp_parse_args(
				$args,
				array(
					'name'         => '',
					'value'        => '',
					'rel1'         => '',
					'rel2'         => '',
					'rel3'         => '',
					'date_created' => bp_core_current_time(),
					'error_type'   => '',
				)
			);

			/**
			 * Fires before the add user item reaction in DB.
			 *
			 * @since BuddyBoss 2.4.30
			 *
			 * @param array $r Args of user item reactions.
			 */
			do_action( 'bb_reaction_before_add_reactions_data', $r );

			// Reaction need reaction ID.
			if ( empty( $r['name'] ) ) {
				if ( 'wp_error' === $r['error_type'] ) {
					return new WP_Error( 'bb_reaction_data_empty_name', __( 'The item summary is required to add reaction data.', 'buddyboss' ) );
				}

				return false;
				// Reaction need item type.
			}

			$reactions_data = $this->bb_get_reactions_data( $r );
			if ( ! empty( $reactions_data['reaction_data'] ) ) {
				$reaction_data = current( $reactions_data['reaction_data'] );

				$sql = $wpdb->prepare(
					// phpcs:ignore
					'UPDATE ' . self::$reaction_data_table . ' SET
                        value = %s,
                        date = %s
                    WHERE
                        rel1 = %s AND
                        rel2 = %s AND
                        rel3 = %s AND
                        name = %s
                    ',
					$r['value'],
					( ! empty( $r['date_created'] ) ? $r['date_created'] : $reaction_data->date ),
					( ! empty( $r['rel1'] ) ? $r['rel1'] : $reaction_data->rel1 ),
					( ! empty( $r['rel2'] ) ? $r['rel2'] : $reaction_data->rel2 ),
					( ! empty( $r['rel3'] ) ? $r['rel3'] : $reaction_data->rel3 ),
					$r['name']
				);
			} else {
				$sql = $wpdb->prepare(
					// phpcs:ignore
					'INSERT INTO ' . self::$reaction_data_table . ' (
                        name,
                        value,
                        rel1,
                        rel2,
                        rel3,
                        date
                    ) VALUES (
                        %s, %s, %s, %s, %s, %s
                    )',
					$r['name'],
					$r['value'],
					$r['rel1'],
					$r['rel2'],
					$r['rel3'],
					$r['date_created']
				);
			}

			// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			if ( false === $wpdb->query( $sql ) ) {
				if ( 'wp_error' === $r['error_type'] ) {
					return new WP_Error( 'bb_reaction_cannot_add', __( 'There is an error while adding the reaction data.', 'buddyboss' ) );
				} else {
					return false;
				}
			}

			$reaction_data_id = $wpdb->insert_id;

			/**
			 * Fires after the add user item reaction in DB.
			 *
			 * @since BuddyBoss 2.4.30
			 *
			 * @param int   $reaction_data_id Reaction data id.
			 * @param array $r                Args of user item reactions.
			 */
			do_action( 'bb_reaction_after_add_reactions_data', $reaction_data_id, $r );

			return $this->bb_get_reaction_data( $reaction_data_id );
		}

		/**
		 * Prepare reaction summary data when reaction to any item.
		 *
		 * @since BuddyBoss 2.4.30
		 *
		 * @param array $args Args of reaction data.
		 *
		 * @return false|int|WP_Error
		 */
		public function bb_prepare_reaction_summary_data( $args ) {
			if ( empty( $args['item_id'] ) && empty( $args['item_type'] ) ) {
				return false;
			}

			$item_id   = $args['item_id'];
			$item_type = $args['item_type'];

			// Calculate total counts of each reaction and sum of all that reactions.
			$reaction_counts = $this->bb_fetch_reaction_counts( $args );

			// Fetch latest 10 reactions.
			$latest_reaction = $this->bb_get_user_reactions(
				array(
					'item_id'   => $item_id,
					'item_type' => $item_type,
					'per_page'  => 10,
					'order_by'  => 'date_created',
					'order'     => 'DESC',
				)
			);

			$last_10_reactions = ! empty( $latest_reaction['reactions'] ) ? $latest_reaction['reactions'] : array();

			// Fetch last 10 reactions.
			$last_reaction = $this->bb_get_user_reactions(
				array(
					'item_id'   => $args['item_id'],
					'item_type' => $args['item_type'],
					'per_page'  => 10,
					'order_by'  => 'date_created',
					'order'     => 'ASC',
				)
			);

			$first_10_reactions = ! empty( $last_reaction['reactions'] ) ? $last_reaction['reactions'] : array();

			// Prepare data array for bb_add_reactions_data function.
			$data = array(
				'reactions_count'    => $reaction_counts,
				'last_10_reactions'  => $last_10_reactions,
				'first_10_reactions' => $first_10_reactions,
			);

			// Store the data in bb_reactions_data table.
			return $this->bb_add_reactions_data(
				array(
					'name'  => 'item_summary',
					'value' => maybe_serialize( $data ),
					'rel1'  => $item_type,
					'rel2'  => $item_id,
					'rel3'  => '0',
				)
			);
		}

		/**
		 * Add or update total item reaction count.
		 *
		 * @since BuddyBoss 2.4.30
		 *
		 * @param array $args Args of reaction data.
		 *
		 * @return false|int|WP_Error
		 */
		public function bb_total_item_reactions_count( $args ) {

			$item_id   = $args['item_id'];
			$item_type = $args['item_type'];

			$total_item_reactions_count = 0;

			$all_reactions = $this->bb_get_user_reactions(
				array(
					'count_total' => true,
					'per_page'    => 1,
					'paged'       => 1,
					'item_type'   => $item_type,
					'item_id'     => $item_id,
				)
			);

			if ( isset( $all_reactions['total'] ) ) {
				$total_item_reactions_count = $all_reactions['total'];
			}

			$total_item_reactions_data = $this->bb_add_reactions_data(
				array(
					'name'  => 'total_item_reactions_count',
					'value' => $total_item_reactions_count,
					'rel1'  => $item_type,
					'rel2'  => $item_id,
					'rel3'  => '0',
				)
			);

			if ( ! empty( $total_item_reactions_data->id ) ) {
				wp_cache_delete( $total_item_reactions_data->id, self::$rd_cache_group );
			}

			return $total_item_reactions_count;
		}

		/**
		 * Add or update total item reaction count based on reaction id.
		 *
		 * @since BuddyBoss 2.4.30
		 *
		 * @param array $args Args of reaction data.
		 */
		public function bb_total_item_reaction_reactions_count( $args ) {

			$item_id     = $args['item_id'];
			$item_type   = $args['item_type'];
			$reaction_id = $args['reaction_id'];

			$reaction_data = $this->bb_get_reaction_reactions_count( $args );

			$reaction_args = array(
				'name'  => 'total_item_reaction_count',
				'rel1'  => $item_type,
				'rel2'  => $item_id,
				'value' => 0,
				'rel3'  => $reaction_id,
			);
			if ( ! empty( $reaction_data ) ) {
				foreach ( $reaction_data as $value ) {
					$reaction_args['value'] = $value->total;
					$reaction_args['rel3']  = $value->reaction_id;
					$this->bb_add_reactions_data( $reaction_args );
				}
			} else {
				$this->bb_add_reactions_data( $reaction_args );
			}

			$total_item_reactions_data = $this->bb_get_reactions_data(
				array(
					'name'        => 'total_item_reaction_count',
					'rel1'        => $item_type,
					'rel2'        => $item_id,
					'count_total' => false,
				)
			);

			if (
				! empty( $total_item_reactions_data['reaction_data'] ) &&
				! empty( $reaction_data )
			) {
				$reaction_ids         = array_unique( wp_list_pluck( $total_item_reactions_data['reaction_data'], 'rel3' ) );
				$updated_reaction_ids = array_unique( wp_list_pluck( $reaction_data, 'reaction_id' ) );
				$unwated              = array_diff( $reaction_ids, $updated_reaction_ids );
				if ( ! empty( $unwated ) ) {
					foreach ( $unwated as $unwanted_reaction_id ) {
						$this->bb_add_reactions_data(
							array(
								'name'  => 'total_item_reaction_count',
								'value' => 0,
								'rel1'  => $item_type,
								'rel2'  => $item_id,
								'rel3'  => $unwanted_reaction_id,
							)
						);
					}
				}
			}
		}

		/**
		 * Add or update total reaction count.
		 *
		 * @since BuddyBoss 2.4.30
		 *
		 * @return false|int|WP_Error
		 */
		public function bb_total_reactions_count() {
			$total_reactions_count = 0;

			$all_reactions = $this->bb_get_user_reactions(
				array(
					'count_total' => true,
					'per_page'    => 1,
					'paged'       => 1,
				)
			);

			if ( isset( $all_reactions['total'] ) ) {
				$total_reactions_count = $all_reactions['total'];
			}

			$this->bb_add_reactions_data(
				array(
					'name'  => 'total_reactions_count',
					'value' => $total_reactions_count,
					'rel1'  => '0',
					'rel2'  => '0',
					'rel3'  => '0',
				)
			);

			return $total_reactions_count;
		}

		/**
		 * Fetch the single reaction data.
		 *
		 * @since BuddyBoss 2.4.30
		 *
		 * @param int $reaction_data_id Reaction data id.
		 *
		 * @return array|bool|mixed|object|stdClass|null
		 */
		private function bb_get_reaction_data( $reaction_data_id ) {
			global $wpdb;

			if ( empty( $reaction_data_id ) ) {
				return false;
			}

			$reaction_data = wp_cache_get( $reaction_data_id, self::$rd_cache_group );
			if ( false !== $reaction_data ) {
				return $reaction_data;
			}

			// phpcs:ignore
			$sql           = $wpdb->prepare( 'SELECT * FROM ' . self::$reaction_data_table . " WHERE id = %d", $reaction_data_id );
			$reaction_data = $wpdb->get_row( $sql ); // phpcs:ignore

			return $reaction_data;
		}

		/**
		 * Add or update total reaction count.
		 *
		 * @since BuddyBoss 2.4.30
		 *
		 * @param array $args Args of reaction data.
		 *
		 * @return false|int|WP_Error
		 */
		public function bb_fetch_reaction_counts( $args ) {

			$this->bb_total_item_reactions_count( $args ); // total_item_reactions_count.
			$this->bb_total_item_reaction_reactions_count( $args ); // total_item_reaction_count.
			$this->bb_total_reactions_count(); // total_reactions_count.

			// Calculate total counts of each reaction for the item.
			$reaction_counts = $this->bb_get_reaction_reactions_count( $args );

			$total_item_reaction_count = array_reduce(
				$reaction_counts,
				function ( $data, $item ) {
					$data[ $item->reaction_id ] = intval( $item->total );

					return $data;
				},
				array()
			);

			$total_item_reaction_count['total'] = array_sum( $total_item_reaction_count );

			return $total_item_reaction_count;
		}

		/******************* General functions ******************/

		/**
		 * Get registered reaction item types.
		 *
		 * @since BuddyBoss 2.4.30
		 *
		 * @return mixed|null
		 */
		public function bb_get_registered_reaction_item_types() {
			return apply_filters( 'bb_register_reaction_types', $this->registered_reaction_types );
		}

		/**
		 * Validate callback for reaction item type.
		 *
		 * @since BuddyBoss 2.4.30
		 *
		 * @param array $args Array of arguments.
		 *
		 * @return bool|WP_Error
		 */
		public function bb_validate_activity_reaction_request( $args ) {
			$r = bp_parse_args(
				$args,
				array(
					'item_type' => '',
					'item_id'   => '',
				)
			);

			$valid_item_ids = array();

			if (
				! bp_is_active( 'activity' ) ||
				false === bp_is_activity_like_active()
			) {
				return $valid_item_ids;
			}

			$activities_ids = array();
			if ( ! empty( $r['item_id'] ) && 'activity' === $r['item_type'] ) {
				$activities = BP_Activity_Activity::get(
					array(
						'per_page' => 0,
						'fields'   => 'ids',
						'in'       => ! is_array( $r['item_id'] ) ? array( $r['item_id'] ) : $r['item_id'],
					),
				);

				if ( ! empty( $activities['activities'] ) ) {
					$activities_ids = $activities['activities'];
				}
			}

			return $activities_ids;
		}

		/**
		 * Setup Like reaction for the activity.
		 *
		 * @since BuddyBoss 2.4.30
		 *
		 * @return void
		 */
		public function bb_register_activity_like() {
			$reaction_id = (int) bp_get_option( 'bb_reactions_default_like_reaction_added' );
			if ( empty( $reaction_id ) ) {
				$reaction_id = $this->bb_add_reaction( array( 'name' => 'Like' ) );
				if ( ! empty( $reaction_id ) ) {
					bp_update_option( 'bb_reactions_default_like_reaction_added', (int) $reaction_id );
				}
			}
		}

		/**
		 * Function to fetch reaction total count with id.
		 *
		 * @since BuddyBoss 2.4.30
		 *
		 * @param array $args Array of arguments.
		 *
		 * @return array|object|stdClass[]|null
		 */
		public function bb_get_reaction_reactions_count( $args ) {
			global $wpdb;

			$item_id   = $args['item_id'];
			$item_type = $args['item_type'];

			$data_sql = 'SELECT reaction_id, count(id) AS total FROM ' . self::$user_reaction_table . ' WHERE item_type = %s and item_id = %d GROUP BY reaction_id;';
			$sql      = $wpdb->prepare( $data_sql, $item_type, $item_id ); // phpcs:ignore

			return $wpdb->get_results( $sql ); // phpcs:ignore
		}

		/**
		 * Backward compatibility to add user favorite.
		 *
		 * @since BuddyBoss 2.4.30
		 *
		 * @param int   $user_reaction_id User reaction id.
		 * @param array $args             Array of arguments.
		 *
		 * @return void
		 */
		public function bb_add_activity_reaction_data( $user_reaction_id, $args ) {
			if (
				! bp_is_active( 'activity' ) ||
				empty( $args['item_id'] ) ||
				empty( $args['item_type'] ) ||
				empty( $args['user_id'] ) ||
				'activity' !== $args['item_type']
			) {
				return;
			}

			bp_activity_add_user_favorite( $args['item_id'], $args['user_id'] );

		}

		/**
		 * Backward compatibility to remove user favorite.
		 *
		 * @since BuddyBoss 2.4.30
		 *
		 * @param int       $user_reaction_id User reaction id.
		 * @param int|false $deleted          The number of rows deleted, or false on error.
		 * @param object    $get              Reaction data.
		 *
		 * @return void
		 */
		public function bb_remove_activity_reaction_data( $user_reaction_id, $deleted, $get ) {
			if (
				! bp_is_active( 'activity' ) ||
				empty( $get->item_type ) ||
				'activity' !== $get->item_type ||
				! $deleted ||
				empty( $get->user_id ) ||
				empty( $get->item_id )
			) {
				return;
			}

			bp_activity_remove_user_favorite( $get->item_id, $get->user_id );
		}

		/**
		 * Get reaction id from option table.
		 *
		 * @since BuddyBoss 2.4.30
		 *
		 * @return int|void
		 */
		public function bb_reactions_get_like_reaction_id() {
			$reaction_id = (int) bp_get_option( 'bb_reactions_default_like_reaction_added' );

			if ( empty( $reaction_id ) ) {
				return;
			}

			return $reaction_id;
		}
	}
}
