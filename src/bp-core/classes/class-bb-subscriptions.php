<?php
/**
 * Subscriptions class
 *
 * @package BuddyBoss\Core
 * @since BuddyBoss 2.2.6
 */

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'BB_Subscriptions' ) ) {

	/**
	 * BuddyBoss Subscriptions object.
	 *
	 * @since BuddyBoss 2.2.6
	 */
	#[\AllowDynamicProperties]
	class BB_Subscriptions {

		/**
		 * ID of the subscriptions.
		 *
		 * @since BuddyBoss 2.2.6
		 * @var int
		 */
		public $id;

		/**
		 * Blog site ID.
		 *
		 * @since BuddyBoss 2.2.6
		 * @var int
		 */
		public $blog_id;

		/**
		 * User ID.
		 *
		 * @since BuddyBoss 2.2.6
		 * @var int
		 */
		public $user_id;

		/**
		 * Subscription type.
		 *
		 * Core statuses are 'forum', 'topic'.
		 *
		 * @since BuddyBoss 2.2.6
		 * @var string
		 */
		public $type;

		/**
		 * ID of subscription item.
		 *
		 * @since BuddyBoss 2.2.6
		 * @var int
		 */
		public $item_id;

		/**
		 * ID of parent item.
		 *
		 * @since BuddyBoss 2.2.6
		 * @var int
		 */
		public $secondary_item_id;

		/**
		 * Status of the subscription item.
		 *
		 * @since BuddyBoss 2.2.6
		 * @var int
		 */
		public $status;

		/**
		 * Date the subscription was created.
		 *
		 * @since BuddyBoss 2.2.6
		 * @var string
		 */
		public $date_recorded;

		/**
		 * Title of the subscription item.
		 *
		 * @since BuddyBoss 2.2.6
		 * @var string
		 */
		public $title;

		/**
		 * Description of the subscription item.
		 *
		 * @since BuddyBoss 2.2.6
		 * @var string
		 */
		public $description_html;

		/**
		 * Parent of the subscription item.
		 *
		 * @since BuddyBoss 2.2.6
		 * @var string
		 */
		public $parent_html;

		/**
		 * Image of the subscription item.
		 *
		 * @since BuddyBoss 2.2.6
		 * @var string
		 */
		public $icon;

		/**
		 * Link of the subscription item.
		 *
		 * @since BuddyBoss 2.2.6
		 * @var string
		 */
		public $link;

		/**
		 * Constructor method.
		 *
		 * @since BuddyBoss 2.2.6
		 *
		 * @param int|null $id              Optional. If the ID of an existing subscriptions is provided,
		 *                                  the object will be pre-populated with info about that subscriptions.
		 * @param bool     $populate_extras Whether to fetch extra information. Default: true.
		 */
		public function __construct( $id = null, $populate_extras = true ) {
			if ( ! empty( $id ) ) {
				$this->id = (int) $id;
				$this->populate();

				if ( ! empty( $populate_extras ) ) {
					$this->populate_extras();
				}
			}
		}

		/**
		 * Set up data about the current subscriptions.
		 *
		 * @since BuddyBoss 2.2.6
		 */
		public function populate() {
			global $wpdb;

			// Get table name.
			$subscription_tbl = self::get_subscription_tbl();

			// Check cache for subscription data.
			$subscription = wp_cache_get( $this->id, 'bb_subscriptions' );

			// Cache missed, so query the DB.
			if ( false === $subscription ) {
				$subscription = $wpdb->get_row( $wpdb->prepare( "SELECT sc.* FROM {$subscription_tbl} sc WHERE sc.id = %d", $this->id ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared

				wp_cache_set( $this->id, $subscription, 'bb_subscriptions' );
			}

			// Bail if no subscription is found.
			if ( empty( $subscription ) || is_wp_error( $subscription ) ) {
				$this->id = 0;
				return;
			}

			/**
			 * Pre validate the subscription before fetch.
			 *
			 * @since BuddyBoss 2.2.6
			 *
			 * @param boolean $validate     Whether to check the subscriptions is valid or not.
			 * @param object  $subscription Subscription object.
			 */
			$validate = apply_filters( 'bb_subscriptions_pre_validate', true, $subscription );

			if ( empty( $validate ) ) {
				$this->id = 0;
				return;
			}

			// Subscription found so set up the object variables.
			$this->id                = (int) $subscription->id;
			$this->blog_id           = (int) $subscription->blog_id;
			$this->user_id           = (int) $subscription->user_id;
			$this->type              = $subscription->type;
			$this->item_id           = (int) $subscription->item_id;
			$this->secondary_item_id = (int) $subscription->secondary_item_id;
			$this->status            = (int) $subscription->status;
			$this->date_recorded     = $subscription->date_recorded;
		}

		/**
		 * Populates extra fields such as render data.
		 *
		 * @since BuddyBoss 2.2.6
		 */
		public function populate_extras() {

			if ( empty( $this->type ) ) {
				return;
			}

			$type_data = bb_register_subscriptions_types( $this->type );

			if (
				! empty( $type_data ) &&
				! empty( $type_data['items_callback'] ) &&
				is_callable( $type_data['items_callback'] )
			) {
				$item_data = call_user_func(
					$type_data['items_callback'],
					array( $this )
				);

				$item_extra = array(
					'title'            => '',
					'description_html' => '',
					'parent_html'      => '',
					'icon'             => array(),
					'link'             => '',
				);

				if ( ! empty( $item_data ) && ! empty( current( $item_data ) ) ) {
					$item_extra = bp_parse_args( (array) current( $item_data ), $item_extra );
				}

				$this->title            = $item_extra['title'];
				$this->description_html = $item_extra['description_html'];
				$this->parent_html      = $item_extra['parent_html'];
				$this->icon             = $item_extra['icon'];
				$this->link             = $item_extra['link'];
			}
		}

		/**
		 * Save the current subscription to the database.
		 *
		 * @since BuddyBoss 2.2.6
		 *
		 * @return bool|WP_Error True on success, false on failure.
		 */
		public function save() {
			global $wpdb;

			// Get table name.
			$subscription_tbl = self::get_subscription_tbl();

			$this->blog_id           = apply_filters( 'bb_subscriptions_blog_id_before_save', $this->blog_id, $this->id );
			$this->user_id           = apply_filters( 'bb_subscriptions_user_id_before_save', $this->user_id, $this->id );
			$this->type              = apply_filters( 'bb_subscriptions_type_before_save', $this->type, $this->id );
			$this->item_id           = apply_filters( 'bb_subscriptions_item_id_before_save', $this->item_id, $this->id );
			$this->secondary_item_id = apply_filters( 'bb_subscriptions_secondary_item_id_before_save', $this->secondary_item_id, $this->id );
			$this->status            = apply_filters( 'bb_subscriptions_status_before_save', $this->status, $this->id );
			$this->date_recorded     = apply_filters( 'bb_subscriptions_date_recorded_before_save', $this->date_recorded, $this->id );

			/**
			 * Fires before the current subscription item gets saved.
			 *
			 * Please use this hook to filter the properties above. Each part will be passed in.
			 *
			 * @since BuddyBoss 2.2.6
			 *
			 * @param BB_Subscriptions $this Current instance of the subscription item being saved. Passed by reference.
			 */
			do_action_ref_array( 'bb_subscriptions_before_save', array( &$this ) );

			// Subscription need user ID.
			if ( empty( $this->user_id ) ) {
				if ( isset( $this->error_type ) && 'wp_error' === $this->error_type ) {
					return new WP_Error( 'bb_subscriptions_empty_user_id', __( 'The user ID is required to create a subscription.', 'buddyboss' ) );
				} else {
					return false;
				}

				// Subscription need Type.
			} elseif ( empty( $this->type ) ) {
				if ( isset( $this->error_type ) && 'wp_error' === $this->error_type ) {
					return new WP_Error( 'bb_subscriptions_empty_type', __( 'The type is required to create a subscription.', 'buddyboss' ) );
				} else {
					return false;
				}

				// Subscription need Item ID.
			} elseif ( empty( $this->item_id ) ) {
				if ( isset( $this->error_type ) && 'wp_error' === $this->error_type ) {
					return new WP_Error( 'bb_subscriptions_empty_item_id', __( 'The item ID is required to create a subscription.', 'buddyboss' ) );
				} else {
					return false;
				}
			}

			/**
			 * Fires before the current subscription item gets saved.
			 *
			 * Please use this filter to validate subscription request. Each part will be passed in.
			 *
			 * @since BuddyBoss 2.2.6
			 *
			 * @param bool             $is_validate True when subscription request correct otherwise false/WP_Error. Default true.
			 * @param BB_Subscriptions $this        Current instance of the subscription item being saved.
			 */
			$is_validate = apply_filters( 'bb_subscriptions_validate_before_save', true, $this );

			if ( ! $is_validate || is_wp_error( $is_validate ) ) {
				return $is_validate;
			}

			if ( ! empty( $this->id ) ) {
				$sql = $wpdb->prepare(
					// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
					"UPDATE {$subscription_tbl} SET
						blog_id = %d,
						user_id = %d,
						type = %s,
						item_id = %d,
						secondary_item_id = %d,
						status = %d,
						date_recorded = %s
					WHERE
						id = %d
					",
					$this->blog_id,
					$this->user_id,
					$this->type,
					$this->item_id,
					$this->secondary_item_id,
					$this->status,
					$this->date_recorded,
					$this->id
				);
			} else {
				$sql = $wpdb->prepare(
					// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
					"INSERT INTO {$subscription_tbl} (
						blog_id,
						user_id,
						type,
						item_id,
						secondary_item_id,
						status,
						date_recorded
					) VALUES (
						%d, %d, %s, %d, %d, %d, %s
					)",
					$this->blog_id,
					$this->user_id,
					$this->type,
					$this->item_id,
					$this->secondary_item_id,
					$this->status,
					$this->date_recorded
				);
			}

			// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			if ( false === $wpdb->query( $sql ) ) {
				if ( isset( $this->error_type ) && 'wp_error' === $this->error_type ) {
					return new WP_Error( 'bb_subscriptions_cannot_create', __( 'There is an error while adding the subscription.', 'buddyboss' ) );
				} else {
					return false;
				}
			}

			if ( empty( $this->id ) ) {
				$this->id = $wpdb->insert_id;
			}

			/**
			 * Fires after the current subscription item has been saved.
			 *
			 * @since BuddyBoss 2.2.6
			 *
			 * @param BB_Subscriptions $this Current instance of the subscription item that was saved. Passed by reference.
			 */
			do_action_ref_array( 'bb_subscriptions_after_save', array( &$this ) );

			return $this->id;
		}

		/**
		 * Update the subscription secondary item ID.
		 *
		 * @since BuddyBoss 2.2.6
		 *
		 * @param array $args Subscription arguments.
		 *
		 * @return bool True on success, false on failure.
		 */
		public static function update_secondary_item_id( $args = array() ) {
			global $wpdb;

			$r = bp_parse_args(
				$args,
				array(
					'blog_id'           => get_current_blog_id(),
					'type'              => '',
					'item_id'           => 0,
					'secondary_item_id' => 0,
				),
				'bb_update_subscription'
			);

			// Get table name.
			$subscription_tbl = self::get_subscription_tbl();

			// phpcs:ignore
			$update = $wpdb->update(
				$subscription_tbl,
				array(
					'secondary_item_id' => $r['secondary_item_id'],
				),
				array(
					'blog_id' => $r['blog_id'],
					'type'    => $r['type'],
					'item_id' => $r['item_id'],
				)
			);

			if ( false === $update ) {
				return false;
			}

			/**
			 * Fires after the subscription secondary item ID has been updated.
			 *
			 * @since BuddyBoss 2.2.6
			 *
			 * @param array $r Subscription arguments.
			 */
			do_action_ref_array( 'bb_subscriptions_after_update_secondary_item_id', array( $r ) );

			return true;
		}

		/**
		 * Delete the current subscription.
		 *
		 * @since BuddyBoss 2.2.6
		 *
		 * @return bool True on success, false on failure.
		 */
		public function delete() {
			global $wpdb;

			// Get table name.
			$subscription_tbl = self::get_subscription_tbl();

			/**
			 * Fires before the deletion of a subscriptions.
			 *
			 * @since BuddyBoss 2.2.6
			 *
			 * @param BB_Subscriptions $this Current instance of the subscription item being deleted. Passed by reference.
			 * @param int              $id   ID of subscription.
			 */
			do_action_ref_array( 'bb_subscriptions_before_delete_subscription', array( &$this, $this->id ) );

			// Finally, remove the subscription entry from the DB.
			if ( ! $wpdb->query( $wpdb->prepare( "DELETE FROM {$subscription_tbl} WHERE id = %d", $this->id ) ) ) { // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
				return false;
			}

			/**
			 * Fires after the deletion of a subscriptions.
			 *
			 * @since BuddyBoss 2.2.6
			 *
			 * @param BB_Subscriptions $this Current instance of the subscription item being deleted. Passed by reference.
			 * @param int              $id   ID of subscription.
			 */
			do_action_ref_array( 'bb_subscriptions_after_delete_subscription', array( &$this, $this->id ) );

			return true;
		}

		/**
		 * Update the subscription items status.
		 *
		 * @since BuddyBoss 2.2.6
		 *
		 * @param string $type    Type subscription item.
		 * @param int    $item_id The subscription item ID.
		 * @param int    $status  The subscription item status, 1 = active, 0 = inactive.
		 * @param int    $blog_id The site ID. Default current site ID.
		 *
		 * @return bool
		 */
		public static function update_status( $type, $item_id, $status, $blog_id = 0 ) {
			global $wpdb;

			// Get table name.
			$subscription_tbl = self::get_subscription_tbl();

			/**
			 * Fires before the update status of a subscriptions.
			 *
			 * @since BuddyBoss 2.2.6
			 *
			 * @param string $type    Type subscription item.
			 * @param int    $item_id The subscription item ID.
			 * @param int    $status  The subscription item status, 1 = active, 0 = inactive.
			 * @param int    $blog_id The site ID.
			 */
			do_action_ref_array( 'bb_subscriptions_before_update_subscription_status', array( $type, $item_id, $status, $blog_id ) );

			$where = array(
				'type'    => $type,
				'item_id' => $item_id,
			);

			if ( ! empty( $blog_id ) ) {
				$where['blog_id'] = $blog_id;
			}

			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$is_updated = $wpdb->update(
				$subscription_tbl,
				array(
					'status' => $status,
				),
				$where
			);

			if ( ! is_int( $is_updated ) ) {
				return false;
			}

			/**
			 * Fires after the update status of a subscriptions.
			 *
			 * @since BuddyBoss 2.2.6
			 *
			 * @param string $type    Type subscription item.
			 * @param int    $item_id The subscription item ID.
			 * @param int    $status  The subscription item status, 1 = active, 0 = inactive.
			 * @param int    $blog_id The site ID.
			 */
			do_action_ref_array( 'bb_subscriptions_after_update_subscription_status', array( $type, $item_id, $status, $blog_id ) );

			return true;
		}

		/**
		 * Magic getter.
		 *
		 * @since BuddyBoss 2.2.6
		 *
		 * @param string $key Property name.
		 *
		 * @return mixed
		 */
		public function __get( $key ) {
			return $this->{$key} ?? null;
		}

		/**
		 * Magic issetter.
		 *
		 * Used to maintain backward compatibility for properties that are now
		 * accessible only via magic method.
		 *
		 * @since BuddyBoss 2.2.6
		 *
		 * @param string $key Property name.
		 * @return bool
		 */
		public function __isset( $key ) {
			return isset( $this->{$key} );
		}

		/**
		 * Magic setter.
		 *
		 * Used to maintain backward compatibility for properties that are now
		 * accessible only via magic method.
		 *
		 * @since BuddyBoss 2.2.6
		 *
		 * @param string $key   Property name.
		 * @param mixed  $value Property value.
		 */
		public function __set( $key, $value ) {
			$this->{$key} = $value;
		}

		/** Static Methods ****************************************************/
		/**
		 * Query for subscriptions.
		 *
		 * @since BuddyBoss 2.2.6
		 *
		 * @param array $args {
		 *     Array of parameters. All items are optional.
		 *
		 *     @type array|string $type               Optional. Array or comma-separated list of subscription types.
		 *                                            'Forum', 'topic', etc...
		 *                                            Default: null.
		 *     @type int          $blog_id            Optional. Get subscription site wise. Default current site ID.
		 *     @type int          $user_id            Optional. If provided, results will be limited to subscriptions.
		 *                                            Default: null.
		 *     @type int          $item_id            Optional. If provided, results will be limited to subscriptions.
		 *                                            Default: null.
		 *     @type int          $secondary_item_id  Optional. If provided, results will be limited to subscriptions.
		 *                                            Default: null.
		 *     @type bool         $status             Optional. Get all active subscription if true otherwise return inactive.
		 *                                            Default: true.
		 *     @type string       $order_by           Optional. Property to sort by. 'date_recorded', 'item_id',
		 *                                            'secondary_item_id', 'user_id', 'id', 'type'
		 *                                            'total_subscription_count', 'random', 'include'.
		 *                                            Default: 'date_recorded'.
		 *     @type string       $order              Optional. Sort order. 'ASC' or 'DESC'. Default: 'DESC'.
		 *     @type int          $per_page           Optional. Number of items to return per page of results.
		 *                                            Default: null (no limit).
		 *     @type int          $page               Optional. Page offset of results to return.
		 *     @type array|string $include            Optional. Array or comma-separated list of subscription IDs.
		 *                                            Results will include the listed subscriptions. Default: false.
		 *     @type array|string $exclude            Optional. Array or comma-separated list of subscription IDs.
		 *                                            Results will exclude the listed subscriptions. Default: false.
		 *     @type string       $fields             Which fields to return. Specify 'id' to fetch a list of IDs.
		 *                                            Default: 'all' (return BP_Subscription objects).
		 *     @type array|string $include_items      Optional. Array or comma-separated list of subscription item IDs.
		 *                                            Results will include the listed subscriptions. Default: false.
		 *     @type array|string $exclude_items      Optional. Array or comma-separated list of subscription item IDs.
		 *                                            Results will exclude the listed subscriptions. Default: false.
		 *     @type bool         $count              Optional. Fetch total count of all subscriptions matching non-
		 *                                            paginated query params when it false.
		 *                                            Default: true.
		 *     @type bool         $cache              Optional. Fetch the fresh result instead of cache when it true.
		 *                                            Default: false.
		 * }
		 * @return array {
		 *     @type array $subscriptions Array of subscription objects returned by the
		 *                                paginated query. (IDs only if `fields` is set to `id`.)
		 *     @type int   $total         Total count of all subscriptions matching non-
		 *                                paginated query params.
		 * }
		 */
		public static function get( $args = array() ) {
			global $wpdb;

			$defaults = array(
				'type'              => array(),
				'blog_id'           => get_current_blog_id(),
				'user_id'           => 0,
				'item_id'           => 0,
				'secondary_item_id' => 0,
				'status'            => null,
				'order_by'          => 'date_recorded',
				'order'             => 'DESC',
				'per_page'          => null,
				'page'              => null,
				'include'           => false,
				'exclude'           => false,
				'fields'            => 'all',
				'include_items'     => false,
				'exclude_items'     => false,
				'count'             => true,
				'cache'             => true,
			);

			$r = bp_parse_args( $args, $defaults, 'bb_subscriptions_subscription_get' );

			// Sanitize the column name.
			$r['fields'] = self::validate_column( $r['fields'] );

			// Get the database table name.
			$subscription_tbl = self::get_subscription_tbl();

			$results = array();
			$sql     = array(
				'select'     => 'SELECT DISTINCT sc.id',
				'from'       => $subscription_tbl . ' sc',
				'where'      => '',
				'order_by'   => '',
				'pagination' => '',
			);

			$where_conditions = array();

			if ( ! empty( $r['type'] ) ) {
				if ( ! is_array( $r['type'] ) ) {
					$r['type'] = preg_split( '/[\s,]+/', $r['type'] );
				}
				$r['type']                = array_map( 'sanitize_title', $r['type'] );
				$type_in                  = "'" . implode( "','", $r['type'] ) . "'";
				$where_conditions['type'] = "sc.type IN ({$type_in})";
			}

			if ( ! empty( $r['blog_id'] ) ) {
				$where_conditions['blog_id'] = $wpdb->prepare( 'sc.blog_id = %d', $r['blog_id'] );
			}

			if ( ! empty( $r['user_id'] ) ) {
				$where_conditions['user_id'] = $wpdb->prepare( 'sc.user_id = %d', $r['user_id'] );
			}

			if ( ! empty( $r['item_id'] ) ) {
				$where_conditions['item_id'] = $wpdb->prepare( 'sc.item_id = %d', $r['item_id'] );
			}

			if ( ! empty( $r['secondary_item_id'] ) ) {
				$where_conditions['secondary_item_id'] = $wpdb->prepare( 'sc.secondary_item_id = %d', $r['secondary_item_id'] );
			}

			if ( null !== $r['status'] ) {
				$where_conditions['status'] = $wpdb->prepare( 'sc.status = %d', (int) $r['status'] );
			}

			if ( ! empty( $r['include'] ) ) {
				$include                     = implode( ',', wp_parse_id_list( $r['include'] ) );
				$where_conditions['include'] = "sc.id IN ({$include})";
			}

			if ( ! empty( $r['exclude'] ) ) {
				$exclude                     = implode( ',', wp_parse_id_list( $r['exclude'] ) );
				$where_conditions['exclude'] = "sc.id NOT IN ({$exclude})";
			}

			if ( ! empty( $r['include_items'] ) ) {
				$include                           = implode( ',', wp_parse_id_list( $r['include_items'] ) );
				$where_conditions['include_items'] = "sc.item_id IN ({$include})";
			}

			if ( ! empty( $r['exclude_items'] ) ) {
				$exclude                           = implode( ',', wp_parse_id_list( $r['exclude_items'] ) );
				$where_conditions['exclude_items'] = "sc.item_id NOT IN ({$exclude})";
			}

			/* Order/orderby ********************************************/
			$order           = bp_esc_sql_order( $r['order'] );
			$order_by        = $r['order_by'];
			$sql['order_by'] = "ORDER BY {$order_by} {$order}";

			// Random order is a special case.
			if ( 'rand()' === $order_by ) {
				$sql['order_by'] = 'ORDER BY rand()';
			} elseif ( ! empty( $r['include'] ) && 'in' === $order_by ) { // Support order by fields for generally.
				$field_data      = implode( ',', array_map( 'absint', $r['include'] ) );
				$sql['order_by'] = "ORDER BY FIELD(sc.id, {$field_data})";
			}

			if ( ! empty( $r['per_page'] ) && ! empty( $r['page'] ) && -1 !== $r['per_page'] ) {
				$sql['pagination'] = $wpdb->prepare( 'LIMIT %d, %d', intval( ( $r['page'] - 1 ) * $r['per_page'] ), intval( $r['per_page'] ) );
			}

			/**
			 * Filters the Where SQL statement.
			 *
			 * @since BuddyBoss 2.2.6
			 *
			 * @param array $r                Array of parsed arguments for the get method.
			 * @param array $where_conditions Where conditions SQL statement.
			 */
			$where_conditions = apply_filters( 'bb_subscriptions_get_where_conditions', $where_conditions, $r );

			$where = '';
			if ( ! empty( $where_conditions ) ) {
				$sql['where'] = implode( ' AND ', $where_conditions );
				$where        = "WHERE {$sql['where']}";
			}

			/**
			 * Filters the From SQL statement.
			 *
			 * @since BuddyBoss 2.2.6
			 *
			 * @param array $r    Array of parsed arguments for the get method.
			 * @param string $sql From SQL statement.
			 */
			$sql['from'] = apply_filters( 'bb_subscriptions_get_join_sql', $sql['from'], $r );

			$paged_subscriptions_sql = "{$sql['select']} FROM {$sql['from']} {$where} {$sql['order_by']} {$sql['pagination']}";

			/**
			 * Filters the pagination SQL statement.
			 *
			 * @since BuddyBoss 2.2.6
			 *
			 * @param string $value Concatenated SQL statement.
			 * @param array  $sql   Array of SQL parts before concatenation.
			 * @param array  $r     Array of parsed arguments for the get method.
			 */
			$paged_subscriptions_sql = apply_filters( 'bb_subscriptions_get_paged_subscriptions_sql', $paged_subscriptions_sql, $sql, $r );

			$cached = bp_core_get_incremented_cache( $paged_subscriptions_sql, 'bb_subscriptions' );
			if ( false === $cached || false === $r['cache'] ) {
				$paged_subscription_ids = $wpdb->get_col( $paged_subscriptions_sql ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
				bp_core_set_incremented_cache( $paged_subscriptions_sql, 'bb_subscriptions', $paged_subscription_ids );
			} else {
				$paged_subscription_ids = $cached;
			}

			if ( 'id' === $r['fields'] ) {
				// We only want the IDs.
				$paged_subscriptions = array_map( 'intval', $paged_subscription_ids );
			} else {
				$uncached_subscription_ids = bp_get_non_cached_ids( $paged_subscription_ids, 'bb_subscriptions' );
				if ( $uncached_subscription_ids ) {
					$subscription_ids_sql      = implode( ',', array_map( 'intval', $uncached_subscription_ids ) );
					$subscription_data_objects = $wpdb->get_results( "SELECT sc.* FROM {$subscription_tbl} sc WHERE sc.id IN ({$subscription_ids_sql})" ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
					foreach ( $subscription_data_objects as $subscription_data_object ) {
						wp_cache_set( $subscription_data_object->id, $subscription_data_object, 'bb_subscriptions' );
					}
				}

				$paged_subscriptions = array();
				foreach ( $paged_subscription_ids as $paged_subscription_id ) {
					$paged_subscriptions[] = new BB_Subscriptions( $paged_subscription_id, ( 'all' === $r['fields'] ) );
				}

				if ( 'all' !== $r['fields'] ) {
					$paged_subscriptions = array_column( $paged_subscriptions, $r['fields'] );
				}
			}

			// Set in response array.
			$results['subscriptions'] = $paged_subscriptions;

			// If count is true then will get total subscription counts.
			if ( ! empty( $r['count'] ) ) {
				// Find the total number of subscriptions in the results set.
				$total_subscriptions_sql = "SELECT COUNT(DISTINCT sc.id) FROM {$sql['from']} $where";

				/**
				 * Filters the SQL used to retrieve total subscriptions results.
				 *
				 * @since BuddyBoss 2.2.6
				 *
				 * @param string $total_subscriptions_sql Concatenated SQL statement used for retrieving total subscriptions results.
				 * @param array  $sql                     Array of SQL parts for the query.
				 * @param array  $r                       Array of parsed arguments for the get method.
				 */
				$total_subscriptions_sql = apply_filters( 'bb_subscriptions_get_total_subscriptions_sql', $total_subscriptions_sql, $sql, $r );

				$cached = bp_core_get_incremented_cache( $total_subscriptions_sql, 'bb_subscriptions' );
				if ( false === $cached || false === $r['cache'] ) {
					$total_subscriptions = (int) $wpdb->get_var( $total_subscriptions_sql ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
					bp_core_set_incremented_cache( $total_subscriptions_sql, 'bb_subscriptions', array( $total_subscriptions ) );
				} else {
					$total_subscriptions = (int) ( ! empty( $cached ) ? current( $cached ) : 0 );
				}

				// Set in response array.
				$results['total'] = $total_subscriptions;
			}

			return $results;
		}

		/**
		 * Validate the column name.
		 *
		 * @since BuddyBoss 2.2.6
		 *
		 * @param string $column Column name of database.
		 *
		 * @return string.
		 */
		public static function validate_column( $column ) {
			$columns = self::get_tbl_columns();

			if ( 'all' === $column ) {
				return $column;
			} elseif ( in_array( $column, $columns, true ) ) {
				return $column;
			}

			return 'all';
		}

		/**
		 * Get database table name for subscription.
		 *
		 * @since BuddyBoss 2.2.6
		 *
		 * @return string.
		 */
		public static function get_subscription_tbl() {
			global $wpdb;

			if ( is_multisite() ) {
				switch_to_blog( 1 );
				$subscription_tbl = $wpdb->base_prefix . 'bb_notifications_subscriptions';
				restore_current_blog();
			} else {
				$subscription_tbl = $wpdb->base_prefix . 'bb_notifications_subscriptions';
			}

			return $subscription_tbl;
		}

		/**
		 * Supported DB columns.
		 *
		 * See the 'bb_notifications_subscriptions' DB table schema.
		 *
		 * @since BuddyBoss 2.2.6
		 * @return string[]
		 */
		public static function get_tbl_columns() {
			return array(
				'id',
				'blog_id',
				'user_id',
				'type',
				'item_id',
				'secondary_item_id',
				'status',
				'date_recorded',
			);
		}
	}
}
