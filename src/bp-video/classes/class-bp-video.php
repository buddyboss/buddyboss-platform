<?php
/**
 * BuddyBoss Video Classes
 *
 * @package BuddyBoss\Video
 * @since BuddyBoss 1.7.0
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Database interaction class for the BuddyBoss video component.
 * Instance methods are available for creating/editing an video,
 * static methods for querying video.
 *
 * @since BuddyBoss 1.7.0
 */
class BP_Video {

	/** Properties ************************************************************/

	/**
	 * ID of the video item.
	 *
	 * @since BuddyBoss 1.7.0
	 * @var int
	 */
	public $id;

	/**
	 * Blog ID of the video item.
	 *
	 * @since BuddyBoss 1.7.0
	 * @var int
	 */
	public $blog_id;

	/**
	 * Attachment ID of the video item.
	 *
	 * @since BuddyBoss 1.7.0
	 * @var int
	 */
	public $attachment_id;

	/**
	 * User ID of the video item.
	 *
	 * @since BuddyBoss 1.7.0
	 * @var int
	 */
	public $user_id;

	/**
	 * Title of the video item.
	 *
	 * @since BuddyBoss 1.7.0
	 * @var string
	 */
	public $title;

	/**
	 * Album ID of the video item.
	 *
	 * @since BuddyBoss 1.7.0
	 * @var int
	 */
	public $album_id;

	/**
	 * Activity ID of the video item.
	 *
	 * @since BuddyBoss 1.7.0
	 * @var int
	 */
	public $activity_id;

	/**
	 * Message ID of the video item.
	 *
	 * @since BuddyBoss 2.3.60
	 * @var int
	 */
	var $message_id;

	/**
	 * Group ID of the video item.
	 *
	 * @since BuddyBoss 1.7.0
	 * @var int
	 */
	public $group_id;

	/**
	 * Privacy of the video item.
	 *
	 * @since BuddyBoss 1.7.0
	 * @var string
	 */
	public $privacy;

	/**
	 * Menu order of the video item.
	 *
	 * @since BuddyBoss 1.7.0
	 * @var int
	 */
	public $menu_order;

	/**
	 * Upload date of the video item.
	 *
	 * @since BuddyBoss 1.7.0
	 * @var string
	 */
	public $date_created;

	/**
	 * Error holder.
	 *
	 * @since BuddyBoss 1.7.0
	 *
	 * @var WP_Error
	 */
	public $errors;

	/**
	 * Error type to return. Either 'bool' or 'wp_error'.
	 *
	 * @since BuddyBoss 1.7.0
	 *
	 * @var string
	 */
	public $error_type = 'bool';

	/**
	 * Constructor method.
	 *
	 * @since BuddyBoss 1.7.0
	 *
	 * @param int|bool $id Optional. The ID of a specific activity item.
	 */
	public function __construct( $id = false ) {
		// Instantiate errors object.
		$this->errors = new WP_Error();

		if ( ! empty( $id ) ) {
			$this->id = (int) $id;
			$this->populate();
		}
	}

	/**
	 * Populate the object with data about the specific video item.
	 *
	 * @since BuddyBoss 1.7.0
	 */
	public function populate() {

		global $wpdb;

		$row = wp_cache_get( $this->id, 'bp_video' );

		if ( false === $row ) {
			$bp  = buddypress();
			$row = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$bp->video->table_name} WHERE id = %d", $this->id ) ); //phpcs:ignore

			wp_cache_set( $this->id, $row, 'bp_video' );
		}

		if ( empty( $row ) ) {
			$this->id = 0;
			return;
		}

		$this->id            = (int) $row->id;
		$this->blog_id       = (int) $row->blog_id;
		$this->attachment_id = (int) $row->attachment_id;
		$this->user_id       = (int) $row->user_id;
		$this->title         = $row->title;
		$this->album_id      = (int) $row->album_id;
		$this->activity_id   = (int) $row->activity_id;
		$this->message_id    = (int) $row->message_id;
		$this->group_id      = (int) $row->group_id;
		$this->privacy       = $row->privacy;
		$this->menu_order    = (int) $row->menu_order;
		$this->date_created  = $row->date_created;
	}

	/**
	 * Save the video item to the database.
	 *
	 * @since BuddyBoss 1.7.0
	 *
	 * @return WP_Error|bool True on success.
	 */
	public function save() {

		global $wpdb;

		$bp = buddypress();

		$this->id            = apply_filters_ref_array( 'bp_video_id_before_save', array( $this->id, &$this ) );
		$this->blog_id       = apply_filters_ref_array( 'bp_video_blog_id_before_save', array( $this->blog_id, &$this ) );
		$this->attachment_id = apply_filters_ref_array( 'bp_video_attachment_id_before_save', array( $this->attachment_id, &$this ) );
		$this->user_id       = apply_filters_ref_array( 'bp_video_user_id_before_save', array( $this->user_id, &$this ) );
		$this->title         = apply_filters_ref_array( 'bp_video_title_before_save', array( $this->title, &$this ) );
		$this->album_id      = apply_filters_ref_array( 'bp_video_album_id_before_save', array( $this->album_id, &$this ) );
		$this->activity_id   = apply_filters_ref_array( 'bp_video_activity_id_before_save', array( $this->activity_id, &$this ) );
		$this->message_id    = apply_filters_ref_array( 'bp_video_message_id_before_save', array( $this->message_id, &$this ) );
		$this->group_id      = apply_filters_ref_array( 'bp_video_group_id_before_save', array( $this->group_id, &$this ) );
		$this->privacy       = apply_filters_ref_array( 'bp_video_privacy_before_save', array( $this->privacy, &$this ) );
		$this->menu_order    = apply_filters_ref_array( 'bp_video_menu_order_before_save', array( $this->menu_order, &$this ) );
		$this->date_created  = apply_filters_ref_array( 'bp_video_date_created_before_save', array( $this->date_created, &$this ) );

		/**
		 * Fires before the current video item gets saved.
		 *
		 * Please use this hook to filter the properties above. Each part will be passed in.
		 *
		 * @since BuddyBoss 1.7.0
		 *
		 * @param BP_Video $this Current instance of the video item being saved. Passed by reference.
		 */
		do_action_ref_array( 'bp_video_before_save', array( &$this ) );

		if ( 'wp_error' === $this->error_type && $this->errors->get_error_code() ) {
			return $this->errors;
		}

		if ( empty( $this->attachment_id ) ) {
			if ( 'bool' === $this->error_type ) {
				return false;
			} else {
				if ( empty( $this->activity_id ) ) {
					$this->errors->add( 'bp_video_missing_activity' );
				} else {
					$this->errors->add( 'bp_video_missing_attachment' );
				}

				return $this->errors;
			}
		}

		// If we have an existing ID, update the video item, otherwise insert it.
		if ( ! empty( $this->id ) ) {
			$q = $wpdb->prepare( "UPDATE {$bp->video->table_name} SET blog_id = %d, attachment_id = %d, user_id = %d, title = %s, album_id = %d, activity_id = %d, message_id = %d, group_id = %d, privacy = %s, menu_order = %d, date_created = %s WHERE id = %d", $this->blog_id, $this->attachment_id, $this->user_id, $this->title, $this->album_id, $this->activity_id, $this->message_id, $this->group_id, $this->privacy, $this->menu_order, $this->date_created, $this->id ); //phpcs:ignore
		} else {
			$q = $wpdb->prepare( "INSERT INTO {$bp->video->table_name} ( blog_id, attachment_id, user_id, title, album_id, activity_id, message_id, group_id, privacy, menu_order, date_created, type ) VALUES ( %d, %d, %d, %s, %d, %d, %d, %d, %s, %d, %s, %s )", $this->blog_id, $this->attachment_id, $this->user_id, $this->title, $this->album_id, $this->activity_id, $this->message_id, $this->group_id, $this->privacy, $this->menu_order, $this->date_created, 'video' );  //phpcs:ignore
		}

		if ( false === $wpdb->query( $q ) ) { //phpcs:ignore
			return false;
		}

		// If this is a new video item, set the $id property.
		if ( empty( $this->id ) ) {
			$this->id = $wpdb->insert_id;
		}

		/**
		 * Fires after an video item has been saved to the database.
		 *
		 * @since BuddyBoss 1.7.0
		 *
		 * @param BP_Video $this Current instance of video item being saved. Passed by reference.
		 */
		do_action_ref_array( 'bp_video_after_save', array( &$this ) );

		return true;
	}

	/** Static Methods ***************************************************/

	/**
	 * Get video items, as specified by parameters.
	 *
	 * @since BuddyBoss 1.7.0
	 *
	 * @param array $args {
	 *     An array of arguments. All items are optional.
	 *     @type int          $page              Which page of results to fetch. Using page=1 without per_page will result
	 *                                           in no pagination. Default: 1.
	 *     @type int|bool     $per_page          Number of results per page. Default: 20.
	 *     @type int|bool     $max               Maximum number of results to return. Default: false (unlimited).
	 *     @type string       $fields            Video fields to return. Pass 'ids' to get only the video IDs.
	 *                                           'all' returns full video objects.
	 *     @type string       $sort              ASC or DESC. Default: 'DESC'.
	 *     @type string       $order_by          Column to order results by.
	 *     @type array        $exclude           Array of video IDs to exclude. Default: false.
	 *     @type string       $search_terms      Limit results by a search term. Default: false.
	 *     @type string|bool  $count_total       If true, an additional DB query is run to count the total video items
	 *                                           for the query. Default: false.
	 * }
	 * @return array The array returned has two keys:
	 *               - 'total' is the count of located videos
	 *               - 'videos' is an array of the located videos
	 */
	public static function get( $args = array() ) {

		global $wpdb;

		$bp = buddypress();
		$r  = bp_parse_args(
			$args,
			array(
				'page'             => 1,               // The current page.
				'scope'            => '',              // Scope - Groups, friends etc.
				'per_page'         => 20,              // Video items per page.
				'max'              => false,           // Max number of items to return.
				'fields'           => 'all',           // Fields to include.
				'sort'             => 'DESC',          // ASC or DESC.
				'order_by'         => 'date_created',  // Column to order by.
				'exclude'          => false,           // Array of ids to exclude.
				'in'               => false,           // Array of ids to limit query by (IN).
				'search_terms'     => false,           // Terms to search by.
				'album_id'         => false,           // Album ID.
				'user_id'          => false,           // User ID.
				'group_id'         => false,           // Group ID.
				'activity_id'      => false,           // Activity ID.
				'privacy'          => false,           // public, loggedin, onlyme, friends, grouponly, message.
				'moderation_query' => false,           // Whether to include moderation or not.
				'count_total'      => false,           // Whether to use count_total.
			)
		);

		// Select conditions.
		$select_sql = 'SELECT DISTINCT m.id';

		$from_sql = " FROM {$bp->video->table_name} m";

		$join_sql = '';

		// Where conditions.
		$where_conditions = array();

		if ( ! empty( $r['scope'] ) ) {
			$scope_query = self::get_scope_query_sql( $r['scope'], $r );

			// Override some arguments if needed.
			if ( ! empty( $scope_query['override'] ) ) {
				$r = array_replace_recursive( $r, $scope_query['override'] );
			}
		}

		// Searching.
		if ( $r['search_terms'] ) {
			$search_terms_like              = '%' . bp_esc_like( $r['search_terms'] ) . '%';
			$where_conditions['search_sql'] = $wpdb->prepare( 'm.title LIKE %s', $search_terms_like );

			/**
			 * Filters whether or not to include users for search parameters.
			 *
			 * @since BuddyBoss 1.7.0
			 *
			 * @param bool $value Whether or not to include user search. Default false.
			 */
			if ( apply_filters( 'bp_video_get_include_user_search', false ) ) {
				$user_search = get_user_by( 'slug', $r['search_terms'] );
				if ( false !== $user_search ) {
					$user_id                         = $user_search->ID;
					$where_conditions['search_sql'] .= $wpdb->prepare( ' OR m.user_id = %d', $user_id );
				}
			}
		}

		// Sorting.
		$sort = $r['sort'];
		if ( $sort != 'ASC' && $sort != 'DESC' ) { //phpcs:ignore
			$sort = 'DESC';
		}

		switch ( $r['order_by'] ) {
			case 'id':
			case 'user_id':
			case 'blog_id':
			case 'attachment_id':
			case 'title':
			case 'album_id':
			case 'activity_id':
			case 'group_id':
			case 'menu_order':
				break;

			case 'in':
				$r['order_by'] = 'in';
				break;

			default:
				$r['order_by'] = 'date_created';
				break;
		}
		$order_by = 'm.' . $r['order_by'];
		// Support order by fields for generally.
		if ( ! empty( $r['in'] ) && 'in' === $r['order_by'] ) {
			$order_by = 'FIELD(m.id, ' . implode( ',', wp_parse_id_list( $r['in'] ) ) . ')';
			$sort     = '';
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
		}

		if ( ! empty( $r['activity_id'] ) ) {
			$where_conditions['activity'] = "m.activity_id = {$r['activity_id']}";
		}

		// existing-video check to query video which has no albums assigned.
		if ( ! empty( $r['album_id'] ) && 'existing-video' !== $r['album_id'] ) {
			$where_conditions['album'] = "m.album_id = {$r['album_id']}";
		} elseif ( ! empty( $r['album_id'] ) && 'existing-video' === $r['album_id'] ) {
			$where_conditions['album'] = 'm.album_id = 0';
		}

		if ( ! empty( $r['user_id'] ) ) {
			$where_conditions['user'] = "m.user_id = {$r['user_id']}";
		}

		if ( ! empty( $r['group_id'] ) ) {
			$where_conditions['group'] = "m.group_id = {$r['group_id']}";
		}

		if ( ! empty( $r['privacy'] ) ) {
			$privacy                     = "'" . implode( "', '", $r['privacy'] ) . "'";
			$where_conditions['privacy'] = "m.privacy IN ({$privacy})";
		}

		/**
		 * Filters the MySQL WHERE conditions for the Video items get method.
		 *
		 * @since BuddyBoss 1.7.0
		 *
		 * @param array  $where_conditions Current conditions for MySQL WHERE statement.
		 * @param array  $r                Parsed arguments passed into method.
		 * @param string $select_sql       Current SELECT MySQL statement at point of execution.
		 * @param string $from_sql         Current FROM MySQL statement at point of execution.
		 * @param string $join_sql         Current INNER JOIN MySQL statement at point of execution.
		 */
		$where_conditions = apply_filters( 'bp_video_get_where_conditions', $where_conditions, $r, $select_sql, $from_sql, $join_sql );

		$where_conditions['type'] = "m.type = 'video'";

		if ( empty( $where_conditions ) ) {
			$where_conditions['2'] = '2';
		}

		// Join the where conditions together.
		if ( ! empty( $scope_query['sql'] ) ) {
			$where_sql = 'WHERE ' . ( ! empty( $where_conditions ) ? '( ' . join( ' AND ', $where_conditions ) . ' ) AND ' : '' ) . ' ( ' . $scope_query['sql'] . ' )';
		} else {
			$where_sql = 'WHERE ' . join( ' AND ', $where_conditions );
		}

		/**
		 * Filter the MySQL JOIN clause for the main video query.
		 *
		 * @since BuddyBoss 1.7.0
		 *
		 * @param string $join_sql   JOIN clause.
		 * @param array  $r          Method parameters.
		 * @param string $select_sql Current SELECT MySQL statement.
		 * @param string $from_sql   Current FROM MySQL statement.
		 * @param string $where_sql  Current WHERE MySQL statement.
		 */
		$join_sql = apply_filters( 'bp_video_get_join_sql', $join_sql, $r, $select_sql, $from_sql, $where_sql );

		// Sanitize page and per_page parameters.
		$page     = absint( $r['page'] );
		$per_page = absint( $r['per_page'] );

		$retval = array(
			'videos'         => null,
			'total'          => null,
			'has_more_items' => null,
		);

		// Query first for video IDs.
		$video_ids_sql = "{$select_sql} {$from_sql} {$join_sql} {$where_sql} ORDER BY {$order_by} {$sort}, m.id {$sort}";

		if ( ! empty( $per_page ) && ! empty( $page ) && - 1 !== $r['per_page'] ) {
			// We query for $per_page + 1 items in order to
			// populate the has_more_items flag.
			$video_ids_sql .= $wpdb->prepare( ' LIMIT %d, %d', absint( ( $page - 1 ) * $per_page ), $per_page + 1 );
		}

		/**
		 * Filters the paged video MySQL statement.
		 *
		 * @since BuddyBoss 1.7.0
		 *
		 * @param string $video_ids_sql    MySQL statement used to query for Video IDs.
		 * @param array  $r                Array of arguments passed into method.
		 */
		$video_ids_sql = apply_filters( 'bp_video_paged_activities_sql', $video_ids_sql, $r );

		$cache_group = 'bp_video';

		$cached = bp_core_get_incremented_cache( $video_ids_sql, $cache_group );

		if ( false === $cached ) {
			// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$video_ids = $wpdb->get_col( $video_ids_sql );
			bp_core_set_incremented_cache( $video_ids_sql, $cache_group, $video_ids );
		} else {
			$video_ids = $cached;
		}

		$retval['has_more_items'] = ! empty( $per_page ) && count( $video_ids ) > $per_page;

		// If we've fetched more than the $per_page value, we
		// can discard the extra now.
		if ( ! empty( $per_page ) && count( $video_ids ) === $per_page + 1 ) {
			array_pop( $video_ids );
		}

		if ( 'ids' === $r['fields'] ) {
			$videos = array_map( 'intval', $video_ids );
		} else {
			$videos = self::get_video_data( $video_ids );
		}

		if ( 'ids' !== $r['fields'] ) {
			// Pre-fetch data associated with video users and other objects.
			$videos = self::prefetch_object_data( $videos );
		}

		$retval['videos'] = $videos;

		// If $max is set, only return up to the max results.
		if ( ! empty( $r['count_total'] ) ) {

			/**
			 * Filters the total video MySQL statement.
			 *
			 * @since BuddyBoss 1.7.0
			 *
			 * @param string $value     MySQL statement used to query for total videos.
			 * @param string $where_sql MySQL WHERE statement portion.
			 * @param string $sort      Sort direction for query.
			 */
			$total_videos_sql = apply_filters( 'bp_video_total_videos_sql', "SELECT count(DISTINCT m.id) FROM {$bp->video->table_name} m {$join_sql} {$where_sql}", $where_sql, $sort );
			$cached           = bp_core_get_incremented_cache( $total_videos_sql, $cache_group );
			if ( false === $cached ) {
				// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
				$total_videos = $wpdb->get_var( $total_videos_sql );
				bp_core_set_incremented_cache( $total_videos_sql, $cache_group, $total_videos );
			} else {
				$total_videos = $cached;
			}

			if ( ! empty( $r['max'] ) ) {
				if ( (int) $total_videos > (int) $r['max'] ) {
					$total_videos = $r['max'];
				}
			}

			$retval['total'] = $total_videos;
		}

		return $retval;
	}

	/**
	 * Convert video IDs to video objects, as expected in template loop.
	 *
	 * @since BuddyBoss 1.7.0
	 *
	 * @param array $video_ids Array of video IDs.
	 * @return array
	 */
	protected static function get_video_data( $video_ids = array() ) {
		global $wpdb;

		// Bail if no video ID's passed.
		if ( empty( $video_ids ) ) {
			return array();
		}

		// Get BuddyPress.
		$bp = buddypress();

		$videos       = array();
		$uncached_ids = bp_get_non_cached_ids( $video_ids, 'bp_video' );

		// Prime caches as necessary.
		if ( ! empty( $uncached_ids ) ) {
			// Format the video ID's for use in the query below.
			$uncached_ids_sql = implode( ',', wp_parse_id_list( $uncached_ids ) );

			// Fetch data from video table, preserving order.
			$queried_adata = $wpdb->get_results( "SELECT * FROM {$bp->video->table_name} WHERE id IN ({$uncached_ids_sql})" ); //phpcs:ignore
			// Put that data into the placeholders created earlier,
			// and add it to the cache.
			foreach ( (array) $queried_adata as $adata ) {
				wp_cache_set( $adata->id, $adata, 'bp_video' );
			}
		}

		// Now fetch data from the cache.
		foreach ( $video_ids as $video_id ) {
			// Integer casting.
			$video = wp_cache_get( $video_id, 'bp_video' );
			if ( ! empty( $video ) ) {
				$video->id            = (int) $video->id;
				$video->blog_id       = (int) $video->blog_id;
				$video->user_id       = (int) $video->user_id;
				$video->attachment_id = (int) $video->attachment_id;
				$video->album_id      = (int) $video->album_id;
				$video->activity_id   = (int) $video->activity_id;
				$video->message_id    = (int) $video->message_id;
				$video->group_id      = (int) $video->group_id;
				$video->menu_order    = (int) $video->menu_order;
			}

			$file_url = wp_get_attachment_url( $video->attachment_id );
			$filetype = wp_check_filetype( $file_url );
			$ext      = $filetype['ext'];
			if ( empty( $ext ) ) {
				$path = parse_url( $file_url, PHP_URL_PATH );
				$ext  = pathinfo( basename( $path ), PATHINFO_EXTENSION );
			}
			// https://stackoverflow.com/questions/40995987/how-to-play-mov-files-in-video-tag/40999234#40999234.
			// https://stackoverflow.com/a/44858204.
			if ( in_array( $ext, array( 'mov', 'm4v' ), true ) ) {
				$ext = 'mp4';
			}

			// fetch video thumbnail attachment data.
			$attachment_data                             = new stdClass();
			$attachment_data->meta                       = new stdClass();
			$attachment_data->meta->mime_type            = apply_filters( 'bb_video_extension', 'video/' . $ext, $video );
			$length_formatted                            = wp_get_attachment_metadata( $video->attachment_id );
			$attachment_data->meta->length_formatted     = isset( $length_formatted['length_formatted'] ) ? $length_formatted['length_formatted'] : '0:00';
			$attachment_thumb_id                         = bb_get_video_thumb_id( $video->attachment_id );
			$default_thumb                               = bb_get_video_default_placeholder_image();
			$attachment_data->full                       = $default_thumb;
			$attachment_data->thumb                      = $default_thumb;
			$attachment_data->activity_thumb             = $default_thumb;
			$attachment_data->thumb_meta                 = array();
			$attachment_data->video_user_profile_thumb   = $default_thumb;
			$attachment_data->video_directory_page_thumb = $default_thumb;
			$attachment_data->media_album_cover          = $default_thumb;
			$attachment_data->video_album_cover_thumb    = $default_thumb;
			$attachment_data->video_add_thumbnail_thumb  = $default_thumb;
			$attachment_data->video_popup_thumb          = $default_thumb;
			$attachment_data->video_activity_thumb       = $default_thumb;

			if ( $attachment_thumb_id ) {

				$video_user_profile_thumb   = bb_video_get_thumb_url( $video->id, $attachment_thumb_id, 'bb-video-profile-album-add-thumbnail-directory-poster-image' );
				$video_directory_page_thumb = $video_user_profile_thumb;
				$video_album_cover_thumb    = $video_user_profile_thumb;
				$video_add_thumbnail_thumb  = $video_album_cover_thumb;
				$video_popup_thumb          = bb_video_get_thumb_url( $video->id, $attachment_thumb_id, 'bb-video-poster-popup-image' );
				$video_activity_thumb       = bb_video_get_thumb_url( $video->id, $attachment_thumb_id, 'bb-video-activity-image' );

				$attachment_data->full                       = $video_popup_thumb;
				$attachment_data->thumb                      = $video_album_cover_thumb;
				$attachment_data->activity_thumb             = $video_activity_thumb;
				$attachment_data->video_user_profile_thumb   = $video_user_profile_thumb;
				$attachment_data->video_directory_page_thumb = $video_directory_page_thumb;
				$attachment_data->video_album_cover_thumb    = $video_album_cover_thumb;
				$attachment_data->media_album_cover          = $video_album_cover_thumb;
				$attachment_data->video_add_thumbnail_thumb  = $video_add_thumbnail_thumb;
				$attachment_data->video_popup_thumb          = $video_popup_thumb;
				$attachment_data->video_activity_thumb       = $video_activity_thumb;
				$attachment_data->thumb_meta                 = wp_get_attachment_metadata( $attachment_thumb_id );
			}

			$video->attachment_data = $attachment_data;
			$group_name             = '';

			if ( bp_is_active( 'groups' ) && $video->group_id > 0 ) {
				$group      = groups_get_group( $video->group_id );
				$group_name = bp_get_group_name( $group );
				$status     = bp_get_group_status( $group );
				if ( 'hidden' === $status || 'private' === $status ) {
					$visibility = esc_html__( 'Group Members', 'buddyboss' );
				} else {
					$visibility = ucfirst( $status );
				}
			} else {
				$video_privacy = bp_video_get_visibility_levels();
				if ( 'friends' === $video->privacy && bp_loggedin_user_id() !== (int) $video->user_id ) {
					$visibility = esc_html__( 'Connections', 'buddyboss' );
				} elseif ( 'message' === $video->privacy ) {
					$visibility = esc_html__( 'Message', 'buddyboss' );
				} elseif ( 'forums' === $video->privacy ) {
					$visibility = esc_html__( 'Forums', 'buddyboss' );
				} else {
					$visibility = ( isset( $video_privacy[ $video->privacy ] ) ) ? ucfirst( $video_privacy[ $video->privacy ] ) : '';
				}
			}

			$video->group_name = $group_name;
			$video->visibility = $visibility;
			$video->video_link = bb_video_get_symlink( $video );
			$videos[]          = $video;
		}

		return $videos;
	}

	/**
	 * Append xProfile fullnames to an video array.
	 *
	 * @since BuddyBoss 1.7.0
	 *
	 * @param array $videos Videos array.
	 * @return array
	 */
	protected static function append_user_fullnames( $videos ) {

		if ( bp_is_active( 'xprofile' ) && ! empty( $videos ) ) {
			$video_user_ids = wp_list_pluck( $videos, 'user_id' );

			if ( ! empty( $video_user_ids ) ) {
				$fullnames = bp_core_get_user_displaynames( $video_user_ids );
				if ( ! empty( $fullnames ) ) {
					foreach ( (array) $videos as $i => $video ) {
						if ( ! empty( $fullnames[ $video->user_id ] ) ) {
							$videos[ $i ]->user_fullname = $fullnames[ $video->user_id ];
						}
					}
				}
			}
		}

		return $videos;
	}

	/**
	 * Pre-fetch data for objects associated with video items.
	 *
	 * Video items are associated with users, and often with other
	 * BuddyPress data objects. Here, we pre-fetch data about these
	 * associated objects, so that inline lookups - done primarily when
	 * building action strings - do not result in excess database queries.
	 *
	 * @since BuddyBoss 1.7.0
	 *
	 * @param array $videos Array of video.
	 * @return array $videos Array of video.
	 */
	protected static function prefetch_object_data( $videos ) {

		/**
		 * Filters inside prefetch_object_data method to aid in pre-fetching object data associated with video item.
		 *
		 * @since BuddyBoss 1.7.0
		 *
		 * @param array $videos Array of video.
		 */
		return apply_filters( 'bp_video_prefetch_object_data', $videos );
	}

	/**
	 * Get the SQL for the 'scope' param in BP_Video::get().
	 *
	 * A scope is a predetermined set of video arguments.  This method is used
	 * to grab these video arguments and override any existing args if needed.
	 *
	 * Can handle multiple scopes.
	 *
	 * @since BuddyBoss 1.7.0
	 *
	 * @param mixed $scope   The video scope. Accepts string or array of scopes.
	 * @param array $r       Current activity arguments. Same as those of BP_Video::get(),
	 *                       but merged with defaults.
	 *
	 * @return false|array 'sql' WHERE SQL string and 'override' video args.
	 */
	public static function get_scope_query_sql( $scope = false, $r = array() ) {

		// Define arrays for future use.
		$query_args = array();
		$override   = array();
		$retval     = array();

		// Check for array of scopes.
		if ( is_array( $scope ) ) {
			$scopes = $scope;

			// Explode a comma separated string of scopes.
		} elseif ( is_string( $scope ) ) {
			$scopes = explode( ',', $scope );
		}

		// Bail if no scope passed.
		if ( empty( $scopes ) ) {
			return false;
		}

		// Helper to easily grab the 'user_id'.
		if ( ! empty( $r['filter']['user_id'] ) ) {
			$r['user_id'] = $r['filter']['user_id'];
		}

		// Parse each scope; yes! we handle multiples!
		foreach ( $scopes as $scope ) {
			$scope_args = array();

			/**
			 * Plugins can hook here to set their video arguments for custom scopes.
			 *
			 * This is a dynamic filter based on the video scope. eg:
			 *   - 'bp_video_set_groups_scope_args'
			 *   - 'bp_video_set_friends_scope_args'
			 *
			 * To see how this filter is used, plugin devs should check out:
			 *   - bp_groups_filter_video_scope() - used for 'groups' scope
			 *   - bp_friends_filter_video_scope() - used for 'friends' scope
			 *
			 * @since BuddyBoss 1.7.0
			 *
			 * @param array {
			 *     Video query clauses.
			 *     @type array {
			 *         Video arguments for your custom scope.
			 *         See {@link BP_Video_Query::_construct()} for more details.
			 *     }
			 *     @type array  $override Optional. Override existing video arguments passed by $r.
			 *     }
			 * }
			 * @param array $r Current activity arguments passed in BP_Video::get().
			 */
			$scope_args = apply_filters( "bp_video_set_{$scope}_scope_args", array(), $r );

			if ( ! empty( $scope_args ) ) {
				// Merge override properties from other scopes
				// this might be a problem...
				if ( ! empty( $scope_args['override'] ) ) {
					$override = array_merge( $override, $scope_args['override'] );
					unset( $scope_args['override'] );
				}

				// Save scope args.
				if ( ! empty( $scope_args ) ) {
					$query_args[] = $scope_args;
				}
			}
		}

		if ( ! empty( $query_args ) ) {

			if ( count( $scopes ) > 1 ) {
				// Set relation to OR.
				$query_args['relation'] = 'OR';
			} else {
				// Set relation to OR.
				$query_args['relation'] = 'AND';
			}

			$query = new BP_Video_Query( $query_args );
			$sql   = $query->get_sql();
			if ( ! empty( $sql ) ) {
				$retval['sql'] = $sql;
			}
		}

		if ( ! empty( $override ) ) {
			$retval['override'] = $override;
		}

		return $retval;
	}

	/**
	 * Create SQL IN clause for filter queries.
	 *
	 * @since BuddyBoss 1.7.0
	 *
	 * @see   BP_Video::get_filter_sql()
	 *
	 * @param string     $field The database field.
	 * @param array|bool $items The values for the IN clause, or false when none are found.
	 *
	 * @return string|false
	 */
	public static function get_in_operator_sql( $field, $items ) {
		global $wpdb;

		// Split items at the comma.
		if ( ! is_array( $items ) ) {
			$items = explode( ',', $items );
		}

		// Array of prepared integers or quoted strings.
		$items_prepared = array();

		// Clean up and format each item.
		foreach ( $items as $item ) {
			// Clean up the string.
			$item = trim( $item );
			// Pass everything through prepare for security and to safely quote strings.
			$items_prepared[] = ( is_numeric( $item ) ) ? $wpdb->prepare( '%d', $item ) : $wpdb->prepare( '%s', $item );
		}

		// Build IN operator sql syntax.
		if ( count( $items_prepared ) ) {
			return sprintf( '%s IN ( %s )', trim( $field ), implode( ',', $items_prepared ) );
		} else {
			return false;
		}
	}

	/**
	 * Delete video items from the database.
	 *
	 * To delete a specific video item, pass an 'id' parameter.
	 * Otherwise use the filters.
	 *
	 * @since BuddyBoss 1.7.0
	 *
	 * @param array $args          {
	 *                             An array of arguments.
	 *
	 * @type int    $id            Optional. The ID of a specific item to delete.
	 * @type int    $blog_id       Optional. The blog ID to filter by.
	 * @type int    $attachment_id Optional. The attachment ID to filter by.
	 * @type int    $user_id       Optional. The user ID to filter by.
	 * @type string $title         Optional. The title to filter by.
	 * @type int    $album_id      Optional. The album ID to filter by.
	 * @type int    $activity_id   Optional. The activity ID to filter by.
	 * @type int    $group_id      Optional. The group ID to filter by.
	 * @type string $privacy       Optional. The privacy to filter by.
	 * @type string $date_created  Optional. The date to filter by.
	 *                    }
	 *
	 * @param bool  $from          Context of deletion from. ex. attachment, activity etc.
	 *
	 * @return array|bool An array of deleted video IDs on success, false on failure.
	 */
	public static function delete( $args = array(), $from = false ) {
		global $wpdb;

		$bp = buddypress();
		$r  = bp_parse_args(
			$args,
			array(
				'id'            => false,
				'blog_id'       => false,
				'attachment_id' => false,
				'user_id'       => false,
				'title'         => false,
				'album_id'      => false,
				'activity_id'   => false,
				'group_id'      => false,
				'privacy'       => false,
				'date_created'  => false,
			)
		);

		// Setup empty array from where query arguments.
		$where_args = array();

		// ID.
		if ( ! empty( $r['id'] ) ) {
			$where_args[] = $wpdb->prepare( 'id = %d', $r['id'] );
		}

		// blog ID.
		if ( ! empty( $r['blog_id'] ) ) {
			$where_args[] = $wpdb->prepare( 'blog_id = %d', $r['blog_id'] );
		}

		// attachment ID.
		if ( ! empty( $r['attachment_id'] ) ) {
			$where_args[] = $wpdb->prepare( 'attachment_id = %d', $r['attachment_id'] );
		}

		// User ID.
		if ( ! empty( $r['user_id'] ) ) {
			$where_args[] = $wpdb->prepare( 'user_id = %d', $r['user_id'] );
		}

		// title.
		if ( ! empty( $r['title'] ) ) {
			$where_args[] = $wpdb->prepare( 'title = %s', $r['title'] );
		}

		// album ID.
		if ( ! empty( $r['album_id'] ) ) {
			$where_args[] = $wpdb->prepare( 'album_id = %d', $r['album_id'] );
		}

		// activity ID.
		if ( ! empty( $r['activity_id'] ) ) {
			$where_args[] = $wpdb->prepare( 'activity_id = %d', $r['activity_id'] );
		}

		// group ID.
		if ( ! empty( $r['group_id'] ) ) {
			$where_args[] = $wpdb->prepare( 'group_id = %d', $r['group_id'] );
		}

		// privacy.
		if ( ! empty( $r['privacy'] ) ) {
			$where_args[] = $wpdb->prepare( 'privacy = %s', $r['privacy'] );
		}

		// Date created.
		if ( ! empty( $r['date_created'] ) ) {
			$where_args[] = $wpdb->prepare( 'date_created = %s', $r['date_created'] );
		}

		// Delete the video.
		$where_args[] = $wpdb->prepare( 'type = %s', 'video' );

		// Bail if no where arguments.
		if ( empty( $where_args ) ) {
			return false;
		}

		// Join the where arguments for querying.
		$where_sql = 'WHERE ' . join( ' AND ', $where_args );

		// Fetch all video being deleted so we can perform more actions.
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$videos = $wpdb->get_results( "SELECT * FROM {$bp->video->table_name} {$where_sql}" );

		/**
		 * Action to allow intercepting video items to be deleted.
		 *
		 * @since BuddyBoss 1.7.0
		 *
		 * @param array $videos Array of video.
		 * @param array $r          Array of parsed arguments.
		 */
		do_action_ref_array( 'bp_video_before_delete', array( $videos, $r ) );

		// Attempt to delete video from the database.
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$deleted = $wpdb->query( "DELETE FROM {$bp->video->table_name} {$where_sql}" );

		// Bail if nothing was deleted.
		if ( empty( $deleted ) ) {
			return false;
		}

		/**
		 * Action to allow intercepting video items just deleted.
		 *
		 * @since BuddyBoss 1.7.0
		 *
		 * @param array $videos Array of video.
		 * @param array $r      Array of parsed arguments.
		 */
		do_action_ref_array( 'bp_video_after_delete', array( $videos, $r ) );

		// Pluck the video IDs out of the $videos array.
		$video_ids      = wp_parse_id_list( wp_list_pluck( $videos, 'id' ) );
		$activity_ids   = wp_parse_id_list( wp_list_pluck( $videos, 'activity_id' ) );
		$attachment_ids = wp_parse_id_list( wp_list_pluck( $videos, 'attachment_id' ) );

		// Handle accompanying attachments and meta deletion.
		if ( ! empty( $attachment_ids ) ) {

			// Loop through attachment ids and attempt to delete.
			foreach ( $attachment_ids as $attachment_id ) {

				if ( bp_is_active( 'activity' ) ) {
					$parent_activity_id = get_post_meta( $attachment_id, 'bp_video_parent_activity_id', true );
					if ( ! empty( $parent_activity_id ) ) {
						$activity_video_ids = bp_activity_get_meta( $parent_activity_id, 'bp_video_ids', true );
						if ( ! empty( $activity_video_ids ) ) {
							$activity_video_ids = explode( ',', $activity_video_ids );
							$activity_video_ids = array_diff( $activity_video_ids, $video_ids );
							if ( ! empty( $activity_video_ids ) ) {
								$activity_video_ids = implode( ',', $activity_video_ids );
								bp_activity_update_meta( $parent_activity_id, 'bp_video_ids', $activity_video_ids );
							} else {
								$activity_ids[] = $parent_activity_id;
							}
						}
					}
				}

				// Delete poster images.
				$get_auto_generated_thumbnails = get_post_meta( $attachment_id, 'video_preview_thumbnails', true );
				if ( ! empty( $get_auto_generated_thumbnails ) ) {
					foreach ( $get_auto_generated_thumbnails as $key => $attachment ) {
						if ( is_array( $attachment ) && ! empty( $attachment ) ) {
							foreach ( $attachment as $thumb_id ) {
								wp_delete_attachment( $thumb_id, true );
							}
						} elseif ( ! empty( $attachment ) ) {
							wp_delete_attachment( $attachment, true );
						}
					}
				}

				$get_existing = get_post_meta( $attachment_id, 'bp_video_preview_thumbnail_id', true );
				if ( ! $get_existing ) {
					wp_delete_attachment( $get_existing, true );
				}

				if ( empty( $from ) ) {
					wp_delete_attachment( $attachment_id, true );
				}
			}
		}

		// delete related activity.
		if ( ! empty( $activity_ids ) && bp_is_active( 'activity' ) ) {

			foreach ( $activity_ids as $activity_id ) {
				$activity = new BP_Activity_Activity( (int) $activity_id );

				// Check access.
				if ( bp_activity_user_can_delete( $activity ) ) {
					/** This action is documented in bp-activity/bp-activity-actions.php */
					do_action( 'bp_activity_before_action_delete_activity', $activity->id, $activity->user_id );

					// Deleting an activity comment.
					if ( 'activity_comment' === $activity->type ) {

						// Do not delete the activity if activity type is comment & have a multiple videos attached.
						$video_ids = self::get_activity_video_id( $activity_id );
						if ( ! empty( $video_ids ) && self::get_activity_attachment_id( $activity_id ) > 0 ) {
							continue;
						}

						if ( bp_activity_delete_comment( $activity->item_id, $activity->id ) ) {
							/** This action is documented in bp-activity/bp-activity-actions.php */
							do_action( 'bp_activity_action_delete_activity', $activity->id, $activity->user_id );
						}

						// Deleting an activity.
					} else {
						$activity_delete  = false;
						$activity_content = ! empty( $activity->content ) ? wp_strip_all_tags( $activity->content, true ) : '';
						if (
							(
								'activity' !== $from && empty( $activity_content )
							) ||
							(
								'activity' === $from && ! empty( $activity->secondary_item_id )
							)
						) {
							$activity_delete = true;
						}
						if (
							true === $activity_delete &&
							bp_activity_delete(
								array(
									'id'      => $activity->id,
									'user_id' => $activity->user_id,
								)
							)
						) {
							/** This action is documented in bp-activity/bp-activity-actions.php */
							do_action( 'bp_activity_action_delete_activity', $activity->id, $activity->user_id );
						}
					}
				}
			}
		}

		return $video_ids;
	}

	/**
	 * Count total video for the given user
	 *
	 * @since BuddyBoss 1.7.0
	 *
	 * @param int $user_id User ID.
	 *
	 * @return array|bool|int
	 */
	public static function total_video_count( $user_id = 0 ) {

		if ( empty( $user_id ) ) {
			return 0;
		}

		$privacy = bp_video_query_privacy( $user_id );

		$total_count = self::get(
			array(
				'user_id'     => $user_id,
				'privacy'     => $privacy,
				'count_total' => true,
				'fields'      => 'ids',
			)
		);

		return ( isset( $total_count['total'] ) ? $total_count['total'] : 0 );

	}

	/**
	 * Count total video for the given group
	 *
	 * @since BuddyBoss 1.7.0
	 *
	 * @param int $group_id Group id.
	 *
	 * @return array|bool|int
	 */
	public static function total_group_video_count( $group_id = 0 ) {

		if ( empty( $group_id ) ) {
			return 0;
		}

		$total_count = self::get(
			array(
				'group_id'    => $group_id,
				'privacy'     => array( 'grouponly' ),
				'count_total' => true,
			)
		);

		return ( isset( $total_count['total'] ) ? $total_count['total'] : 0 );
	}

	/**
	 * Count total groups video for the given user.
	 *
	 * @param int $user_id User ID.
	 *
	 * @return array|bool|int
	 * @since BuddyBoss 1.7.0
	 */
	public static function total_user_group_video_count( $user_id = 0 ) {

		if ( empty( $user_id ) ) {
			return 0;
		}

		$privacy = bp_video_query_privacy( $user_id, 0, 'groups' );

		$total_count = self::get(
			array(
				'user_id'     => $user_id,
				'privacy'     => $privacy,
				'count_total' => true,
			)
		);

		return ( isset( $total_count['total'] ) ? $total_count['total'] : 0 );
	}

	/**
	 * Get all video ids for the album
	 *
	 * @since BuddyBoss 1.7.0
	 * @param int $album_id Media Album id.
	 *
	 * @return array
	 */
	public static function get_album_video_ids( $album_id = 0 ) {

		if ( ! $album_id ) {
			return array();
		}

		$video_ids = self::get(
			array(
				'album_id' => $album_id,
				'fields'   => 'ids',
				'per_page' => -1,
			)
		);

		return (array) ( isset( $video_ids['videos'] ) ? $video_ids['videos'] : array() );

	}

	/**
	 * Get video id for the activity.
	 *
	 * @since BuddyBoss 1.7.0
	 * @param bool $activity_id Activity id.
	 *
	 * @return array|bool
	 */
	public static function get_activity_video_id( $activity_id = false ) {
		global $bp, $wpdb;

		if ( ! $activity_id ) {
			return false;
		}

		$cache_key         = 'bp_video_activity_id_' . $activity_id;
		$activity_video_id = wp_cache_get( $cache_key, 'bp_video' );

		if ( ! empty( $activity_video_id ) ) {
			return $activity_video_id;
		}

		// Check activity component enabled or not.
		if ( bp_is_active( 'activity' ) ) {
			$activity_video_id = bp_activity_get_meta( $activity_id, 'bp_video_id', true );
		}

		if ( empty( $activity_video_id ) ) {
			$activity_video_id = (int) $wpdb->get_var( "SELECT DISTINCT v.id FROM {$bp->video->table_name} v WHERE v.activity_id = {$activity_id} AND v.type='video' " ); //phpcs:ignore

			if ( bp_is_active( 'activity' ) ) {
				$video_activity = bp_activity_get_meta( $activity_id, 'bp_video_activity', true );
				if ( ! empty( $video_activity ) && ! empty( $activity_video_id ) ) {
					bp_activity_update_meta( $activity_id, 'bp_video_id', $activity_video_id );
				}
			}
		}

		wp_cache_set( $cache_key, $activity_video_id, 'bp_video' );

		return $activity_video_id;
	}

	/**
	 * Get video attachment id for the activity.
	 *
	 * @param integer $activity_id Activity ID.
	 *
	 * @return integer|bool
	 * @since BuddyBoss 1.7.0
	 */
	public static function get_activity_attachment_id( $activity_id = 0 ) {
		global $bp, $wpdb;

		if ( empty( $activity_id ) ) {
			return false;
		}

		$cache_key           = 'bp_video_attachment_id_' . $activity_id;
		$video_attachment_id = wp_cache_get( $cache_key, 'bp_video' );

		if ( ! empty( $video_attachment_id ) ) {
			return $video_attachment_id;
		}

		$video_attachment_id = (int) $wpdb->get_var( "SELECT DISTINCT attachment_id FROM {$bp->video->table_name} WHERE activity_id = {$activity_id}" ); // phpcs:ignore
		wp_cache_set( $cache_key, $video_attachment_id, 'bp_video' );

		return $video_attachment_id;
	}

}
