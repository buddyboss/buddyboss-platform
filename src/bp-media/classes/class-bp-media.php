<?php
/**
 * BuddyBoss Media Classes
 *
 * @package BuddyBoss\Media
 * @since BuddyBoss 1.0.0
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Database interaction class for the BuddyBoss media component.
 * Instance methods are available for creating/editing an media,
 * static methods for querying media.
 *
 * @since BuddyPress 1.0.0
 */
class BP_Media {

	/** Properties ************************************************************/

	/**
	 * ID of the media item.
	 *
	 * @since BuddyBoss 1.0.0
	 * @var int
	 */
	var $id;

	/**
	 * Blog ID of the media item.
	 *
	 * @since BuddyBoss 1.0.0
	 * @var int
	 */
	var $blog_id;

	/**
	 * Attachment ID of the media item.
	 *
	 * @since BuddyBoss 1.0.0
	 * @var int
	 */
	var $attachment_id;

	/**
	 * User ID of the media item.
	 *
	 * @since BuddyBoss 1.0.0
	 * @var int
	 */
	var $user_id;

	/**
	 * Title of the media item.
	 *
	 * @since BuddyBoss 1.0.0
	 * @var string
	 */
	var $title;

	/**
	 * Album ID of the media item.
	 *
	 * @since BuddyBoss 1.0.0
	 * @var int
	 */
	var $album_id;

	/**
	 * Activity ID of the media item.
	 *
	 * @since BuddyBoss 1.0.0
	 * @var int
	 */
	var $activity_id;

	/**
	 * Message ID of the media item.
	 *
	 * @since BuddyBoss 2.3.60
	 * @var int
	 */
	var $message_id;

	/**
	 * Group ID of the media item.
	 *
	 * @since BuddyBoss 1.0.0
	 * @var int
	 */
	var $group_id;

	/**
	 * Privacy of the media item.
	 *
	 * @since BuddyBoss 1.0.0
	 * @var string
	 */
	var $privacy;

	/**
	 * Menu order of the media item.
	 *
	 * @since BuddyBoss 1.0.0
	 * @var int
	 */
	var $menu_order;

	/**
	 * Upload date of the media item.
	 *
	 * @since BuddyBoss 1.0.0
	 * @var string
	 */
	var $date_created;

	/**
	 * Error holder.
	 *
	 * @since BuddyBoss 1.0.0
	 *
	 * @var WP_Error
	 */
	public $errors;

	/**
	 * Error type to return. Either 'bool' or 'wp_error'.
	 *
	 * @since BuddyBoss 1.0.0
	 *
	 * @var string
	 */
	public $error_type = 'bool';

	/**
	 * Constructor method.
	 *
	 * @since BuddyBoss 1.0.0
	 *
	 * @param int|bool $id Optional. The ID of a specific activity item.
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
	 * Populate the object with data about the specific media item.
	 *
	 * @since BuddyBoss 1.0.0
	 */
	public function populate() {

		global $wpdb;

		$row = wp_cache_get( $this->id, 'bp_media' );

		if ( false === $row ) {
			$bp  = buddypress();
			$row = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$bp->media->table_name} WHERE id = %d", $this->id ) );

			wp_cache_set( $this->id, $row, 'bp_media' );
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
	 * Save the media item to the database.
	 *
	 * @since BuddyBoss 1.0.0
	 *
	 * @return WP_Error|bool True on success.
	 */
	public function save() {

		global $wpdb;

		$bp = buddypress();

		$this->id            = apply_filters_ref_array( 'bp_media_id_before_save', array( $this->id, &$this ) );
		$this->blog_id       = apply_filters_ref_array( 'bp_media_blog_id_before_save', array( $this->blog_id, &$this ) );
		$this->attachment_id = apply_filters_ref_array( 'bp_media_attachment_id_before_save', array( $this->attachment_id, &$this ) );
		$this->user_id       = apply_filters_ref_array( 'bp_media_user_id_before_save', array( $this->user_id, &$this ) );
		$this->title         = apply_filters_ref_array( 'bp_media_title_before_save', array( $this->title, &$this ) );
		$this->album_id      = apply_filters_ref_array( 'bp_media_album_id_before_save', array( $this->album_id, &$this ) );
		$this->activity_id   = apply_filters_ref_array( 'bp_media_activity_id_before_save', array( $this->activity_id, &$this ) );
		$this->message_id    = apply_filters_ref_array( 'bp_media_message_id_before_save', array( $this->message_id, &$this ) );
		$this->group_id      = apply_filters_ref_array( 'bp_media_group_id_before_save', array( $this->group_id, &$this ) );
		$this->privacy       = apply_filters_ref_array( 'bp_media_privacy_before_save', array( $this->privacy, &$this ) );
		$this->menu_order    = apply_filters_ref_array( 'bp_media_menu_order_before_save', array( $this->menu_order, &$this ) );
		$this->date_created  = apply_filters_ref_array( 'bp_media_date_created_before_save', array( $this->date_created, &$this ) );

		/**
		 * Fires before the current media item gets saved.
		 *
		 * Please use this hook to filter the properties above. Each part will be passed in.
		 *
		 * @since BuddyBoss 1.0.0
		 *
		 * @param BP_Media $this Current instance of the media item being saved. Passed by reference.
		 */
		do_action_ref_array( 'bp_media_before_save', array( &$this ) );

		if ( 'wp_error' === $this->error_type && $this->errors->get_error_code() ) {
			return $this->errors;
		}

		if ( empty( $this->attachment_id )
		// || empty( $this->activity_id ) //todo: when forums media is saving, it should have activity id assigned if settings enabled need to check this
		) {
			if ( 'bool' === $this->error_type ) {
				return false;
			} else {
				if ( empty( $this->activity_id ) ) {
					$this->errors->add( 'bp_media_missing_activity' );
				} else {
					$this->errors->add( 'bp_media_missing_attachment' );
				}

				return $this->errors;
			}
		}

		// If we have an existing ID, update the media item, otherwise insert it.
		if ( ! empty( $this->id ) ) {
			$q = $wpdb->prepare( "UPDATE {$bp->media->table_name} SET blog_id = %d, attachment_id = %d, user_id = %d, title = %s, album_id = %d, activity_id = %d, message_id = %d, group_id = %d, privacy = %s, menu_order = %d, date_created = %s WHERE id = %d", $this->blog_id, $this->attachment_id, $this->user_id, $this->title, $this->album_id, $this->activity_id, $this->message_id, $this->group_id, $this->privacy, $this->menu_order, $this->date_created, $this->id );
		} else {
			$q = $wpdb->prepare( "INSERT INTO {$bp->media->table_name} ( blog_id, attachment_id, user_id, title, album_id, activity_id, message_id, group_id, privacy, menu_order, date_created ) VALUES ( %d, %d, %d, %s, %d, %d, %d, %d, %s, %d, %s )", $this->blog_id, $this->attachment_id, $this->user_id, $this->title, $this->album_id, $this->activity_id, $this->message_id, $this->group_id, $this->privacy, $this->menu_order, $this->date_created );
		}

		if ( false === $wpdb->query( $q ) ) {
			return false;
		}

		// If this is a new media item, set the $id property.
		if ( empty( $this->id ) ) {
			$this->id = $wpdb->insert_id;
		}

		/**
		 * Fires after an media item has been saved to the database.
		 *
		 * @since BuddyBoss 1.0.0
		 *
		 * @param BP_Media $this Current instance of media item being saved. Passed by reference.
		 */
		do_action_ref_array( 'bp_media_after_save', array( &$this ) );

		return true;
	}

	/** Static Methods ***************************************************/

	/**
	 * Get media items, as specified by parameters.
	 *
	 * @since BuddyBoss 1.0.0
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
	 *               - 'medias' is an array of the located medias
	 */
	public static function get( $args = array() ) {

		global $wpdb;

		$bp = buddypress();
		$r  = bp_parse_args(
			$args,
			array(
				'scope'            => '',              // Scope - Groups, friends etc.
				'page'             => 1,               // The current page.
				'per_page'         => 20,              // Media items per page.
				'max'              => false,           // Max number of items to return.
				'fields'           => 'all',           // Fields to include.
				'sort'             => 'DESC',          // ASC or DESC.
				'order_by'         => 'date_created',  // Column to order by.
				'exclude'          => false,           // Array of ids to exclude.
				'in'               => false,           // Array of ids to limit query by (IN).
				'search_terms'     => false,           // Terms to search by.
				'album_id'         => false,           // Album ID.
				'privacy'          => false,           // public, loggedin, onlyme, friends, grouponly, message.
				'count_total'      => false,           // Whether to use count_total.
				'video'            => false,           // Whether to include videos.
				'user_id'          => false,           // Filter by user id.
				'activity_id'      => false,           // Filter by activity id.
				'group_id'         => false,           // Filter by group id.
				'moderation_query' => false,           // Filter to include moderation.
			)
		);

		// Select conditions.
		$select_sql = 'SELECT DISTINCT m.id';

		$from_sql = " FROM {$bp->media->table_name} m";

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
			 * @since BuddyBoss 1.0.0
			 *
			 * @param bool $value Whether or not to include user search. Default false.
			 */
			if ( apply_filters( 'bp_media_get_include_user_search', false ) ) {
				$user_search = get_user_by( 'slug', $r['search_terms'] );
				if ( false !== $user_search ) {
					$user_id                         = $user_search->ID;
					$where_conditions['search_sql'] .= $wpdb->prepare( ' OR m.user_id = %d', $user_id );
				}
			}
		}

		// Sorting.
		$sort = $r['sort'];
		if ( 'ASC' !== $sort && 'DESC' !== $sort ) {
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

		// existing-media check to query media which has no albums assigned.
		if ( ! empty( $r['album_id'] ) && 'existing-media' !== $r['album_id'] ) {
			$where_conditions['album'] = "m.album_id = {$r['album_id']}";
		} elseif ( ! empty( $r['album_id'] ) && 'existing-media' === $r['album_id'] ) {
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

		if ( ! $r['video'] ) {
			$where_conditions['type'] = "m.type = 'photo'";
		}

		/**
		 * Filters the MySQL WHERE conditions for the Media items get method.
		 *
		 * @since BuddyBoss 1.0.0
		 *
		 * @param array  $where_conditions Current conditions for MySQL WHERE statement.
		 * @param array  $r                Parsed arguments passed into method.
		 * @param string $select_sql       Current SELECT MySQL statement at point of execution.
		 * @param string $from_sql         Current FROM MySQL statement at point of execution.
		 * @param string $join_sql         Current INNER JOIN MySQL statement at point of execution.
		 */
		$where_conditions = apply_filters( 'bp_media_get_where_conditions', $where_conditions, $r, $select_sql, $from_sql, $join_sql );

		if ( empty( $where_conditions ) ) {
			$where_conditions['2'] = '2';
		}

		// Join the where conditions together.
		if ( ! empty( $scope_query['sql'] ) ) {
			$where_sql = 'WHERE ' .
						 ( ! empty( $where_conditions ) ? '( ' . join( ' AND ', $where_conditions ) . ' ) AND ' : '' ) .
						 ' ( ' . $scope_query['sql'] . ' )';
		} else {
			$where_sql = 'WHERE ' . join( ' AND ', $where_conditions );
		}

		/**
		 * Filter the MySQL JOIN clause for the main media query.
		 *
		 * @since BuddyBoss 1.0.0
		 *
		 * @param string $join_sql   JOIN clause.
		 * @param array  $r          Method parameters.
		 * @param string $select_sql Current SELECT MySQL statement.
		 * @param string $from_sql   Current FROM MySQL statement.
		 * @param string $where_sql  Current WHERE MySQL statement.
		 */
		$join_sql = apply_filters( 'bp_media_get_join_sql', $join_sql, $r, $select_sql, $from_sql, $where_sql );

		// Sanitize page and per_page parameters.
		$page     = absint( $r['page'] );
		$per_page = absint( $r['per_page'] );

		$retval = array(
			'medias'         => null,
			'total'          => null,
			'has_more_items' => null,
		);

		if ( $r['video'] ) {
			$retval['total_video'] = null;
		}

		// Query first for media IDs.
		$media_ids_sql = "{$select_sql} {$from_sql} {$join_sql} {$where_sql} ORDER BY {$order_by} {$sort}, m.id {$sort}";

		if ( ! empty( $per_page ) && ! empty( $page ) ) {
			// We query for $per_page + 1 items in order to
			// populate the has_more_items flag.
			$media_ids_sql .= $wpdb->prepare( ' LIMIT %d, %d', absint( ( $page - 1 ) * $per_page ), $per_page + 1 );
		}

		/**
		 * Filters the paged media MySQL statement.
		 *
		 * @since BuddyBoss 1.0.0
		 *
		 * @param string $media_ids_sql    MySQL statement used to query for Media IDs.
		 * @param array  $r                Array of arguments passed into method.
		 */
		$media_ids_sql = apply_filters( 'bp_media_paged_activities_sql', $media_ids_sql, $r );

		$cache_group = 'bp_media';

		$cached = bp_core_get_incremented_cache( $media_ids_sql, $cache_group );

		if ( false === $cached ) {
			$media_ids = $wpdb->get_col( $media_ids_sql );
			bp_core_set_incremented_cache( $media_ids_sql, $cache_group, $media_ids );
		} else {
			$media_ids = $cached;
		}

		$retval['has_more_items'] = ! empty( $per_page ) && count( $media_ids ) > $per_page;

		// If we've fetched more than the $per_page value, we
		// can discard the extra now.
		if ( ! empty( $per_page ) && count( $media_ids ) === $per_page + 1 ) {
			array_pop( $media_ids );
		}

		if ( 'ids' === $r['fields'] ) {
			$medias = array_map( 'intval', $media_ids );
		} else {
			$medias = self::get_media_data( $media_ids );
		}

		if ( 'ids' !== $r['fields'] ) {
			// Get the fullnames of users so we don't have to query in the loop.
			// $medias = self::append_user_fullnames( $medias );

			// Pre-fetch data associated with media users and other objects.
			$medias = self::prefetch_object_data( $medias );
		}

		$retval['medias'] = $medias;

		// If $max is set, only return up to the max results.
		if ( ! empty( $r['count_total'] ) ) {

			/**
			 * Filters the total media MySQL statement.
			 *
			 * @since BuddyBoss 1.0.0
			 *
			 * @param string $value     MySQL statement used to query for total medias.
			 * @param string $where_sql MySQL WHERE statement portion.
			 * @param string $sort      Sort direction for query.
			 */
			$total_medias_sql = apply_filters( 'bp_media_total_medias_sql', "SELECT count(DISTINCT m.id) FROM {$bp->media->table_name} m {$join_sql} {$where_sql}", $where_sql, $sort );
			$cached           = bp_core_get_incremented_cache( $total_medias_sql, $cache_group );
			if ( false === $cached ) {
				$total_medias = $wpdb->get_var( $total_medias_sql );
				bp_core_set_incremented_cache( $total_medias_sql, $cache_group, $total_medias );
			} else {
				$total_medias = $cached;
			}

			if ( ! empty( $r['max'] ) ) {
				if ( (int) $total_medias > (int) $r['max'] ) {
					$total_medias = $r['max'];
				}
			}

			$retval['total'] = $total_medias;

			if ( $r['video'] ) {

				$where_sql .= " AND m.type = 'video'";

				/**
				 * Filters the total video MySQL statement.
				 *
				 * @since BuddyBoss 1.5.8
				 *
				 * @param string $value     MySQL statement used to query for total medias.
				 * @param string $where_sql MySQL WHERE statement portion.
				 * @param string $sort      Sort direction for query.
				 */
				$total_videos_sql = apply_filters( 'bp_media_total_videos_sql', "SELECT count(DISTINCT m.id) FROM {$bp->media->table_name} m {$join_sql} {$where_sql}", $where_sql, $sort );
				$cached           = bp_core_get_incremented_cache( $total_videos_sql, $cache_group );
				if ( false === $cached ) {
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

				$retval['total_video'] = $total_videos;
				$retval['total']       = $retval['total'] - $total_videos;
			} else {
				$retval['total_video'] = null;
			}
		}

		return $retval;
	}

	/**
	 * Convert media IDs to media objects, as expected in template loop.
	 *
	 * @since BuddyBoss 1.0.0
	 *
	 * @param array $media_ids Array of media IDs.
	 * @return array
	 */
	protected static function get_media_data( $media_ids = array() ) {
		global $wpdb;

		// Bail if no media ID's passed.
		if ( empty( $media_ids ) ) {
			return array();
		}

		// Get BuddyPress.
		$bp = buddypress();

		// Media Privacy array.
		$media_privacy = bp_media_get_visibility_levels();

		$medias       = array();
		$uncached_ids = bp_get_non_cached_ids( $media_ids, 'bp_media' );

		// Prime caches as necessary.
		if ( ! empty( $uncached_ids ) ) {
			// Format the media ID's for use in the query below.
			$uncached_ids_sql = implode( ',', wp_parse_id_list( $uncached_ids ) );

			// Fetch data from media table, preserving order.
			$queried_adata = $wpdb->get_results( "SELECT * FROM {$bp->media->table_name} WHERE id IN ({$uncached_ids_sql})" );

			// Put that data into the placeholders created earlier,
			// and add it to the cache.
			foreach ( (array) $queried_adata as $adata ) {
				wp_cache_set( $adata->id, $adata, 'bp_media' );
			}
		}

		// Now fetch data from the cache.
		foreach ( $media_ids as $media_id ) {
			// Integer casting.
			$media = wp_cache_get( $media_id, 'bp_media' );
			if ( ! empty( $media ) ) {
				$media->id            = (int) $media->id;
				$media->blog_id       = (int) $media->blog_id;
				$media->user_id       = (int) $media->user_id;
				$media->attachment_id = (int) $media->attachment_id;
				$media->album_id      = (int) $media->album_id;
				$media->activity_id   = (int) $media->activity_id;
				$media->message_id    = (int) $media->message_id;
				$media->group_id      = (int) $media->group_id;
				$media->menu_order    = (int) $media->menu_order;
			}

			// fetch attachment data.
			$attachment_data                              = new stdClass();
			$activity_thumb                               = bp_media_get_preview_image_url( $media->id, $media->attachment_id, 'bb-media-activity-image' );
			$media_album_cover_thumb                      = bp_media_get_preview_image_url( $media->id, $media->attachment_id, 'bb-media-photos-album-directory-image-medium' );
			$media_photos_page_thumb                      = $media_album_cover_thumb;
			$media_theatre_popup                          = bp_media_get_preview_image_url( $media->id, $media->attachment_id, 'bb-media-photos-popup-image' );
			$attachment_data->full                        = $activity_thumb;
			$attachment_data->thumb                       = $activity_thumb;
			$attachment_data->media_album_cover           = $media_album_cover_thumb;
			$attachment_data->media_photos_directory_page = $media_photos_page_thumb;
			$attachment_data->media_theatre_popup         = $media_theatre_popup;
			$attachment_data->activity_thumb              = $activity_thumb;
			$attachment_data->meta                        = self::attachment_meta( $media->attachment_id );
			$media->attachment_data                       = $attachment_data;

			if( $media->type == 'video' && function_exists( 'bb_video_get_thumb_url' ) ) {
				$get_video_thumb_ids = get_post_meta( $media->attachment_id, 'video_preview_thumbnails', true );
				$get_video_thumb_id  = get_post_meta( $media->attachment_id, 'bp_video_preview_thumbnail_id', true );

				$attachment_data->mime_type        = get_post_mime_type( $media->attachment_id );
				$length_formatted                  = wp_get_attachment_metadata( $media->attachment_id );
				$attachment_data->length_formatted = isset( $length_formatted['length_formatted'] ) ? $length_formatted['length_formatted'] : '0:00';
				$default_image                     = bb_get_video_default_placeholder_image();

				if ( $get_video_thumb_id ) {
					$video_user_profile_thumb                    = bb_video_get_thumb_url( $media->id, $get_video_thumb_id, 'bb-video-profile-album-add-thumbnail-directory-poster-image' );
					$video_directory_page_thumb                  = $video_user_profile_thumb;
					$video_album_cover_thumb                     = $video_user_profile_thumb;
					$video_add_thumbnail_thumb                   = $video_album_cover_thumb;
					$video_popup_thumb                           = bb_video_get_thumb_url( $media->id, $get_video_thumb_id, 'bb-video-poster-popup-image' );
					$video_activity_thumb                        = bb_video_get_thumb_url( $media->id, $get_video_thumb_id, 'bb-video-activity-image' );
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
					$attachment_data->thumb_meta                 = wp_get_attachment_metadata( $get_video_thumb_id );
				} elseif ( isset( $get_video_thumb_ids['default_images'] ) && ! empty( $get_video_thumb_ids['default_images'] ) ) {
					$get_video_thumb_id                          = current( $get_video_thumb_ids['default_images'] );
					$video_user_profile_thumb                    = bb_video_get_thumb_url( $media->id, $get_video_thumb_id, 'bb-video-profile-album-add-thumbnail-directory-poster-image' );
					$video_directory_page_thumb                  = $video_user_profile_thumb;
					$video_album_cover_thumb                     = $video_user_profile_thumb;
					$video_add_thumbnail_thumb                   = $video_album_cover_thumb;
					$video_popup_thumb                           = bb_video_get_thumb_url( $media->id, $get_video_thumb_id, 'bb-video-poster-popup-image' );
					$video_activity_thumb                        = bb_video_get_thumb_url( $media->id, $get_video_thumb_id, 'bb-video-activity-image' );
					$attachment_data->full                       = $video_popup_thumb;
					$attachment_data->thumb                      = $video_album_cover_thumb;
					$attachment_data->activity_thumb             = $video_activity_thumb;
					$attachment_data->video_user_profile_thumb   = $video_user_profile_thumb;
					$attachment_data->video_directory_page_thumb = $video_directory_page_thumb;
					$attachment_data->media_album_cover          = $video_album_cover_thumb;
					$attachment_data->video_album_cover_thumb    = $video_album_cover_thumb;
					$attachment_data->video_add_thumbnail_thumb  = $video_add_thumbnail_thumb;
					$attachment_data->video_popup_thumb          = $video_popup_thumb;
					$attachment_data->video_activity_thumb       = $video_activity_thumb;
					$attachment_data->thumb_meta                 = wp_get_attachment_metadata( $get_video_thumb_id );
				} else {
					$attachment_data->full           = $default_image;
					$attachment_data->thumb          = $default_image;
					$attachment_data->activity_thumb = $default_image;
					$attachment_data->thumb_meta     = $default_image;
				}

				$media->attachment_data = $attachment_data;
			}
			$group_name = '';
			if ( bp_is_active( 'groups' ) && $media->group_id > 0 ) {
				$group      = groups_get_group( $media->group_id );
				$group_name = bp_get_group_name( $group );
				$status     = bp_get_group_status( $group );
				if ( 'hidden' === $status || 'private' === $status ) {
					$visibility = esc_html__( 'Group Members', 'buddyboss' );
				} else {
					$visibility = ucfirst( $status );
				}
			} else {
				$media_privacy = bp_media_get_visibility_levels();
				if ( 'friends' === $media->privacy && bp_loggedin_user_id() !== (int) $media->user_id ) {
					$visibility = esc_html__( 'Connections', 'buddyboss' );
				} elseif ( 'message' === $media->privacy ) {
					$visibility = esc_html__( 'Message', 'buddyboss' );
				} elseif ( 'forums' === $media->privacy ) {
					$visibility = esc_html__( 'Forums', 'buddyboss' );
				} else {
					$visibility = ( isset( $media_privacy[ $media->privacy ] ) ) ? ucfirst( $media_privacy[ $media->privacy ] ) : '';
				}
			}

			$media->group_name = $group_name;
			$media->visibility = $visibility;

			$medias[] = $media;
		}

		return $medias;
	}

	/**
	 * Get attachment meta.
	 *
	 * @param int $attachment_id Attachment ID.
	 *
	 * @return array
	 * @since BuddyBoss 1.5.7
	 */
	protected static function attachment_meta( $attachment_id ) {
		$metadata = wp_get_attachment_metadata( $attachment_id );

		if ( ! $metadata ) {
			return $metadata;
		}

		$meta = array(
			'width'  => $metadata['width'],
			'height' => $metadata['height'],
			'sizes'  => array(),
		);

		if ( isset( $metadata['sizes']['bb-media-activity-image'] ) ) {
			$meta['sizes']['bb-media-activity-image'] = $metadata['sizes']['bb-media-activity-image'];
		}

		if ( isset( $metadata['sizes']['bb-media-photos-album-directory-image'] ) ) {
			$meta['sizes']['bb-media-photos-album-directory-image'] = $metadata['sizes']['bb-media-photos-album-directory-image'];
		}
		if ( isset( $metadata['sizes']['bb-media-photos-album-directory-image-medium'] ) ) {
			$meta['sizes']['bb-media-photos-album-directory-image-medium'] = $metadata['sizes']['bb-media-photos-album-directory-image-medium'];
		}

		if ( isset( $metadata['sizes']['bb-media-photos-popup-image'] ) ) {
			$meta['sizes']['bb-media-photos-popup-image'] = $metadata['sizes']['bb-media-photos-popup-image'];
		}

		return $meta;
	}

	/**
	 * Append xProfile fullnames to an media array.
	 *
	 * @since BuddyBoss 1.0.0
	 *
	 * @param array $medias Medias array.
	 * @return array
	 */
	protected static function append_user_fullnames( $medias ) {

		if ( bp_is_active( 'xprofile' ) && ! empty( $medias ) ) {
			$media_user_ids = wp_list_pluck( $medias, 'user_id' );

			if ( ! empty( $media_user_ids ) ) {
				$fullnames = bp_core_get_user_displaynames( $media_user_ids );
				if ( ! empty( $fullnames ) ) {
					foreach ( (array) $medias as $i => $media ) {
						if ( ! empty( $fullnames[ $media->user_id ] ) ) {
							$medias[ $i ]->user_fullname = $fullnames[ $media->user_id ];
						}
					}
				}
			}
		}

		return $medias;
	}

	/**
	 * Pre-fetch data for objects associated with media items.
	 *
	 * Media items are associated with users, and often with other
	 * BuddyPress data objects. Here, we pre-fetch data about these
	 * associated objects, so that inline lookups - done primarily when
	 * building action strings - do not result in excess database queries.
	 *
	 * @since BuddyBoss 1.0.0
	 *
	 * @param array $medias Array of media.
	 * @return array $medias Array of media.
	 */
	protected static function prefetch_object_data( $medias ) {

		/**
		 * Filters inside prefetch_object_data method to aid in pre-fetching object data associated with media item.
		 *
		 * @since BuddyBoss 1.0.0
		 *
		 * @param array $medias Array of media.
		 */
		return apply_filters( 'bp_media_prefetch_object_data', $medias );
	}

	/**
	 * Get the SQL for the 'scope' param in BP_Media::get().
	 *
	 * A scope is a predetermined set of media arguments.  This method is used
	 * to grab these media arguments and override any existing args if needed.
	 *
	 * Can handle multiple scopes.
	 *
	 * @since BuddyBoss 1.1.9
	 *
	 * @param  mixed $scope  The media scope. Accepts string or array of scopes.
	 * @param  array $r      Current activity arguments. Same as those of BP_Media::get(),
	 *                       but merged with defaults.
	 * @return false|array 'sql' WHERE SQL string and 'override' media args.
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
			 * Plugins can hook here to set their media arguments for custom scopes.
			 *
			 * This is a dynamic filter based on the media scope. eg:
			 *   - 'bp_media_set_groups_scope_args'
			 *   - 'bp_media_set_friends_scope_args'
			 *
			 * To see how this filter is used, plugin devs should check out:
			 *   - bp_groups_filter_media_scope() - used for 'groups' scope
			 *   - bp_friends_filter_media_scope() - used for 'friends' scope
			 *
			 * @since BuddyBoss 1.1.9
			 *
			 * @param array {
			 *     Media query clauses.
			 *     @type array {
			 *         Media arguments for your custom scope.
			 *         See {@link BP_Media_Query::_construct()} for more details.
			 *     }
			 *     @type array  $override Optional. Override existing media arguments passed by $r.
			 *     }
			 * }
			 * @param array $r Current activity arguments passed in BP_Media::get().
			 */
			$scope_args = apply_filters( "bp_media_set_{$scope}_scope_args", array(), $r );

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

			$query = new BP_Media_Query( $query_args );
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
	 * @since BuddyBoss 1.1.9
	 *
	 * @see BP_Media::get_filter_sql()
	 *
	 * @param string     $field The database field.
	 * @param array|bool $items The values for the IN clause, or false when none are found.
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
	 * Delete media items from the database.
	 *
	 * To delete a specific media item, pass an 'id' parameter.
	 * Otherwise use the filters.
	 *
	 * @since BuddyBoss 1.0.0
	 *
	 * @param array $args {
	 * @int    $id                Optional. The ID of a specific item to delete.
	 * @int    $blog_id           Optional. The blog ID to filter by.
	 * @int    $attachment_id           Optional. The attachment ID to filter by.
	 * @int    $user_id           Optional. The user ID to filter by.
	 * @string    $title           Optional. The title to filter by.
	 * @int    $album_id           Optional. The album ID to filter by.
	 * @int    $activity_id           Optional. The activity ID to filter by.
	 * @int    $group_id           Optional. The group ID to filter by.
	 * @string    $privacy           Optional. The privacy to filter by.
	 * @string $date_created      Optional. The date to filter by.
	 * }
	 * @param bool  $from Context of deletion from. ex. attachment, activity etc.
	 *
	 * @return array|bool An array of deleted media IDs on success, false on failure.
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

		// Bail if no where arguments.
		if ( empty( $where_args ) ) {
			return false;
		}

		// Join the where arguments for querying.
		$where_sql = 'WHERE ' . join( ' AND ', $where_args );

		// Fetch all media being deleted so we can perform more actions.
		$medias = $wpdb->get_results( "SELECT * FROM {$bp->media->table_name} {$where_sql}" );

		/**
		 * Action to allow intercepting media items to be deleted.
		 *
		 * @since BuddyBoss 1.0.0
		 *
		 * @param array $medias Array of media.
		 * @param array $r          Array of parsed arguments.
		 */
		do_action_ref_array( 'bp_media_before_delete', array( $medias, $r ) );

		// Attempt to delete media from the database.
		$deleted = $wpdb->query( "DELETE FROM {$bp->media->table_name} {$where_sql}" );

		// Bail if nothing was deleted.
		if ( empty( $deleted ) ) {
			return false;
		}

		/**
		 * Action to allow intercepting media items just deleted.
		 *
		 * @since BuddyBoss 1.0.0
		 *
		 * @param array $medias Array of media.
		 * @param array $r          Array of parsed arguments.
		 */
		do_action_ref_array( 'bp_media_after_delete', array( $medias, $r ) );

		// Pluck the media IDs out of the $medias array.
		$media_ids      = wp_parse_id_list( wp_list_pluck( $medias, 'id' ) );
		$activity_ids   = wp_parse_id_list( wp_list_pluck( $medias, 'activity_id' ) );
		$attachment_ids = wp_parse_id_list( wp_list_pluck( $medias, 'attachment_id' ) );

		// Handle accompanying attachments and meta deletion.
		if ( ! empty( $attachment_ids ) ) {

			// Loop through attachment ids and attempt to delete.
			foreach ( $attachment_ids as $attachment_id ) {

				if ( bp_is_active( 'activity' ) ) {
					$parent_activity_id = get_post_meta( $attachment_id, 'bp_media_parent_activity_id', true );
					if ( ! empty( $parent_activity_id ) ) {
						$activity_media_ids = bp_activity_get_meta( $parent_activity_id, 'bp_media_ids', true );
						if ( ! empty( $activity_media_ids ) ) {
							$activity_media_ids = explode( ',', $activity_media_ids );
							$activity_media_ids = array_diff( $activity_media_ids, $media_ids );
							if ( ! empty( $activity_media_ids ) ) {
								$activity_media_ids = implode( ',', $activity_media_ids );
								bp_activity_update_meta( $parent_activity_id, 'bp_media_ids', $activity_media_ids );
							} else {
								$activity_ids[] = $parent_activity_id;
							}
						}
					}
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
					if ( 'activity_comment' == $activity->type ) {
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

		return $media_ids;
	}

	/**
	 * Count total media for the given user
	 *
	 * @since BuddyBoss 1.0.0
	 *
	 * @param int $user_id User id.
	 *
	 * @return array|bool|int
	 */
	public static function total_media_count( $user_id = 0 ) {
		global $bp, $wpdb;

		$privacy = bp_media_query_privacy( $user_id );
		$privacy = "'" . implode( "', '", $privacy ) . "'";

		$cache_key   = 'bp_total_media_count_' . $user_id;
		$total_count = wp_cache_get( $cache_key, 'bp_media' );

		if ( false === $total_count ) {
			$total_count = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$bp->media->table_name} WHERE user_id = {$user_id} AND privacy IN ({$privacy})" );
			wp_cache_set( $cache_key, $total_count, 'bp_media' );
		}

		return $total_count;
	}

	/**
	 * Count total media for the given group
	 *
	 * @since BuddyBoss 1.0.0
	 *
	 * @param int $group_id group id to get the photos count.
	 *
	 * @return array|bool|int
	 */
	public static function total_group_media_count( $group_id = 0 ) {
		global $bp, $wpdb;

		$cache_key   = 'total_group_media_count_' . $group_id;
		$total_count = wp_cache_get( $cache_key, 'bp_media' );

		if ( false === $total_count ) {
			$select_sql = 'SELECT COUNT(*)';

			$from_sql = " FROM {$bp->media->table_name} m";

			// Where conditions.
			$where_conditions = array();

			$where_conditions['group_sql']         = $wpdb->prepare( 'm.group_id = %s', $group_id );
			$where_conditions['group_media_count'] = $wpdb->prepare( 'm.type = %s', 'photo' );
			$where_conditions['group_privacy']     = $wpdb->prepare( 'm.privacy = %s', 'grouponly' );

			/**
			 * Filters the MySQL WHERE conditions for the Media items get method.
			 *
			 * @param array $where_conditions Current conditions for MySQL WHERE statement.
			 * @param array $args array of arguments.
			 *
			 * @since BuddyBoss 1.5.6
			 *
			 */
			$where_conditions = apply_filters( 'bp_media_get_where_count_conditions', $where_conditions, array( 'group_id' => $group_id ) );

			$where_sql = 'WHERE ' . join( ' AND ', $where_conditions );

			/**
			 * Filter the MySQL JOIN clause for the main media query.
			 *
			 * @param string $join_sql JOIN clause.
			 * @param array $args array of arguments.
			 *
			 * @since BuddyBoss 1.5.6
			 *
			 */
			$from_sql = apply_filters( 'bp_media_get_join_count_sql', $from_sql, array( 'group_id' => $group_id ) );

			$media_ids_sql = "{$select_sql} {$from_sql} {$where_sql}";
			$total_count   = (int) $wpdb->get_var( $media_ids_sql ); // phpcs:ignore.
			wp_cache_set( $cache_key, $total_count, 'bp_media' );
		}

		return $total_count;
	}

	/**
	 * Count total groups media for the given user.
	 *
	 * @param int $user_id User id.
	 *
	 * @return array|bool|int
	 * @since BuddyBoss 1.4.0
	 */
	public static function total_user_group_media_count( $user_id = 0 ) {
		global $bp, $wpdb;

		$cache_key   = 'total_user_group_media_count_' . $user_id;
		$total_count = wp_cache_get( $cache_key, 'bp_media' );

		if ( false === $total_count ) {
			$privacy = bp_media_query_privacy( $user_id, 0, 'groups' );
			$privacy = "'" . implode( "', '", $privacy ) . "'";

			$total_count = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$bp->media->table_name} WHERE user_id = {$user_id} AND privacy IN ({$privacy})" );
			wp_cache_set( $cache_key, $total_count, 'bp_media' );
		}

		return $total_count;
	}

	/**
	 * Get all media ids for the album
	 *
	 * @since BuddyBoss 1.0.0
	 * @param bool $album_id
	 *
	 * @return array|bool
	 */
	public static function get_album_media_ids( $album_id = false ) {
		global $bp, $wpdb;

		if ( ! $album_id ) {
			return false;
		}

		$album_media_sql = $wpdb->prepare( "SELECT DISTINCT m.id FROM {$bp->media->table_name} m WHERE m.album_id = %d", $album_id );

		$cached = bp_core_get_incremented_cache( $album_media_sql, 'bp_media' );

		if ( false === $cached ) {
			$media_ids = $wpdb->get_col( $album_media_sql );
			bp_core_set_incremented_cache( $album_media_sql, 'bp_media', $media_ids );
		} else {
			$media_ids = $cached;
		}

		return (array) $media_ids;
	}

	/**
	 * Get media id for the activity.
	 *
	 * @since BuddyBoss 1.1.6
	 * @param bool $activity_id
	 *
	 * @return array|bool
	 */
	public static function get_activity_media_id( $activity_id = false ) {
		global $bp, $wpdb;

		if ( ! $activity_id ) {
			return false;
		}

		$cache_key         = 'bp_media_activity_id_' . $activity_id;
		$activity_media_id = wp_cache_get( $cache_key, 'bp_media' );

		if ( ! empty( $activity_media_id ) ) {
			return $activity_media_id;
		}

		// Check activity component enabled or not.
		if ( bp_is_active( 'activity' ) ) {
			$activity_media_id = bp_activity_get_meta( $activity_id, 'bp_media_id', true );
		}

		if ( empty( $activity_media_id ) ) {
			$cache_key         = 'get_activity_media_id_' . $activity_id;
			$activity_media_id = wp_cache_get( $cache_key, 'bp_media' );

			if ( false === $activity_media_id ) {
				$activity_media_id = (int) $wpdb->get_var( "SELECT DISTINCT m.id FROM {$bp->media->table_name} m WHERE m.activity_id = {$activity_id} AND m.type = 'photo' " );
				wp_cache_set( $cache_key, $activity_media_id, 'bp_media' );
			}

			if ( bp_is_active( 'activity' ) ) {
				$media_activity = bp_activity_get_meta( $activity_id, 'bp_media_activity', true );
				if ( ! empty( $media_activity ) && ! empty( $activity_media_id ) ) {
					bp_activity_update_meta( $activity_id, 'bp_media_id', $activity_media_id );
				}
			}
		}

		if ( ! empty( $activity_media_id ) ) {
			wp_cache_set( $cache_key, $activity_media_id, 'bp_media' );
		}

		return $activity_media_id;
	}

	/**
	 * Get media attachment id for the activity.
	 *
	 * @param integer $activity_id Activity ID
	 *
	 * @return integer|bool
	 * @since BuddyBoss 1.4.0
	 */
	public static function get_activity_attachment_id( $activity_id = 0 ) {
		global $bp, $wpdb;

		if ( empty( $activity_id ) ) {
			return false;
		}

		$cache_key = 'bp_media_attachment_id_' . $activity_id;
		$result    = wp_cache_get( $cache_key, 'bp_media' );

		if ( false === $result ) {
			$result = (int) $wpdb->get_var( "SELECT DISTINCT m.attachment_id FROM {$bp->media->table_name} m WHERE m.activity_id = {$activity_id}" );
			wp_cache_set( $cache_key, $result, 'bp_media' );
		}

		return $result;
	}

}
