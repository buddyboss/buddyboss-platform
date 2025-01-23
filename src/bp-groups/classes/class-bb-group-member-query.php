<?php
/**
 * BuddyBoss Groups Classes.
 *
 * @package BuddyBoss\Groups\Classes
 * @since BuddyPress 1.8.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Query for the members of a group.
 *
 * Special notes about the group members data schema:
 * - *Members* are entries with is_confirmed = 1.
 * - *Pending requests* are entries with is_confirmed = 0 and inviter_id = 0.
 * - *Pending and sent invitations* are entries with is_confirmed = 0 and
 *   inviter_id != 0 and invite_sent = 1.
 * - *Pending and unsent invitations* are entries with is_confirmed = 0 and
 *   inviter_id != 0 and invite_sent = 0.
 * - *Membership requests* are entries with is_confirmed = 0 and
 *   inviter_id = 0 (and invite_sent = 0).
 *
 * @since BuddyPress 1.8.0
 * @since BuddyPress 3.0.0 $group_id now supports multiple values.
 *
 * @param array $args  {
 *     Array of arguments. Accepts all arguments from
 *     {@link BP_User_Query}, with the following additions:
 *
 *     @type int|array|string $group_id     ID of the group to limit results to. Also accepts multiple values
 *                                          either as an array or as a comma-delimited string.
 *     @type array            $group_role   Array of group roles to match ('member', 'mod', 'admin', 'banned').
 *                                          Default: array( 'member' ).
 *     @type bool             $is_confirmed Whether to limit to confirmed members. Default: true.
 *     @type string           $type         Sort order. Accepts any value supported by {@link BP_User_Query}, in
 *                                          addition to 'last_joined' and 'first_joined'. Default: 'last_joined'.
 * }
 */
class BB_Group_Member_Query extends BP_User_Query {

	/**
	 * Array of group member ids, cached to prevent redundant lookups.
	 *
	 * @since BuddyPress 1.8.1
	 * @var null|array Null if not yet defined, otherwise an array of ints.
	 */
	protected $group_member_ids;

	/**
	 * Array of group member ids, cached to prevent redundant lookups SQL
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @var null|array Null if not yet defined, otherwise an array of ints.
	 */
	protected $group_member_ids_sql;

	protected $inviter_sql;

	/**
	 * Set up action hooks.
	 *
	 * @since BuddyPress 1.8.0
	 */
	public function setup_hooks() {
		// Take this early opportunity to set the default 'type' param
		// to 'last_joined', which will ensure that BP_User_Query
		// trusts our order and does not try to apply its own.
		if ( empty( $this->query_vars_raw['type'] ) ) {
			$this->query_vars_raw['type'] = 'last_joined';
		}

		// Set the sort order.
		add_action( 'bp_pre_user_query', array( $this, 'set_orderby' ) );

		add_filter( 'bp_user_query_join_sql', array( $this, 'group_user_query_join_sql' ), 10, 2 );
		add_filter( 'bp_user_query_where_sql', array( $this, 'group_user_query_where_sql' ), 10, 2 );

		// Set up our populate_extras method.
		add_action( 'bp_user_query_populate_extras', array( $this, 'populate_group_member_extras' ), 10, 2 );
	}

	public function group_user_query_join_sql( $sql_select, $uid_name ) {
		global $wpdb, $bp;

		$this->query_vars = bp_parse_args(
			$this->query_vars,
			array(
				'group_id'     => 0,
				'group_role'   => array( 'member' ),
				'is_confirmed' => true,
				'invite_sent'  => null,
				'inviter_id'   => null,
				'type'         => 'last_joined',
			),
			'bp_group_member_query_join_sql'
		);

		$u_field = 'u.' . $uid_name;

		// GROUP MEMBER JOIN LOGIC
		// Add a JOIN clause to include group members.
		$this->inviter_sql = $this->get_inviter_sql( $this );
		if ( ! empty( $this->inviter_sql ) ) {
			$invites_table_name = BP_Invitation_Manager::get_table_name();
			$group_member_join = "LEFT JOIN {$bp->groups->table_name_members} gm ON {$u_field} = gm.user_id";
			$inviter_join = "LEFT JOIN {$invites_table_name} i ON {$u_field} = i.user_id ";
			$group_member_join = $group_member_join . ' ' .  $inviter_join;
			remove_filter( 'bp_user_query_where_sql', array( $this, 'group_user_query_where_sql' ), 10, 2 );
			add_filter( 'bp_user_query_where_sql', array( $this, 'group_user_invite_query_where_sql' ), 10, 2 );

		} else {
			$group_member_join = "INNER JOIN {$bp->groups->table_name_members} gm ON {$u_field} = gm.user_id";
		}

		if ( ! empty( $this->query_vars['type'] ) && 'group_activity' == $this->query_vars['type'] && bp_is_active( 'activity' ) ) {
			$activity_sql      = $this->get_activity_order_sql( $this );
			$group_member_join = $group_member_join . ' INNER JOIN (' . $activity_sql . ' ) a';
		}

		remove_filter( 'bp_user_query_join_sql', array( $this, 'group_user_query_join_sql' ), 10, 2 );

		return $sql_select . ' ' . $group_member_join;
	}

	public function group_user_query_where_sql( $where, $uid_name ) {
		global $wpdb;

		// Add group member conditions to the WHERE clause.
		$group_ids = wp_parse_id_list( $this->query_vars['group_id'] );
		$group_ids = implode( ',', $group_ids );
		$where[] = "gm.group_id IN ({$group_ids})";

		// If is_confirmed.
		$is_confirmed = empty( $this->query_vars['is_confirmed'] ) ? 0 : 1;
		$where[] = $wpdb->prepare( 'gm.is_confirmed = %d', $is_confirmed );

		// If invite_sent.
		if ( ! is_null( $this->query_vars['invite_sent'] ) ) {
			$invite_sent    = ! empty( $this->query_vars['invite_sent'] ) ? 1 : 0;
			$where[] = $wpdb->prepare( 'gm.invite_sent = %d', $invite_sent );
		}

		// If inviter_id.
		if ( ! is_null( $this->query_vars['inviter_id'] ) ) {
			$inviter_id = $this->query_vars['inviter_id'];

			// Empty: inviter_id = 0. (pass false, 0, or empty array).
			if ( empty( $inviter_id ) ) {
				$where[] = 'gm.inviter_id = 0';

				// The string 'any' matches any non-zero value (inviter_id != 0).
			} elseif ( 'any' === $inviter_id ) {
				$where[] = 'gm.inviter_id != 0';

				// Assume that a list of inviter IDs has been passed.
			} else {
				// Parse and sanitize.
				$inviter_ids = wp_parse_id_list( $inviter_id );
				if ( ! empty( $inviter_ids ) ) {
					$inviter_ids_sql = implode( ',', $inviter_ids );
					$where[]  = "gm.inviter_id IN ({$inviter_ids_sql})";
				}
			}
		}

		// Role information is stored as follows: admins have
		// is_admin = 1, mods have is_mod = 1, banned have is_banned =
		// 1, and members have all three set to 0.
		$roles = ! empty( $this->query_vars['group_role'] ) ? $this->query_vars['group_role'] : array();
		if ( is_string( $roles ) ) {
			$roles = explode( ',', $roles );
		}

		// Sanitize: Only 'admin', 'mod', 'member', and 'banned' are valid.
		$allowed_roles = array( 'admin', 'mod', 'member', 'banned' );
		foreach ( $roles as $role_key => $role_value ) {
			if ( ! in_array( $role_value, $allowed_roles ) ) {
				unset( $roles[ $role_key ] );
			}
		}

		$roles = array_unique( $roles );

		// When querying for a set of roles containing 'member' (for
		// which there is no dedicated is_ column), figure out a list
		// of columns *not* to match.
		$roles_sql = '';
		if ( in_array( 'member', $roles ) ) {
			$role_columns = array();
			foreach ( array_diff( $allowed_roles, $roles ) as $excluded_role ) {
				$role_columns[] = 'gm.is_' . $excluded_role . ' = 0';
			}

			if ( ! empty( $role_columns ) ) {
				$roles_sql = '(' . implode( ' AND ', $role_columns ) . ')';
			}

			// When querying for a set of roles *not* containing 'member',
			// simply construct a list of is_* = 1 clauses.
		} else {
			$role_columns = array();
			foreach ( $roles as $role ) {
				$role_columns[] = 'gm.is_' . $role . ' = 1';
			}

			if ( ! empty( $role_columns ) ) {
				$roles_sql = '(' . implode( ' OR ', $role_columns ) . ')';
			}
		}

		if ( ! empty( $roles_sql ) ) {
			$where[] = $roles_sql;
		}

		remove_filter( 'bp_user_query_where_sql', array( $this, 'group_user_query_where_sql' ), 10, 2 );

		return $where;
	}

	public function group_user_invite_query_where_sql( $where, $uid_name ) {
		if ( ! empty( $this->inviter_sql ) ) {
			$group_where  = implode( ' AND ', $this->group_user_query_where_sql( array(), $uid_name ) );
			$inviter_join = str_replace( 'WHERE i.class', 'i.class', $this->inviter_sql['where'] );
			$where[]      = "(gm.user_id IS NOT NULL OR i.user_id IS NOT NULL) AND ( ({$group_where}) OR ({$inviter_join}) )";
		}

		remove_filter( 'bp_user_query_where_sql', array( $this, 'group_user_invite_query_where_sql' ), 10, 2 );

		return $where;
	}

	/**
	 * Get a list of user_ids to include in the IN clause of the main query.
	 *
	 * Overrides BP_User_Query::get_include_ids(), adding our additional
	 * group-member logic.
	 *
	 * @since BuddyPress 1.8.0
	 *
	 * @param array $include Existing group IDs in the $include parameter,
	 *                       as calculated in BP_User_Query.
	 * @return array
	 */
	public function get_include_ids( $include = array() ) {

		return $include;
	}

	/**
	 * Tell BP_User_Query to order by the order of our query results.
	 *
	 * We only override BP_User_Query's native ordering in case of the
	 * 'last_joined' and 'first_joined' $type parameters.
	 *
	 * @since BuddyPress 1.8.1
	 *
	 * @param BP_User_Query $query BP_User_Query object.
	 */
	public function set_orderby( $query ) {

		if ( in_array( $query->query_vars['type'], array( 'last_joined', 'first_joined', 'group_activity', 'group_role' ) ) ) {
			if ( ! empty( $query->query_vars['type'] ) && 'group_activity' == $query->query_vars['type'] && bp_is_active( 'activity' ) ) {
				$query->uid_clauses['orderby'] = 'ORDER BY a.latest_date';
			} elseif ( $query->query_vars['type'] === 'group_role' ) {
				$query->uid_clauses['orderby'] = 'ORDER BY gm.is_admin DESC, gm.is_mod DESC, gm.date_modified DESC, gm.user_id ASC';
			} else {
				$query->uid_clauses['orderby'] = 'ORDER BY gm.date_modified';
			}

			$query->uid_clauses['order'] = 'first_joined' === $query->query_vars['type'] ? 'ASC' : $query->uid_clauses['order'];
		}


		// Prevent this filter from running on future BP_User_Query
		// instances on the same page.
		remove_action( 'bp_pre_user_query', array( $this, 'set_orderby' ) );
	}

	/**
	 * Fetch additional data required in bp_group_has_members() loops.
	 *
	 * Additional data fetched:
	 *      - is_banned
	 *      - date_modified
	 *
	 * @since BuddyPress 1.8.0
	 *
	 * @param BP_User_Query $query        BP_User_Query object. Because we're
	 *                                    filtering the current object, we use
	 *                                    $this inside of the method instead.
	 * @param string        $user_ids_sql Sanitized, comma-separated string of
	 *                                    the user ids returned by the main query.
	 */
	public function populate_group_member_extras( $query, $user_ids_sql ) {
		global $wpdb;
		static $cache = array();

		$bp = buddypress();

		if ( is_array( $this->query_vars['group_id'] ) ) {
			$group_ids = wp_parse_id_list( $this->query_vars['group_id'] );
		} else {
			$group_ids = wp_parse_id_list( explode( ',', $this->query_vars['group_id'] ) );
		}

		$cache_key = 'bb_populate_group_member_extras_' . str_replace( ',', '_', implode( ',', $group_ids ) ) . '_' . str_replace( ',', '_', $user_ids_sql );
		if ( ! isset( $cache[ $cache_key ] ) ) {

			$sql = "SELECT id, user_id, date_modified, is_admin, is_mod, comments, user_title, invite_sent, is_confirmed, inviter_id, is_banned FROM {$bp->groups->table_name_members} WHERE user_id IN ({$user_ids_sql}) AND group_id IN ( " . implode( ', ', array_fill( 0, count( $group_ids ), '%s' ) ) . ' )';

			// Call $wpdb->prepare passing the values of the array as separate arguments.
			$query = call_user_func_array( array( $wpdb, 'prepare' ), array_merge( array( $sql ), $group_ids ) );

			$extras              = $wpdb->get_results( $query );
			$cache[ $cache_key ] = $extras;
		} else {
			$extras = $cache[ $cache_key ];
		}

		foreach ( (array) $extras as $extra ) {
			if ( isset( $this->results[ $extra->user_id ] ) ) {
				// The user_id is provided for backward compatibility.
				$this->results[ $extra->user_id ]->user_id       = (int) $extra->user_id;
				$this->results[ $extra->user_id ]->is_admin      = (int) $extra->is_admin;
				$this->results[ $extra->user_id ]->is_mod        = (int) $extra->is_mod;
				$this->results[ $extra->user_id ]->is_banned     = (int) $extra->is_banned;
				$this->results[ $extra->user_id ]->date_modified = $extra->date_modified;
				$this->results[ $extra->user_id ]->user_title    = $extra->user_title;
				$this->results[ $extra->user_id ]->comments      = $extra->comments;
				$this->results[ $extra->user_id ]->invite_sent   = (int) $extra->invite_sent;
				$this->results[ $extra->user_id ]->inviter_id    = (int) $extra->inviter_id;
				$this->results[ $extra->user_id ]->is_confirmed  = (int) $extra->is_confirmed;
				$this->results[ $extra->user_id ]->membership_id = (int) $extra->id;
			}
		}

		// Add accurate invitation info from the invitations table.
		$invites = groups_get_invites(
			array(
				'user_id' => $user_ids_sql,
				'item_id' => $this->query_vars['group_id'],
				'type'    => 'all',
			)
		);
		foreach ( $invites as $invite ) {
			if ( isset( $this->results[ $invite->user_id ] ) ) {
				$this->results[ $invite->user_id ]->comments      = $invite->content;
				$this->results[ $invite->user_id ]->is_confirmed  = 0;
				$this->results[ $invite->user_id ]->invitation_id = $invite->id;
				$this->results[ $invite->user_id ]->invite_sent   = (int) $invite->invite_sent;
				$this->results[ $invite->user_id ]->inviter_id    = $invite->inviter_id;

				// Backfill properties that are not being set above.
				if ( ! isset( $this->results[ $invite->user_id ]->user_id ) ) {
					$this->results[ $invite->user_id ]->user_id = $invite->user_id;
				}
				if ( ! isset( $this->results[ $invite->user_id ]->is_admin ) ) {
					$this->results[ $invite->user_id ]->is_admin = 0;
				}
				if ( ! isset( $this->results[ $invite->user_id ]->is_mod ) ) {
					$this->results[ $invite->user_id ]->is_mod = 0;
				}
				if ( ! isset( $this->results[ $invite->user_id ]->is_banned ) ) {
					$this->results[ $invite->user_id ]->is_banned = 0;
				}
				if ( ! isset( $this->results[ $invite->user_id ]->date_modified ) ) {
					$this->results[ $invite->user_id ]->date_modified = $invite->date_modified;
				}
				if ( ! isset( $this->results[ $invite->user_id ]->user_title ) ) {
					$this->results[ $invite->user_id ]->user_title = '';
				}
				if ( ! isset( $this->results[ $invite->user_id ]->membership_id ) ) {
					$this->results[ $invite->user_id ]->membership_id = 0;
				}
			}
		}

		// Don't filter other BP_User_Query objects on the same page.
		remove_action( 'bp_user_query_populate_extras', array( $this, 'populate_group_member_extras' ), 10 );
	}

	/**
	 * Sort user IDs by how recently they have generated activity within a given group.
	 *
	 * @since BuddyPress 2.1.0
	 *
	 * @param BP_User_Query $query  BP_User_Query object.
	 * @param array         $gm_ids array of group member ids.
	 *
	 * @return string
	 */
	public function get_activity_order_sql( $query ) {
		global $wpdb;

		$return_sql = '';

		if ( ! bp_is_active( 'activity' ) ) {
			return $return_sql;
		}

		$activity_table = buddypress()->activity->table_name;

		$sql = array(
			'select'  => "SELECT user_id, max( date_recorded ) as latest_date FROM {$activity_table}",
			'where'   => array(),
			'groupby' => 'GROUP BY user_id',
			'orderby' => 'ORDER BY date_recorded',
			'order'   => 'DESC',
		);

		$sql['where'] = array(
			'item_id = ' . absint( $query->query_vars['group_id'] ),
			$wpdb->prepare( 'component = %s', buddypress()->groups->id ),
		);

		$sql['where'] = 'WHERE ' . implode( ' AND ', $sql['where'] );

		return $wpdb->prepare( "{$sql['select']} {$sql['where']} {$sql['groupby']} {$sql['orderby']} {$sql['order']}" );
	}

	public function get_inviter_sql( $query ) {
		global $wpdb;

		$invited_member_sql = array();

		$is_confirmed   = ! empty( $query->query_vars['is_confirmed'] ) ? 1 : 0;

		// If appropriate, fetch invitations and add them to the results.
		if ( ! $is_confirmed || ! is_null( $query->query_vars['invite_sent'] ) || ! is_null( $query->query_vars['inviter_id'] ) ) {
			$invite_args = array(
				'item_id' => $query->query_vars['group_id'],
				'fields'  => 'user_ids',
				'type'    => 'all',
			);

			if ( ! is_null( $query->query_vars['invite_sent'] ) ) {
				$invite_args['invite_sent'] = ! empty( $query->query_vars['invite_sent'] ) ? 'sent' : 'draft';
			}

			// If inviter_id.
			if ( ! is_null( $query->query_vars['inviter_id'] ) ) {
				$inviter_id = $query->query_vars['inviter_id'];

				// Empty: inviter_id = 0. (pass false, 0, or empty array).
				if ( empty( $inviter_id ) ) {
					$invite_args['type'] = 'request';

					/*
					* The string 'any' matches any non-zero value (inviter_id != 0).
					* These are invitations, not requests.
					*/
				} elseif ( 'any' === $inviter_id ) {
					$invite_args['type'] = 'invite';

					// Assume that a list of inviter IDs has been passed.
				} else {
					$invite_args['type'] = 'invite';
					// Parse and sanitize.
					$inviter_ids = wp_parse_id_list( $inviter_id );
					if ( ! empty( $inviter_ids ) ) {
						$invite_args['inviter_id'] = $inviter_ids;
					}
				}
			}

			$invite_args['retval'] = 'sql';

			$invited_member_sql = groups_get_invites( $invite_args );
		}

		return $invited_member_sql;
	}
}
