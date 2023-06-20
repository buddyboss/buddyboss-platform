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
 * Database interaction class for the BuddyBoss document component.
 * Instance methods are available for creating/editing an document,
 * static methods for querying document.
 *
 * @since BuddyBoss 1.4.0
 */
class BP_Document {

	/** Properties ************************************************************/

	/**
	 * ID of the document item.
	 *
	 * @since BuddyBoss 1.4.0
	 * @var int
	 */
	var $id;

	/**
	 * Blog ID of the document item.
	 *
	 * @since BuddyBoss 1.4.0
	 * @var int
	 */
	var $blog_id;

	/**
	 * Attachment ID of the document item.
	 *
	 * @since BuddyBoss 1.4.0
	 * @var int
	 */
	var $attachment_id;

	/**
	 * User ID of the document item.
	 *
	 * @since BuddyBoss 1.4.0
	 * @var int
	 */
	var $user_id;

	/**
	 * Title of the document item.
	 *
	 * @since BuddyBoss 1.4.0
	 * @var string
	 */
	var $title;

	/**
	 * Folder ID of the document item.
	 *
	 * @since BuddyBoss 1.4.0
	 * @var int
	 */
	var $folder_id;

	/**
	 * Group ID of the document item.
	 *
	 * @since BuddyBoss 1.4.0
	 * @var int
	 */
	var $group_id;

	/**
	 * Activity ID of the document item.
	 *
	 * @since BuddyBoss 1.4.0
	 * @var int
	 */
	var $activity_id;

	/**
	 * Message ID of the document item.
	 *
	 * @since BuddyBoss 2.3.60
	 * @var int
	 */
	var $message_id;

	/**
	 * Privacy of the document item.
	 *
	 * @since BuddyBoss 1.4.0
	 * @var string
	 */
	var $privacy;

	/**
	 * Menu order of the document item.
	 *
	 * @since BuddyBoss 1.4.0
	 * @var int
	 */
	var $menu_order;

	/**
	 * Upload date of the document item.
	 *
	 * @since BuddyBoss 1.4.0
	 * @var string
	 */
	var $date_created;

	/**
	 * Update date of the document item.
	 *
	 * @since BuddyBoss 1.4.0
	 * @var string
	 */
	var $date_modified;

	/**
	 * Extension of the document item.
	 *
	 * @since BuddyBoss 1.7.0
	 * @var string
	 */
	var $extension;

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
	 * @param int|bool $id Optional. The ID of a specific activity item.
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
	 * Populate the object with data about the specific document item.
	 *
	 * @since BuddyBoss 1.4.0
	 */
	public function populate() {

		global $wpdb;

		$row = wp_cache_get( $this->id, 'bp_document' );

		if ( false === $row ) {
			$bp  = buddypress();
			$row = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$bp->document->table_name} WHERE id = %d", $this->id ) ); // db call ok; no-cache ok;

			wp_cache_set( $this->id, $row, 'bp_document' );
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
		$this->folder_id     = (int) $row->folder_id;
		$this->group_id      = (int) $row->group_id;
		$this->activity_id   = (int) $row->activity_id;
		$this->message_id    = (int) $row->message_id;
		$this->privacy       = $row->privacy;
		$this->menu_order    = (int) $row->menu_order;
		$this->date_created  = $row->date_created;
		$this->date_modified = $row->date_modified;
		$this->extension     = bp_document_extension( $this->attachment_id );

	}

	/**
	 * Get document items, as specified by parameters.
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
	 * @type string|bool $count_total  If true, an additional DB query is run to count the total document items
	 *                                           for the query. Default: false.
	 * }
	 * @return array The array returned has two keys:
	 *               - 'total' is the count of located documents
	 *               - 'documents' is an array of the located documents
	 * @since BuddyBoss 1.4.0
	 */
	public static function get( $args = array() ) {

		global $wpdb;

		$bp = buddypress();
		$r  = bp_parse_args(
			$args,
			array(
				'scope'          => '',              // Scope - Groups, friends etc.
				'page'           => 1,               // The current page.
				'per_page'       => 20,              // Document items per page.
				'max'            => false,           // Max number of items to return.
				'fields'         => 'all',           // Fields to include.
				'sort'           => 'DESC',          // ASC or DESC.
				'order_by'       => 'date_created',  // Column to order by.
				'exclude'        => false,           // Array of ids to exclude.
				'in'             => false,           // Array of ids to limit query by (IN).
				'search_terms'   => false,           // Terms to search by.
				'privacy'        => false,           // public, loggedin, onlyme, friends, grouponly, message.
				'count_total'    => false,           // Whether or not to use count_total.
				'folder_id'      => false,
				'folder'         => true,
				'user_directory' => true,
				'meta_query'     => false,           // Filter by document meta.
			)
		);

		// Select conditions.
		$select_sql = 'SELECT DISTINCT d.id';

		$from_sql = " FROM {$bp->document->table_name} d";

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
			$where_conditions['search_sql'] = $wpdb->prepare( 'd.title LIKE %s', $search_terms_like );

			/**
			 * Filters whether or not to include users for search parameters.
			 *
			 * @param bool $value Whether or not to include user search. Default false.
			 *
			 * @since BuddyBoss 1.4.0
			 */
			if ( apply_filters( 'bp_document_get_include_user_search', false ) ) {
				$user_search = get_user_by( 'slug', $r['search_terms'] );
				if ( false !== $user_search ) {
					$user_id                         = $user_search->ID;
					$where_conditions['search_sql'] .= $wpdb->prepare( ' OR d.user_id = %d', $user_id );
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
			case 'folder':
			case 'folder_id':
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
		$order_by = 'd.' . $r['order_by'];
		// Support order by fields for generally.
		if ( ! empty( $r['in'] ) && 'in' === $r['order_by'] ) {
			$order_by = 'FIELD(d.id, ' . implode( ',', wp_parse_id_list( $r['in'] ) ) . ')';
			$sort     = '';
		}

		// Exclude specified items.
		if ( ! empty( $r['exclude'] ) ) {
			$exclude                     = implode( ',', wp_parse_id_list( $r['exclude'] ) );
			$where_conditions['exclude'] = "d.id NOT IN ({$exclude})";
		}

		// The specific ids to which you want to limit the query.
		if ( ! empty( $r['in'] ) ) {
			$in                     = implode( ',', wp_parse_id_list( $r['in'] ) );
			$where_conditions['in'] = "d.id IN ({$in})";

			// we want to disable limit query when include document ids.
			$r['page']     = false;
			$r['per_page'] = false;
		}

		if ( ! empty( $r['activity_id'] ) ) {
			$where_conditions['activity'] = "d.activity_id = {$r['activity_id']}";
		}

		// existing-document check to query document which has no folders assigned.
		if ( ! empty( $r['folder_id'] ) && 'existing-document' !== $r['folder_id'] ) {
			$where_conditions['folder'] = "d.folder_id = {$r['folder_id']}";
		} elseif ( ! empty( $r['folder_id'] ) && 'existing-document' === $r['folder_id'] ) {
			$where_conditions['folder'] = 'd.folder_id = 0';
		}

		if ( ! empty( $r['user_id'] ) ) {
			$where_conditions['user'] = "d.user_id = {$r['user_id']}";
		}

		if ( ! empty( $r['group_id'] ) ) {
			$where_conditions['group'] = "d.group_id = {$r['group_id']}";
		}

		if ( ! empty( $r['privacy'] ) ) {
			$privacy                     = "'" . implode( "', '", $r['privacy'] ) . "'";
			$where_conditions['privacy'] = "d.privacy IN ({$privacy})";
		}

		// Process meta_query into SQL.
		$meta_query_sql = self::get_meta_query_sql( $r['meta_query'] );

		if ( ! empty( $meta_query_sql['join'] ) ) {
			$join_sql .= $meta_query_sql['join'];
		}

		if ( ! empty( $meta_query_sql['where'] ) ) {
			$where_conditions[] = $meta_query_sql['where'];
		}

		/**
		 * Filters the MySQL WHERE conditions for the Document items get method.
		 *
		 * @param array  $where_conditions Current conditions for MySQL WHERE statement.
		 * @param array  $r                Parsed arguments passed into method.
		 * @param string $select_sql       Current SELECT MySQL statement at point of execution.
		 * @param string $from_sql         Current FROM MySQL statement at point of execution.
		 * @param string $join_sql         Current INNER JOIN MySQL statement at point of execution.
		 *
		 * @since BuddyBoss 1.4.0
		 */
		$where_conditions = apply_filters( 'bp_document_get_where_conditions', $where_conditions, $r, $select_sql, $from_sql, $join_sql );

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
		$join_sql = apply_filters( 'bp_document_get_join_sql', $join_sql, $r, $select_sql, $from_sql, $where_sql );

		// Sanitize page and per_page parameters.
		$page     = absint( $r['page'] );
		$per_page = absint( $r['per_page'] );

		$retval = array(
			'documents'      => null,
			'total'          => null,
			'has_more_items' => null,
		);

		// Query first for document IDs.
		$document_ids_sql = "{$select_sql} {$from_sql} {$join_sql} {$where_sql} ORDER BY {$order_by} {$sort}, d.id {$sort}";

		if ( ! empty( $per_page ) && ! empty( $page ) ) {
			// We query for $per_page + 1 items in order to
			// populate the has_more_items flag.
			$document_ids_sql .= $wpdb->prepare( ' LIMIT %d, %d', absint( ( $page - 1 ) * $per_page ), $per_page + 1 );
		}

		/**
		 * Filters the paged document MySQL statement.
		 *
		 * @param string $document_ids_sql MySQL statement used to query for Document IDs.
		 * @param array  $r                Array of arguments passed into method.
		 *
		 * @since BuddyBoss 1.4.0
		 */
		$document_ids_sql = apply_filters( 'bp_document_paged_activities_sql', $document_ids_sql, $r );

		$cache_group = 'bp_document';

		$cached = bp_core_get_incremented_cache( $document_ids_sql, $cache_group );
		if ( false === $cached ) {
			$document_ids = $wpdb->get_col( $document_ids_sql ); // phpcs:ignore
			bp_core_set_incremented_cache( $document_ids_sql, $cache_group, $document_ids );
		} else {
			$document_ids = $cached;
		}

		$retval['has_more_items'] = ! empty( $per_page ) && count( $document_ids ) > $per_page;

		// If we've fetched more than the $per_page value, we
		// can discard the extra now.
		if ( ! empty( $per_page ) && count( $document_ids ) === $per_page + 1 ) {
			array_pop( $document_ids );
		}

		if ( 'ids' === $r['fields'] ) {
			$documents = array_map( 'intval', $document_ids );
		} else {
			$documents = self::get_document_data( $document_ids );
		}

		if ( 'ids' !== $r['fields'] ) {
			// Pre-fetch data associated with document users and other objects.
			$documents = self::prefetch_object_data( $documents );
		}

		$retval['documents'] = $documents;

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
			$total_documents_sql = apply_filters( 'bp_document_total_documents_sql', "SELECT count(DISTINCT d.id) FROM {$bp->document->table_name} d {$join_sql} {$where_sql}", $where_sql, $sort );
			$cached              = bp_core_get_incremented_cache( $total_documents_sql, $cache_group );
			if ( false === $cached ) {
				$total_documents = $wpdb->get_var( $total_documents_sql ); // phpcs:ignore
				bp_core_set_incremented_cache( $total_documents_sql, $cache_group, $total_documents );
			} else {
				$total_documents = $cached;
			}

			if ( ! empty( $r['max'] ) ) {
				if ( (int) $total_documents > (int) $r['max'] ) {
					$total_documents = $r['max'];
				}
			}

			$retval['total'] = $total_documents;
		}

		return $retval;
	}

	/** Static Methods ***************************************************/

	/**
	 * Get the SQL for the 'scope' param in BP_Document::get().
	 * A scope is a predetermined set of document arguments.  This method is used
	 * to grab these document arguments and override any existing args if needed.
	 * Can handle multiple scopes.
	 *
	 * @param mixed $scope   The document scope. Accepts string or array of scopes.
	 * @param array $r       Current activity arguments. Same as those of BP_document::get(),
	 *                       but merged with defaults.
	 *
	 * @return false|array 'sql' WHERE SQL string and 'override' document args.
	 * @since BuddyBoss 1.4.0
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
			 * Plugins can hook here to set their document arguments for custom scopes.
			 * This is a dynamic filter based on the document scope. eg:
			 *   - 'bp_document_set_groups_scope_args'
			 *   - 'bp_document_set_friends_scope_args'
			 * To see how this filter is used, plugin devs should check out:
			 *   - bp_groups_filter_document_scope() - used for 'groups' scope
			 *   - bp_friends_filter_document_scope() - used for 'friends' scope
			 *
			 * @param array {
			 *                        Document query clauses.
			 *
			 * @type array {
			 *         Document arguments for your custom scope.
			 *         See {@link BP_Document_Query::_construct()} for more details.
			 *     }
			 * @type array  $override Optional. Override existing document arguments passed by $r.
			 *     }
			 * }
			 *
			 * @param array $r        Current activity arguments passed in BP_Document::get().
			 *
			 * @since BuddyBoss 1.4.0
			 */
			$scope_args = apply_filters( "bp_document_set_{$scope}_scope_args", array(), $r );

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
			// Set relation to OR.
			$query_args['relation'] = 'OR';

			$query = new BP_Document_Query( $query_args );
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
	 * Convert document IDs to document objects, as expected in template loop.
	 *
	 * @param array $document_ids Array of document IDs.
	 * @param bool  $thumb_gen    Whether to generate symlink or not.
	 *
	 * @return array
	 * @since BuddyBoss 1.4.0
	 */
	protected static function get_document_data( $document_ids = array(), $thumb_gen = true ) {
		global $wpdb;

		// Bail if no document ID's passed.
		if ( empty( $document_ids ) ) {
			return array();
		}

		// Get BuddyPress.
		$bp = buddypress();

		$documents    = array();
		$uncached_ids = bp_get_non_cached_ids( $document_ids, 'bp_document' );

		// Prime caches as necessary.
		if ( ! empty( $uncached_ids ) ) {
			// Format the document ID's for use in the query below.
			$uncached_ids_sql = implode( ',', wp_parse_id_list( $uncached_ids ) );

			// Fetch data from document table, preserving order.
			$queried_adata = $wpdb->get_results( "SELECT * FROM {$bp->document->table_name} WHERE id IN ({$uncached_ids_sql})" ); // db call ok; no-cache ok;

			// Put that data into the placeholders created earlier,
			// and add it to the cache.
			foreach ( (array) $queried_adata as $adata ) {
				wp_cache_set( $adata->id, $adata, 'bp_document' );
			}
		}

		// Now fetch data from the cache.
		foreach ( $document_ids as $document_id ) {
			// Integer casting.
			$document = wp_cache_get( $document_id, 'bp_document' );
			if ( ! empty( $document ) ) {
				$document->id            = (int) $document->id;
				$document->blog_id       = (int) $document->blog_id;
				$document->user_id       = (int) $document->user_id;
				$document->attachment_id = (int) $document->attachment_id;
				$document->folder_id     = (int) $document->folder_id;
				$document->activity_id   = (int) $document->activity_id;
				$document->message_id    = (int) $document->message_id;
				$document->group_id      = (int) $document->group_id;
				$document->menu_order    = (int) $document->menu_order;
				$document->parent        = (int) $document->folder_id;
				$document->extension     = ( $thumb_gen ? bp_document_extension( $document->attachment_id ) : false ); // Get document extension.
			}

			$group_name = '';
			$visibility = '';
			if ( $document->group_id > 0 ) {
				if ( bp_is_active( 'groups' ) ) {
					$group      = groups_get_group( $document->group_id );
					$group_name = bp_get_group_name( $group );
					$status     = bp_get_group_status( $group );
					if ( 'hidden' === $status || 'private' === $status ) {
						$visibility = esc_html__( 'Group Members', 'buddyboss' );
					} else {
						$visibility = esc_html__( ucfirst( $status ), 'buddyboss' ); // phpcs:ignore WordPress.WP.I18n.NonSingularStringLiteralText
					}
				} else {
					$visibility = '';
				}
			} else {
				$document_privacy = bp_document_get_visibility_levels();
				if ( 'friends' === $document->privacy && bp_loggedin_user_id() !== (int) $document->user_id ) {
					$visibility = esc_html__( 'Connections', 'buddyboss' );
				} elseif ( 'message' === $document->privacy ) {
					$visibility = esc_html__( 'Message', 'buddyboss' );
				} elseif ( 'forums' === $document->privacy ) {
					$visibility = esc_html__( 'Forums', 'buddyboss' );
				} else {
					$visibility = ( isset( $document_privacy[ $document->privacy ] ) ) ? ucfirst( $document_privacy[ $document->privacy ] ) : '';
				}
			}

			// fetch attachment data.
			$large_pdf_image_popup = ( $thumb_gen ? bp_document_get_preview_url( $document->id, $document->attachment_id, 'bb-document-pdf-image-popup-image' ) : '' );
			$activity_thumb_pdf    = ( $thumb_gen ? bp_document_get_preview_url( $document->id, $document->attachment_id, 'bb-document-pdf-preview-activity-image' ) : '' );
			$activity_thumb_image  = ( $thumb_gen ? bp_document_get_preview_url( $document->id, $document->attachment_id, 'bb-document-image-preview-activity-image' ) : '' );
			$video_symlink         = ( $thumb_gen ? bb_document_video_get_symlink( $document ) : '' );

			$attachment_data                     = new stdClass();
			$attachment_data->full               = $large_pdf_image_popup;
			$attachment_data->thumb              = $activity_thumb_image;
			$attachment_data->activity_thumb     = $activity_thumb_image;
			$attachment_data->activity_thumb_pdf = $activity_thumb_pdf;
			$attachment_data->video_symlink      = $video_symlink;
			$attachment_data->meta               = ( $thumb_gen ? self::attachment_meta( $document->attachment_id ) : '' );
			$document->attachment_data           = $attachment_data;
			$document->group_name                = $group_name;
			$document->visibility                = $visibility;

			$documents[] = $document;
		}

		return $documents;
	}

	/**
	 * Get attachment meta.
	 *
	 * @param int $attachment_id Attachment ID.
	 *
	 * @return array
	 * @since BuddyBoss 1.7.0
	 */
	protected static function attachment_meta( $attachment_id ) {
		$metadata  = wp_get_attachment_metadata( $attachment_id );
		$extension = bp_document_extension( $attachment_id );

		$meta = array();

		switch ( $extension ) {
			case 'pdf':
				$meta['sizes'] = isset( $metadata['sizes'] ) ? $metadata['sizes'] : array();
				break;
			case 'jpg':
			case 'jpeg':
			case 'png':
			case 'gif':
			case 'svg':
				$meta['width']  = isset( $metadata['width'] ) ? $metadata['width'] : 0;
				$meta['height'] = isset( $metadata['height'] ) ? $metadata['height'] : 0;
				$meta['sizes']  = array(
					'medium' => isset( $metadata['sizes']['medium'] ) ? $metadata['sizes']['medium'] : false,
					'large'  => isset( $metadata['sizes']['large'] ) ? $metadata['sizes']['large'] : false,
				);
				break;
			default:
				break;
		}

		return $meta;
	}

	/**
	 * Pre-fetch data for objects associated with document items.
	 * Document items are associated with users, and often with other
	 * BuddyPress data objects. Here, we pre-fetch data about these
	 * associated objects, so that inline lookups - done primarily when
	 * building action strings - do not result in excess database queries.
	 *
	 * @param array $documents Array of document.
	 *
	 * @return array $documents Array of document.
	 * @since BuddyBoss 1.4.0
	 */
	protected static function prefetch_object_data( $documents ) {

		/**
		 * Filters inside prefetch_object_data method to aid in pre-fetching object data associated with document item.
		 *
		 * @param array $documents Array of document.
		 *
		 * @since BuddyBoss 1.4.0
		 */
		return apply_filters( 'bp_document_prefetch_object_data', $documents );
	}

	/**
	 * Get document/folder items, as specified by parameters.
	 *
	 * @param array $args Array of arguments.
	 *
	 * @return null[]
	 */
	public static function documents( $args = array() ) {

		global $wpdb;

		$bp = buddypress();
		$r  = bp_parse_args(
			$args,
			array(
				'scope'               => '',              // Scope - Groups, friends etc.
				'page'                => 1,               // The current page.
				'per_page'            => 20,              // Document items per page.
				'max'                 => false,           // Max number of items to return.
				'fields'              => 'all',           // Fields to include.
				'sort'                => 'DESC',          // ASC or DESC.
				'order_by'            => 'date_created',  // Column to order by.
				'exclude'             => false,           // Array of ids to exclude.
				'in'                  => false,           // Array of ids to limit query by (IN).
				'search_terms'        => false,           // Terms to search by.
				'privacy'             => false,           // public, loggedin, onlyme, friends, grouponly, message.
				'count_total'         => false,           // Whether or not to use count_total.
				'user_directory'      => true,
				'folder_id'           => 0,
				'meta_query_document' => false,
				'meta_query_folder'   => false,
			)
		);

		// Select conditions.
		$select_sql_document = 'SELECT DISTINCT d.*';
		$select_sql_folder   = 'SELECT DISTINCT f.*';

		$from_sql_document = " FROM {$bp->document->table_name} d INNER JOIN {$bp->document->table_name_meta} dm ON ( d.id = dm.document_id ) ";
		$from_sql_folder   = " FROM {$bp->document->table_name_folder} f";

		$join_sql_document = '';
		$join_sql_folder   = '';

		// Where conditions.
		$where_conditions_document = array( '1=1' );
		$where_conditions_folder   = array( "f.id != '0'" );

		if ( ! empty( $r['scope'] ) ) {
			$scope_query_document = self::get_scope_document_query_sql( $r['scope'], $r );
			$scope_query_folder   = self::get_scope_folder_query_sql( $r['scope'], $r );

			// Override some arguments if needed.
			if ( ! empty( $scope_query_document['override'] ) ) {
				$r = array_replace_recursive( $r, $scope_query_document['override'] );
			}

			// Override some arguments if needed.
			if ( ! empty( $scope_query_folder['override'] ) ) {
				$r = array_replace_recursive( $r, $scope_query_folder['override'] );
			}
		}

		// Searching.
		if ( $r['search_terms'] ) {

			$search_terms_like                       = '%' . bp_esc_like( $r['search_terms'] ) . '%';
			$where_conditions_document['search_sql'] = $wpdb->prepare( '( d.title LIKE %s', $search_terms_like );
			$where_conditions_folder['search_sql']   = $wpdb->prepare( 'f.title LIKE %s', $search_terms_like );

			$where_conditions_document['search_sql'] .= $wpdb->prepare( ' OR dm.meta_key = "extension" AND dm.meta_value LIKE %s ', $search_terms_like );
			$where_conditions_document['search_sql'] .= $wpdb->prepare( ' OR dm.meta_key = "file_name" AND dm.meta_value LIKE %s )', $search_terms_like );

			/**
			 * Filters whether or not to include users for search parameters.
			 *
			 * @param bool $value Whether or not to include user search. Default false.
			 *
			 * @since BuddyBoss 1.4.0
			 */
			if ( apply_filters( 'bp_document_get_include_user_search', false ) ) {
				$user_search = get_user_by( 'slug', $r['search_terms'] );
				if ( false !== $user_search ) {
					$user_id                                  = $user_search->ID;
					$where_conditions_document['search_sql'] .= $wpdb->prepare( ' OR d.user_id = %d', $user_id );
					$where_conditions_folder['search_sql']   .= $wpdb->prepare( ' OR f.user_id = %d', $user_id );
				}
			}
		}

		// Sorting.
		$sort = $r['sort'];
		if ( 'ASC' !== $sort && 'DESC' !== $sort ) {
			$sort = 'ASC';
		}

		switch ( $r['order_by'] ) {
			case 'id':
			case 'user_id':
			case 'blog_id':
			case 'attachment_id':
			case 'title':
			case 'folder_id':
			case 'activity_id':
			case 'privacy':
			case 'group_id':
			case 'menu_order':
			case 'visibility':
			case 'date_modified':
			case 'date_created':
				break;

			default:
				$r['order_by'] = 'title';
				break;
		}
		$order_by_document = 'd.' . $r['order_by'];
		$order_by_folder   = 'f.' . $r['order_by'];

		// Exclude specified items.
		if ( ! empty( $r['exclude'] ) ) {
			$exclude                              = implode( ',', wp_parse_id_list( $r['exclude'] ) );
			$where_conditions_document['exclude'] = "d.id NOT IN ({$exclude})";
			$where_conditions_folder['exclude']   = "f.id NOT IN ({$exclude})";
		}

		// The specific ids to which you want to limit the query.
		if ( ! empty( $r['in'] ) ) {
			$in                              = implode( ',', wp_parse_id_list( $r['in'] ) );
			$where_conditions_document['in'] = "d.id IN ({$in})";
			$where_conditions_folder['in']   = "f.id IN ({$in})";
		}

		if ( ! empty( $r['folder_id'] ) ) {
			$folder_id                              = implode( ',', wp_parse_id_list( $r['folder_id'] ) );
			$where_conditions_document['folder_id'] = "d.folder_id IN ({$folder_id})";
			$where_conditions_folder['folder_id']   = "f.parent IN ({$folder_id})";
		}

		if ( ! empty( $r['activity_id'] ) ) {
			$where_conditions_document['activity'] = "d.activity_id = {$r['activity_id']}";
		}

		if ( ! empty( $r['user_id'] ) ) {
			$where_conditions_document['user'] = "d.user_id = {$r['user_id']}";
			$where_conditions_folder['user']   = "f.user_id = {$r['user_id']}";
		}

		if ( ! empty( $r['group_id'] ) ) {
			$where_conditions_document['group'] = "d.group_id = {$r['group_id']}";
			$where_conditions_folder['group']   = "f.group_id = {$r['group_id']}";
		}

		if ( ! empty( $r['privacy'] ) ) {
			$privacy                              = "'" . implode( "', '", $r['privacy'] ) . "'";
			$where_conditions_document['privacy'] = "d.privacy IN ({$privacy})";
			$where_conditions_folder['privacy']   = "f.privacy IN ({$privacy})";
		}

		// Process meta_query into SQL.
		$meta_query_sql_document = self::get_meta_query_sql( $r['meta_query_document'] );
		$meta_query_sql_folder   = self::get_document_folder_meta_query_sql( $r['meta_query_folder'] );

		if ( ! empty( $meta_query_sql_document['join'] ) ) {
			$join_sql_document .= $meta_query_sql_document['join'];
		}

		if ( ! empty( $meta_query_sql_folder['join'] ) ) {
			$join_sql_folder .= $meta_query_sql_folder['join'];
		}

		if ( ! empty( $meta_query_sql_document['where'] ) ) {
			$where_conditions_document[] = $meta_query_sql_document['where'];
		}

		if ( ! empty( $meta_query_sql_folder['where'] ) ) {
			$where_conditions_folder[] = $meta_query_sql_folder['where'];
		}

		/**
		 * Filters the MySQL WHERE conditions for the Document items get method.
		 *
		 * @param array  $where_conditions Current conditions for MySQL WHERE statement.
		 * @param array  $r                Parsed arguments passed into method.
		 * @param string $select_sql       Current SELECT MySQL statement at point of execution.
		 * @param string $from_sql         Current FROM MySQL statement at point of execution.
		 * @param string $join_sql         Current INNER JOIN MySQL statement at point of execution.
		 *
		 * @since BuddyBoss 1.4.0
		 */
		$where_conditions_document = apply_filters( 'bp_document_get_where_conditions', $where_conditions_document, $r, $select_sql_document, $from_sql_document, $join_sql_document );
		$where_conditions_folder   = apply_filters( 'bp_document_folder_get_where_conditions', $where_conditions_folder, $r, $select_sql_folder, $from_sql_folder, $join_sql_folder );

		// Join the where conditions together for document.
		if ( ! empty( $scope_query_document['sql'] ) ) {
			$where_sql_document = 'WHERE ' .
								( ! empty( $where_conditions_document ) ? '( ' . join( ' AND ', $where_conditions_document ) . ' ) AND ' : '' ) .
								' ( ' . $scope_query_document['sql'] . ' )';
		} else {
			$where_sql_document = 'WHERE ' . ( ! empty( $where_conditions_document ) ? join( ' AND ', $where_conditions_document ) : '' );
		}

		// Join the where conditions together for folder.
		if ( ! empty( $scope_query_folder['sql'] ) ) {
			$where_sql_folder = 'WHERE ' . ( ! empty( $where_conditions_folder ) ? '( ' . join( ' AND ', $where_conditions_folder ) . ' ) AND ' : '' ) .
								' ( ' . $scope_query_folder['sql'] . ' )';
		} else {
			$where_sql_folder = 'WHERE ' . ( ! empty( $where_conditions_folder ) ? join( ' AND ', $where_conditions_folder ) : '' );
		}

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
		$join_sql_folder   = apply_filters( 'bp_document_folder_get_join_sql', $join_sql_folder, $r, $select_sql_folder, $from_sql_folder, $where_sql_folder );
		$join_sql_document = apply_filters( 'bp_document_get_join_sql', $join_sql_document, $r, $select_sql_document, $from_sql_document, $where_sql_document );

		$retval = array(
			'documents'      => null,
			'total'          => null,
			'has_more_items' => null,
		);

		// Query first for document IDs.
		$document_ids_sql_folder   = "{$select_sql_folder} {$from_sql_folder} {$join_sql_folder} {$where_sql_folder} ORDER BY {$order_by_folder} {$sort}";
		$document_ids_sql_document = "{$select_sql_document} {$from_sql_document} {$join_sql_document} {$where_sql_document} ORDER BY {$order_by_document} {$sort}";

		/**
		 * Filters the paged document MySQL statement.
		 *
		 * @param string $document_ids_sql MySQL statement used to query for Document IDs.
		 * @param array  $r                Array of arguments passed into method.
		 *
		 * @since BuddyBoss 1.4.0
		 */
		$document_ids_sql_folder   = apply_filters( 'bp_document_paged_activities_sql_folder', $document_ids_sql_folder, $r );
		$document_ids_sql_document = apply_filters( 'bp_document_paged_activities_sql_document', $document_ids_sql_document, $r );

		$cache_group = 'bp_document';

		$cached_folder   = bp_core_get_incremented_cache( $document_ids_sql_folder, $cache_group );
		$cached_document = bp_core_get_incremented_cache( $document_ids_sql_document, $cache_group );

		if ( false === $cached_folder ) {
			$document_ids_folder = $wpdb->get_col( $document_ids_sql_folder ); // db call ok; no-cache ok;
			bp_core_set_incremented_cache( $document_ids_sql_folder, $cache_group, $document_ids_folder );
		} else {
			$document_ids_folder = $cached_folder;
		}

		if ( false === $cached_document ) {
			$document_ids_document = $wpdb->get_col( $document_ids_sql_document ); // db call ok; no-cache ok;
			bp_core_set_incremented_cache( $document_ids_sql_document, $cache_group, $document_ids_document );
		} else {
			$document_ids_document = $cached_document;
		}

		if ( 'ids' === $r['fields'] ) {
			$documents_folder   = array_map( 'intval', $document_ids_folder );
			$documents_document = array_map( 'intval', $document_ids_document );

			$documents = array_merge( $documents_folder, $documents_document );
		} else {
			$documents_document = self::get_document_data( $document_ids_document, false );
			$documents_folder   = self::get_folder_data( $document_ids_folder );

			$documents = array_merge( $documents_folder, $documents_document );
		}

		if ( 'ids' !== $r['fields'] ) {
			// Pre-fetch data associated with document users and other objects.
			$documents = self::prefetch_object_data( $documents );
		}

		$direction = 'SORT_' . $sort;

		if ( 'privacy' === $r['order_by'] ) {
			$r['order_by'] = 'visibility';
		} elseif ( 'group_id' === $r['order_by'] ) {
			$r['order_by'] = 'group_name';
		}

		$documents = self::array_msort( $documents, array( $r['order_by'] => $direction ) );

		$retval['total']          = ( ! empty( $documents ) ? count( $documents ) : 0 );

		if ( isset( $r['per_page'] ) && isset( $r['page'] ) && ! empty( $r['per_page'] ) && ! empty( $r['page'] ) ) {
			$total                    = count( $documents );
			$current_page             = $r['page'];
			$item_per_page            = $r['per_page'];
			$start                    = ( $current_page - 1 ) * $item_per_page;
			$documents                = array_slice( $documents, $start, $item_per_page );
			$retval['has_more_items'] = $total > ( $current_page * $item_per_page );
			$retval['documents']      = $documents;
		} else {
			$retval['documents']      = $documents;
			$retval['has_more_items'] = false;
		}

		if ( ! empty( $documents ) && 'ids' !== $r['fields'] ) {
			foreach ( $documents as $key => $data ) {
				if ( ! empty( $data->attachment_id ) ) {
					$documents[ $key ] = current( self::get_document_data( array( $data->id ), true ) );
				}
			}
			$retval['documents'] = $documents;
		}

		return $retval;
	}

	/**
	 * Get the SQL for the 'scope' param in BP_Document::get().
	 * A scope is a predetermined set of document arguments.  This method is used
	 * to grab these document arguments and override any existing args if needed.
	 * Can handle multiple scopes.
	 *
	 * @param mixed $scope   The document scope. Accepts string or array of scopes.
	 * @param array $r       Current activity arguments. Same as those of BP_document::get(),
	 *                       but merged with defaults.
	 *
	 * @return false|array 'sql' WHERE SQL string and 'override' document args.
	 * @since BuddyBoss 1.4.0
	 */
	public static function get_scope_document_query_sql( $scope = false, $r = array() ) {

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
			 * Plugins can hook here to set their document arguments for custom scopes.
			 * This is a dynamic filter based on the document scope. eg:
			 *   - 'bp_document_set_groups_scope_args'
			 *   - 'bp_document_set_friends_scope_args'
			 * To see how this filter is used, plugin devs should check out:
			 *   - bp_groups_filter_document_scope() - used for 'groups' scope
			 *   - bp_friends_filter_document_scope() - used for 'friends' scope
			 *
			 * @param array {
			 *                        Document query clauses.
			 *
			 * @type array {
			 *         Document arguments for your custom scope.
			 *         See {@link BP_Document_Query::_construct()} for more details.
			 *     }
			 * @type array  $override Optional. Override existing document arguments passed by $r.
			 *     }
			 * }
			 *
			 * @param array $r        Current activity arguments passed in BP_Document::get().
			 *
			 * @since BuddyBoss 1.4.0
			 */
			$scope_args = apply_filters( "bp_document_set_document_{$scope}_scope_args", array(), $r );

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
			// Set relation to OR.
			$query_args['relation'] = 'OR';

			$query = new BP_Document_Query( $query_args );
			$sql   = $query->get_sql_document();
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
	 * Get the SQL for the 'scope' param in BP_Document::get().
	 * A scope is a predetermined set of document arguments.  This method is used
	 * to grab these document arguments and override any existing args if needed.
	 * Can handle multiple scopes.
	 *
	 * @param mixed $scope   The document scope. Accepts string or array of scopes.
	 * @param array $r       Current activity arguments. Same as those of BP_document::get(),
	 *                       but merged with defaults.
	 *
	 * @return false|array 'sql' WHERE SQL string and 'override' document args.
	 * @since BuddyBoss 1.4.0
	 */
	public static function get_scope_folder_query_sql( $scope = false, $r = array() ) {

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
			 * Plugins can hook here to set their document arguments for custom scopes.
			 * This is a dynamic filter based on the document scope. eg:
			 *   - 'bp_document_set_groups_scope_args'
			 *   - 'bp_document_set_friends_scope_args'
			 * To see how this filter is used, plugin devs should check out:
			 *   - bp_groups_filter_document_scope() - used for 'groups' scope
			 *   - bp_friends_filter_document_scope() - used for 'friends' scope
			 *
			 * @param array {
			 *                        Document query clauses.
			 *
			 * @type array {
			 *         Document arguments for your custom scope.
			 *         See {@link BP_Document_Query::_construct()} for more details.
			 *     }
			 * @type array  $override Optional. Override existing document arguments passed by $r.
			 *     }
			 * }
			 *
			 * @param array $r        Current activity arguments passed in BP_Document::get().
			 *
			 * @since BuddyBoss 1.4.0
			 */
			$scope_args = apply_filters( "bp_document_set_folder_{$scope}_scope_args", array(), $r );

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
			// Set relation to OR.
			$query_args['relation'] = 'OR';

			$query = new BP_Document_Query( $query_args );
			$sql   = $query->get_sql_folder();
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
	 * Convert document IDs to document objects, as expected in template loop.
	 *
	 * @param array $folder_ids Array of document IDs.
	 *
	 * @return array
	 * @since BuddyBoss 1.4.0
	 */
	protected static function get_folder_data( $folder_ids = array() ) {
		global $wpdb;

		// Bail if no document ID's passed.
		if ( empty( $folder_ids ) ) {
			return array();
		}

		// Get BuddyPress.
		$bp = buddypress();

		$documents    = array();
		$uncached_ids = bp_get_non_cached_ids( $folder_ids, 'bp_document_folder' );

		// Prime caches as necessary.
		if ( ! empty( $uncached_ids ) ) {
			// Format the document ID's for use in the query below.
			$uncached_ids_sql = implode( ',', wp_parse_id_list( $uncached_ids ) );

			// Fetch data from document table, preserving order.
			$queried_adata = $wpdb->get_results( "SELECT * FROM {$bp->document->table_name_folder} WHERE id IN ({$uncached_ids_sql})" ); // db call ok; no-cache ok;

			// Put that data into the placeholders created earlier,
			// and add it to the cache.
			foreach ( (array) $queried_adata as $adata ) {
				wp_cache_set( $adata->id, $adata, 'bp_document_folder' );
			}
		}

		// Now fetch data from the cache.
		foreach ( $folder_ids as $document_id ) {
			// Integer casting.
			$document = wp_cache_get( $document_id, 'bp_document_folder' );
			if ( ! empty( $document ) ) {
				$document->id           = (int) $document->id;
				$document->user_id      = (int) $document->user_id;
				$document->group_id     = (int) $document->group_id;
				$document->date_created = $document->date_created;
				$document->title        = $document->title;
				$document->privacy      = $document->privacy;
				$document->parent       = $document->parent;
				$document->folder_id    = (int) $document->id;

				if ( (int) $document->group_id > 0 ) {
					$document->folder = 'group';
					if ( bp_is_active( 'groups' ) ) {
						$group          = groups_get_group( array( 'group_id' => $document->group_id ) );
						$document->link = bp_get_group_permalink( $group ) . bp_get_document_slug() . '/folder/' . (int) $document->id;
					}
					$document->link = '';

				} else {
					$document->folder = 'profile';
					$document->link   = bp_core_get_user_domain( (int) $document->user_id ) . bp_get_document_slug() . '/folder/' . (int) $document->id;
				}
			}

			$group_name = '';
			$visibility = '';
			if ( $document->group_id > 0 ) {
				if ( bp_is_active( 'groups' ) ) {
					$group      = groups_get_group( $document->group_id );
					$group_name = bp_get_group_name( $group );
					$status     = bp_get_group_status( $group );
					if ( 'hidden' === $status || 'private' === $status ) {
						$visibility = esc_html__( 'Group Members', 'buddyboss' );
					} else {
						$visibility = ucfirst( $status );
					}
				} else {
					$visibility = '';
				}
			} else {
				$document_privacy = bp_document_get_visibility_levels();
				if ( 'friends' === $document->privacy && bp_loggedin_user_id() !== (int) $document->user_id ) {
					$visibility = esc_html__( 'Connections', 'buddyboss' );
				} else {
					$visibility = ( isset( $document_privacy[ $document->privacy ] ) ) ? $document_privacy[ $document->privacy ] : $document->privacy;
				}
			}

			$document->group_name = $group_name;
			$document->visibility = $visibility;

			$documents[] = $document;
		}

		return $documents;
	}

	/**
	 * Sort data based on order.
	 *
	 * @param $array
	 * @param $cols
	 *
	 * @return array|mixed
	 *
	 * @since BuddyBoss 1.4.0
	 */
	public static function array_msort( $array, $cols ) {

		$array = json_decode( wp_json_encode( $array ), true );

		$colarr = array();
		foreach ( $cols as $col => $order ) {
			$colarr[ $col ] = array();
			foreach ( $array as $k => $row ) {
				$colarr[ $col ][ '_' . $k ] = strtolower( $row[ $col ] );
			}
		}
		$eval = 'array_multisort(';
		foreach ( $cols as $col => $order ) {
			$eval .= '$colarr[\'' . $col . '\'],' . $order . ',';
		}
		$eval = substr( $eval, 0, - 1 ) . ');';
		eval( $eval );
		$ret = array();
		foreach ( $colarr as $col => $arr ) {
			foreach ( $arr as $k => $v ) {
				$k = substr( $k, 1 );
				if ( ! isset( $ret[ $k ] ) ) {
					$ret[ $k ] = $array[ $k ];
				}
				$ret[ $k ][ $col ] = $array[ $k ][ $col ];
			}
		}

		if ( ! empty( $ret ) ) {
			$i   = 0;
			$arr = array();
			foreach ( $ret as $k => $v ) {
				$ret[ $i ] = (object) $v;
				$arr[ $i ] = (object) $v;
				$i ++;
			}
		}

		return $arr;

	}

	/**
	 * Create SQL IN clause for filter queries.
	 *
	 * @param string     $field The database field.
	 * @param array|bool $items The values for the IN clause, or false when none are found.
	 *
	 * @return string|false
	 * @see   BP_Document::get_filter_sql()
	 * @since BuddyBoss 1.4.0
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
	 * Delete document items from the database.
	 * To delete a specific document item, pass an 'id' parameter.
	 * Otherwise use the filters.
	 *
	 * @param array $args {
	 * @int    $id                Optional. The ID of a specific item to delete.
	 * @int    $blog_id           Optional. The blog ID to filter by.
	 * @int    $attachment_id     Optional. The attachment ID to filter by.
	 * @int    $user_id           Optional. The user ID to filter by.
	 * @string    $title          Optional. The title to filter by.
	 * @int    $folder_id         Optional. The folder ID to filter by.
	 * @int    $activity_id       Optional. The activity ID to filter by.
	 * @int    $group_id          Optional. The group ID to filter by.
	 * @string    $privacy        Optional. The privacy to filter by.
	 * @string $date_created      Optional. The date to filter by.
	 *                    }
	 * @param bool  $from Context of deletion from. ex. attachment, activity etc.
	 *
	 * @return array|bool An array of deleted document IDs on success, false on failure.
	 * @since BuddyBoss 1.4.0
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
				'folder_id'     => false,
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

		// folder ID.
		if ( ! empty( $r['folder_id'] ) ) {
			$where_args[] = $wpdb->prepare( 'folder_id = %d', $r['folder_id'] );
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

		// Fetch all document being deleted so we can perform more actions.
		$documents = $wpdb->get_results( "SELECT * FROM {$bp->document->table_name} {$where_sql}" ); // db call ok; no-cache ok;

		/**
		 * Action to allow intercepting document items to be deleted.
		 *
		 * @param array $documents Array of document.
		 * @param array $r         Array of parsed arguments.
		 *
		 * @since BuddyBoss 1.4.0
		 */
		do_action_ref_array( 'bp_document_before_delete', array( $documents, $r ) );

		// Attempt to delete document from the database.
		$deleted = $wpdb->query( "DELETE FROM {$bp->document->table_name} {$where_sql}" );           // db call ok; no-cache ok;

		// Bail if nothing was deleted.
		if ( empty( $deleted ) ) {
			return false;
		}

		/**
		 * Action to allow intercepting document items just deleted.
		 *
		 * @param array $documents Array of document.
		 * @param array $r         Array of parsed arguments.
		 *
		 * @since BuddyBoss 1.4.0
		 */
		do_action_ref_array( 'bp_document_after_delete', array( $documents, $r ) );

		// Pluck the document IDs out of the $documents array.
		$document_ids   = wp_parse_id_list( wp_list_pluck( $documents, 'id' ) );
		$activity_ids   = wp_parse_id_list( wp_list_pluck( $documents, 'activity_id' ) );
		$attachment_ids = wp_parse_id_list( wp_list_pluck( $documents, 'attachment_id' ) );

		// Delete preview attachment.
		foreach ( $document_ids as $document_delete ) {
			$preview_id = bp_document_get_meta( $document_delete, 'preview_attachment_id', true );
			if ( $preview_id ) {
				wp_delete_attachment( $preview_id, true );
			}
		}

		// Delete meta.
		if ( ! empty( $document_ids ) ) {
			// Delete all document meta entries for document items.
			self::delete_document_meta_entries( wp_list_pluck( $documents, 'id' ) );
		}

		// Handle accompanying attachments and meta deletion.
		if ( ! empty( $attachment_ids ) ) {

			// Loop through attachment ids and attempt to delete.
			foreach ( $attachment_ids as $attachment_id ) {

				if ( bp_is_active( 'activity' ) ) {
					$parent_activity_id = get_post_meta( $attachment_id, 'bp_document_parent_activity_id', true );
					if ( ! empty( $parent_activity_id ) ) {
						$activity_document_ids = bp_activity_get_meta( $parent_activity_id, 'bp_document_ids', true );
						if ( ! empty( $activity_document_ids ) ) {
							$activity_document_ids = explode( ',', $activity_document_ids );
							$activity_document_ids = array_diff( $activity_document_ids, $document_ids );
							if ( ! empty( $activity_document_ids ) ) {
								$activity_document_ids = implode( ',', $activity_document_ids );
								bp_activity_update_meta( $parent_activity_id, 'bp_document_ids', $activity_document_ids );
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
					if ( 'activity_comment' === $activity->type ) {
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

		return $document_ids;
	}

	/**
	 * Delete the meta entries associated with a set of document items.
	 *
	 * @since BuddyBoss 1.4.0
	 *
	 * @param array $document_ids Document IDs whose meta should be deleted.
	 * @return bool True on success.
	 */
	public static function delete_document_meta_entries( $document_ids = array() ) {
		$document_ids = wp_parse_id_list( $document_ids );

		foreach ( $document_ids as $document_id ) {
			bp_document_delete_meta( $document_id );
		}

		return true;
	}

	/**
	 * Count total document for the given user.
	 *
	 * @param int $user_id User ID.
	 *
	 * @return array|bool|int
	 * @since BuddyBoss 1.4.0
	 */
	public static function total_document_count( $user_id = 0 ) {
		global $bp, $wpdb;

		$privacy = array( 'public' );
		if ( is_user_logged_in() ) {
			$privacy[] = 'loggedin';
			if ( bp_is_active( 'friends' ) ) {
				$is_friend = friends_check_friendship( get_current_user_id(), $user_id );
				if ( $is_friend ) {
					$privacy[] = 'friends';
				}
			}
		}
		$privacy = "'" . implode( "', '", $privacy ) . "'";

		$total_count_document = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$bp->document->table_name} WHERE user_id = {$user_id} AND privacy IN ({$privacy}) AND folder_id = 0" );       // db call ok; no-cache ok;
		$total_count_folder   = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$bp->document->table_name_folder} WHERE user_id = {$user_id} AND privacy IN ({$privacy}) AND parent = 0" ); // db call ok; no-cache ok;
		$total_count          = $total_count_folder + $total_count_document;

		return $total_count;
	}

	/**
	 * Count total document for the given group.
	 *
	 * @param int $group_id Group ID.
	 *
	 * @return array|bool|int
	 * @since BuddyBoss 1.4.0
	 */
	public static function total_group_document_count( $group_id = 0 ) {
		global $bp, $wpdb;

		$total_count_document = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$bp->document->table_name} WHERE group_id = {$group_id} AND folder_id = 0" );       // db call ok; no-cache ok;
		$total_count_folder   = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$bp->document->table_name_folder} WHERE group_id = {$group_id} AND parent = 0" ); // db call ok; no-cache ok;
		$total_count          = $total_count_folder + $total_count_document;

		return $total_count;
	}

	/**
	 * Get all document ids for the folder.
	 *
	 * @param int $folder_id Folder ID.
	 *
	 * @return array|bool
	 * @since BuddyBoss 1.4.0
	 */
	public static function get_folder_document_ids( $folder_id = 0 ) {
		global $bp, $wpdb;

		if ( ! $folder_id ) {
			return false;
		}

		$folder_document_sql = $wpdb->prepare( "SELECT DISTINCT d.id FROM {$bp->document->table_name} d WHERE d.folder_id = %d", $folder_id );

		$cached = bp_core_get_incremented_cache( $folder_document_sql, 'bp_document' );

		if ( false === $cached ) {
			$document_ids = $wpdb->get_col( $folder_document_sql ); // db call ok; no-cache ok;
			bp_core_set_incremented_cache( $folder_document_sql, 'bp_document', $document_ids );
		} else {
			$document_ids = $cached;
		}

		return (array) $document_ids;
	}

	/**
	 * Get document id for the activity.
	 *
	 * @param int $activity_id Activity ID.
	 *
	 * @return array|bool
	 * @since BuddyBoss 1.1.6
	 */
	public static function get_activity_document_id( $activity_id = 0 ) {
		global $bp, $wpdb;

		if ( ! $activity_id ) {
			return false;
		}

		$cache_key            = 'bp_document_activity_id_' . $activity_id;
		$activity_document_id = wp_cache_get( $cache_key, 'bp_document' );

		if ( ! empty( $activity_document_id ) ) {
			return $activity_document_id;
		}

		// Check activity component enabled or not.
		if ( bp_is_active( 'activity' ) ) {
			$activity_document_id = bp_activity_get_meta( $activity_id, 'bp_document_id', true );
		}

		if ( empty( $activity_document_id ) ) {
			$activity_document_id = (int) $wpdb->get_var( "SELECT DISTINCT d.id FROM {$bp->document->table_name} d WHERE d.activity_id = {$activity_id}" ); // db call ok; no-cache ok;

			if ( bp_is_active( 'activity' ) ) {
				$document_activity = bp_activity_get_meta( $activity_id, 'bp_document_activity', true );
				if ( ! empty( $document_activity ) && ! empty( $activity_document_id ) ) {
					bp_activity_update_meta( $activity_id, 'bp_document_id', $activity_document_id );
				}
			}
		}

		wp_cache_set( $cache_key, $activity_document_id, 'bp_document' );

		return $activity_document_id;
	}

	/**
	 * Save the document item to the database.
	 *
	 * @return WP_Error|bool True on success.
	 * @since BuddyBoss 1.4.0
	 */
	public function save() {

		global $wpdb;

		$bp = buddypress();

		$this->id            = apply_filters_ref_array( 'bp_document_id_before_save', array( $this->id, &$this ) );
		$this->blog_id       = apply_filters_ref_array( 'bp_document_blog_id_before_save', array( $this->blog_id, &$this ) );
		$this->attachment_id = apply_filters_ref_array( 'bp_document_attachment_id_before_save', array( $this->attachment_id, &$this ) );
		$this->user_id       = apply_filters_ref_array( 'bp_document_user_id_before_save', array( $this->user_id, &$this ) );
		$this->title         = apply_filters_ref_array( 'bp_document_title_before_save', array( $this->title, &$this ) );
		$this->folder_id     = apply_filters_ref_array( 'bp_document_folder_id_before_save', array( $this->folder_id, &$this ) );
		$this->activity_id   = apply_filters_ref_array( 'bp_document_activity_id_before_save', array( $this->activity_id, &$this ) );
		$this->message_id    = apply_filters_ref_array( 'bp_document_message_id_before_save', array( $this->message_id, &$this ) );
		$this->group_id      = apply_filters_ref_array( 'bp_document_group_id_before_save', array( $this->group_id, &$this ) );
		$this->privacy       = apply_filters_ref_array( 'bp_document_privacy_before_save', array( $this->privacy, &$this ) );
		$this->menu_order    = apply_filters_ref_array( 'bp_document_menu_order_before_save', array( $this->menu_order, &$this ) );
		$this->date_created  = apply_filters_ref_array( 'bp_document_date_created_before_save', array( $this->date_created, &$this ) );
		$this->date_modified = apply_filters_ref_array( 'bp_document_date_modified_before_save', array( $this->date_modified, &$this ) );

		/**
		 * Fires before the current document item gets saved.
		 * Please use this hook to filter the properties above. Each part will be passed in.
		 *
		 * @param BP_Document $this Current instance of the document item being saved. Passed by reference.
		 *
		 * @since BuddyBoss 1.4.0
		 */
		do_action_ref_array( 'bp_document_before_save', array( &$this ) );

		if ( 'wp_error' === $this->error_type && $this->errors->get_error_code() ) {
			return $this->errors;
		}

		if ( empty( $this->attachment_id ) // || empty( $this->activity_id ) //todo: when forums document is saving, it should have activity id assigned if settings enabled need to check this
		) {
			if ( 'bool' === $this->error_type ) {
				return false;
			} else {
				if ( empty( $this->activity_id ) ) {
					$this->errors->add( 'bp_document_missing_activity' );
				} else {
					$this->errors->add( 'bp_document_missing_attachment' );
				}

				return $this->errors;
			}
		}

		// If we have an existing ID, update the document item, otherwise insert it.
		if ( ! empty( $this->id ) ) {
			$q = $wpdb->prepare( "UPDATE {$bp->document->table_name} SET blog_id = %d, attachment_id = %d, user_id = %d, title = %s, folder_id = %d, activity_id = %d, message_id = %d, group_id = %d, privacy = %s, menu_order = %d, date_modified = %s WHERE id = %d", $this->blog_id, $this->attachment_id, $this->user_id, $this->title, $this->folder_id, $this->activity_id, $this->message_id, $this->group_id, $this->privacy, $this->menu_order, $this->date_modified, $this->id );
		} else {
			$q = $wpdb->prepare( "INSERT INTO {$bp->document->table_name} ( blog_id, attachment_id, user_id, title, folder_id, activity_id, message_id, group_id, privacy, menu_order, date_created, date_modified ) VALUES ( %d, %d, %d, %s, %d, %d, %d, %d, %s, %d, %s, %s )", $this->blog_id, $this->attachment_id, $this->user_id, $this->title, $this->folder_id, $this->activity_id, $this->message_id, $this->group_id, $this->privacy, $this->menu_order, $this->date_created, $this->date_modified );
		}

		if ( false === $wpdb->query( $q ) ) {
			return false;
		}

		// If this is a new document item, set the $id property.
		if ( empty( $this->id ) ) {
			$this->id = $wpdb->insert_id;
		}


		/**
		 * Fire before documents preview generate.
		 *
		 * @since BuddyBoss 1.7.0.1
		 */
		do_action( 'bb_document_before_generate_document_previews' );

		bp_document_generate_document_previews( $this->attachment_id );

		/**
		 * Fire after documents preview generate.
		 *
		 * @since BuddyBoss 1.7.0.1
		 */
		do_action( 'bb_document_after_generate_document_previews' );

		// Update folder modified date.
		$folder = (int) $this->folder_id;
		if ( $folder > 0 ) {
			bp_document_update_folder_modified_date( $folder );
		}

		/**
		 * Fires after an document item has been saved to the database.
		 *
		 * @param BP_Document $this Current instance of document item being saved. Passed by reference.
		 *
		 * @since BuddyBoss 1.4.0
		 */
		do_action_ref_array( 'bp_document_after_save', array( &$this ) );

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
			$document_meta_query = new WP_Meta_Query( $meta_query );

			// WP_Meta_Query expects the table name at
			// $wpdb->document_meta.
			$wpdb->documentmeta = buddypress()->document->table_name_meta;

			$meta_sql = $document_meta_query->get_sql( 'document', 'd', 'id' );

			// Strip the leading AND - BP handles it in get().
			$sql_array['where'] = preg_replace( '/^\sAND/', '', $meta_sql['where'] );
			$sql_array['join']  = $meta_sql['join'];
		}

		return $sql_array;
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
	public static function get_document_folder_meta_query_sql( $meta_query = array() ) {
		global $wpdb;

		$sql_array = array(
			'join'  => '',
			'where' => '',
		);

		if ( ! empty( $meta_query ) ) {
			$document_meta_query = new WP_Meta_Query( $meta_query );

			// WP_Meta_Query expects the table name at
			// $wpdb->document_meta.
			$wpdb->documentmeta = buddypress()->document->table_name_folder_meta;

			$meta_sql = $document_meta_query->get_sql( 'document_folder', 'f', 'id' );

			// Strip the leading AND - BP handles it in get().
			$sql_array['where'] = preg_replace( '/^\sAND/', '', $meta_sql['where'] );
			$sql_array['join']  = $meta_sql['join'];
		}

		return $sql_array;
	}

	/**
	 * Get document attachment id for the activity.
	 *
	 * @param int $activity_id Activity ID.
	 *
	 * @return integer|bool
	 * @since BuddyBoss 1.4.0
	 */
	public static function get_activity_attachment_id( $activity_id = 0 ) {
		global $bp, $wpdb;

		if ( empty( $activity_id ) ) {
			return false;
		}

		$cache_key              = 'bp_document_attachment_id_' . $activity_id;
		$document_attachment_id = wp_cache_get( $cache_key, 'bp_document' );

		if ( ! empty( $document_attachment_id ) ) {
			return $document_attachment_id;
		}

		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery
		$document_attachment_id = (int) $wpdb->get_var( "SELECT DISTINCT attachment_id FROM {$bp->document->table_name} WHERE activity_id = {$activity_id}" );
		wp_cache_set( $cache_key, $document_attachment_id, 'bp_document' );

		return $document_attachment_id;
	}
}
