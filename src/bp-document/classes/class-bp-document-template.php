<?php
/**
 * BuddyPress Document Template loop class.
 *
 * @package BuddyBoss\Document
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * The main document template loop class.
 * Responsible for loading a group of document into a loop for display.
 *
 * @since BuddyBoss 1.4.0
 */
#[\AllowDynamicProperties]
class BP_Document_Template {

	/**
	 * The loop iterator.
	 *
	 * @since BuddyBoss 1.4.0
	 * @var int
	 */
	public $current_document = - 1;

	/**
	 * The document count.
	 *
	 * @since BuddyBoss 1.4.0
	 * @var int
	 */
	public $document_count;

	/**
	 * The total document count.
	 *
	 * @since BuddyBoss 1.4.0
	 * @var int
	 */
	public $total_document_count;

	/**
	 * Array of document located by the query.
	 *
	 * @since BuddyBoss 1.4.0
	 * @var array
	 */
	public $documents;

	/**
	 * The document object currently being iterated on.
	 *
	 * @since BuddyBoss 1.4.0
	 * @var object
	 */
	public $document;

	/**
	 * A flag for whether the loop is currently being iterated.
	 *
	 * @since BuddyBoss 1.4.0
	 * @var bool
	 */
	public $in_the_loop;

	/**
	 * URL parameter key for document pagination. Default: 'acpage'.
	 *
	 * @since BuddyBoss 1.4.0
	 * @var string
	 */
	public $pag_arg;

	/**
	 * The page number being requested.
	 *
	 * @since BuddyBoss 1.4.0
	 * @var int
	 */
	public $pag_page;

	/**
	 * The number of items being requested per page.
	 *
	 * @since BuddyBoss 1.4.0
	 * @var int
	 */
	public $pag_num;

	/**
	 * An HTML string containing pagination links.
	 *
	 * @since BuddyBoss 1.4.0
	 * @var string
	 */
	public $pag_links;

	/**
	 * The displayed user's full name.
	 *
	 * @since BuddyBoss 1.4.0
	 * @var string
	 */
	public $full_name;

	/**
	 * Constructor method.
	 * The arguments passed to this class constructor are of the same
	 * format as {@link BP_Document::get()}.
	 *
	 * @param array $args     {
	 *                        Array of arguments. Supports all arguments from
	 *                        BP_Document::get(), as well as 'page_arg' and
	 *                        'include'. Default values for 'per_page'
	 *                        differ from the originating function, and are described below.
	 *
	 * @type string     $page_arg The string used as a query parameter in
	 *                                         pagination links. Default: 'acpage'.
	 * @type array|bool $include  Pass an array of document IDs to
	 *                                         retrieve only those items, or false to noop the 'include'
	 *                                         parameter. 'include' differs from 'in' in that 'in' forms
	 *                                         an IN clause that works in conjunction with other filters
	 *                                         passed to the function, while 'include' is interpreted as
	 *                                         an exact list of items to retrieve, which skips all other
	 *                                         filter-related parameters. Default: false.
	 * @type int|bool   $per_page Default: 20.
	 * }
	 * @see   BP_Document::get() for a description of the argument
	 *        structure, as well as default values.
	 * @since BuddyBoss 1.4.0
	 */
	public function __construct( $args ) {

		$defaults = array(
			'page'                => 1,
			'per_page'            => 20,
			'page_arg'            => 'acpage',
			'max'                 => false,
			'fields'              => 'all',
			'count_total'         => false,
			'sort'                => false,
			'order_by'            => false,
			'include'             => false,
			'exclude'             => false,
			'search_terms'        => false,
			'scope'               => false,
			'user_id'             => false,
			'folder_id'           => false,
			'group_id'            => false,
			'privacy'             => false,
			'folder'              => true,
			'user_directory'      => true,
			'meta_query_document' => false,
			'meta_query_folder'   => false,
			'meta_query'          => false,
			'moderation_query'    => true,
		);

		$r = bp_parse_args( $args, $defaults );
		extract( $r );

		$this->pag_arg  = sanitize_key( $r['page_arg'] );
		$this->pag_page = bp_sanitize_pagination_arg( $this->pag_arg, $r['page'] );
		$this->pag_num  = bp_sanitize_pagination_arg( 'num', $r['per_page'] );

		// Get an array of the logged in user's favorite document.
		$this->my_favs = bp_get_user_meta( bp_loggedin_user_id(), 'bp_favorite_document', true );

		// Fetch specific document items based on ID's.
		if ( ! empty( $include ) ) {

			$this->documents = bp_document_get_specific(
				array(
					'document_ids'     => ( ! is_array( $include ) ? explode( ',', $include ) : $include ),
					'max'              => $max,
					'count_total'      => $count_total,
					'page'             => $this->pag_page,
					'per_page'         => $this->pag_num,
					'sort'             => $sort,
					'order_by'         => $order_by,
					'user_id'          => $user_id,
					'folder_id'        => $folder_id,
					'folder'           => $folder,
					'user_directory'   => $user_directory,
					'meta_query'       => $meta_query,
					'privacy'          => $privacy,
					'moderation_query' => $moderation_query,
				)
			);

			// Fetch all activity items.
		} else {
			$this->documents = bp_document_get(
				array(
					'max'                 => $max,
					'count_total'         => $count_total,
					'per_page'            => $this->pag_num,
					'page'                => $this->pag_page,
					'sort'                => $sort,
					'order_by'            => $order_by,
					'search_terms'        => $search_terms,
					'scope'               => $scope,
					'user_id'             => $user_id,
					'folder_id'           => $folder_id,
					'group_id'            => $group_id,
					'exclude'             => $exclude,
					'privacy'             => $privacy,
					'folder'              => $folder,
					'user_directory'      => $user_directory,
					'meta_query_document' => $meta_query_document,
					'meta_query_folder'   => $meta_query_folder,
				)
			);
		}

		// The total_document_count property will be set only if a
		// 'count_total' query has taken place.
		if ( ! is_null( $this->documents['total'] ) ) {
			if ( ! $max || $max >= (int) $this->documents['total'] ) {
				$this->total_document_count = (int) $this->documents['total'];
			} else {
				$this->total_document_count = (int) $max;
			}
		}

		$this->has_more_items = $this->documents['has_more_items'];

		$this->documents = $this->documents['documents'];

		if ( $max ) {
			if ( $max >= count( $this->documents ) ) {
				$this->document_count = count( $this->documents );
			} else {
				$this->document_count = (int) $max;
			}
		} else {
			$this->document_count = count( $this->documents );
		}

		if ( (int) $this->total_document_count && (int) $this->pag_num ) {
			$this->pag_links = paginate_links(
				array(
					'base'      => add_query_arg( $this->pag_arg, '%#%' ),
					'format'    => '',
					'total'     => ceil( (int) $this->total_document_count / (int) $this->pag_num ),
					'current'   => (int) $this->pag_page,
					'prev_text' => __( '&larr;', 'buddyboss' ),
					'next_text' => __( '&rarr;', 'buddyboss' ),
					'mid_size'  => 1,
					'add_args'  => array(),
				)
			);
		}
	}

	/**
	 * Whether there are document items available in the loop.
	 *
	 * @return bool True if there are items in the loop, otherwise false.
	 * @see   bp_has_document()
	 * @since BuddyBoss 1.4.0
	 */
	function has_document() {
		if ( $this->document_count ) {
			return true;
		}

		return false;
	}

	/**
	 * Whether there are document items left in the loop to iterate over.
	 * This method is used by {@link bp_document()} as part of the while loop
	 * that controls iteration inside the document loop, eg:
	 *     while ( bp_document() ) { ...
	 *
	 * @return bool True if there are more document items to show,
	 *              otherwise false.
	 * @see   bp_document()
	 * @since BuddyBoss 1.4.0
	 */
	public function user_documents() {
		if ( ( $this->current_document + 1 ) < $this->document_count ) {
			return true;
		} elseif ( ( $this->current_document + 1 ) == $this->document_count ) {

			/**
			 * Fires right before the rewinding of document posts.
			 *
			 * @since BuddyBoss 1.1.0
			 */
			do_action( 'document_loop_end' );

			// Do some cleaning up after the loop.
			$this->rewind_documents();
		}

		$this->in_the_loop = false;

		return false;
	}

	/**
	 * Rewind the posts and reset post index.
	 *
	 * @since BuddyBoss 1.4.0
	 */
	public function rewind_documents() {
		$this->current_document = - 1;
		if ( $this->document_count > 0 ) {
			$this->document = $this->documents[0];
		}
	}

	/**
	 * Set up the current document item inside the loop.
	 * Used by {@link bp_the_document()} to set up the current document item
	 * data while looping, so that template tags used during that iteration
	 * make reference to the current document item.
	 *
	 * @since BuddyBoss 1.4.0
	 * @see   bp_the_document()
	 */
	public function the_document() {

		$this->in_the_loop = true;
		$this->document    = $this->next_document();

		if ( is_array( $this->document ) ) {
			$this->document = (object) $this->document;
		}

		// Loop has just started.
		if ( $this->current_document == 0 ) {

			/**
			 * Fires if the current document item is the first in the activity loop.
			 *
			 * @since BuddyBoss 1.4.0
			 */
			do_action( 'document_loop_start' );
		}
	}

	/**
	 * Set up the next document item and iterate index.
	 *
	 * @return object The next document item to iterate over.
	 * @since BuddyBoss 1.4.0
	 */
	public function next_document() {
		$this->current_document ++;
		$this->document = $this->documents[ $this->current_document ];

		return $this->document;
	}
}
