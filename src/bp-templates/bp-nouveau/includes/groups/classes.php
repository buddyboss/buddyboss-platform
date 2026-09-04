<?php
/**
 * Groups classes
 *
 * @since BuddyPress 3.0.0
 * @version 3.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Query to get members that are not already members of the group
 *
 * @since BuddyPress 3.0.0
 */
class BP_Nouveau_Group_Invite_Query extends BP_User_Query {
	/**
	 * Array of group member ids, cached to prevent redundant lookups
	 *
	 * @var null|array Null if not yet defined, otherwise an array of ints
	 * @since BuddyPress 3.0.0
	 */
	protected $group_member_ids;

	/**
	 * Set up action hooks
	 *
	 * @since BuddyPress 3.0.0
	 */
	public function setup_hooks() {
		add_action( 'bp_pre_user_query_construct', array( $this, 'build_exclude_args' ) );
		add_action( 'bp_pre_user_query', array( $this, 'build_meta_query' ) );
	}

	/**
	 * Exclude group members from the user query as it's not needed to invite members to join the group.
	 *
	 * @since BuddyPress 3.0.0
	 */
	public function build_exclude_args() {
		$this->query_vars = bp_parse_args( $this->query_vars, array(
			'group_id'     => 0,
			'is_confirmed' => true,
		) );

		$group_member_ids = $this->get_group_member_ids();

		// We want to get users that are already members of the group
		$type = 'exclude';

		// We want to get invited users who did not confirmed yet
		if ( false === $this->query_vars['is_confirmed'] ) {
			$type = 'include';
		}

		// We have to exclude users if set on $this->query_vars_raw["exclude"] parameter
		if ( ! empty( $this->query_vars_raw["exclude"] ) ) {
			$group_member_ids = array_merge( $group_member_ids, explode(',', $this->query_vars_raw["exclude"] ) );
		}

		if ( ! empty( $group_member_ids ) ) {
			$this->query_vars[ $type ] = $group_member_ids;
		}
	}

	/**
	 * Get the members of the queried group
	 *
	 * @since BuddyPress 3.0.0
	 *
	 * @return array $ids User IDs of relevant group member ids
	 */
	protected function get_group_member_ids() {
		global $wpdb;

		if ( is_array( $this->group_member_ids ) ) {
			return $this->group_member_ids;
		}

		// Fetch **all** invited users.
		$pending_invites = groups_get_invites( array(
			'item_id'     => $this->query_vars['group_id'],
			'invite_sent' => 'sent',
			'fields'      => 'user_ids'
		) );

		// This is a clue that we only want the invitations.
		if ( false === $this->query_vars['is_confirmed'] ) {
			return $pending_invites;
		}

		/**
		 * Otherwise, we want group members _and_ users with outstanding invitations,
		 * because we're doing an "exclude" query.
		 */
		$bp  = buddypress();
		$sql = array(
			'select'  => "SELECT user_id FROM {$bp->groups->table_name_members}",
			'where'   => array(),
			'orderby' => '',
			'order'   => '',
			'limit'   => '',
		);

		/** WHERE clauses *****************************************************/

		// Group id
		$sql['where'][] = $wpdb->prepare( 'group_id = %d', $this->query_vars['group_id'] );

		if ( false === $this->query_vars['is_confirmed'] ) {
			$sql['where'][] = $wpdb->prepare( 'is_confirmed = %d', (int) $this->query_vars['is_confirmed'] );
			$sql['where'][] = 'inviter_id != 0';
		}

		// Join the query part
		$sql['where'] = ! empty( $sql['where'] ) ? 'WHERE ' . implode( ' AND ', $sql['where'] ) : '';

		/** ORDER BY clause ***************************************************/
		$sql['orderby'] = 'ORDER BY date_modified';
		$sql['order']   = 'DESC';

		/** LIMIT clause ******************************************************/
		$this->group_member_ids = $wpdb->get_col( "{$sql['select']} {$sql['where']} {$sql['orderby']} {$sql['order']} {$sql['limit']}" );

		return array_merge( $this->group_member_ids, $pending_invites );
	}

	/**
	 * Build the meta query for the potential group invites user query.
	 *
	 * @since BuddyPress 3.0.0
	 * @since BuddyBoss [BBVERSION] Resolve a lone `NOT EXISTS` clause to an excluded user ID list
	 *                              and join the `WP_Meta_Query` fallback on the query's uid column.
	 *
	 * @param BP_User_Query $bp_user_query The user query being built.
	 */
	public function build_meta_query( BP_User_Query $bp_user_query ) {
		global $wpdb;

		if ( isset( $this->query_vars['scope'] ) && 'members' === $this->query_vars['scope'] && isset( $this->query_vars['meta_query'] ) ) {
			$meta_query = $this->query_vars['meta_query'];

			// A `NOT EXISTS` anti-join scans the whole user table, so query the excluded ids
			// instead. The fast path is deliberately restricted to the one meta key the invite
			// screen uses: the AJAX handler merges `$_POST` wholesale, so any invite-capable
			// member could otherwise point this lookup at an arbitrary key and force an uncached
			// scan of the whole usermeta table. Every other shape (relation, keyed/nested
			// clauses, compare_key, array keys, any other key) keeps the WP_Meta_Query path
			// below, whose semantics the fast path cannot replicate.
			if (
				is_array( $meta_query ) &&
				1 === count( $meta_query ) &&
				isset( $meta_query[0]['key'], $meta_query[0]['compare'] ) &&
				'_bp_nouveau_restrict_invites_to_friends' === $meta_query[0]['key'] &&
				is_string( $meta_query[0]['compare'] ) &&
				'NOT EXISTS' === strtoupper( $meta_query[0]['compare'] ) &&
				( ! isset( $meta_query[0]['compare_key'] ) || '=' === $meta_query[0]['compare_key'] )
			) {

				/**
				 * Filters the hard ceiling on excluded user IDs resolved inline for group invites.
				 *
				 * This is only a memory backstop for pathological data. It is deliberately not a
				 * performance boundary: the inline `NOT IN` gets *faster* as the excluded set
				 * grows, because fewer rows survive to the sort, while the `WP_Meta_Query`
				 * anti-join it falls back to is roughly 3x slower. The default keeps the emitted
				 * SQL under a conservative 1MB `max_allowed_packet`.
				 *
				 * Return `0` to force the legacy path whenever any user holds the meta, or a
				 * negative value to force it unconditionally. The distinction matters: with
				 * nobody opted in there is nothing to exclude, so a `0` ceiling still returns
				 * on the fast path without ever building a `WP_Meta_Query` - and third-party
				 * code filtering `get_meta_sql` for this clause would never see it. A negative
				 * ceiling is the only way to guarantee the clause is always built.
				 *
				 * @since BuddyBoss [BBVERSION]
				 *
				 * @param int $limit Maximum number of excluded user IDs. Default 100000.
				 *                   `0` disables the fast path once anyone holds the meta;
				 *                   any negative value disables it unconditionally.
				 */
				$raw_limit = (int) apply_filters( 'bb_nouveau_group_invites_excluded_ids_limit', 100000 );

				// A negative ceiling opts out of the fast path entirely. Handled as an
				// immediate over-ceiling verdict so no lookup runs and no cache is touched.
				$fast_path_disabled = $raw_limit < 0;
				$limit              = min( max( 0, $raw_limit ), PHP_INT_MAX - 1 );

				/**
				 * Filters the largest excluded-ID list that is written to the object cache.
				 *
				 * Deliberately separate from the ceiling above, because the two are sized
				 * against different limits. The ceiling bounds the emitted SQL against
				 * `max_allowed_packet` — 100k ids is ~0.7MB. The cached payload is a
				 * serialised PHP array, and the same 100k ids are ~1.6MB, which exceeds
				 * memcached's 1MB default item size: such a set is dropped silently, so every
				 * request re-pays the lookup while believing it cached the result. Redis has
				 * no comparable per-item limit, so no second ceiling is imposed by default —
				 * lower this on a memcached backend with a small item size.
				 *
				 * A list larger than this is still used inline for the request; it is only
				 * not stored.
				 *
				 * @since BuddyBoss [BBVERSION]
				 *
				 * @param int $cacheable Maximum number of ids to store. Defaults to $limit.
				 * @param int $limit     The resolved inline ceiling.
				 */
				$cacheable = max( 0, (int) apply_filters( 'bb_nouveau_group_invites_cacheable_ids_limit', $limit, $limit ) );

				// Capture the cache version BEFORE reading or querying. Invalidation resets the
				// incrementor, so a rebuild that raced a member toggling the setting writes under
				// a key nobody will read again, instead of overwriting the fresh state with its
				// pre-toggle snapshot. All three keys below carry it, so one reset clears them.
				$incrementor   = bp_core_get_incrementor( 'bb_nouveau_group_invites' );
				$list_key      = 'bb_restrict_invites_ids_' . $incrementor;
				$ceiling_key   = 'bb_restrict_invites_over_' . $limit . '_' . $incrementor;
				$lock_key      = 'bb_restrict_invites_lock_' . $incrementor;
				$lookup_failed = false;

				// Past the ceiling the lookup result is truncated and discarded, so remember that
				// verdict briefly: re-running a throwaway lookup on every request costs more than
				// the anti-join it falls back to. Keyed by `$limit` so a caller that filters the
				// ceiling down cannot force the fallback on callers using a different ceiling.
				$over_ceiling = $fast_path_disabled ? true : (bool) wp_cache_get( $ceiling_key, 'bb_nouveau_group_invites' );
				$excluded_ids = $over_ceiling ? false : wp_cache_get( $list_key, 'bb_nouveau_group_invites' );

				// A miss returns false; anything not an array is unusable, so re-query rather
				// than counting it. A cached empty array is a valid hit and is kept.
				if ( ! $over_ceiling && ! is_array( $excluded_ids ) ) {

					// Reduces — but does not bound — a rebuild stampede: the cache goes cold on
					// every toggle and this runs for each concurrent viewer. The winner rebuilds
					// while a loser re-reads once, which is often enough to catch a just-finished
					// rebuild; a loser that still misses queries rather than blocking the request.
					$lock_held_elsewhere = ! wp_cache_add( $lock_key, 1, 'bb_nouveau_group_invites', 30 );

					if ( $lock_held_elsewhere ) {
						$excluded_ids = wp_cache_get( $list_key, 'bb_nouveau_group_invites' );
					}

					if ( ! is_array( $excluded_ids ) ) {
						// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery -- Indexed meta_key lookup, cached immediately below.
						$excluded_ids  = wp_parse_id_list( $wpdb->get_col( $wpdb->prepare( "SELECT DISTINCT user_id FROM {$wpdb->usermeta} WHERE meta_key = %s LIMIT %d", $meta_query[0]['key'], $limit + 1 ) ) );
						$lookup_failed = ! empty( $wpdb->last_error );

						// `LIMIT $limit + 1` means an over-ceiling result is truncated and must
						// never be cached or emitted as an exclusion.
						if ( ! $lookup_failed && count( $excluded_ids ) > $limit ) {
							$over_ceiling = true;

							// Without a persistent object cache this verdict does not survive the
							// request, so such a site re-pays the throwaway lookup each time.
							wp_cache_set( $ceiling_key, 1, 'bb_nouveau_group_invites', 5 * MINUTE_IN_SECONDS );
						} elseif ( ! $lookup_failed && count( $excluded_ids ) <= $cacheable ) {
							// Cache the parsed ids: the payload is smaller than the raw string
							// column and callers get integers back. Skipped above the cacheable
							// size so a backend that would silently drop the set is not asked to
							// take it on every request - see the filter docblock.
							wp_cache_set( $list_key, $excluded_ids, 'bb_nouveau_group_invites', HOUR_IN_SECONDS );
						}
					}

					if ( ! $lock_held_elsewhere ) {
						wp_cache_delete( $lock_key, 'bb_nouveau_group_invites' );
					}
				}

				// On a lookup failure fall through to `WP_Meta_Query` — a privacy exclusion must
				// fail closed, never silently disappear. The count is re-checked here so a cached
				// list built under a higher ceiling still honours a lowered one.
				if ( ! $lookup_failed && ! $over_ceiling && is_array( $excluded_ids ) && count( $excluded_ids ) <= $limit ) {
					if ( ! empty( $excluded_ids ) ) {
						// Re-parsed rather than trusted: the value may have come from the object
						// cache, and it is interpolated straight into SQL.
						$bp_user_query->uid_clauses['where'] .= " AND u.{$bp_user_query->uid_name} NOT IN (" . implode( ',', wp_parse_id_list( $excluded_ids ) ) . ')';
					}

					return;
				}

				// The invite list re-queries on submitted searches and on every page of results,
				// so log each distinct reason once per request rather than once per query.
				//
				// Keyed by request rather than left to accumulate: a function static is scoped to
				// the process, not the request, so under a worker SAPI (Swoole, RoadRunner,
				// FrankenPHP worker mode) it would survive between requests and silence this
				// diagnostic for every later request that worker handled. Standard PHP-FPM and
				// mod_php reset it anyway; this makes the cadence the same everywhere.
				static $logged_fallbacks = array();
				static $logged_request   = null;

				// Cast rather than sanitised: the value is only compared with itself to detect a
				// request boundary, and a float cast is what makes that safe.
				$request_token = isset( $_SERVER['REQUEST_TIME_FLOAT'] ) ? (float) $_SERVER['REQUEST_TIME_FLOAT'] : 0.0;

				if ( $logged_request !== $request_token ) {
					$logged_fallbacks = array();
					$logged_request   = $request_token;
				}

				if ( $lookup_failed ) {
					$fallback_kind   = 'error';
					$fallback_reason = 'lookup query failed: ' . $wpdb->last_error;
				} elseif ( $fast_path_disabled ) {
					$fallback_kind   = 'disabled';
					$fallback_reason = 'the bb_nouveau_group_invites_excluded_ids_limit filter returned a negative ceiling';
				} else {
					$fallback_kind   = 'ceiling';
					$fallback_reason = 'more than ' . $limit . ' users hold the meta, exceeding the inline ceiling';
				}

				if ( empty( $logged_fallbacks[ $fallback_kind ] ) && defined( 'WP_DEBUG' ) && WP_DEBUG ) {
					$logged_fallbacks[ $fallback_kind ] = true;

					// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- Diagnostic logging gated behind WP_DEBUG.
					error_log(
						sprintf(
							'[BuddyBoss] Group invites excluded-ID fast path skipped for meta key "%s" (%s); using the WP_Meta_Query anti-join fallback.',
							$meta_query[0]['key'],
							$fallback_reason
						)
					);
				}
			}

			$invites_meta_query = new WP_Meta_Query( $meta_query );
			$meta_sql           = $invites_meta_query->get_sql( 'user', 'u', $bp_user_query->uid_name );

			if ( empty( $meta_sql['join'] ) || empty( $meta_sql['where'] ) ) {
				return;
			}

			$bp_user_query->uid_clauses['select'] .= ' ' . $meta_sql['join'];
			$bp_user_query->uid_clauses['where']  .= ' ' . $meta_sql['where'];
		}
	}

	/**
	 * @since BuddyPress 3.0.0
	 */
	public static function get_inviter_ids( $user_id = 0, $group_id = 0 ) {
		global $wpdb;

		if ( empty( $group_id ) || empty( $user_id ) ) {
			return array();
		}

		return groups_get_invites( array(
			'user_id'     => $user_id,
			'item_id'     => $group_id,
			'invite_sent' => 'sent',
			'fields'      => 'inviter_ids'
		) );
	}
}

/**
 * A specific Group Nav class to make it possible to set new positions for
 * buddypress()->groups->nav.
 *
 * @since BuddyPress 3.0.0
 */
class BP_Nouveau_Customizer_Group_Nav extends BP_Core_Nav {


	/**
	 * Store group.
	 *
	 * @since BuddyBoss 2.4.80
	 */
	public $group;

	/**
	 * Constructor
	 *
	 * @param int $object_id Optional. The random group ID used to generate the nav.
	 */
	public function __construct( $object_id = 0 ) {
		$error = new WP_Error( 'missing_parameter' );

		if ( empty( $object_id ) || ! bp_current_user_can( 'bp_moderate' ) || ! did_action( 'admin_init' ) ) {
			return $error;
		}

		$group = groups_get_group( array( 'group_id' => $object_id ) );
		if ( empty( $group->id ) ) {
			return $error;
		}

		$this->group = $group;

		parent::__construct( $group->id );
		$this->setup_nav();
	}

	/**
	 * Checks whether a property is set.
	 *
	 * Overrides BP_Core_Nav::__isset() to avoid looking into its nav property.
	 *
	 * @since BuddyPress 3.0.0
	 *
	 * @param string $key The property.
	 *
	 * @return bool True if the property is set, false otherwise.
	 */
	public function __isset( $key ) {
		return isset( $this->{$key} );
	}

	/**
	 * Gets a property.
	 *
	 * Overrides BP_Core_Nav::__isset() to avoid looking into its nav property.
	 *
	 * @since BuddyPress 3.0.0
	 *
	 * @param string $key The property.
	 *
	 * @return mixed The value corresponding to the property.
	 */
	public function __get( $key ) {
		if ( ! isset( $this->{$key} ) ) {
			$this->{$key} = null;
		}

		return $this->{$key};
	}

	/**
	 * Sets a property.
	 *
	 * Overrides BP_Core_Nav::__isset() to avoid adding a value to its nav property.
	 *
	 * @since BuddyPress 3.0.0
	 *
	 * @param string $key The property.
	 *
	 * @param mixed $value The value of the property.
	 */
	public function __set( $key, $value ) {
		$this->{$key} = $value;
	}

	/**
	 * Setup a temporary nav with only the needed parameters.
	 *
	 * @since BuddyPress 3.0.0
	 */
	protected function setup_nav() {
		$nav_items = array(
			'root'    => array(
				'name'                => __( 'My Groups', 'buddyboss' ),
				'slug'                => $this->group->slug,
				'position'            => -1,
				/** This filter is documented in bp-groups/classes/class-bp-groups-component.php. */
				'default_subnav_slug' => apply_filters( 'bp_groups_default_extension', defined( 'BP_GROUPS_DEFAULT_EXTENSION' ) ? BP_GROUPS_DEFAULT_EXTENSION : 'home' ),
			),
			'members'    => array(
				'name'        => __( 'Members', 'buddyboss' ),
				'slug'        => 'members',
				'parent_slug' => $this->group->slug,
				'position'    => 10,
			),
			'invites' => array(
				'name'        => __( 'Send Invites', 'buddyboss' ),
				'slug'        => 'invite',
				'parent_slug' => $this->group->slug,
				'position'    => 30,
			),
			'manage'  => array(
				'name'        => __( 'Manage', 'buddyboss' ),
				'slug'        => 'admin',
				'parent_slug' => $this->group->slug,
				'position'    => 1000,
			),
		);

		if ( bp_is_active( 'media' ) && bp_is_group_media_support_enabled() ) {
			$nav_items['photos'] = array(
				'name'        => __( 'Photos', 'buddyboss' ),
				'slug'        => 'photos',
				'parent_slug' => $this->group->slug,
				'position'    => 21,
			);

			if ( bp_is_group_video_support_enabled() ) {
				// Checked if order already set before, New menu(video) will be added at last
				$video_menu_position = 22;
				$orders              = get_option( 'bp_nouveau_appearance' );
				if ( isset( $orders['group_nav_order'] ) && ! empty( $orders['group_nav_order'] ) && ! in_array( 'vide', $orders['group_nav_order'] ) ) {
					$video_menu_position = 1001;
				}
				$nav_items['videos'] = array(
					'name'        => __( 'Videos', 'buddyboss' ),
					'slug'        => 'videos',
					'parent_slug' => $this->group->slug,
					'position'    => $video_menu_position,
				);
			}

			if ( bp_is_group_albums_support_enabled() ) {
				$nav_items['albums'] = array(
					'name'        => __( 'Albums', 'buddyboss' ),
					'slug'        => 'albums',
					'parent_slug' => $this->group->slug,
					'position'    => 23,
				);
			}

		}

		if ( bp_is_active( 'forums' ) && function_exists( 'bbp_is_group_forums_active' ) ) {
			if ( bbp_is_group_forums_active() ) {
				$nav_items['forum'] = array(
					'name'        => __( 'Discussions', 'buddyboss' ),
					'slug'        => get_option( '_bbp_forum_slug', 'forum' ),
					'parent_slug' => $this->group->slug,
					'position'    => 30,
				);
			}
		}

		if ( bp_enable_group_hierarchies() ) {
			$nav_items['subgroups'] = array(
				'name'        => __( 'Subgroups', 'buddyboss' ),
				'slug'        => 'subgroups',
				'parent_slug' => $this->group->slug,
				'position'    => 28,
			);
		}

		// LearnDash Courses and Reports group nav items used to be registered
		// here inline. As of BuddyBoss 3.0.0 the buddyboss-learndash
		// addon adds them via the existing bp_nouveau_customizer_group_nav_items
		// filter below — no Platform-side knowledge of LD slugs needed.

		if ( bp_is_active( 'activity' ) ) {
			$nav_items['activity'] = array(
				'name'        => __( 'Feed', 'buddyboss' ),
				'slug'        => 'activity',
				'parent_slug' => $this->group->slug,
				'position'    => 20,
			);
		}

		if ( bp_is_active( 'messages' ) && true === bp_disable_group_messages() && groups_can_user_manage_messages( bp_loggedin_user_id(), $this->group->id ) ) {
			$nav_items['messages'] = array(
				'name'        => __( 'Send Messages', 'buddyboss' ),
				'slug'        => 'messages',
				'parent_slug' => $this->group->slug,
				'position'    => 25,
			);
		}

		if ( bp_is_active( 'media' ) && bp_is_group_document_support_enabled() ) {
			$nav_items['documents'] = array(
				'name'        => __( 'Documents', 'buddyboss' ),
				'slug'        => 'documents',
				'parent_slug' => $this->group->slug,
				'position'    => 24,
			);
		}

		// Required params
		$required_params = array(
			'slug'              => true,
			'name'              => true,
			'nav_item_position' => true,
		);

		// Now find nav items plugins are creating within their Group extensions!
		foreach ( get_declared_classes() as $class ) {
			if ( is_subclass_of( $class, 'BP_Group_Extension' ) ) {
				$extension = new $class;

				if ( ! empty( $extension->params ) && ! array_diff_key( $required_params, $extension->params ) ) {
					$nav_items[ $extension->params['slug'] ] = array(
						'name'        => $extension->params['name'],
						'slug'        => $extension->params['slug'],
						'parent_slug' => $this->group->slug,
						'position'    => $extension->params['nav_item_position'],
					);
				}
			}
		}

		/**
		 * Filters group customizer navigation items.
		 *
		 * @since BuddyBoss 1.4.4
		 */
		$nav_items = apply_filters( 'bp_nouveau_customizer_group_nav_items', $nav_items, $this->group );

		// Now we got all, create the temporary nav.
		foreach ( $nav_items as $nav_item ) {
			$this->add_nav( $nav_item );
		}
	}

	/**
	 * Front template: do not look into group's template hierarchy.
	 *
	 * @since BuddyPress 3.0.0
	 *
	 * @param array $templates The list of possible group front templates.
	 *
	 * @return array The list of "global" group front templates.
	 */
	public function all_groups_fronts( $templates = array() ) {
		return array_intersect( array(
			'groups/single/front.php',
			'groups/single/default-front.php',
		), $templates );
	}

	/**
	 * Get the original order for the group navigation.
	 *
	 * @since BuddyPress 3.0.0
	 *
	 * @return array a list of nav items slugs ordered.
	 */
	public function get_default_value() {
		$default_nav = $this->get_secondary( array( 'parent_slug' => $this->group->slug ) );
		return wp_list_pluck( $default_nav, 'slug' );
	}

	/**
	 * Get the list of nav items ordered according to the Site owner preferences.
	 *
	 * @since BuddyPress 3.0.0
	 *
	 * @return array the nav items ordered.
	 */
	public function get_group_nav() {
		// Eventually reset the order
		bp_nouveau_set_nav_item_order( $this, bp_nouveau_get_appearance_settings( 'group_nav_order' ), $this->group->slug );

		return $this->get_secondary( array( 'parent_slug' => $this->group->slug ) );
	}
}
