<?php
/**
 * BuddyBoss Document Classes
 *
 * @package BuddyBoss\Document
 * @since   BuddyBoss 1.4.0
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Database interaction class for the BuddyBoss document folder component.
 * Instance methods are available for creating/editing an document folders,
 * static methods for querying document folder.
 *
 * @since BuddyBoss 1.4.0
 */
class BP_Document_Folder {

	/** Properties ************************************************************/

	/**
	 * ID of the folder.
	 *
	 * @since BuddyBoss 1.4.0
	 * @var int
	 */
	var $id;

	/**
	 * Blog ID of the folder item.
	 *
	 * @since BuddyBoss 1.4.0
	 * @var int
	 */
	var $blog_id;

	/**
	 * User ID of the folder.
	 *
	 * @since BuddyBoss 1.4.0
	 * @var int
	 */
	var $user_id;

	/**
	 * Group ID of the folder.
	 *
	 * @since BuddyBoss 1.4.0
	 * @var int
	 */
	var $group_id;

	/**
	 * Parent ID of the folder.
	 *
	 * @since BuddyBoss 1.4.0
	 * @var int
	 */
	public $parent;

	/**
	 * Title of the folder.
	 *
	 * @since BuddyBoss 1.4.0
	 * @var string
	 */
	var $title;

	/**
	 * Privacy of the folder.
	 *
	 * @since BuddyBoss 1.4.0
	 * @var string
	 */
	var $privacy;

	/**
	 * Upload date of the folder.
	 *
	 * @since BuddyBoss 1.4.0
	 * @var string
	 */
	var $date_created;

	/**
	 * Update date of the folder.
	 *
	 * @since BuddyBoss 1.4.0
	 * @var string
	 */
	var $date_modified;

	/**
	 * Error holder.
	 *
	 * @since BuddyBoss 1.4.0
	 * @var WP_Error
	 */
	public $errors;

	/**
	 * Error type to return. Either 'bool' or 'wp_error'.
	 *
	 * @since BuddyBoss 1.4.0
	 * @var string
	 */
	public $error_type = 'bool';

	/**
	 * Constructor method.
	 *
	 * @param int|bool $id Optional. The ID of a specific document folder.
	 *
	 * @since BuddyBoss 1.4.0
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
	 * @since BuddyBoss 1.4.0
	 */
	public function populate() {

		global $wpdb;

		$row = wp_cache_get( $this->id, 'bp_document_folder' );

		if ( false === $row ) {
			$bp  = buddypress();
			$row = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$bp->document->table_name_folder} WHERE id = %d", $this->id ) ); // db call ok; no-cache ok;

			wp_cache_set( $this->id, $row, 'bp_document_folder' );
		}

		if ( empty( $row ) ) {
			$this->id = 0;

			return;
		}

		$this->id            = (int) $row->id;
		$this->blog_id       = (int) $row->blog_id;
		$this->user_id       = (int) $row->user_id;
		$this->group_id      = (int) $row->group_id;
		$this->parent        = $row->parent;
		$this->title         = $row->title;
		$this->privacy       = $row->privacy;
		$this->date_created  = $row->date_created;
		$this->date_modified = $row->date_modified;

	}

	/**
	 * Get whether an folder exists for a given id.
	 *
	 * @param string $id ID to check.
	 *
	 * @return int|bool Folder ID if found; false if not.
	 * @since BuddyBoss 1.4.0
	 */
	public static function folder_exists( $id ) {
		if ( empty( $id ) ) {
			return false;
		}

		$args = array(
			'in'     => $id,
			'fields'  => 'ids'
		);

		$folders = self::get( $args );

		$folder_id = false;
		if ( ! empty( $folders['folders'] ) ) {
			$folder_id = current( $folders['folders'] );
		}

		return $folder_id;
	}

	/** Static Methods ***************************************************/

	/**
	 * Get folders, as specified by parameters.
	 *
	 * @param array $args         {
	 *                            An array of arguments. All items are optional.
	 *
	 * @type int         $page         Which page of results to fetch. Using page=1 without per_page will result
	 *                                           in no pagination. Default: 1.
	 * @type int|bool    $per_page     Number of results per page. Default: 20.
	 * @type int|bool    $max          Maximum number of results to return. Default: false (unlimited).
	 * @type string      $fields       Document fields to return. Pass 'ids' to get only the document IDs.
	 *                                           'all' returns full document objects.
	 * @type string      $sort         ASC or DESC. Default: 'DESC'.
	 * @type string      $order_by     Column to order results by.
	 * @type array       $exclude      Array of document IDs to exclude. Default: false.
	 * @type string      $search_terms Limit results by a search term. Default: false.
	 * @type string|bool $count_total  If true, an additional DB query is run to count the total documents
	 *                                           for the query. Default: false.
	 * }
	 * @return array The array returned has two keys:
	 *               - 'total' is the count of located documents
	 *               - 'folders' is an array of the located documents
	 * @since BuddyBoss 1.4.0
	 */
	public static function get( $args = array() ) {

		global $wpdb;

		$bp = buddypress();
		$r  = bp_parse_args(
			$args,
			array(
				'page'         => 1,               // The current page.
				'per_page'     => 20,              // folders per page.
				'max'          => false,           // Max number of items to return.
				'fields'       => 'all',           // Fields to include.
				'sort'         => 'DESC',          // ASC or DESC.
				'order_by'     => 'date_created',  // Column to order by.
				'parent'       => null,           // Parent Folder ID.
				'exclude'      => false,           // Array of ids to exclude.
				'search_terms' => false,           // Terms to search by.
				'user_id'      => false,           // user id.
				'group_id'     => false,           // group id.
				'privacy'      => false,           // public, loggedin, onlyme, friends, grouponly.
				'count_total'  => false,           // Whether or not to use count_total.
				'in'           => false,           // Array of ids to limit query by (IN).
				'meta_query'   => false,           // Filter by foldermeta.
			)
		);

		// Select conditions.
		$select_sql = 'SELECT DISTINCT f.id';

		$from_sql = " FROM {$bp->document->table_name_folder} f";

		$join_sql = '';

		// Where conditions.
		$where_conditions = array();

		// Searching.
		if ( $r['search_terms'] ) {
			$search_terms_like              = '%' . bp_esc_like( $r['search_terms'] ) . '%';
			$where_conditions['search_sql'] = $wpdb->prepare( 'f.title LIKE %s', $search_terms_like );

			/**
			 * Filters whether or not to include users for search parameters.
			 *
			 * @param bool $value Whether or not to include user search. Default false.
			 *
			 * @since BuddyBoss 1.4.0
			 */
			if ( apply_filters( 'bp_document_folder_get_include_user_search', false ) ) {
				$user_search = get_user_by( 'slug', $r['search_terms'] );
				if ( false !== $user_search ) {
					$user_id                         = $user_search->ID;
					$where_conditions['search_sql'] .= $wpdb->prepare( ' OR f.user_id = %d', $user_id );
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
			case 'title':
			case 'privacy':
				break;

			default:
				$r['order_by'] = 'date_created';
				break;
		}
		$order_by = 'f.' . $r['order_by'];

		// Exclude specified items.
		if ( ! empty( $r['exclude'] ) ) {
			$exclude                     = implode( ',', wp_parse_id_list( $r['exclude'] ) );
			$where_conditions['exclude'] = "f.id NOT IN ({$exclude})";
		}

		// The specific ids to which you want to limit the query.
		if ( ! empty( $r['in'] ) ) {
			$in                     = implode( ',', wp_parse_id_list( $r['in'] ) );
			$where_conditions['in'] = "f.id IN ({$in})";
		}

		if ( ! empty( $r['user_id'] ) ) {
			$where_conditions['user'] = "f.user_id = {$r['user_id']}";
		}

		if ( null !== $r['parent'] ) {
			$where_conditions['parent'] = "f.parent = {$r['parent']}";
		}

		if ( ! empty( $r['group_id'] ) ) {
			$where_conditions['group'] = "f.group_id = {$r['group_id']}";
		}

		if ( ! empty( $r['privacy'] ) ) {
			$privacy                     = "'" . implode( "', '", $r['privacy'] ) . "'";
			$where_conditions['privacy'] = "f.privacy IN ({$privacy})";
		}

		// Process meta_query into SQL.
		$meta_query_sql = self::get_meta_query_sql( $r['meta_query'] );

		if ( ! empty( $meta_query_sql['join'] ) ) {
			$join_sql .= $meta_query_sql['join'];
		}

		/**
		 * Filters the MySQL WHERE conditions for the folders get method.
		 *
		 * @param array  $where_conditions Current conditions for MySQL WHERE statement.
		 * @param array  $r                Parsed arguments passed into method.
		 * @param string $select_sql       Current SELECT MySQL statement at point of execution.
		 * @param string $from_sql         Current FROM MySQL statement at point of execution.
		 * @param string $join_sql         Current INNER JOIN MySQL statement at point of execution.
		 *
		 * @since BuddyBoss 1.4.0
		 */
		$where_conditions = apply_filters( 'bp_document_folder_get_where_conditions', $where_conditions, $r, $select_sql, $from_sql, $join_sql );

		if ( empty( $where_conditions ) ) {
			$where_conditions['2'] = '2';
		}

		// Join the where conditions together.
		$where_sql = 'WHERE ' . join( ' AND ', $where_conditions );

		/**
		 * Filter the MySQL JOIN clause for the main document query.
		 *
		 * @param string $join_sql   JOIN clause.
		 * @param array  $r          Method parameters.
		 * @param string $select_sql Current SELECT MySQL statement.
		 * @param string $from_sql   Current FROM MySQL statement.
		 * @param string $where_sql  Current WHERE MySQL statement.
		 *
		 * @since BuddyBoss 1.4.0
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

		// Query first for folder IDs.
		$folder_ids_sql = "{$select_sql} {$from_sql} {$join_sql} {$where_sql} ORDER BY {$order_by} {$sort}, f.id {$sort}";

		if ( ! empty( $per_page ) && ! empty( $page ) ) {
			// We query for $per_page + 1 items in order to
			// populate the has_more_items flag.
			$folder_ids_sql .= $wpdb->prepare( ' LIMIT %d, %d', absint( ( $page - 1 ) * $per_page ), $per_page + 1 );
		}

		/**
		 * Filters the paged document MySQL statement.
		 *
		 * @param string $folder_ids_sql MySQL statement used to query for Document IDs.
		 * @param array  $r              Array of arguments passed into method.
		 *
		 * @since BuddyBoss 1.4.0
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

			// Pre-fetch data associated with document users and other objects.
			$folders = self::prefetch_object_data( $folders );
		}

		$retval['folders'] = $folders;

		// If $max is set, only return up to the max results.
		if ( ! empty( $r['count_total'] ) ) {

			/**
			 * Filters the total document MySQL statement.
			 *
			 * @param string $value     MySQL statement used to query for total documents.
			 * @param string $where_sql MySQL WHERE statement portion.
			 * @param string $sort      Sort direction for query.
			 *
			 * @since BuddyBoss 1.4.0
			 */
			$total_folders_sql = apply_filters( 'bp_document_folder_total_documents_sql', "SELECT count(DISTINCT f.id) FROM {$bp->document->table_name_folder} f {$join_sql} {$where_sql}", $where_sql, $sort );
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
	 * @param array $folder_ids Array of document IDs.
	 *
	 * @return array
	 * @since BuddyBoss 1.4.0
	 */
	public static function get_folder_data( $folder_ids = array() ) {
		global $wpdb;

		// Bail if no document ID's passed.
		if ( empty( $folder_ids ) ) {
			return array();
		}

		// Get BuddyPress.
		$bp = buddypress();

		$folders      = array();
		$uncached_ids = bp_get_non_cached_ids( $folder_ids, 'bp_document_folder' );

		// Prime caches as necessary.
		if ( ! empty( $uncached_ids ) ) {
			// Format the folder ID's for use in the query below.
			$uncached_ids_sql = implode( ',', wp_parse_id_list( $uncached_ids ) );

			// Fetch data from folder table, preserving order.
			$queried_adata = $wpdb->get_results( "SELECT * FROM {$bp->document->table_name_folder} WHERE id IN ({$uncached_ids_sql})" ); // db call ok; no-cache ok;

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
				$folder->id        = (int) $folder->id;
				$folder->user_id   = (int) $folder->user_id;
				$folder->group_id  = (int) $folder->group_id;
				$folder->folder_id = (int) $folder->id;
			}

			$folder->document = bp_document_get(
				array(
					'folder_id'   => $folder->id,
					'count_total' => true,
				)
			);

			$group_name = '';
			$visibility = '';
			if ( bp_is_active( 'groups') && $folder->group_id > 0 ) {
				$group      = groups_get_group( $folder->group_id );
				$group_name = bp_get_group_name( $group );
				$status     = bp_get_group_status( $group );
				if ( 'hidden' === $status || 'private' === $status ) {
					$visibility = esc_html__( 'Group Members', 'buddyboss' );
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

		return $folders;
	}

	/**
	 * Pre-fetch data for objects associated with folders.
	 * folders are associated with users, and often with other
	 * BuddyPress data objects. Here, we pre-fetch data about these
	 * associated objects, so that inline lookups - done primarily when
	 * building action strings - do not result in excess database queries.
	 *
	 * @param array $folders Array of document folders.
	 *
	 * @return array $folders Array of document folders.
	 * @since BuddyBoss 1.4.0
	 */
	protected static function prefetch_object_data( $folders ) {

		/**
		 * Filters inside prefetch_object_data method to aid in pre-fetching object data associated with folder.
		 *
		 * @param array $documents Array of document folders.
		 *
		 * @since BuddyBoss 1.4.0
		 */
		return apply_filters( 'bp_document_folder_prefetch_object_data', $folders );
	}

	/**
	 * Count total folder for the given group
	 *
	 * @param int $group_id
	 *
	 * @return array|bool|int
	 * @since BuddyBoss 1.4.0
	 */
	public static function total_group_folder_count( $group_id = 0 ) {
		global $bp, $wpdb;

		$total_count = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$bp->document->table_name_folder} WHERE group_id = {$group_id}" ); // db call ok; no-cache ok;

		return $total_count;
	}

	/**
	 * Delete folders from the database.
	 * To delete a specific folder, pass an 'id' parameter.
	 * Otherwise use the filters.
	 *
	 * @param array $args {
	 * @int    $id                Optional. The ID of a specific item to delete.
	 * @int    $user_id           Optional. The user ID to filter by.
	 * @int    $group_id           Optional. The group ID to filter by.
	 * @string    $title          Optional. The title to filter by.
	 * @string $date_created      Optional. The date to filter by.
	 *                    }
	 *
	 * @return array|bool An array of deleted document IDs on success, false on failure.
	 * @since BuddyBoss 1.4.0
	 */
	public static function delete( $args = array() ) {
		global $wpdb;

		$bp = buddypress();
		$r  = bp_parse_args(
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
		$folders = $wpdb->get_results( "SELECT * FROM {$bp->document->table_name_folder} {$where_sql}" ); // db call ok; no-cache ok;

		if ( ! empty( $r['id'] ) && empty( $r['date_created'] ) && empty( $r['group_id'] ) && empty( $r['user_id'] ) ) {
			$recursive_folders = $wpdb->get_results( "SELECT * FROM {$bp->document->table_name_folder} WHERE FIND_IN_SET(ID,(SELECT GROUP_CONCAT(lv SEPARATOR ',') FROM ( SELECT @pv:=(SELECT GROUP_CONCAT(id SEPARATOR ',') FROM {$bp->document->table_name_folder} WHERE parent IN (@pv)) AS lv FROM {$bp->document->table_name_folder} JOIN (SELECT @pv:= {$r['id']})tmp WHERE parent IN (@pv)) a))" ); // db call ok; no-cache ok;
			$folders           = array_merge( $folders, $recursive_folders );
		}

		/**
		 * Action to allow intercepting folders to be deleted.
		 *
		 * @param array $folders Array of document folders.
		 * @param array $r       Array of parsed arguments.
		 *
		 * @since BuddyBoss 1.4.0
		 */
		do_action_ref_array( 'bp_document_folder_before_delete', array( $folders, $r ) );

		if ( ! empty( $r['id'] ) && empty( $r['date_created'] ) && empty( $r['group_id'] ) && empty( $r['user_id'] ) ) {
			$recursive_folders = $wpdb->get_results( "SELECT * FROM {$bp->document->table_name_folder} WHERE FIND_IN_SET(ID,(SELECT GROUP_CONCAT(lv SEPARATOR ',') FROM ( SELECT @pv:=(SELECT GROUP_CONCAT(id SEPARATOR ',') FROM {$bp->document->table_name_folder} WHERE parent IN (@pv)) AS lv FROM {$bp->document->table_name_folder} JOIN (SELECT @pv:= {$r['id']})tmp WHERE parent IN (@pv)) a))" ); // db call ok; no-cache ok;
			$folders           = array_merge( $folders, $recursive_folders );

			// Pluck the document folders IDs out of the $folders array.
			$foldr_ids = wp_parse_id_list( wp_list_pluck( $folders, 'id' ) );

			// delete the document associated with folder.
			if ( ! empty( $foldr_ids ) ) {
				foreach ( $foldr_ids as $folder_id ) {
					// Attempt to delete document folders from the database.
					$deleted = $wpdb->query( "DELETE FROM {$bp->document->table_name_folder} where id = {$folder_id}" ); // db call ok; no-cache ok;
				}
			}
		} else {
			// Attempt to delete document folders from the database.
			$deleted = $wpdb->query( "DELETE FROM {$bp->document->table_name_folder} {$where_sql}" ); // db call ok; no-cache ok;
		}

		// Bail if nothing was deleted.
		if ( empty( $deleted ) ) {
			return false;
		}

		/**
		 * Action to allow intercepting folders just deleted.
		 *
		 * @param array $folders Array of document folders.
		 * @param array $r       Array of parsed arguments.
		 *
		 * @since BuddyBoss 1.4.0
		 */
		do_action_ref_array( 'bp_document_folder_after_delete', array( $folders, $r ) );

		// Pluck the document folders IDs out of the $folders array.
		$foldr_ids = wp_parse_id_list( wp_list_pluck( $folders, 'id' ) );

		// delete the document associated with folder.
		if ( ! empty( $foldr_ids ) ) {
			foreach ( $foldr_ids as $folder_id ) {
				bp_document_delete( array( 'folder_id' => $folder_id ) );
			}
		}

		if ( ! empty( $foldr_ids ) ) {
			// Delete all folder meta entries for folder items.
			self::delete_document_folder_meta_entries( wp_list_pluck( $folders, 'id' ) );
		}

		// delete all child folders.
		if ( ! empty( $foldr_ids ) ) {

			foreach ( $foldr_ids as $folder_id ) {

				// Get child folders.
				$get_children = bp_document_get_folder_children( $folder_id );
				if ( $get_children ) {
					foreach ( $get_children as $child ) {
						// Delete all documents of folder.
						bp_document_delete( array( 'folder_id' => $child ) );

						// Delete Child folder.
						bp_folder_delete( array( 'id' => $child ) );
					}
				}
			}
		}

		return $foldr_ids;
	}

	/**
	 * Delete the meta entries associated with a set of folder items.
	 *
	 * @since BuddyBoss 1.4.0
	 *
	 * @param array $folder_ids Folder IDs whose meta should be deleted.
	 * @return bool True on success.
	 */
	public static function delete_document_folder_meta_entries( $folder_ids = array() ) {
		$folder_ids = wp_parse_id_list( $folder_ids );

		foreach ( $folder_ids as $folder_id ) {
			bp_document_folder_delete_meta( $folder_id );
		}

		return true;
	}

	/**
	 * Append xProfile fullnames to an document array.
	 *
	 * @param array $folders Folders array.
	 *
	 * @return array
	 * @since BuddyBoss 1.4.0
	 */
	protected static function append_user_fullnames( $folders ) {

		if ( bp_is_active( 'xprofile' ) && ! empty( $folders ) ) {
			$folder_user_ids = wp_list_pluck( $folders, 'user_id' );

			if ( ! empty( $folder_user_ids ) ) {
				$fullnames = bp_core_get_user_displaynames( $folder_user_ids );
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
	 * Save the document folder to the database.
	 *
	 * @return WP_Error|bool True on success.
	 * @since BuddyBoss 1.4.0
	 */
	public function save() {

		global $wpdb;

		$bp = buddypress();

		$this->id            = apply_filters_ref_array( 'bp_document_id_before_save', array( $this->id, &$this ) );
		$this->user_id       = apply_filters_ref_array( 'bp_document_user_id_before_save', array( $this->user_id, &$this, ) );
		$this->blog_id       = apply_filters_ref_array( 'bp_document_blog_id_before_save', array( $this->blog_id, &$this, ) );
		$this->group_id      = apply_filters_ref_array( 'bp_document_group_id_before_save', array( $this->group_id, &$this, ) );
		$this->title         = apply_filters_ref_array( 'bp_document_title_before_save', array( $this->title, &$this, ) );
		$this->privacy       = apply_filters_ref_array( 'bp_document_privacy_before_save', array( $this->privacy, &$this, ) );
		$this->date_created  = apply_filters_ref_array( 'bp_document_date_created_before_save', array( $this->date_created, &$this, ) );
		$this->date_modified = apply_filters_ref_array( 'bp_document_date_modified_before_save', array( $this->date_modified, &$this, ) );
		$this->parent        = apply_filters_ref_array( 'bp_document_parent_before_save', array( $this->parent, &$this, ) );

		/**
		 * Fires before the current folder gets saved.
		 * Please use this hook to filter the properties above. Each part will be passed in.
		 *
		 * @param BP_Document $this Current instance of the folder being saved. Passed by reference.
		 *
		 * @since BuddyBoss 1.4.0
		 */
		do_action_ref_array( 'bp_document_folder_before_save', array( &$this ) );

		if ( 'wp_error' === $this->error_type && $this->errors->get_error_code() ) {
			return $this->errors;
		}

		// If we have an existing ID, update the folder, otherwise insert it.
		if ( ! empty( $this->id ) ) {
			$q = $wpdb->prepare( "UPDATE {$bp->document->table_name_folder} SET blog_id = %d, user_id = %d, group_id = %d, title = %s, privacy = %s, parent = %d, date_modified = %s WHERE id = %d", $this->blog_id, $this->user_id, $this->group_id, $this->title, $this->privacy, $this->parent, $this->date_modified, $this->id );
		} else {
			$q = $wpdb->prepare( "INSERT INTO {$bp->document->table_name_folder} ( blog_id, user_id, group_id, title, privacy, date_created, date_modified, parent ) VALUES ( %d, %d, %d, %s, %s, %s, %s, %d )", $this->blog_id, $this->user_id, $this->group_id, $this->title, $this->privacy, $this->date_created, $this->date_modified, $this->parent );
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
		 * Fires after an folder has been saved to the database.
		 *
		 * @param BP_Document $this Current instance of folder being saved. Passed by reference.
		 *
		 * @since BuddyBoss 1.4.0
		 */
		do_action_ref_array( 'bp_document_folder_after_save', array( &$this ) );

		return true;
	}

	/**
	 * Get the SQL for the 'meta_query' param in BP_Document::get().
	 *
	 * We use WP_Meta_Query to do the heavy lifting of parsing the
	 * meta_query array and creating the necessary SQL clauses. However,
	 * since BP_Document::get() builds its SQL differently than
	 * WP_Query, we have to alter the return value (stripping the leading
	 * AND keyword from the 'where' clause).
	 *
	 * @since BuddyPress 1.8.0
	 *
	 * @param array $meta_query An array of meta_query filters. See the
	 *                          documentation for WP_Meta_Query for details.
	 * @return array $sql_array 'join' and 'where' clauses.
	 */
	public static function get_meta_query_sql( $meta_query = array() ) {
		global $wpdb;

		$sql_array = array(
			'join'  => '',
			'where' => '',
		);

		if ( ! empty( $meta_query ) ) {
			$folder_meta_query = new WP_Meta_Query( $meta_query );

			// WP_Meta_Query expects the table name at
			// $wpdb->document_folder_meta.
			$wpdb->documentfoldermeta = buddypress()->document->table_name_folder_meta;

			$meta_sql = $folder_meta_query->get_sql( 'document_folder', 'f', 'id' );

			// Strip the leading AND - BP handles it in get().
			$sql_array['where'] = preg_replace( '/^\sAND/', '', $meta_sql['where'] );
			$sql_array['join']  = $meta_sql['join'];
		}

		return $sql_array;
	}

}
