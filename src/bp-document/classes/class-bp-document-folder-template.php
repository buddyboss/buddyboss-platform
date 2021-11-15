<?php
/**
 * BuddyBoss Document Folder Template loop class.
 *
 * @package BuddyBoss\Document
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * The main document template loop class.
 * Responsible for loading a group of document folders into a loop for display.
 *
 * @since BuddyBoss 1.4.0
 */
class BP_Document_Folder_Template {

	/**
	 * The loop iterator.
	 *
	 * @since BuddyBoss 1.4.0
	 * @var int
	 */
	public $current_folder = - 1;

	/**
	 * The folder count.
	 *
	 * @since BuddyBoss 1.4.0
	 * @var int
	 */
	public $folder_count;

	/**
	 * The total folder count.
	 *
	 * @since BuddyBoss 1.4.0
	 * @var int
	 */
	public $total_folder_count;

	/**
	 * Array of folder located by the query.
	 *
	 * @since BuddyBoss 1.4.0
	 * @var array
	 */
	public $folders;

	/**
	 * The folder object currently being iterated on.
	 *
	 * @since BuddyBoss 1.4.0
	 * @var object
	 */
	public $folder;

	/**
	 * A flag for whether the loop is currently being iterated.
	 *
	 * @since BuddyBoss 1.4.0
	 * @var bool
	 */
	public $in_the_loop;

	/**
	 * URL parameter key for folder pagination. Default: 'acpage'.
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
	 * format as {@link BP_Document_Folder::get()}.
	 *
	 * @param array $args     {
	 *                        Array of arguments. Supports all arguments from
	 *                        BP_Document_Folder::get(), as well as 'page_arg' and
	 *                        'include'. Default values for 'per_page'
	 *                        differ from the originating function, and are described below.
	 *
	 * @type string     $page_arg The string used as a query parameter in
	 *                                         pagination links. Default: 'acpage'.
	 * @type array|bool $include  Pass an array of activity IDs to
	 *                                         retrieve only those items, or false to noop the 'include'
	 *                                         parameter. 'include' differs from 'in' in that 'in' forms
	 *                                         an IN clause that works in conjunction with other filters
	 *                                         passed to the function, while 'include' is interpreted as
	 *                                         an exact list of items to retrieve, which skips all other
	 *                                         filter-related parameters. Default: false.
	 * @type int|bool   $per_page Default: 20.
	 * }
	 * @see   BP_Document_Folder::get() for a description of the argument
	 *        structure, as well as default values.
	 * @since BuddyBoss 1.4.0
	 */
	public function __construct( $args ) {

		$defaults = array(
			'page'         => 1,
			'per_page'     => 20,
			'page_arg'     => 'acpage',
			'max'          => false,
			'user_id'      => false,
			'fields'       => 'all',
			'count_total'  => false,
			'sort'         => false,
			'include'      => false,
			'exclude'      => false,
			'privacy'      => false,
			'search_terms' => false,
		);
		$r        = bp_parse_args( $args, $defaults );
		extract( $r );

		$this->pag_arg  = sanitize_key( $r['page_arg'] );
		$this->pag_page = bp_sanitize_pagination_arg( $this->pag_arg, $r['page'] );
		$this->pag_num  = bp_sanitize_pagination_arg( 'num', $r['per_page'] );

		// Get an array of the logged in user's favorite folder.
		$this->my_favs = bp_get_user_meta( bp_loggedin_user_id(), 'bp_favorite_folder', true );

		// Fetch specific folder items based on ID's.
		if ( ! empty( $include ) ) {
			$this->folders = bp_folder_get_specific(
				array(
					'folder_ids'  => explode( ',', $include ),
					'max'         => $max,
					'count_total' => $count_total,
					'page'        => $this->pag_page,
					'per_page'    => $this->pag_num,
					'sort'        => $sort,
					'user_id'     => $user_id,
				)
			);

			// Fetch all folder.
		} else {
			$this->folders = bp_folder_get(
				array(
					'max'          => $max,
					'count_total'  => $count_total,
					'per_page'     => $this->pag_num,
					'page'         => $this->pag_page,
					'sort'         => $sort,
					'search_terms' => $search_terms,
					'user_id'      => $user_id,
					'group_id'     => $group_id,
					'exclude'      => $exclude,
					'privacy'      => $privacy,
				)
			);
		}

		// The total_folder_count property will be set only if a
		// 'count_total' query has taken place.
		if ( ! is_null( $this->folders['total'] ) ) {
			if ( ! $max || $max >= (int) $this->folders['total'] ) {
				$this->total_folder_count = (int) $this->folders['total'];
			} else {
				$this->total_folder_count = (int) $max;
			}
		}

		$this->has_more_items = $this->folders['has_more_items'];

		$this->folders = $this->folders['folders'];

		if ( $max ) {
			if ( $max >= count( $this->folders ) ) {
				$this->folder_count = count( $this->folders );
			} else {
				$this->folder_count = (int) $max;
			}
		} else {
			$this->folder_count = count( $this->folders );
		}

		if ( (int) $this->total_folder_count && (int) $this->pag_num ) {
			$this->pag_links = paginate_links(
				array(
					'base'      => add_query_arg( $this->pag_arg, '%#%' ),
					'format'    => '',
					'total'     => ceil( (int) $this->total_folder_count / (int) $this->pag_num ),
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
	 * Whether there are folder items available in the loop.
	 *
	 * @return bool True if there are items in the loop, otherwise false.
	 * @see   bp_has_folders()
	 * @since BuddyBoss 1.4.0
	 */
	function has_folders() {
		if ( $this->folder_count ) {
			return true;
		}

		return false;
	}

	/**
	 * Whether there are folder items left in the loop to iterate over.
	 * This method is used by {@link bp_folders()} as part of the while loop
	 * that controls iteration inside the folder loop, eg:
	 *     while ( bp_folders() ) { ...
	 *
	 * @return bool True if there are more folder items to show,
	 *              otherwise false.
	 * @see   bp_folders()
	 * @since BuddyBoss 1.4.0
	 */
	public function user_folders() {
		if ( ( $this->current_folder + 1 ) < $this->folder_count ) {
			return true;
		} elseif ( ( $this->current_folder + 1 ) == $this->folder_count ) {

			/**
			 * Fires right before the rewinding of folder posts.
			 *
			 * @since BuddyBoss 1.4.0
			 */
			do_action( 'folder_loop_end' );

			// Do some cleaning up after the loop.
			$this->rewind_folders();
		}

		$this->in_the_loop = false;

		return false;
	}

	/**
	 * Rewind the posts and reset post index.
	 *
	 * @since BuddyBoss 1.4.0
	 */
	public function rewind_folders() {
		$this->current_folder = - 1;
		if ( $this->folder_count > 0 ) {
			$this->folder = $this->folders[0];
		}
	}

	/**
	 * Set up the current folder item inside the loop.
	 * Used by {@link bp_the_folder()} to set up the current folder item
	 * data while looping, so that template tags used during that iteration
	 * make reference to the current folder item.
	 *
	 * @since BuddyBoss 1.4.0
	 * @see   bp_the_folder()
	 */
	public function the_folder() {

		$this->in_the_loop = true;
		$this->folder      = $this->next_folder();

		if ( is_array( $this->folder ) ) {
			$this->folder = (object) $this->folder;
		}

		// Loop has just started.
		if ( $this->current_folder == 0 ) {

			/**
			 * Fires if the current folder item is the first in the activity loop.
			 *
			 * @since BuddyBoss 1.4.0
			 */
			do_action( 'folder_loop_start' );
		}
	}

	/**
	 * Set up the next folder item and iterate index.
	 *
	 * @return object The next folder item to iterate over.
	 * @since BuddyBoss 1.4.0
	 */
	public function next_folder() {
		$this->current_folder ++;
		$this->folder = $this->folders[ $this->current_folder ];

		return $this->folder;
	}
}
