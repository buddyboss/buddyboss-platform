<?php
/**
 * BuddyBoss Zoom Meeting
 *
 * @since BuddyBoss 1.2.10
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Database interaction class for the BuddyBoss zoom meeting.
 * Instance methods are available for creating/editing an meeting,
 * static methods for querying meeting.
 *
 * @since BuddyPress 1.2.10
 */
class BP_Zoom_Meeting {

	/** Properties ************************************************************/

	/**
	 * ID of the media item.
	 *
	 * @since BuddyBoss 1.2.10
	 * @var int
	 */
	var $id;

	/**
	 * Group ID of the meeting item.
	 *
	 * @since BuddyBoss 1.2.10
	 * @var int
	 */
	var $group_id;

	/**
	 * Title of the meeting item.
	 *
	 * @since BuddyBoss 1.2.10
	 * @var string
	 */
	var $title;

	/**
	 * User ID of the meeting item.
	 *
	 * @since BuddyBoss 1.2.10
	 * @var string
	 */
	var $user_id;

	/**
	 * Start date of the meeting item.
	 *
	 * @since BuddyBoss 1.2.10
	 * @var string
	 */
	var $start_date;

	/**
	 * timezone of the meeting item.
	 *
	 * @since BuddyBoss 1.2.10
	 * @var string
	 */
	var $timezone;

	/**
	 * password of the meeting item.
	 *
	 * @since BuddyBoss 1.2.10
	 * @var string
	 */
	var $password;

	/**
	 * duration of the meeting item.
	 *
	 * @since BuddyBoss 1.2.10
	 * @var int
	 */
	var $duration;

	/**
	 * join before host of the meeting item.
	 *
	 * @since BuddyBoss 1.2.10
	 * @var bool
	 */
	var $join_before_host;

	/**
	 * host video of the media item.
	 *
	 * @since BuddyBoss 1.2.10
	 * @var bool
	 */
	var $host_video;

	/**
	 * participants video of the media item.
	 *
	 * @since BuddyBoss 1.2.10
	 * @var bool
	 */
	var $participants_video;

	/**
	 * mute participants of the media item.
	 *
	 * @since BuddyBoss 1.2.10
	 * @var bool
	 */
	var $mute_participants;

	/**
	 * Meeting authetication.
	 *
	 * @since BuddyBoss 1.2.10
	 * @var bool
	 */
	var $meeting_authentication;

	/**
	 * Enforce login.
	 *
	 * @since BuddyBoss 1.2.10
	 * @var bool
	 */
	var $enforce_login;

	/**
	 * Waiting room.
	 *
	 * @since BuddyBoss 1.2.10
	 * @var bool
	 */
	var $waiting_room;

	/**
	 * Auto recording of the media item.
	 *
	 * @since BuddyBoss 1.2.10
	 * @var string
	 */
	var $auto_recording;

	/**
	 * Alternative host ids of the media item.
	 *
	 * @since BuddyBoss 1.2.10
	 * @var string
	 */
	var $alternative_host_ids;

	/**
	 * zoom details of the media item.
	 *
	 * @since BuddyBoss 1.2.10
	 * @var string
	 */
	var $zoom_details;

	/**
	 * zoom start url of the media item.
	 *
	 * @since BuddyBoss 1.2.10
	 * @var string
	 */
	var $zoom_start_url;

	/**
	 * zoom join url of the media item.
	 *
	 * @since BuddyBoss 1.2.10
	 * @var string
	 */
	var $zoom_join_url;

	/**
	 * zoom meeting id of the media item.
	 *
	 * @since BuddyBoss 1.2.10
	 * @var string
	 */
	var $zoom_meeting_id;

	/**
	 * Error holder.
	 *
	 * @since BuddyBoss 1.2.10
	 *
	 * @var WP_Error
	 */
	public $errors;

	/**
	 * Error type to return. Either 'bool' or 'wp_error'.
	 *
	 * @since BuddyBoss 1.2.10
	 *
	 * @var string
	 */
	public $error_type = 'bool';

	/**
	 * Constructor method.
	 *
	 * @since BuddyBoss 1.2.10
	 *
	 * @param int|bool $id Optional. The ID of a specific meeting item.
	 */
	function __construct( $id = false ) {
		// Instantiate errors object.
		$this->errors = new WP_Error();

		if ( ! empty( $id ) ) {
			$this->id = (int) $id;
			$this->populate();
		}
	}

	/**
	 * Populate the object with data about the specific meeting item.
	 *
	 * @since BuddyBoss 1.2.10
	 */
	public function populate() {

		global $wpdb;

		$row = wp_cache_get( $this->id, 'bp_meeting' );

		if ( false === $row ) {
			$bp  = buddypress();
			$row = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$bp->table_prefix}bp_zoom_meetings WHERE id = %d", $this->id ) );

			wp_cache_set( $this->id, $row, 'bp_meeting' );
		}

		if ( empty( $row ) ) {
			$this->id = 0;
			return;
		}

		$this->id                     = (int) $row->id;
		$this->group_id               = (int) $row->group_id;
		$this->title                  = $row->title;
		$this->user_id                = $row->user_id;
		$this->start_date             = $row->start_date;
		$this->timezone               = $row->timezone;
		$this->password               = $row->password;
		$this->duration               = $row->duration;
		$this->join_before_host       = (bool) $row->join_before_host;
		$this->host_video             = (bool) $row->host_video;
		$this->participants_video     = (bool) $row->participants_video;
		$this->mute_participants      = (bool) $row->mute_participants;
		$this->meeting_authentication = (bool) $row->meeting_authentication;
		$this->enforce_login          = (bool) $row->enforce_login;
		$this->waiting_room           = (bool) $row->waiting_room;
		$this->auto_recording         = $row->auto_recording;
		$this->alternative_host_ids   = $row->alternative_host_ids;
		$this->zoom_details           = $row->zoom_details;
		$this->zoom_start_url         = $row->zoom_start_url;
		$this->zoom_join_url          = $row->zoom_join_url;
		$this->zoom_meeting_id        = $row->zoom_meeting_id;
	}

	/**
	 * Save the meeting item to the database.
	 *
	 * @since BuddyBoss 1.2.10
	 *
	 * @return WP_Error|bool True on success.
	 */
	public function save() {

		global $wpdb;

		$bp = buddypress();

		$this->id                     = apply_filters_ref_array( 'bp_zoom_meeting_id_before_save', array(
			$this->id,
			&$this
		) );
		$this->group_id               = apply_filters_ref_array( 'bp_zoom_meeting_group_id_before_save', array(
			$this->group_id,
			&$this
		) );
		$this->title                  = apply_filters_ref_array( 'bp_zoom_meeting_title_before_save', array(
			$this->title,
			&$this
		) );
		$this->user_id                = apply_filters_ref_array( 'bp_zoom_meeting_user_id_before_save', array(
			$this->user_id,
			&$this
		) );
		$this->start_date             = apply_filters_ref_array( 'bp_zoom_meeting_start_date_before_save', array(
			$this->start_date,
			&$this
		) );
		$this->timezone               = apply_filters_ref_array( 'bp_zoom_meeting_timezone_before_save', array(
			$this->timezone,
			&$this
		) );
		$this->password               = apply_filters_ref_array( 'bp_zoom_meeting_password_before_save', array(
			$this->password,
			&$this
		) );
		$this->duration               = apply_filters_ref_array( 'bp_zoom_meeting_duration_before_save', array(
			$this->duration,
			&$this
		) );
		$this->join_before_host       = apply_filters_ref_array( 'bp_zoom_meeting_join_before_host_before_save', array(
			$this->join_before_host,
			&$this
		) );
		$this->host_video             = apply_filters_ref_array( 'bp_zoom_meeting_host_video_before_save', array(
			$this->host_video,
			&$this
		) );
		$this->participants_video     = apply_filters_ref_array( 'bp_zoom_meeting_participants_video_before_save', array(
			$this->participants_video,
			&$this
		) );
		$this->mute_participants      = apply_filters_ref_array( 'bp_zoom_meeting_mute_participants_before_save', array(
			$this->mute_participants,
			&$this
		) );
		$this->meeting_authentication = apply_filters_ref_array( 'bp_zoom_meeting_meeting_authentication_before_save', array(
			$this->meeting_authentication,
			&$this
		) );
		$this->enforce_login          = apply_filters_ref_array( 'bp_zoom_meeting_enforce_login_before_save', array(
			$this->enforce_login,
			&$this
		) );
		$this->waiting_room           = apply_filters_ref_array( 'bp_zoom_meeting_waiting_room_before_save', array(
			$this->waiting_room,
			&$this
		) );
		$this->auto_recording         = apply_filters_ref_array( 'bp_zoom_meeting_auto_recording_before_save', array(
			$this->auto_recording,
			&$this
		) );
		$this->alternative_host_ids   = apply_filters_ref_array( 'bp_zoom_meeting_alternative_host_ids_before_save', array(
			$this->alternative_host_ids,
			&$this
		) );
		$this->zoom_details           = apply_filters_ref_array( 'bp_zoom_meeting_zoom_details_before_save', array(
			$this->zoom_details,
			&$this
		) );
		$this->zoom_start_url         = apply_filters_ref_array( 'bp_zoom_meeting_zoom_start_url_before_save', array(
			$this->zoom_start_url,
			&$this
		) );
		$this->zoom_join_url          = apply_filters_ref_array( 'bp_zoom_meeting_zoom_join_url_before_save', array(
			$this->zoom_join_url,
			&$this
		) );
		$this->zoom_meeting_id        = apply_filters_ref_array( 'bp_zoom_meeting_zoom_meeting_id_before_save', array(
			$this->zoom_meeting_id,
			&$this
		) );

		/**
		 * Fires before the current meeting item gets saved.
		 *
		 * Please use this hook to filter the properties above. Each part will be passed in.
		 *
		 * @since BuddyBoss 1.2.10
		 *
		 * @param BP_Zoom_Meeting $this Current instance of the meeting item being saved. Passed by reference.
		 */
		do_action_ref_array( 'bp_zoom_meeting_before_save', array( &$this ) );

		if ( 'wp_error' === $this->error_type && $this->errors->get_error_code() ) {
			return $this->errors;
		}

		if ( empty( $this->user_id ) ) {
			if ( 'bool' === $this->error_type ) {
				return false;
			} else {
				$this->errors->add( 'bp_zoom_meeting_missing_user_id' );

				return $this->errors;
			}
		}

		// If we have an existing ID, update the meeting item, otherwise insert it.
		if ( ! empty( $this->id ) ) {
			$q = $wpdb->prepare( "UPDATE {$bp->table_prefix}bp_zoom_meetings SET group_id = %d, title = %s, user_id = %s, start_date = %s, meeting_authentication = %d, password = %s, timezone = %s, duration = %d, join_before_host = %d, host_video = %d, participants_video = %d, mute_participants = %d, waiting_room = %d, enforce_login = %d, auto_recording = %s, alternative_host_ids = %s, zoom_details = %s, zoom_start_url = %s, zoom_join_url = %s, zoom_meeting_id = %d WHERE id = %d", $this->group_id, $this->title, $this->user_id, $this->start_date, $this->meeting_authentication, $this->password, $this->timezone, $this->duration, $this->join_before_host, $this->host_video, $this->participants_video, $this->mute_participants, $this->waiting_room, $this->enforce_login, $this->auto_recording, $this->alternative_host_ids, $this->zoom_details, $this->zoom_start_url, $this->zoom_join_url, $this->zoom_meeting_id, $this->id );
		} else {
			$q = $wpdb->prepare( "INSERT INTO {$bp->table_prefix}bp_zoom_meetings ( group_id, title, user_id, start_date, meeting_authentication, password, timezone, duration, join_before_host, host_video, participants_video, mute_participants, waiting_room, enforce_login, auto_recording, alternative_host_ids, zoom_details, zoom_start_url, zoom_join_url, zoom_meeting_id ) VALUES ( %d, %s, %s, %s, %d, %s, %s, %d, %d, %d, %d, %d, %d, %d, %s, %s, %s, %s, %s, %s )", $this->group_id, $this->title, $this->user_id, $this->start_date, $this->meeting_authentication, $this->password, $this->timezone, $this->duration, $this->join_before_host, $this->host_video, $this->participants_video, $this->mute_participants, $this->waiting_room, $this->enforce_login, $this->auto_recording, $this->alternative_host_ids, $this->zoom_details, $this->zoom_start_url, $this->zoom_join_url, $this->zoom_meeting_id );
		}

		if ( false === $wpdb->query( $q ) ) {
			return false;
		}

		// If this is a new meeting item, set the $id property.
		if ( empty( $this->id ) ) {
			$this->id = $wpdb->insert_id;
		}

		/**
		 * Fires after an meeting item has been saved to the database.
		 *
		 * @since BuddyBoss 1.2.10
		 *
		 * @param BP_Zoom_Meeting $this Current instance of meeting item being saved. Passed by reference.
		 */
		do_action_ref_array( 'bp_zoom_meeting_after_save', array( &$this ) );

		return true;
	}

	/** Static Methods ***************************************************/

	/**
	 * Get meeting items, as specified by parameters.
	 *
	 * @since BuddyBoss 1.2.10
	 *
	 * @param array $args {
	 *     An array of arguments. All items are optional.
	 *     @type int          $page              Which page of results to fetch. Using page=1 without per_page will result
	 *                                           in no pagination. Default: 1.
	 *     @type int|bool     $per_page          Number of results per page. Default: 20.
	 *     @type int|bool     $max               Maximum number of results to return. Default: false (unlimited).
	 *     @type string       $fields            Media fields to return. Pass 'ids' to get only the media IDs.
	 *                                           'all' returns full media objects.
	 *     @type string       $sort              ASC or DESC. Default: 'DESC'.
	 *     @type string       $order_by          Column to order results by.
	 *     @type array        $exclude           Array of media IDs to exclude. Default: false.
	 *     @type string       $search_terms      Limit results by a search term. Default: false.
	 *     @type string|bool  $count_total       If true, an additional DB query is run to count the total media items
	 *                                           for the query. Default: false.
	 * }
	 * @return array The array returned has two keys:
	 *               - 'total' is the count of located medias
	 *               - 'meetings' is an array of the located medias
	 */
	public static function get( $args = array() ) {

		global $wpdb;

		$bp = buddypress();
		$r = wp_parse_args(
			$args,
			array(
				'page'         => 1,               // The current page.
				'per_page'     => 20,              // Media items per page.
				'max'          => false,           // Max number of items to return.
				'fields'       => 'all',           // Fields to include.
				'sort'         => 'DESC',          // ASC or DESC.
				'order_by'     => 'id',             // Column to order by.
				'exclude'      => false,           // Array of ids to exclude.
				'in'           => false,           // Array of ids to limit query by (IN).
				'search_terms' => false,           // Terms to search by.
				'count_total'  => false,           // Whether or not to use count_total.
				'group_id'     => false,           // filter results by group id.
				'meeting_id'   => false,           // filter results by zoom meeting id.
				'since'        => false,           // return items since date.
				'from'         => false,           // return items from date.
			)
		);

		// Select conditions.
		$select_sql = 'SELECT DISTINCT m.id';

		$from_sql = " FROM {$bp->table_prefix}bp_zoom_meetings m";

		$join_sql = '';

		// Where conditions.
		$where_conditions = array();

		// Searching.
		if ( $r['search_terms'] ) {
			$search_terms_like              = '%' . bp_esc_like( $r['search_terms'] ) . '%';
			$where_conditions['search_sql'] = $wpdb->prepare( 'm.title LIKE %s', $search_terms_like );
		}

		// Sorting.
		$sort = $r['sort'];
		if ( $sort != 'ASC' && $sort != 'DESC' ) {
			$sort = 'DESC';
		}

		$order_by = 'm.id' . $r['order_by'];

		if ( ! empty( $r['group_id'] ) ) {
			$where_conditions['group'] = "m.group_id = {$r['group_id']}";
		}

		if ( ! empty( $r['meeting_id'] ) ) {
			$where_conditions['meeting'] = "m.zoom_meeting_id = {$r['meeting_id']}";
		}

		// Exclude specified items.
		if ( ! empty( $r['exclude'] ) ) {
			$exclude                     = implode( ',', wp_parse_id_list( $r['exclude'] ) );
			$where_conditions['exclude'] = "m.id NOT IN ({$exclude})";
		}

		// The specific ids to which you want to limit the query.
		if ( ! empty( $r['in'] ) ) {
			$in                     = implode( ',', wp_parse_id_list( $r['in'] ) );
			$where_conditions['in'] = "m.id IN ({$in})";

			// we want to disable limit query when include media ids
			$r['page']     = false;
			$r['per_page'] = false;
		}

		if ( ! empty( $r['since'] ) && empty( $r['in'] ) && empty( $r['meeting_id'] ) ) {
			// Validate that this is a proper Y-m-d H:i:s date.
			// Trick: parse to UNIX date then translate back.
			$translated_date = date( 'Y-m-d H:i:s', strtotime( $r['since'] ) );
			if ( $translated_date === $r['since'] ) {
				$where_conditions['date_filter'] = "m.start_date > '{$translated_date}'";
			}
		}

		if ( ! empty( $r['from'] ) ) {
			// Validate that this is a proper Y-m-d H:i:s date.
			// Trick: parse to UNIX date then translate back.
			$translated_date = date( 'Y-m-d H:i:s', strtotime( $r['from'] ) );
			if ( $translated_date === $r['from'] ) {
				$where_conditions['date_filter'] = "m.start_date < '{$translated_date}'";
			}
		}

		/**
		 * Filters the MySQL WHERE conditions for the Meeting items get method.
		 *
		 * @since BuddyBoss 1.2.10
		 *
		 * @param array  $where_conditions Current conditions for MySQL WHERE statement.
		 * @param array  $r                Parsed arguments passed into method.
		 * @param string $select_sql       Current SELECT MySQL statement at point of execution.
		 * @param string $from_sql         Current FROM MySQL statement at point of execution.
		 * @param string $join_sql         Current INNER JOIN MySQL statement at point of execution.
		 */
		$where_conditions = apply_filters( 'bp_zoom_meeting_get_where_conditions', $where_conditions, $r, $select_sql, $from_sql, $join_sql );

		if ( empty( $where_conditions ) ) {
			$where_conditions['2'] = '2';
		}

		// Join the where conditions together.
		if ( ! empty( $scope_query['sql'] ) ) {
			$where_sql = 'WHERE ( ' . join( ' AND ', $where_conditions ) . ' ) OR ( ' . $scope_query['sql'] . ' )';
		} else {
			$where_sql = 'WHERE ' . join( ' AND ', $where_conditions );
		}

		/**
		 * Filter the MySQL JOIN clause for the main meeting query.
		 *
		 * @since BuddyBoss 1.2.10
		 *
		 * @param string $join_sql   JOIN clause.
		 * @param array  $r          Method parameters.
		 * @param string $select_sql Current SELECT MySQL statement.
		 * @param string $from_sql   Current FROM MySQL statement.
		 * @param string $where_sql  Current WHERE MySQL statement.
		 */
		$join_sql = apply_filters( 'bp_zoom_meeting_get_join_sql', $join_sql, $r, $select_sql, $from_sql, $where_sql );

		// Sanitize page and per_page parameters.
		$page     = absint( $r['page'] );
		$per_page = absint( $r['per_page'] );

		$retval = array(
			'meetings'         => null,
			'total'          => null,
			'has_more_items' => null,
		);

		// Query first for media IDs.
		$meeting_ids_sql = "{$select_sql} {$from_sql} {$join_sql} {$where_sql} ORDER BY {$order_by} {$sort}, m.id {$sort}";

		if ( ! empty( $per_page ) && ! empty( $page ) ) {
			// We query for $per_page + 1 items in order to
			// populate the has_more_items flag.
			$meeting_ids_sql .= $wpdb->prepare( ' LIMIT %d, %d', absint( ( $page - 1 ) * $per_page ), $per_page + 1 );
		}

		/**
		 * Filters the paged meeting MySQL statement.
		 *
		 * @since BuddyBoss 1.2.10
		 *
		 * @param string $meeting_ids_sql    MySQL statement used to query for Meeting IDs.
		 * @param array  $r                Array of arguments passed into method.
		 */
		$meeting_ids_sql = apply_filters( 'bp_zoom_meeting_paged_meetings_sql', $meeting_ids_sql, $r );

		$cache_group = 'bp_meeting';

		$cached = bp_core_get_incremented_cache( $meeting_ids_sql, $cache_group );
		if ( false === $cached ) {
			$meeting_ids = $wpdb->get_col( $meeting_ids_sql );
			bp_core_set_incremented_cache( $meeting_ids_sql, $cache_group, $meeting_ids );
		} else {
			$meeting_ids = $cached;
		}

		$retval['has_more_items'] = ! empty( $per_page ) && count( $meeting_ids ) > $per_page;

		// If we've fetched more than the $per_page value, we
		// can discard the extra now.
		if ( ! empty( $per_page ) && count( $meeting_ids ) === $per_page + 1 ) {
			array_pop( $meeting_ids );
		}

		if ( 'ids' === $r['fields'] ) {
			$meetings = array_map( 'intval', $meeting_ids );
		} else {
			$meetings = self::get_meeting_data( $meeting_ids );
		}

		$retval['meetings'] = $meetings;

		// If $max is set, only return up to the max results.
		if ( ! empty( $r['count_total'] ) ) {

			/**
			 * Filters the total meeting MySQL statement.
			 *
			 * @since BuddyBoss 1.2.10
			 *
			 * @param string $value     MySQL statement used to query for total meetings.
			 * @param string $where_sql MySQL WHERE statement portion.
			 * @param string $sort      Sort direction for query.
			 */
			$total_meetings_sql = apply_filters( 'bp_zoom_meeting_total_medias_sql', "SELECT count(DISTINCT m.id) FROM {$bp->table_prefix}bp_zoom_meetings m {$join_sql} {$where_sql}", $where_sql, $sort );
			$cached           = bp_core_get_incremented_cache( $total_meetings_sql, $cache_group );
			if ( false === $cached ) {
				$total_meetings = $wpdb->get_var( $total_meetings_sql );
				bp_core_set_incremented_cache( $total_meetings_sql, $cache_group, $total_meetings );
			} else {
				$total_meetings = $cached;
			}

			if ( ! empty( $r['max'] ) ) {
				if ( (int) $total_meetings > (int) $r['max'] ) {
					$total_meetings = $r['max'];
				}
			}

			$retval['total'] = $total_meetings;
		}

		return $retval;
	}

	/**
	 * Convert media IDs to meeting objects, as expected in template loop.
	 *
	 * @since BuddyBoss 1.2.10
	 *
	 * @param array $media_ids Array of meeting IDs.
	 * @return array
	 */
	protected static function get_meeting_data( $meeting_ids = array() ) {
		global $wpdb;

		// Bail if no meeting ID's passed.
		if ( empty( $meeting_ids ) ) {
			return array();
		}

		// Get BuddyPress.
		$bp = buddypress();

		$meetings       = array();
		$uncached_ids = bp_get_non_cached_ids( $meeting_ids, 'bp_meeting' );

		// Prime caches as necessary.
		if ( ! empty( $uncached_ids ) ) {
			// Format the meeting ID's for use in the query below.
			$uncached_ids_sql = implode( ',', wp_parse_id_list( $uncached_ids ) );

			// Fetch data from meeting table, preserving order.
			$queried_adata = $wpdb->get_results( "SELECT * FROM {$bp->table_prefix}bp_zoom_meetings WHERE id IN ({$uncached_ids_sql})" );

			// Put that data into the placeholders created earlier,
			// and add it to the cache.
			foreach ( (array) $queried_adata as $adata ) {
				wp_cache_set( $adata->id, $adata, 'bp_meeting' );
			}
		}

		// Now fetch data from the cache.
		foreach ( $meeting_ids as $media_id ) {
			// Integer casting.
			$meeting = wp_cache_get( $media_id, 'bp_meeting' );
			if ( ! empty( $meeting ) ) {
				$meeting->id                     = (int) $meeting->id;
				$meeting->group_id               = (int) $meeting->group_id;
				$meeting->duration               = (int) $meeting->duration;
				$meeting->join_before_host       = (bool) $meeting->join_before_host;
				$meeting->host_video             = (bool) $meeting->host_video;
				$meeting->participants_video     = (bool) $meeting->participants_video;
				$meeting->mute_participants      = (bool) $meeting->mute_participants;
				$meeting->meeting_authentication = (bool) $meeting->meeting_authentication;
				$meeting->enforce_login          = (bool) $meeting->enforce_login;
				$meeting->waiting_room           = (bool) $meeting->waiting_room;
			}

			$meetings[] = $meeting;
		}

		return $meetings;
	}

	/**
	 * Delete meeting items from the database.
	 *
	 * To delete a specific meeting item, pass an 'id' parameter.
	 * Otherwise use the filters.
	 *
	 * @param array $args {
	 * @int    $id                Optional. The ID of a specific item to delete.
	 * @int    $meeting_id           Optional. The meeting ID to filter by.
	 * @int    $group_id           Optional. The group ID to filter by.
	 * }
	 *
	 * @return array|bool An array of deleted meeting IDs on success, false on failure.
	 * @since BuddyBoss 1.2.10
	 *
	 */
	public static function delete( $args = array(), $from = false ) {
		global $wpdb;

		$bp = buddypress();
		$r  = wp_parse_args(
			$args,
			array(
				'id'         => false,
				'meeting_id' => false,
				'group_id'   => false,
			)
		);

		// Setup empty array from where query arguments.
		$where_args = array();

		// ID.
		if ( ! empty( $r['id'] ) ) {
			$where_args[] = $wpdb->prepare( 'id = %d', $r['id'] );
		}

		// meeting ID.
		if ( ! empty( $r['meeting_id'] ) ) {
			$where_args[] = $wpdb->prepare( 'meeting_id = %d', $r['meeting_id'] );
		}

		// group ID.
		if ( ! empty( $r['group_id'] ) ) {
			$where_args[] = $wpdb->prepare( 'group_id = %d', $r['group_id'] );
		}

		// Bail if no where arguments.
		if ( empty( $where_args ) ) {
			return false;
		}

		// Join the where arguments for querying.
		$where_sql = 'WHERE ' . join( ' AND ', $where_args );

		// Fetch all meeting being deleted so we can perform more actions.
		$meetings = $wpdb->get_results( "SELECT * FROM {$bp->table_prefix}bp_zoom_meetings {$where_sql}" );

		/**
		 * Action to allow intercepting meeting items to be deleted.
		 *
		 * @param array $meetings Array of meeting.
		 * @param array $r Array of parsed arguments.
		 *
		 * @since BuddyBoss 1.2.10
		 *
		 */
		do_action_ref_array( 'bp_zoom_meeting_before_delete', array( $meetings, $r ) );

		// Attempt to delete meeting from the database.
		$deleted = $wpdb->query( "DELETE FROM {$bp->table_prefix}bp_zoom_meetings {$where_sql}" );

		// Bail if nothing was deleted.
		if ( empty( $deleted ) ) {
			return false;
		}

		/**
		 * Action to allow intercepting meeting items just deleted.
		 *
		 * @param array $meetings Array of meeting.
		 * @param array $r Array of parsed arguments.
		 *
		 * @since BuddyBoss 1.2.10
		 *
		 */
		do_action_ref_array( 'bp_zoom_meeting_after_delete', array( $meetings, $r ) );

		// Pluck the meeting IDs out of the $meetings array.
		$meeting_ids = wp_parse_id_list( wp_list_pluck( $meetings, 'id' ) );

		return $meeting_ids;
	}
}
