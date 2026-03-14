<?php
/**
 * BuddyBoss Events Functions.
 *
 * @package BuddyBoss\Events\Functions
 * @since BuddyBoss Events 1.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/** Install / Schema ***********************************************************/

/**
 * Install database tables on plugin activation.
 *
 * @since BuddyBoss Events 1.0.0
 */
function bp_events_install() {
	global $wpdb;

	$charset_collate = $wpdb->get_charset_collate();
	$bp              = buddypress();

	$sql = array();

	// Events table.
	$sql[] = "CREATE TABLE {$bp->events->table_name} (
		id             bigint(20)    NOT NULL AUTO_INCREMENT,
		title          varchar(255)  NOT NULL DEFAULT '',
		description    longtext      NOT NULL,
		slug           varchar(200)  NOT NULL DEFAULT '',
		organizer_id   bigint(20)    NOT NULL DEFAULT 0,
		group_id       bigint(20)             DEFAULT NULL,
		type           varchar(20)   NOT NULL DEFAULT 'in-person',
		venue_name     varchar(255)  NOT NULL DEFAULT '',
		venue_address  varchar(500)  NOT NULL DEFAULT '',
		venue_lat      decimal(10,7)          DEFAULT NULL,
		venue_lng      decimal(10,7)          DEFAULT NULL,
		virtual_url    varchar(500)  NOT NULL DEFAULT '',
		virtual_type   varchar(20)   NOT NULL DEFAULT '',
		start_date     datetime      NOT NULL DEFAULT '0000-00-00 00:00:00',
		end_date       datetime      NOT NULL DEFAULT '0000-00-00 00:00:00',
		timezone       varchar(100)  NOT NULL DEFAULT 'UTC',
		capacity       int(11)                DEFAULT NULL,
		status         varchar(20)   NOT NULL DEFAULT 'draft',
		recurrence_rule varchar(500) NOT NULL DEFAULT '',
		parent_event_id bigint(20)            DEFAULT NULL,
		date_created   datetime      NOT NULL DEFAULT '0000-00-00 00:00:00',
		date_modified  datetime      NOT NULL DEFAULT '0000-00-00 00:00:00',
		PRIMARY KEY (id),
		KEY organizer_id (organizer_id),
		KEY group_id (group_id),
		KEY status (status),
		KEY start_date (start_date),
		KEY slug (slug(191))
	) {$charset_collate};";

	// Event meta table.
	$sql[] = "CREATE TABLE {$bp->events->table_name_meta} (
		id         bigint(20)   NOT NULL AUTO_INCREMENT,
		event_id   bigint(20)   NOT NULL DEFAULT 0,
		meta_key   varchar(255)          DEFAULT NULL,
		meta_value longtext              DEFAULT NULL,
		PRIMARY KEY (id),
		KEY event_id (event_id),
		KEY meta_key (meta_key(191))
	) {$charset_collate};";

	// Attendees table.
	$sql[] = "CREATE TABLE {$bp->events->table_name_attendees} (
		id           bigint(20)  NOT NULL AUTO_INCREMENT,
		event_id     bigint(20)  NOT NULL DEFAULT 0,
		user_id      bigint(20)  NOT NULL DEFAULT 0,
		ticket_id    bigint(20)           DEFAULT NULL,
		order_id     bigint(20)           DEFAULT NULL,
		status       varchar(20) NOT NULL DEFAULT 'registered',
		date_created datetime    NOT NULL DEFAULT '0000-00-00 00:00:00',
		PRIMARY KEY (id),
		KEY event_id (event_id),
		KEY user_id (user_id),
		KEY status (status),
		UNIQUE KEY event_user (event_id, user_id)
	) {$charset_collate};";

	// Invites table.
	$sql[] = "CREATE TABLE {$bp->events->table_name_invites} (
		id           bigint(20)  NOT NULL AUTO_INCREMENT,
		event_id     bigint(20)  NOT NULL DEFAULT 0,
		inviter_id   bigint(20)  NOT NULL DEFAULT 0,
		invitee_id   bigint(20)  NOT NULL DEFAULT 0,
		status       varchar(20) NOT NULL DEFAULT 'pending',
		date_created datetime    NOT NULL DEFAULT '0000-00-00 00:00:00',
		PRIMARY KEY (id),
		KEY event_id (event_id),
		KEY invitee_id (invitee_id),
		UNIQUE KEY event_invitee (event_id, invitee_id)
	) {$charset_collate};";

	require_once ABSPATH . 'wp-admin/includes/upgrade.php';

	foreach ( $sql as $query ) {
		dbDelta( $query );
	}
}

/** Event CRUD *****************************************************************/

/**
 * Create a new event.
 *
 * @since BuddyBoss Events 1.0.0
 *
 * @param array $args {
 *     Event arguments.
 *     @type string   $title           Required. Event title.
 *     @type string   $description     Event description HTML.
 *     @type int      $organizer_id    Organizer user ID. Defaults to current user.
 *     @type int      $group_id        Group ID.
 *     @type string   $type            Event type: in-person|virtual|hybrid.
 *     @type string   $venue_name      Venue name.
 *     @type string   $venue_address   Full venue address.
 *     @type float    $venue_lat       Latitude.
 *     @type float    $venue_lng       Longitude.
 *     @type string   $virtual_url     Virtual meeting URL.
 *     @type string   $virtual_type    zoom|meet|other.
 *     @type string   $start_date      Start datetime (MySQL).
 *     @type string   $end_date        End datetime (MySQL).
 *     @type string   $timezone        Timezone string.
 *     @type int      $capacity        Capacity limit.
 *     @type string   $status          draft|pending|published.
 *     @type string   $recurrence_rule RRULE string.
 *     @type int      $parent_event_id Parent event ID for occurrences.
 * }
 * @return int|false New event ID on success, false on failure.
 */
function bp_events_create_event( $args = array() ) {
	$r = bp_parse_args(
		$args,
		array(
			'title'            => '',
			'description'      => '',
			'organizer_id'     => bp_loggedin_user_id(),
			'group_id'         => null,
			'type'             => 'in-person',
			'venue_name'       => '',
			'venue_address'    => '',
			'venue_lat'        => null,
			'venue_lng'        => null,
			'virtual_url'      => '',
			'virtual_type'     => '',
			'start_date'       => '',
			'end_date'         => '',
			'timezone'         => 'UTC',
			'capacity'         => null,
			'status'           => bp_events_moderation_enabled() ? 'pending' : 'published',
			'recurrence_rule'  => '',
			'parent_event_id'  => null,
		),
		'events_create_event'
	);

	if ( empty( $r['title'] ) || empty( $r['start_date'] ) ) {
		return false;
	}

	// Permission check.
	if ( ! bp_events_user_can_create( $r['organizer_id'], $r['group_id'] ) ) {
		return false;
	}

	$event = new BP_Event();

	foreach ( $r as $key => $value ) {
		$event->$key = $value;
	}

	$event->slug = bp_events_generate_slug( $r['title'] );

	if ( ! $event->save() ) {
		return false;
	}

	return $event->id;
}

/**
 * Update an existing event.
 *
 * @since BuddyBoss Events 1.0.0
 *
 * @param int   $event_id Event ID.
 * @param array $args     Fields to update.
 * @return bool True on success, false on failure.
 */
function bp_events_update_event( $event_id, $args = array() ) {
	$event = new BP_Event( $event_id );

	if ( empty( $event->id ) ) {
		return false;
	}

	if ( ! $event->user_can_edit() ) {
		return false;
	}

	$allowed = array(
		'title', 'description', 'type', 'venue_name', 'venue_address',
		'venue_lat', 'venue_lng', 'virtual_url', 'virtual_type',
		'start_date', 'end_date', 'timezone', 'capacity', 'status',
		'recurrence_rule',
	);

	foreach ( $args as $key => $value ) {
		if ( in_array( $key, $allowed, true ) ) {
			$event->$key = $value;
		}
	}

	return $event->save();
}

/**
 * Delete an event.
 *
 * @since BuddyBoss Events 1.0.0
 *
 * @param int $event_id Event ID.
 * @return bool True on success, false on failure.
 */
function bp_events_delete_event( $event_id ) {
	$event = new BP_Event( $event_id );

	if ( empty( $event->id ) ) {
		return false;
	}

	if ( ! $event->user_can_edit() ) {
		return false;
	}

	return $event->delete();
}

/**
 * Get a single event.
 *
 * @since BuddyBoss Events 1.0.0
 *
 * @param int $event_id Event ID.
 * @return BP_Event|false Event object or false if not found.
 */
function bp_events_get_event( $event_id ) {
	$event = new BP_Event( $event_id );

	if ( empty( $event->id ) ) {
		return false;
	}

	return $event;
}

/**
 * Get events with filtering and pagination.
 *
 * @since BuddyBoss Events 1.0.0
 *
 * @param array $args {
 *     Query arguments.
 *     @type int      $group_id    Filter by group ID.
 *     @type int      $organizer_id Filter by organizer.
 *     @type int      $user_id     Filter by attendee user ID.
 *     @type string   $status      Filter by status.
 *     @type string   $type        Filter by type.
 *     @type string   $from        Start date filter (Y-m-d).
 *     @type string   $to          End date filter (Y-m-d).
 *     @type string   $search      Search term.
 *     @type int      $per_page    Results per page. Default 20.
 *     @type int      $page        Page number. Default 1.
 *     @type string   $orderby     Order by column. Default start_date.
 *     @type string   $order       ASC|DESC. Default ASC.
 * }
 * @return array { events, total }
 */
function bp_events_get_events( $args = array() ) {
	global $wpdb;

	$bp = buddypress();

	$r = bp_parse_args(
		$args,
		array(
			'group_id'     => null,
			'organizer_id' => null,
			'user_id'      => null,
			'status'       => 'published',
			'type'         => null,
			'from'         => null,
			'to'           => null,
			'search'       => '',
			'per_page'     => 20,
			'page'         => 1,
			'orderby'      => 'start_date',
			'order'        => 'ASC',
		),
		'events_get_events'
	);

	$where_clauses = array( '1=1' );

	if ( ! is_null( $r['group_id'] ) ) {
		$where_clauses[] = $wpdb->prepare( 'e.group_id = %d', $r['group_id'] );
	}

	if ( ! is_null( $r['organizer_id'] ) ) {
		$where_clauses[] = $wpdb->prepare( 'e.organizer_id = %d', $r['organizer_id'] );
	}

	if ( ! empty( $r['status'] ) ) {
		$where_clauses[] = $wpdb->prepare( 'e.status = %s', $r['status'] );
	}

	if ( ! empty( $r['type'] ) ) {
		$where_clauses[] = $wpdb->prepare( 'e.type = %s', $r['type'] );
	}

	if ( ! empty( $r['from'] ) ) {
		$where_clauses[] = $wpdb->prepare( 'e.start_date >= %s', $r['from'] . ' 00:00:00' );
	}

	if ( ! empty( $r['to'] ) ) {
		$where_clauses[] = $wpdb->prepare( 'e.start_date <= %s', $r['to'] . ' 23:59:59' );
	}

	if ( ! empty( $r['search'] ) ) {
		$like            = '%' . $wpdb->esc_like( $r['search'] ) . '%';
		$where_clauses[] = $wpdb->prepare( '(e.title LIKE %s OR e.description LIKE %s)', $like, $like );
	}

	// If filtering by attendee, join attendees table.
	$join = '';
	if ( ! is_null( $r['user_id'] ) ) {
		$join = $wpdb->prepare(
			" INNER JOIN {$bp->events->table_name_attendees} a ON e.id = a.event_id AND a.user_id = %d AND a.status = 'registered'",
			$r['user_id']
		);
	}

	// Site-wide query (no group_id filter): enforce group privacy rules.
	// Group-scoped queries bypass site-wide privacy — the group controls its own view.
	if ( is_null( $r['group_id'] ) ) {
		$groups_table    = $wpdb->prefix . 'bp_groups';
		$groupmeta_table = $wpdb->prefix . 'bp_groups_groupmeta';

		// LEFT JOIN to groups table for status check (EVNT-06).
		$join .= " LEFT JOIN {$groups_table} AS g ON e.group_id = g.id";

		// LEFT JOIN to groupmeta for per-group site-calendar setting (EVNT-05).
		// meta_key is a literal constant — no user input — so prepare() is not needed here.
		$join .= " LEFT JOIN {$groupmeta_table} AS gm"
			. " ON g.id = gm.group_id AND gm.meta_key = 'bb_events_public_group_site_calendar'";

		// EVNT-06 (non-negotiable): standalone events pass; group events only if group is public.
		$where_clauses[] = "( e.group_id IS NULL OR g.status = 'public' )";

		// EVNT-05 (per-group opt-out): standalone events pass; group events pass unless
		// the group has explicitly set meta_value = '0'. When meta row is absent (NULL),
		// the event is included by default (NULL != '0' is true in MySQL).
		$where_clauses[] = "( e.group_id IS NULL OR gm.meta_value != '0' )";
	}

	/**
	 * Filter the WHERE clauses used in bp_events_get_events().
	 *
	 * Allows third-party code to add, modify, or remove WHERE conditions.
	 * Each array item is a raw SQL fragment joined with AND.
	 *
	 * @since BuddyBoss Events 1.0.0
	 *
	 * @param array $where_clauses Array of SQL WHERE fragments.
	 * @param array $r             Parsed query arguments.
	 */
	$where_clauses = apply_filters( 'bp_events_get_events_where_clauses', $where_clauses, $r );

	$where = implode( ' AND ', $where_clauses );

	$allowed_orderby = array( 'start_date', 'date_created', 'title', 'id' );
	$orderby         = in_array( $r['orderby'], $allowed_orderby, true ) ? 'e.' . $r['orderby'] : 'e.start_date';
	$order           = 'DESC' === strtoupper( $r['order'] ) ? 'DESC' : 'ASC';

	$offset = ( absint( $r['page'] ) - 1 ) * absint( $r['per_page'] );
	$limit  = absint( $r['per_page'] );

	$total = (int) $wpdb->get_var( "SELECT COUNT(e.id) FROM {$bp->events->table_name} e {$join} WHERE {$where}" );

	$event_ids = $wpdb->get_col(
		"SELECT e.id FROM {$bp->events->table_name} e {$join} WHERE {$where} ORDER BY {$orderby} {$order} LIMIT {$offset}, {$limit}"
	);

	$events = array();
	foreach ( $event_ids as $event_id ) {
		$events[] = new BP_Event( $event_id );
	}

	return array(
		'events' => $events,
		'total'  => $total,
	);
}

/** Permissions ****************************************************************/

/**
 * Check if a user can create events.
 *
 * @since BuddyBoss Events 1.0.0
 *
 * @param int      $user_id  User ID.
 * @param int|null $group_id Group ID (optional).
 * @return bool
 */
function bp_events_user_can_create( $user_id = 0, $group_id = null ) {
	if ( ! $user_id ) {
		$user_id = bp_loggedin_user_id();
	}

	if ( ! $user_id ) {
		return false;
	}

	// Site admins can always create.
	if ( bp_current_user_can( 'bp_moderate' ) ) {
		return true;
	}

	$permission = bp_get_option( 'bb_events_creation_permission', 'members' );

	switch ( $permission ) {
		case 'admins':
			return false;

		case 'organizers':
			// Only group admins/moderators.
			if ( ! empty( $group_id ) ) {
				return groups_is_user_admin( $user_id, $group_id ) || groups_is_user_mod( $user_id, $group_id );
			}
			return false;

		case 'members':
		default:
			return is_user_logged_in();
	}
}

/** Moderation *****************************************************************/

/**
 * Check if event moderation queue is enabled.
 *
 * @since BuddyBoss Events 1.0.0
 * @return bool
 */
function bp_events_moderation_enabled() {
	return (bool) bp_get_option( 'bb_events_moderation_enabled', false );
}

/** Slugs & URLs ***************************************************************/

/**
 * Get the events root slug.
 *
 * @since BuddyBoss Events 1.0.0
 * @return string
 */
function bp_get_events_root_slug() {
	return apply_filters( 'bp_get_events_root_slug', bp_get_option( 'bb_events_root_slug', 'events' ) );
}

/**
 * Generate a unique slug for an event title.
 *
 * @since BuddyBoss Events 1.0.0
 *
 * @param string $title Event title.
 * @return string Unique slug.
 */
function bp_events_generate_slug( $title ) {
	global $wpdb;

	$bp           = buddypress();
	$base         = sanitize_title( $title );
	$slug         = $base;
	$suffix       = 1;

	while ( $wpdb->get_var( $wpdb->prepare( "SELECT id FROM {$bp->events->table_name} WHERE slug = %s", $slug ) ) ) {
		$slug = $base . '-' . $suffix;
		$suffix++;
	}

	return $slug;
}

/**
 * Get the URL for an event.
 *
 * @since BuddyBoss Events 1.0.0
 *
 * @param int|BP_Event $event Event ID or object.
 * @return string Event permalink.
 */
function bp_get_event_permalink( $event ) {
	if ( is_int( $event ) ) {
		$event = bp_events_get_event( $event );
	}

	if ( empty( $event ) ) {
		return '';
	}

	return trailingslashit( bp_get_root_url() . bp_get_events_root_slug() . '/' . $event->slug );
}

/** Settings helpers ***********************************************************/

/**
 * Get the event creation permission setting.
 *
 * @since BuddyBoss Events 1.0.0
 * @return string admins|organizers|members
 */
function bb_get_events_creation_permission() {
	return bp_get_option( 'bb_events_creation_permission', 'members' );
}

/**
 * Get the default calendar view.
 *
 * @since BuddyBoss Events 1.0.0
 * @return string month|week|list
 */
function bb_get_events_default_calendar_view() {
	return bp_get_option( 'bb_events_default_calendar_view', 'month' );
}

/**
 * Check if public groups can post events to the site calendar.
 *
 * @since BuddyBoss Events 1.0.0
 * @return bool
 */
function bb_events_allow_public_group_site_calendar() {
	return (bool) bp_get_option( 'bb_events_public_group_site_calendar', true );
}

/** Event Meta *****************************************************************/

/**
 * Get event meta value.
 *
 * @since BuddyBoss Events 1.0.0
 *
 * @param int    $event_id  Event ID.
 * @param string $meta_key  Meta key.
 * @param bool   $single    Whether to return a single value. Default false.
 * @return mixed Meta value(s) or empty string if not found.
 */
function bp_events_get_meta( $event_id, $meta_key = '', $single = false ) {
	global $wpdb;

	$bp = buddypress();

	if ( empty( $event_id ) ) {
		return false;
	}

	$cache_key = $event_id . '_' . $meta_key;
	$cached    = wp_cache_get( $cache_key, 'bp_event_meta' );

	if ( false !== $cached ) {
		return $single ? $cached : array( $cached );
	}

	if ( ! empty( $meta_key ) ) {
		$results = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT meta_value FROM {$bp->events->table_name_meta} WHERE event_id = %d AND meta_key = %s",
				$event_id,
				$meta_key
			)
		);
	} else {
		$results = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT meta_key, meta_value FROM {$bp->events->table_name_meta} WHERE event_id = %d",
				$event_id
			)
		);
	}

	if ( empty( $results ) ) {
		return $single ? '' : array();
	}

	$values = wp_list_pluck( $results, 'meta_value' );

	if ( $single ) {
		$value = maybe_unserialize( $values[0] );
		wp_cache_set( $cache_key, $value, 'bp_event_meta' );
		return $value;
	}

	return array_map( 'maybe_unserialize', $values );
}

/**
 * Add event meta.
 *
 * @since BuddyBoss Events 1.0.0
 *
 * @param int    $event_id   Event ID.
 * @param string $meta_key   Meta key.
 * @param mixed  $meta_value Meta value.
 * @param bool   $unique     Whether to enforce unique meta keys. Default false.
 * @return int|false New meta ID on success, false on failure.
 */
function bp_events_add_meta( $event_id, $meta_key, $meta_value, $unique = false ) {
	global $wpdb;

	$bp = buddypress();

	if ( empty( $event_id ) || empty( $meta_key ) ) {
		return false;
	}

	if ( $unique ) {
		$existing = bp_events_get_meta( $event_id, $meta_key, true );
		if ( '' !== $existing ) {
			return false;
		}
	}

	$result = $wpdb->insert(
		$bp->events->table_name_meta,
		array(
			'event_id'   => $event_id,
			'meta_key'   => $meta_key,
			'meta_value' => maybe_serialize( $meta_value ),
		),
		array( '%d', '%s', '%s' )
	);

	if ( false === $result ) {
		return false;
	}

	wp_cache_delete( $event_id . '_' . $meta_key, 'bp_event_meta' );

	return $wpdb->insert_id;
}

/**
 * Update event meta. Inserts the meta if it does not exist.
 *
 * @since BuddyBoss Events 1.0.0
 *
 * @param int    $event_id   Event ID.
 * @param string $meta_key   Meta key.
 * @param mixed  $meta_value New meta value.
 * @param mixed  $prev_value Optional previous value to match. Default ''.
 * @return bool True on success, false on failure.
 */
function bp_events_update_meta( $event_id, $meta_key, $meta_value, $prev_value = '' ) {
	global $wpdb;

	$bp = buddypress();

	if ( empty( $event_id ) || empty( $meta_key ) ) {
		return false;
	}

	$existing = bp_events_get_meta( $event_id, $meta_key, true );

	if ( '' === $existing && ! is_numeric( $existing ) ) {
		// Insert new.
		$inserted = bp_events_add_meta( $event_id, $meta_key, $meta_value );
		return false !== $inserted;
	}

	$where = array(
		'event_id' => $event_id,
		'meta_key' => $meta_key,
	);

	if ( '' !== $prev_value ) {
		$where['meta_value'] = maybe_serialize( $prev_value );
	}

	$result = $wpdb->update(
		$bp->events->table_name_meta,
		array( 'meta_value' => maybe_serialize( $meta_value ) ),
		$where,
		array( '%s' ),
		array( '%d', '%s', '%s' )
	);

	wp_cache_delete( $event_id . '_' . $meta_key, 'bp_event_meta' );

	return false !== $result;
}

/**
 * Delete event meta.
 *
 * @since BuddyBoss Events 1.0.0
 *
 * @param int    $event_id   Event ID.
 * @param string $meta_key   Meta key.
 * @param mixed  $meta_value Optional meta value to match. Default ''.
 * @return bool True on success, false on failure.
 */
function bp_events_delete_meta( $event_id, $meta_key = '', $meta_value = '' ) {
	global $wpdb;

	$bp = buddypress();

	if ( empty( $event_id ) ) {
		return false;
	}

	$where = array( 'event_id' => $event_id );

	if ( ! empty( $meta_key ) ) {
		$where['meta_key'] = $meta_key;
	}

	if ( '' !== $meta_value ) {
		$where['meta_value'] = maybe_serialize( $meta_value );
	}

	$result = $wpdb->delete( $bp->events->table_name_meta, $where );

	if ( ! empty( $meta_key ) ) {
		wp_cache_delete( $event_id . '_' . $meta_key, 'bp_event_meta' );
	}

	return false !== $result;
}

/** Recurring Events ***********************************************************/

/**
 * Generate child occurrence rows for a published recurring event.
 *
 * Reads the event's recurrence_rule (RRULE string) and creates individual
 * child rows in bp_events for each occurrence up to 2 years ahead. The
 * first occurrence (the parent itself) is skipped. A duplicate-guard meta
 * key prevents re-generation when the window is already extended far enough.
 *
 * @since BuddyBoss Events 1.0.0
 *
 * @param BP_Event $event The parent event object. Must be published and have
 *                        a recurrence_rule set. Must not itself be a child
 *                        (parent_event_id must be null).
 * @return void
 */
function bp_events_generate_occurrences( $event ) {
	// Guard: only generate for published parent events with a rule.
	if ( 'published' !== $event->status ) {
		return;
	}

	if ( empty( $event->recurrence_rule ) ) {
		return;
	}

	if ( ! empty( $event->parent_event_id ) ) {
		return;
	}

	// Duplicate guard: if already generated within 90 days of the 2-year window, skip.
	$until_date = new DateTime( '+2 years' );

	if ( ! empty( $event->id ) ) {
		$generated_until = bp_events_get_meta( $event->id, 'occurrences_generated_until', true );

		if ( ! empty( $generated_until ) ) {
			$generated_until_dt = new DateTime( $generated_until );
			$threshold_dt       = new DateTime( '+2 years -90 days' );

			if ( $generated_until_dt >= $threshold_dt ) {
				return;
			}
		}
	}

	// Require the RRule library.
	$rrule_path = BP_PLUGIN_DIR . 'src/bp-events/includes/lib/php-rrule/RRule.php';

	if ( ! file_exists( $rrule_path ) ) {
		error_log( 'BuddyBoss Events: php-rrule library not found at ' . $rrule_path );
		return;
	}

	require_once $rrule_path;

	try {
		$rule_string = $event->recurrence_rule;

		// Prepend DTSTART if not present in the rule string.
		if ( false === strpos( strtoupper( $rule_string ), 'DTSTART' ) ) {
			$dtstart     = gmdate( 'Ymd\THis\Z', strtotime( $event->start_date ) );
			$rule_string = 'DTSTART:' . $dtstart . "\nRRULE:" . $rule_string;
		}

		$rrule = new RRule\RRule( $rule_string );
		$begin = new DateTime( $event->start_date );
		$dates = $rrule->getOccurrencesBetween( $begin, $until_date );

		if ( empty( $dates ) ) {
			return;
		}

		// Calculate event duration in seconds to preserve it on each child.
		$duration_seconds = strtotime( $event->end_date ) - strtotime( $event->start_date );
		if ( $duration_seconds < 0 ) {
			$duration_seconds = 0;
		}

		$first = true;

		foreach ( $dates as $date ) {
			// Skip the first occurrence — that is the parent event itself.
			if ( $first ) {
				$first = false;
				continue;
			}

			$child_start = $date->format( 'Y-m-d H:i:s' );
			$child_end   = date( 'Y-m-d H:i:s', $date->getTimestamp() + $duration_seconds );

			bp_events_create_event(
				array(
					'title'            => $event->title,
					'description'      => $event->description,
					'organizer_id'     => $event->organizer_id,
					'group_id'         => $event->group_id,
					'type'             => $event->type,
					'venue_name'       => $event->venue_name,
					'venue_address'    => $event->venue_address,
					'venue_lat'        => $event->venue_lat,
					'venue_lng'        => $event->venue_lng,
					'virtual_url'      => $event->virtual_url,
					'virtual_type'     => $event->virtual_type,
					'start_date'       => $child_start,
					'end_date'         => $child_end,
					'timezone'         => $event->timezone,
					'capacity'         => $event->capacity,
					'status'           => 'published',
					'recurrence_rule'  => '',
					'parent_event_id'  => $event->id,
				)
			);
		}

		// Record the generation window so we don't duplicate on next save.
		if ( ! empty( $event->id ) ) {
			bp_events_update_meta( $event->id, 'occurrences_generated_until', $until_date->format( 'Y-m-d' ) );
		}
	} catch ( Exception $e ) {
		error_log( 'BuddyBoss Events: RRule exception during occurrence generation — ' . $e->getMessage() );
	}
}

/**
 * Extend occurrences for a single recurring parent event.
 *
 * Generates occurrences from the current max child date forward to fill
 * the 2-year window. Called by the daily cron job.
 *
 * @since BuddyBoss Events 1.0.0
 *
 * @param BP_Event $event The parent event object.
 * @return void
 */
function bp_events_extend_occurrences_for_event( $event ) {
	global $wpdb;

	$bp = buddypress();

	if ( 'published' !== $event->status || empty( $event->recurrence_rule ) ) {
		return;
	}

	$rrule_path = BP_PLUGIN_DIR . 'src/bp-events/includes/lib/php-rrule/RRule.php';

	if ( ! file_exists( $rrule_path ) ) {
		return;
	}

	require_once $rrule_path;

	// Find the furthest existing child start_date.
	$max_child_date = $wpdb->get_var(
		$wpdb->prepare(
			"SELECT MAX(start_date) FROM {$bp->events->table_name} WHERE parent_event_id = %d",
			$event->id
		)
	);

	if ( empty( $max_child_date ) ) {
		// No children at all — delegate to full generation.
		bp_events_generate_occurrences( $event );
		return;
	}

	$until_date = new DateTime( '+2 years' );
	$threshold  = new DateTime( '+2 years -90 days' );
	$max_dt     = new DateTime( $max_child_date );

	// Already extended far enough.
	if ( $max_dt >= $threshold ) {
		return;
	}

	try {
		$rule_string = $event->recurrence_rule;

		if ( false === strpos( strtoupper( $rule_string ), 'DTSTART' ) ) {
			$dtstart     = gmdate( 'Ymd\THis\Z', strtotime( $event->start_date ) );
			$rule_string = 'DTSTART:' . $dtstart . "\nRRULE:" . $rule_string;
		}

		$rrule    = new RRule\RRule( $rule_string );
		$from_dt  = new DateTime( $max_child_date );
		$from_dt->modify( '+1 second' );
		$dates = $rrule->getOccurrencesBetween( $from_dt, $until_date );

		if ( empty( $dates ) ) {
			return;
		}

		$duration_seconds = strtotime( $event->end_date ) - strtotime( $event->start_date );
		if ( $duration_seconds < 0 ) {
			$duration_seconds = 0;
		}

		foreach ( $dates as $date ) {
			$child_start = $date->format( 'Y-m-d H:i:s' );
			$child_end   = date( 'Y-m-d H:i:s', $date->getTimestamp() + $duration_seconds );

			bp_events_create_event(
				array(
					'title'           => $event->title,
					'description'     => $event->description,
					'organizer_id'    => $event->organizer_id,
					'group_id'        => $event->group_id,
					'type'            => $event->type,
					'venue_name'      => $event->venue_name,
					'venue_address'   => $event->venue_address,
					'venue_lat'       => $event->venue_lat,
					'venue_lng'       => $event->venue_lng,
					'virtual_url'     => $event->virtual_url,
					'virtual_type'    => $event->virtual_type,
					'start_date'      => $child_start,
					'end_date'        => $child_end,
					'timezone'        => $event->timezone,
					'capacity'        => $event->capacity,
					'status'          => 'published',
					'recurrence_rule' => '',
					'parent_event_id' => $event->id,
				)
			);
		}

		bp_events_update_meta( $event->id, 'occurrences_generated_until', $until_date->format( 'Y-m-d' ) );
	} catch ( Exception $e ) {
		error_log( 'BuddyBoss Events: RRule extension exception — ' . $e->getMessage() );
	}
}

/**
 * Split a recurring series at a given occurrence.
 *
 * Deletes all child rows from the split point onward in the original series,
 * creates a new parent event with the provided updated fields, and generates
 * fresh children for the new series.
 *
 * @since BuddyBoss Events 1.0.0
 *
 * @param int   $original_parent_id      ID of the original parent event.
 * @param int   $split_from_occurrence_id ID of the first occurrence that
 *                                         belongs to the new series.
 * @param array $updated_fields          Fields to override on the new parent.
 * @return int|false New parent event ID on success, false on failure.
 */
function bp_events_split_series( $original_parent_id, $split_from_occurrence_id, $updated_fields = array() ) {
	global $wpdb;

	$bp = buddypress();

	$original_parent = bp_events_get_event( $original_parent_id );

	if ( ! $original_parent ) {
		return false;
	}

	$split_occurrence = bp_events_get_event( $split_from_occurrence_id );

	if ( ! $split_occurrence ) {
		return false;
	}

	$split_start_date = $split_occurrence->start_date;

	// Delete all children of the original parent at or after the split date.
	$wpdb->query(
		$wpdb->prepare(
			"DELETE FROM {$bp->events->table_name} WHERE parent_event_id = %d AND start_date >= %s",
			$original_parent_id,
			$split_start_date
		)
	);

	// Update original parent RRULE to end just before the split point.
	$until_dt    = new DateTime( $split_start_date );
	$until_dt->modify( '-1 second' );
	$until_str   = $until_dt->format( 'Ymd\THis\Z' );

	$original_rule = $original_parent->recurrence_rule;

	// Append or replace UNTIL clause.
	if ( false !== strpos( strtoupper( $original_rule ), 'UNTIL=' ) ) {
		$original_rule = preg_replace( '/UNTIL=[^;]+/i', 'UNTIL=' . $until_str, $original_rule );
	} elseif ( false !== strpos( strtoupper( $original_rule ), 'COUNT=' ) ) {
		// Remove COUNT and add UNTIL instead.
		$original_rule = preg_replace( '/;?COUNT=\d+/i', '', $original_rule );
		$original_rule .= ';UNTIL=' . $until_str;
	} else {
		$original_rule .= ';UNTIL=' . $until_str;
	}

	bp_events_update_event( $original_parent_id, array( 'recurrence_rule' => $original_rule ) );

	// Build new parent args by inheriting original parent fields.
	$new_parent_args = array(
		'title'           => $original_parent->title,
		'description'     => $original_parent->description,
		'organizer_id'    => $original_parent->organizer_id,
		'group_id'        => $original_parent->group_id,
		'type'            => $original_parent->type,
		'venue_name'      => $original_parent->venue_name,
		'venue_address'   => $original_parent->venue_address,
		'venue_lat'       => $original_parent->venue_lat,
		'venue_lng'       => $original_parent->venue_lng,
		'virtual_url'     => $original_parent->virtual_url,
		'virtual_type'    => $original_parent->virtual_type,
		'start_date'      => $split_start_date,
		'end_date'        => $split_occurrence->end_date,
		'timezone'        => $original_parent->timezone,
		'capacity'        => $original_parent->capacity,
		'status'          => 'published',
		'recurrence_rule' => $original_parent->recurrence_rule,
		'parent_event_id' => null,
	);

	// Apply caller overrides.
	foreach ( $updated_fields as $field => $value ) {
		$new_parent_args[ $field ] = $value;
	}

	$new_parent_id = bp_events_create_event( $new_parent_args );

	if ( ! $new_parent_id ) {
		return false;
	}

	// Generate occurrences for the new parent series.
	$new_parent = bp_events_get_event( $new_parent_id );

	if ( $new_parent ) {
		bp_events_generate_occurrences( $new_parent );
	}

	return $new_parent_id;
}

/**
 * Detach a single occurrence from its series.
 *
 * Clears parent_event_id and recurrence_rule on the given child row,
 * and applies any additional field updates. Sibling occurrences are
 * left untouched.
 *
 * @since BuddyBoss Events 1.0.0
 *
 * @param int   $occurrence_id  ID of the child occurrence to detach.
 * @param array $updated_fields Optional fields to update on the detached row.
 * @return bool True on success, false on failure.
 */
function bp_events_detach_occurrence( $occurrence_id, $updated_fields = array() ) {
	global $wpdb;

	$bp = buddypress();

	$occurrence = bp_events_get_event( $occurrence_id );

	if ( ! $occurrence ) {
		return false;
	}

	$fields = array_merge(
		$updated_fields,
		array(
			'recurrence_rule' => '',
		)
	);

	// Directly update parent_event_id to NULL — bp_events_update_event() allowlist
	// does not include parent_event_id. wpdb->update() would cast null to 0 with %d,
	// so we use a raw prepared query to set the column to genuine SQL NULL.
	$result = $wpdb->query(
		$wpdb->prepare(
			"UPDATE {$bp->events->table_name} SET parent_event_id = NULL WHERE id = %d",
			$occurrence_id
		)
	);

	if ( false === $result ) {
		return false;
	}

	wp_cache_delete( $occurrence_id, 'bp_events' );

	return bp_events_update_event( $occurrence_id, $fields );
}

/** Notifications **************************************************************/

/**
 * Format event notifications for the BuddyBoss notification system.
 *
 * @since BuddyBoss Events 1.0.0
 *
 * @param string $action            Notification action.
 * @param int    $item_id           Event ID.
 * @param int    $secondary_item_id Secondary item ID.
 * @param int    $total_items       Number of notifications.
 * @param string $format            Return format: string|array.
 * @return string|array Formatted notification.
 */
function bp_events_format_notifications( $action, $item_id, $secondary_item_id, $total_items, $format = 'string' ) {
	switch ( $action ) {
		case 'event_invite':
			$event = bp_events_get_event( $item_id );
			$text  = sprintf(
				/* translators: %s: event title */
				__( 'You have been invited to %s', 'buddyboss' ),
				$event ? $event->title : __( 'an event', 'buddyboss' )
			);
			$link = $event ? bp_get_event_permalink( $event ) : bp_get_events_directory_url();
			break;

		case 'waitlist_spot_open':
			$event = bp_events_get_event( $item_id );
			$text  = sprintf(
				/* translators: %s: event title */
				__( 'A spot has opened for %s — RSVP now!', 'buddyboss' ),
				$event ? $event->title : __( 'an event', 'buddyboss' )
			);
			$link = $event ? bp_get_event_permalink( $event ) : bp_get_events_directory_url();
			break;

		default:
			$text = __( 'You have a new event notification', 'buddyboss' );
			$link = bp_get_events_directory_url();
			break;
	}

	if ( 'array' === $format ) {
		return array( 'text' => $text, 'link' => $link );
	}

	return '<a href="' . esc_url( $link ) . '">' . esc_html( $text ) . '</a>';
}

/**
 * Get the events directory URL.
 *
 * @since BuddyBoss Events 1.0.0
 * @return string
 */
function bp_get_events_directory_url() {
	return trailingslashit( bp_get_root_url() . bp_get_events_root_slug() );
}

/** RSVP & Attendees ***********************************************************/

/**
 * Check if a user can RSVP to an event.
 *
 * Returns true on success or a WP_Error describing why the user cannot RSVP.
 *
 * @since BuddyBoss Events 1.0.0
 *
 * @param int $event_id Event ID.
 * @param int $user_id  User ID. Defaults to current user.
 * @return true|WP_Error True if user can RSVP, WP_Error on failure.
 */
function bp_events_user_can_rsvp( $event_id, $user_id = 0 ) {
	if ( ! $user_id ) {
		$user_id = bp_loggedin_user_id();
	}

	if ( ! $user_id || ! is_user_logged_in() ) {
		return new WP_Error(
			'bp_rest_authorization_required',
			__( 'You must be logged in to RSVP.', 'buddyboss' ),
			array( 'status' => 401 )
		);
	}

	$event = bp_events_get_event( $event_id );

	if ( ! $event ) {
		return new WP_Error(
			'bp_rest_events_not_found',
			__( 'Event not found.', 'buddyboss' ),
			array( 'status' => 404 )
		);
	}

	if ( 'published' !== $event->status ) {
		return new WP_Error(
			'bp_rest_events_rsvp_not_available',
			__( 'RSVP is not available for this event.', 'buddyboss' ),
			array( 'status' => 400 )
		);
	}

	// Check group membership restriction.
	$rsvp_group_id = (int) bp_events_get_meta( $event_id, 'rsvp_group_id', true );

	if ( $rsvp_group_id > 0 ) {
		if ( ! groups_is_user_member( $user_id, $rsvp_group_id ) ) {
			return new WP_Error(
				'bp_rest_events_rsvp_restricted',
				__( 'RSVP limited to members of this group.', 'buddyboss' ),
				array( 'status' => 403 )
			);
		}
	}

	return true;
}

/**
 * RSVP a user to an event.
 *
 * Registers the user if capacity allows, otherwise adds them to the waitlist.
 *
 * @since BuddyBoss Events 1.0.0
 *
 * @param int $event_id Event ID.
 * @param int $user_id  User ID. Defaults to current user.
 * @return string|false 'registered' or 'waitlisted' on success, false on failure.
 */
function bp_events_rsvp_event( $event_id, $user_id = 0 ) {
	global $wpdb;

	$bp = buddypress();

	if ( ! $user_id ) {
		$user_id = bp_loggedin_user_id();
	}

	if ( ! $user_id || ! $event_id ) {
		return false;
	}

	$event = bp_events_get_event( $event_id );

	if ( ! $event ) {
		return false;
	}

	// Count current registered attendees.
	$registered_count = (int) $wpdb->get_var( $wpdb->prepare(
		"SELECT COUNT(*) FROM {$bp->events->table_name_attendees} WHERE event_id = %d AND status = 'registered'",
		$event_id
	) );

	$at_capacity = ! is_null( $event->capacity ) && $registered_count >= (int) $event->capacity;
	$status      = $at_capacity ? 'waitlisted' : 'registered';

	$result = $wpdb->replace(
		$bp->events->table_name_attendees,
		array(
			'event_id'     => $event_id,
			'user_id'      => $user_id,
			'status'       => $status,
			'date_created' => bp_core_current_time(),
		),
		array( '%d', '%d', '%s', '%s' )
	);

	if ( false === $result ) {
		return false;
	}

	return $status;
}

/**
 * Cancel a user's RSVP for an event.
 *
 * Removes the attendee row. If the removed user was registered (not waitlisted),
 * notifies the waitlist that a spot has opened.
 *
 * @since BuddyBoss Events 1.0.0
 *
 * @param int $event_id Event ID.
 * @param int $user_id  User ID. Defaults to current user.
 * @return bool True on success, false on failure.
 */
function bp_events_cancel_rsvp( $event_id, $user_id = 0 ) {
	global $wpdb;

	$bp = buddypress();

	if ( ! $user_id ) {
		$user_id = bp_loggedin_user_id();
	}

	// Get current status before deleting.
	$current_status = $wpdb->get_var( $wpdb->prepare(
		"SELECT status FROM {$bp->events->table_name_attendees} WHERE event_id = %d AND user_id = %d",
		$event_id,
		$user_id
	) );

	$was_registered = ( 'registered' === $current_status );

	$result = $wpdb->delete(
		$bp->events->table_name_attendees,
		array(
			'event_id' => $event_id,
			'user_id'  => $user_id,
		),
		array( '%d', '%d' )
	);

	if ( $was_registered && false !== $result ) {
		bp_events_notify_waitlist( $event_id );
	}

	return false !== $result;
}

/**
 * Get attendees for an event.
 *
 * @since BuddyBoss Events 1.0.0
 *
 * @param int    $event_id Event ID.
 * @param string $status   Attendee status to filter by. Default 'registered'.
 * @return array Array of stdObjects with user_id, status, date_created. Empty array if none.
 */
function bp_events_get_attendees( $event_id, $status = 'registered' ) {
	global $wpdb;

	$bp = buddypress();

	$rows = $wpdb->get_results( $wpdb->prepare(
		"SELECT user_id, status, date_created
		 FROM {$bp->events->table_name_attendees}
		 WHERE event_id = %d AND status = %s
		 ORDER BY date_created ASC",
		$event_id,
		$status
	) );

	if ( empty( $rows ) ) {
		return array();
	}

	return $rows;
}

/**
 * Get the waitlist for an event.
 *
 * Wrapper around bp_events_get_attendees() filtered to status='waitlisted'.
 *
 * @since BuddyBoss Events 1.0.0
 *
 * @param int $event_id Event ID.
 * @return array Array of stdObjects with user_id, status, date_created.
 */
function bp_events_get_waitlist( $event_id ) {
	return bp_events_get_attendees( $event_id, 'waitlisted' );
}

/**
 * Handle capacity change and trigger waitlist notification if a spot opened.
 *
 * This function is responsible ONLY for the notification logic. The REST
 * update_item() handler saves the new capacity value via bp_events_update_event()
 * before calling this function, so there is no double-save.
 *
 * Triggers bp_events_notify_waitlist() when:
 *   - $new_capacity is NULL (unlimited) — all spots open immediately.
 *   - $new_capacity > current registered count — at least one spot is free.
 *
 * Does nothing (returns true silently) when there are no waitlisted users.
 *
 * @since BuddyBoss Events 1.0.0
 *
 * @param int      $event_id     Event ID.
 * @param int|null $new_capacity New capacity value, or NULL for unlimited.
 * @return true Always returns true.
 */
function bp_events_update_capacity( $event_id, $new_capacity ) {
	$registered = bp_events_get_attendees( $event_id, 'registered' );
	$waitlisted = bp_events_get_waitlist( $event_id );

	if ( empty( $waitlisted ) ) {
		return true;
	}

	$spot_opened = is_null( $new_capacity ) || (int) $new_capacity > count( $registered );

	if ( $spot_opened ) {
		bp_events_notify_waitlist( $event_id );
	}

	return true;
}

/**
 * Notify all waitlisted users that a spot has opened for an event.
 *
 * Sends both a BuddyBoss notification and an email to each waitlisted user.
 * This is a broadcast model: the first person to re-RSVP gets the spot.
 *
 * @since BuddyBoss Events 1.0.0
 *
 * @param int $event_id Event ID.
 * @return void
 */
function bp_events_notify_waitlist( $event_id ) {
	global $wpdb;

	$bp = buddypress();

	// Get all waitlisted user IDs.
	$user_ids = $wpdb->get_col( $wpdb->prepare(
		"SELECT user_id FROM {$bp->events->table_name_attendees} WHERE event_id = %d AND status = 'waitlisted'",
		$event_id
	) );

	if ( empty( $user_ids ) ) {
		return;
	}

	$event = bp_events_get_event( $event_id );

	if ( ! $event ) {
		return;
	}

	$event_title     = $event->title;
	$event_permalink = bp_get_event_permalink( $event );

	foreach ( $user_ids as $user_id ) {
		// BuddyBoss in-app notification.
		bp_notifications_add_notification( array(
			'user_id'           => (int) $user_id,
			'item_id'           => $event_id,
			'secondary_item_id' => 0,
			'component_name'    => 'events',
			'component_action'  => 'waitlist_spot_open',
			'is_new'            => 1,
			'allow_duplicate'   => false,
		) );

		// Email notification.
		$user = get_userdata( (int) $user_id );

		if ( $user && ! empty( $user->user_email ) ) {
			$subject = sprintf(
				/* translators: %s: event title */
				__( 'A spot opened for %s', 'buddyboss' ),
				$event_title
			);

			$message = sprintf(
				/* translators: 1: event title, 2: event URL */
				__( "A spot has opened for %1\$s.\n\nRSVP now — this is a broadcast notification sent to all waitlisted attendees. The first person to RSVP will secure the spot.\n\n%2\$s", 'buddyboss' ),
				$event_title,
				$event_permalink
			);

			wp_mail( $user->user_email, $subject, $message );
		}
	}
}
