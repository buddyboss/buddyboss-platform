<?php
/**
 * BuddyBoss Document Classes
 *
 * @package BuddyBoss\Document
 * @since BuddyBoss 1.3.6
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Database interaction class for the BuddyBoss document folder component.
 * Instance methods are available for creating/editing an document folders,
 * static methods for querying document folder.
 *
 * @since BuddyBoss 1.3.6
 */
class BP_Document_Folder {

	/** Properties ************************************************************/

	/**
	 * ID of the folder.
	 *
	 * @since BuddyBoss 1.3.6
	 * @var int
	 */
	var $id;

	/**
	 * User ID of the folder.
	 *
	 * @since BuddyBoss 1.3.6
	 * @var int
	 */
	var $user_id;

	/**
	 * Group ID of the folder.
	 *
	 * @since BuddyBoss 1.3.6
	 * @var int
	 */
	var $group_id;

	/**
	 * Title of the folder.
	 *
	 * @since BuddyBoss 1.3.6
	 * @var string
	 */
	var $title;

	/**
	 * Privacy of the folder.
	 *
	 * @since BuddyBoss 1.3.6
	 * @var string
	 */
	var $privacy;

	/**
	 * Upload date of the folder.
	 *
	 * @since BuddyBoss 1.3.6
	 * @var string
	 */
	var $date_created;

	/**
	 * Update date of the folder.
	 *
	 * @since BuddyBoss 1.3.6
	 * @var string
	 */
	var $date_modified;

	/**
	 * Error holder.
	 *
	 * @since BuddyBoss 1.3.6
	 *
	 * @var WP_Error
	 */
	public $errors;

	/**
	 * Error type to return. Either 'bool' or 'wp_error'.
	 *
	 * @since BuddyBoss 1.3.6
	 *
	 * @var string
	 */
	public $error_type = 'bool';

	/**
	 * Parent ID of the folder.
	 *
	 * @since BuddyBoss 1.3.6
	 * @var int
	 */
	public $parent;

	/**
	 * Constructor method.
	 *
	 * @since BuddyBoss 1.3.6
	 *
	 * @param int|bool $id Optional. The ID of a specific document folder.
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
	 * Populate the object with data about the specific folder item.
	 *
	 * @since BuddyBoss 1.3.6
	 */
	public function populate() {

		global $wpdb;

		$row = wp_cache_get( $this->id, 'bp_document_folder' );

		if ( false === $row ) {
			$bp  = buddypress();
			$row = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$bp->media->table_name_albums} WHERE id = %d", $this->id ) ); // db call ok; no-cache ok;

			wp_cache_set( $this->id, $row, 'bp_document_folder' );
		}

		if ( empty( $row ) ) {
			$this->id = 0;
			return;
		}

		$this->id            = (int) $row->id;
		$this->user_id       = (int) $row->user_id;
		$this->group_id      = (int) $row->group_id;
		$this->title         = $row->title;
		$this->privacy       = $row->privacy;
		$this->date_created  = $row->date_created;
		$this->date_modified = $row->date_modified;
		$this->parent        = $row->parent;
	}

	/**
	 * Save the document folder to the database.
	 *
	 * @since BuddyBoss 1.3.6
	 *
	 * @return WP_Error|bool True on success.
	 */
	public function save() {

		global $wpdb;

		$bp = buddypress();

		$this->id            = apply_filters_ref_array( 'bp_document_id_before_save', array( $this->id, &$this ) );
		$this->user_id       = apply_filters_ref_array( 'bp_document_user_id_before_save', array( $this->user_id, &$this ) );
		$this->group_id      = apply_filters_ref_array( 'bp_document_group_id_before_save', array( $this->group_id, &$this ) );
		$this->title         = apply_filters_ref_array( 'bp_document_title_before_save', array( $this->title, &$this ) );
		$this->privacy       = apply_filters_ref_array( 'bp_document_privacy_before_save', array( $this->privacy, &$this ) );
		$this->date_created  = apply_filters_ref_array( 'bp_document_date_created_before_save', array( $this->date_created, &$this ) );
		$this->date_modified = apply_filters_ref_array( 'bp_document_date_modified_before_save', array( $this->date_modified, &$this ) );
		$this->parent        = apply_filters_ref_array( 'bp_document_parent_before_save', array( $this->parent, &$this ) );

		/**
		 * Fires before the current folder gets saved.
		 *
		 * Please use this hook to filter the properties above. Each part will be passed in.
		 *
		 * @since BuddyBoss 1.3.6
		 *
		 * @param BP_Media $this Current instance of the folder being saved. Passed by reference.
		 */
		do_action_ref_array( 'bp_document_folder_before_save', array( &$this ) );

		if ( 'wp_error' === $this->error_type && $this->errors->get_error_code() ) {
			return $this->errors;
		}

		// If we have an existing ID, update the folder, otherwise insert it.
		if ( ! empty( $this->id ) ) {
			$q = $wpdb->prepare( "UPDATE {$bp->media->table_name_albums} SET user_id = %d, group_id = %d, title = %s, privacy = %s, type = %s, parent = %d, date_modified = %s WHERE id = %d", $this->user_id, $this->group_id, $this->title, $this->privacy, 'document', $this->parent, $this->date_modified, $this->id );
		} else {
			$q = $wpdb->prepare( "INSERT INTO {$bp->media->table_name_albums} ( user_id, group_id, title, privacy, date_created, date_modified, type, parent ) VALUES ( %d, %d, %s, %s, %s, %s, %s, %d )", $this->user_id, $this->group_id, $this->title, $this->privacy, $this->date_created, $this->date_modified, 'document', $this->parent );
		}

		$q = $wpdb->query( $q ); // db call ok; no-cache ok;
		if ( false === $q ) {
			return false;
		}

		// If this is a new folder, set the $id property.
		if ( empty( $this->id ) ) {
			$this->id = $wpdb->insert_id;
		}

		/**
		 * Fires after an album has been saved to the database.
		 *
		 * @since BuddyBoss 1.3.6
		 *
		 * @param BP_Media $this Current instance of album being saved. Passed by reference.
		 */
		do_action_ref_array( 'bp_document_folder_after_save', array( &$this ) );

		return true;
	}

	/** Static Methods ***************************************************/

	/**
	 * Get folders, as specified by parameters.
	 *
	 * @since BuddyBoss 1.3.6
	 *
	 * @param array $args {
	 *     An array of arguments. All items are optional.
	 *     @type int          $page              Which page of results to fetch. Using page=1 without per_page will result
	 *                                           in no pagination. Default: 1.
	 *     @type int|bool     $per_page          Number of results per page. Default: 20.
	 *     @type int|bool     $max               Maximum number of results to return. Default: false (unlimited).
	 *     @type string       $fields            Media fields to return. Pass 'ids' to get only the document IDs.
	 *                                           'all' returns full document objects.
	 *     @type string       $sort              ASC or DESC. Default: 'DESC'.
	 *     @type string       $order_by          Column to order results by.
	 *     @type array        $exclude           Array of document IDs to exclude. Default: false.
	 *     @type string       $search_terms      Limit results by a search term. Default: false.
	 *     @type string|bool  $count_total       If true, an additional DB query is run to count the total documents
	 *                                           for the query. Default: false.
	 * }
	 * @return array The array returned has two keys:
	 *               - 'total' is the count of located medias
	 *               - 'albums' is an array of the located medias
	 */
	public static function get( $args = array() ) {

		global $wpdb;

		$bp = buddypress();
		$r  = wp_parse_args(
			$args,
			array(
				'page'         => 1,               // The current page.
				'per_page'     => 20,              // folders per page.
				'max'          => false,           // Max number of items to return.
				'fields'       => 'all',           // Fields to include.
				'sort'         => 'DESC',          // ASC or DESC.
				'order_by'     => 'date_created',  // Column to order by.
				'exclude'      => false,           // Array of ids to exclude.
				'search_terms' => false,           // Terms to search by.
				'user_id'      => false,           // user id.
				'group_id'     => false,           // group id.
				'privacy'      => false,           // public, loggedin, onlyme, friends, grouponly.
				'count_total'  => false,           // Whether or not to use count_total.
				'in'           => false,           // Array of ids to limit query by (IN).
			)
		);

		// Select conditions.
		$select_sql = 'SELECT DISTINCT m.id';

		$from_sql = " FROM {$bp->document->table_name_folders} m";

		$join_sql = '';

		// Where conditions.
		$where_conditions = array();

		// Searching.
		if ( $r['search_terms'] ) {
			$search_terms_like              = '%' . bp_esc_like( $r['search_terms'] ) . '%';
			$where_conditions['search_sql'] = $wpdb->prepare( 'm.title LIKE %s', $search_terms_like );

			/**
			 * Filters whether or not to include users for search parameters.
			 *
			 * @since BuddyBoss 1.3.6
			 *
			 * @param bool $value Whether or not to include user search. Default false.
			 */
			if ( apply_filters( 'bp_document_folder_get_include_user_search', false ) ) {
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
			case 'group_id':
			case 'attachment_id':
			case 'title':
				break;

			default:
				$r['order_by'] = 'date_created';
				break;
		}
		$order_by = 'm.' . $r['order_by'];

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

		if ( ! empty( $r['user_id'] ) ) {
			$where_conditions['user'] = "m.user_id = {$r['user_id']}";
		}

		$where_conditions['type'] = "m.type = 'document'";

		if ( ! empty( $r['group_id'] ) ) {
			$where_conditions['user'] = "m.group_id = {$r['group_id']}";
		}

		if ( ! empty( $r['privacy'] ) ) {
			$privacy                     = "'" . implode( "', '", $r['privacy'] ) . "'";
			$where_conditions['privacy'] = "m.privacy IN ({$privacy})";
		}

		/**
		 * Filters the MySQL WHERE conditions for the albums get method.
		 *
		 * @since BuddyBoss 1.3.6
		 *
		 * @param array  $where_conditions Current conditions for MySQL WHERE statement.
		 * @param array  $r                Parsed arguments passed into method.
		 * @param string $select_sql       Current SELECT MySQL statement at point of execution.
		 * @param string $from_sql         Current FROM MySQL statement at point of execution.
		 * @param string $join_sql         Current INNER JOIN MySQL statement at point of execution.
		 */
		$where_conditions = apply_filters( 'bp_document_folder_get_where_conditions', $where_conditions, $r, $select_sql, $from_sql, $join_sql );

		if ( empty( $where_conditions ) ) {
			$where_conditions['2'] = '2';
		}

		// Join the where conditions together.
		$where_sql = 'WHERE ' . join( ' AND ', $where_conditions );

		/**
		 * Filter the MySQL JOIN clause for the main media query.
		 *
		 * @since BuddyBoss 1.3.6
		 *
		 * @param string $join_sql   JOIN clause.
		 * @param array  $r          Method parameters.
		 * @param string $select_sql Current SELECT MySQL statement.
		 * @param string $from_sql   Current FROM MySQL statement.
		 * @param string $where_sql  Current WHERE MySQL statement.
		 */
		$join_sql = apply_filters( 'bp_document_folder_get_join_sql', $join_sql, $r, $select_sql, $from_sql, $where_sql );

		// Sanitize page and per_page parameters.
		$page     = absint( $r['page'] );
		$per_page = absint( $r['per_page'] );

		$retval = array(
			'folders'        => null,
			'total'          => null,
			'has_more_items' => null,
		);

		// Query first for album IDs.
		$folder_ids_sql = "{$select_sql} {$from_sql} {$join_sql} {$where_sql} ORDER BY {$order_by} {$sort}, m.id {$sort}";

		if ( ! empty( $per_page ) && ! empty( $page ) ) {
			// We query for $per_page + 1 items in order to
			// populate the has_more_items flag.
			$folder_ids_sql .= $wpdb->prepare( ' LIMIT %d, %d', absint( ( $page - 1 ) * $per_page ), $per_page + 1 );
		}

		/**
		 * Filters the paged media MySQL statement.
		 *
		 * @since BuddyBoss 1.3.6
		 *
		 * @param string $folder_ids_sql    MySQL statement used to query for Media IDs.
		 * @param array  $r                Array of arguments passed into method.
		 */
		$folder_ids_sql = apply_filters( 'bp_document_folder_paged_activities_sql', $folder_ids_sql, $r );

		$cache_group = 'bp_document_folder';

		$cached = bp_core_get_incremented_cache( $folder_ids_sql, $cache_group );
		if ( false === $cached ) {
			$folder_ids = $wpdb->get_col( $folder_ids_sql ); // db call ok; no-cache ok;
			bp_core_set_incremented_cache( $folder_ids_sql, $cache_group, $folder_ids );
		} else {
			$folder_ids = $cached;
		}

		$retval['has_more_items'] = ! empty( $per_page ) && count( $folder_ids ) > $per_page;

		// If we've fetched more than the $per_page value, we
		// can discard the extra now.
		if ( ! empty( $per_page ) && count( $folder_ids ) === $per_page + 1 ) {
			array_pop( $folder_ids );
		}

		if ( 'ids' === $r['fields'] ) {
			$folders = array_map( 'intval', $folder_ids );
		} else {
			$folders = self::get_folder_data( $folder_ids );
		}

		if ( 'ids' !== $r['fields'] ) {
			// Get the fullnames of users so we don't have to query in the loop.
			// $albums = self::append_user_fullnames( $albums );

			// Pre-fetch data associated with media users and other objects.
			$folders = self::prefetch_object_data( $folders );
		}

		$retval['folders'] = $folders;

		// If $max is set, only return up to the max results.
		if ( ! empty( $r['count_total'] ) ) {

			/**
			 * Filters the total document MySQL statement.
			 *
			 * @since BuddyBoss 1.3.6
			 *
			 * @param string $value     MySQL statement used to query for total documents.
			 * @param string $where_sql MySQL WHERE statement portion.
			 * @param string $sort      Sort direction for query.
			 */
			$total_folders_sql = apply_filters( 'bp_document_folder_total_documents_sql', "SELECT count(DISTINCT m.id) FROM {$bp->document->table_name_folders} m {$join_sql} {$where_sql}", $where_sql, $sort );
			$cached            = bp_core_get_incremented_cache( $total_folders_sql, $cache_group );
			if ( false === $cached ) {
				$total_folders = $wpdb->get_var( $total_folders_sql ); // db call ok; no-cache ok;
				bp_core_set_incremented_cache( $total_folders_sql, $cache_group, $total_folders );
			} else {
				$total_folders = $cached;
			}

			if ( ! empty( $r['max'] ) ) {
				if ( (int) $total_folders > (int) $r['max'] ) {
					$total_folders = $r['max'];
				}
			}

			$retval['total'] = $total_folders;
		}

		return $retval;
	}

	/**
	 * Convert document IDs to document objects, as expected in template loop.
	 *
	 * @since BuddyBoss 1.3.6
	 *
	 * @param array $folder_ids Array of document IDs.
	 * @return array
	 */
	public static function get_folder_data( $folder_ids = array() ) {
		global $wpdb;

		// Bail if no media ID's passed.
		if ( empty( $folder_ids ) ) {
			return array();
		}

		// Get BuddyPress.
		$bp = buddypress();

		$folders      = array();
		$uncached_ids = bp_get_non_cached_ids( $folder_ids, 'bp_document_folder' );

		// Prime caches as necessary.
		if ( ! empty( $uncached_ids ) ) {
			// Format the album ID's for use in the query below.
			$uncached_ids_sql = implode( ',', wp_parse_id_list( $uncached_ids ) );

			// Fetch data from album table, preserving order.
			$queried_adata = $wpdb->get_results( "SELECT * FROM {$bp->document->table_name_folders} WHERE id IN ({$uncached_ids_sql})" ); // db call ok; no-cache ok;

			// Put that data into the placeholders created earlier,
			// and add it to the cache.
			foreach ( (array) $queried_adata as $adata ) {
				wp_cache_set( $adata->id, $adata, 'bp_document_folder' );
			}
		}

		// Now fetch data from the cache.
		foreach ( $folder_ids as $folder_id ) {
			// Integer casting.
			$folder = wp_cache_get( $folder_id, 'bp_document_folder' );
			if ( ! empty( $folder ) ) {
				$folder->id           = (int) $folder->id;
				$folder->user_id      = (int) $folder->user_id;
				$folder->group_id     = (int) $folder->group_id;
				$folder->folder_id    = (int) $folder->id;
			}

			$folder->document = bp_document_get(
				array(
					'folder_id'   => $folder->id,
					'count_total' => true,
				)
			);

			$group_name = '';
			$visibility = '';
			if ( $folder->group_id > 0 ) {
				$group      = groups_get_group( $folder->group_id );
				$group_name = bp_get_group_name( $group );
				$status     = bp_get_group_status( $group );
				if ( 'hidden' === $status ) {
					$visibility = esc_html__( 'Group Members', 'buddyboss' );
				} elseif ( 'private' === $status ) {
					$visibility = esc_html__( 'All Members', 'buddyboss' );
				} else {
					$visibility = ucfirst( $status );
				}
			} else {
				$document_privacy = bp_document_get_visibility_levels();
				$visibility       = $document_privacy[ $folder->privacy ];
			}

			$folder->group_name = $group_name;
			$folder->visibility = $visibility;

			$folders[] = $folder;
		}

		// Then fetch user data.
		$user_query = new BP_User_Query(
			array(
				'user_ids'        => wp_list_pluck( $folders, 'user_id' ),
				'populate_extras' => false,
			)
		);

		// Associated located user data with albums.
		foreach ( $folders as $a_index => $a_item ) {
			$a_user_id = intval( $a_item->user_id );
			$a_user    = isset( $user_query->results[ $a_user_id ] ) ? $user_query->results[ $a_user_id ] : '';

			if ( ! empty( $a_user ) ) {
				$folders[ $a_index ]->user_email    = $a_user->user_email;
				$folders[ $a_index ]->user_nicename = $a_user->user_nicename;
				$folders[ $a_index ]->user_login    = $a_user->user_login;
				$folders[ $a_index ]->display_name  = $a_user->display_name;
			}
		}

		return $folders;
	}

	/**
	 * Get whether an folder exists for a given id.
	 *
	 * @since BuddyBoss 1.3.6
	 *
	 * @param string $id       ID to check.
	 * @return int|bool Album ID if found; false if not.
	 */
	public static function folder_exists( $id ) {
		if ( empty( $id ) ) {
			return false;
		}

		$args = array(
			'in' => $id,
		);

		$folders = self::get( $args );

		$folder_id = false;
		if ( ! empty( $folders['folders'] ) ) {
			$folder_id = current( $folders['folders'] )->id;
		}

		return $folder_id;
	}

	/**
	 * Append xProfile fullnames to an document array.
	 *
	 * @since BuddyBoss 1.3.6
	 *
	 * @param array $folders Folders array.
	 * @return array
	 */
	protected static function append_user_fullnames( $folders ) {

		if ( bp_is_active( 'xprofile' ) && ! empty( $folders ) ) {
			$album_user_ids = wp_list_pluck( $folders, 'user_id' );

			if ( ! empty( $album_user_ids ) ) {
				$fullnames = bp_core_get_user_displaynames( $album_user_ids );
				if ( ! empty( $fullnames ) ) {
					foreach ( (array) $folders as $i => $folder ) {
						if ( ! empty( $fullnames[ $folder->user_id ] ) ) {
							$folders[ $i ]->user_fullname = $fullnames[ $folder->user_id ];
						}
					}
				}
			}
		}

		return $folders;
	}

	/**
	 * Pre-fetch data for objects associated with folders.
	 *
	 * folders are associated with users, and often with other
	 * BuddyPress data objects. Here, we pre-fetch data about these
	 * associated objects, so that inline lookups - done primarily when
	 * building action strings - do not result in excess database queries.
	 *
	 * @since BuddyBoss 1.3.6
	 *
	 * @param array $folders Array of document folders.
	 * @return array $folders Array of document folders.
	 */
	protected static function prefetch_object_data( $folders ) {

		/**
		 * Filters inside prefetch_object_data method to aid in pre-fetching object data associated with folder.
		 *
		 * @since BuddyBoss 1.3.6
		 *
		 * @param array $documents Array of document folders.
		 */
		return apply_filters( 'bp_document_folder_prefetch_object_data', $folders );
	}

	/**
	 * Count total folder for the given group
	 *
	 * @since BuddyBoss 1.3.6
	 *
	 * @param int $group_id
	 *
	 * @return array|bool|int
	 */
	public static function total_group_folder_count( $group_id = 0 ) {
		global $bp, $wpdb;

		$total_count = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$bp->document->table_name_folders} WHERE group_id = {$group_id}" ); // db call ok; no-cache ok;

		return $total_count;
	}

	/**
	 * Delete folders from the database.
	 *
	 * To delete a specific folder, pass an 'id' parameter.
	 * Otherwise use the filters.
	 *
	 * @since BuddyBoss 1.3.6
	 *
	 * @param array $args {
	 * @int    $id                Optional. The ID of a specific item to delete.
	 * @int    $user_id           Optional. The user ID to filter by.
	 * @int    $group_id           Optional. The group ID to filter by.
	 * @string    $title          Optional. The title to filter by.
	 * @string $date_created      Optional. The date to filter by.
	 * }
	 *
	 * @return array|bool An array of deleted media IDs on success, false on failure.
	 */
	public static function delete( $args = array() ) {
		global $wpdb;

		$bp = buddypress();
		$r  = wp_parse_args(
			$args,
			array(
				'id'           => false,
				'user_id'      => false,
				'group_id'     => false,
				'date_created' => false,
			)
		);

		// Setup empty array from where query arguments.
		$where_args = array();

		// ID.
		if ( ! empty( $r['id'] ) ) {
			$where_args[] = $wpdb->prepare( 'id = %d', $r['id'] );
		}

		// User ID.
		if ( ! empty( $r['user_id'] ) ) {
			$where_args[] = $wpdb->prepare( 'user_id = %d', $r['user_id'] );
		}

		// Group ID.
		if ( ! empty( $r['group_id'] ) ) {
			$where_args[] = $wpdb->prepare( 'group_id = %d', $r['group_id'] );
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

		// Fetch all document folders being deleted so we can perform more actions.
		$folders = $wpdb->get_results( "SELECT * FROM {$bp->document->table_name_folders} {$where_sql}" ); // db call ok; no-cache ok;

		if ( ! empty( $r['id'] ) && empty( $r['date_created'] ) && empty( $r['group_id'] ) && empty( $r['user_id'] ) ) {
			$recursive_folders = $wpdb->get_results( "SELECT * FROM {$bp->document->table_name_folders} WHERE FIND_IN_SET(ID,(SELECT GROUP_CONCAT(lv SEPARATOR ',') FROM ( SELECT @pv:=(SELECT GROUP_CONCAT(id SEPARATOR ',') FROM {$bp->document->table_name_folders} WHERE parent IN (@pv)) AS lv FROM {$bp->document->table_name_folders} JOIN (SELECT @pv:= {$r['id']})tmp WHERE parent IN (@pv)) a))" ); // db call ok; no-cache ok;
			$folders           = array_merge( $folders, $recursive_folders );
		}

		/**
		 * Action to allow intercepting folders to be deleted.
		 *
		 * @since BuddyBoss 1.3.6
		 *
		 * @param array $albums Array of document folders.
		 * @param array $r          Array of parsed arguments.
		 */
		do_action_ref_array( 'bp_document_folder_before_delete', array( $folders, $r ) );

		if ( ! empty( $r['id'] ) && empty( $r['date_created'] ) && empty( $r['group_id'] ) && empty( $r['user_id'] ) ) {
			$recursive_folders = $wpdb->get_results( "SELECT * FROM {$bp->document->table_name_folders} WHERE FIND_IN_SET(ID,(SELECT GROUP_CONCAT(lv SEPARATOR ',') FROM ( SELECT @pv:=(SELECT GROUP_CONCAT(id SEPARATOR ',') FROM {$bp->document->table_name_folders} WHERE parent IN (@pv)) AS lv FROM {$bp->document->table_name_folders} JOIN (SELECT @pv:= {$r['id']})tmp WHERE parent IN (@pv)) a))" ); // db call ok; no-cache ok;
			$folders           = array_merge( $folders, $recursive_folders );

			// Pluck the media albums IDs out of the $albums array.
			$foldr_ids = wp_parse_id_list( wp_list_pluck( $folders, 'id' ) );

			// delete the media associated with album
			if ( ! empty( $foldr_ids ) ) {
				foreach ( $foldr_ids as $folder_id ) {
					// Attempt to delete media albums from the database.
					$deleted = $wpdb->query( "DELETE FROM {$bp->document->table_name_folders} where id = {$folder_id}" ); // db call ok; no-cache ok;
				}
			}
		} else {
			// Attempt to delete media albums from the database.
			$deleted = $wpdb->query( "DELETE FROM {$bp->document->table_name_folders} {$where_sql}" ); // db call ok; no-cache ok;
		}

		// Bail if nothing was deleted.
		if ( empty( $deleted ) ) {
			return false;
		}

		/**
		 * Action to allow intercepting albums just deleted.
		 *
		 * @since BuddyBoss 1.3.6
		 *
		 * @param array $folders     Array of document folders.
		 * @param array $r          Array of parsed arguments.
		 */
		do_action_ref_array( 'bp_document_album_after_delete', array( $folders, $r ) );

		// Pluck the media albums IDs out of the $albums array.
		$foldr_ids = wp_parse_id_list( wp_list_pluck( $folders, 'id' ) );

		// delete the media associated with album
		if ( ! empty( $foldr_ids ) ) {
			foreach ( $foldr_ids as $folder_id ) {
				bp_document_delete( array( 'folder_id' => $folder_id ) );
			}
		}

		return $foldr_ids;
	}

}
