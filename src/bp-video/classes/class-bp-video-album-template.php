<?php
/**
 * BuddyPress Video Album Template loop class.
 *
 * @package BuddyBoss\Video
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * The main video template loop class.
 *
 * Responsible for loading a group of video albums into a loop for display.
 *
 * @since BuddyBoss 1.7.0
 */
class BP_Video_Album_Template {

	/**
	 * The loop iterator.
	 *
	 * @since BuddyBoss 1.7.0
	 * @var int
	 */
	public $current_album = -1;

	/**
	 * The album count.
	 *
	 * @since BuddyBoss 1.7.0
	 * @var int
	 */
	public $album_count;

	/**
	 * The total album count.
	 *
	 * @since BuddyBoss 1.7.0
	 * @var int
	 */
	public $total_album_count;

	/**
	 * Array of album located by the query.
	 *
	 * @since BuddyBoss 1.7.0
	 * @var array
	 */
	public $albums;

	/**
	 * The album object currently being iterated on.
	 *
	 * @since BuddyBoss 1.7.0
	 * @var object
	 */
	public $album;

	/**
	 * A flag for whether the loop is currently being iterated.
	 *
	 * @since BuddyBoss 1.7.0
	 * @var bool
	 */
	public $in_the_loop;

	/**
	 * URL parameter key for album pagination. Default: 'acpage'.
	 *
	 * @since BuddyBoss 1.7.0
	 * @var string
	 */
	public $pag_arg;

	/**
	 * The page number being requested.
	 *
	 * @since BuddyBoss 1.7.0
	 * @var int
	 */
	public $pag_page;

	/**
	 * The number of items being requested per page.
	 *
	 * @since BuddyBoss 1.7.0
	 * @var int
	 */
	public $pag_num;

	/**
	 * An HTML string containing pagination links.
	 *
	 * @since BuddyBoss 1.7.0
	 * @var string
	 */
	public $pag_links;

	/**
	 * The displayed user's full name.
	 *
	 * @since BuddyBoss 1.7.0
	 * @var string
	 */
	public $full_name;

	/**
	 * Constructor method.
	 *
	 * The arguments passed to this class constructor are of the same
	 * format as {@link BP_Video_Album::get()}.
	 *
	 * @since BuddyBoss 1.7.0
	 *
	 * @see BP_Video_Album::get() for a description of the argument
	 *      structure, as well as default values.
	 *
	 * @param array $args {
	 *     Array of arguments. Supports all arguments from
	 *     BP_Video_Album::get(), as well as 'page_arg' and
	 *     'include'. Default values for 'per_page'
	 *     differ from the originating function, and are described below.
	 *     @type string      $page_arg         The string used as a query parameter in
	 *                                         pagination links. Default: 'acpage'.
	 *     @type array|bool  $include          Pass an array of activity IDs to
	 *                                         retrieve only those items, or false to noop the 'include'
	 *                                         parameter. 'include' differs from 'in' in that 'in' forms
	 *                                         an IN clause that works in conjunction with other filters
	 *                                         passed to the function, while 'include' is interpreted as
	 *                                         an exact list of items to retrieve, which skips all other
	 *                                         filter-related parameters. Default: false.
	 *     @type int|bool    $per_page         Default: 20.
	 * }
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

		$r = bp_parse_args( $args, $defaults );
		extract( $r ); // phpcs:ignore WordPress.PHP.DontExtract.extract_extract

		$this->pag_arg  = sanitize_key( $r['page_arg'] );
		$this->pag_page = bp_sanitize_pagination_arg( $this->pag_arg, $r['page'] );
		$this->pag_num  = bp_sanitize_pagination_arg( 'num', $r['per_page'] );

		// Get an array of the logged in user's favorite album.
		$this->my_favs = bp_get_user_meta( bp_loggedin_user_id(), 'bp_favorite_album', true );

		// Fetch specific album items based on ID's.
		if ( ! empty( $include ) ) {
			$this->albums = bp_video_album_get_specific(
				array(
					'album_ids'   => explode( ',', $include ),
					'max'         => $max,
					'count_total' => $count_total,
					'page'        => $this->pag_page,
					'per_page'    => $this->pag_num,
					'sort'        => $sort,
					'user_id'     => $user_id,
				)
			);

			// Fetch all albums.
		} else {
			$this->albums = bp_video_album_get(
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

		// The total_album_count property will be set only if a
		// 'count_total' query has taken place.
		if ( ! is_null( $this->albums['total'] ) ) {
			if ( ! $max || $max >= (int) $this->albums['total'] ) {
				$this->total_album_count = (int) $this->albums['total'];
			} else {
				$this->total_album_count = (int) $max;
			}
		}

		$this->has_more_items = $this->albums['has_more_items'];

		$this->albums = $this->albums['albums'];

		if ( $max ) {
			if ( $max >= count( $this->albums ) ) {
				$this->album_count = count( $this->albums );
			} else {
				$this->album_count = (int) $max;
			}
		} else {
			$this->album_count = count( $this->albums );
		}

		if ( (int) $this->total_album_count && (int) $this->pag_num ) {
			$this->pag_links = paginate_links(
				array(
					'base'      => add_query_arg( $this->pag_arg, '%#%' ),
					'format'    => '',
					'total'     => ceil( (int) $this->total_album_count / (int) $this->pag_num ),
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
	 * Whether there are album items available in the loop.
	 *
	 * @since BuddyBoss 1.7.0
	 *
	 * @see bp_has_video_albums()
	 *
	 * @return bool True if there are items in the loop, otherwise false.
	 */
	public function has_albums() {
		if ( $this->album_count ) {
			return true;
		}

		return false;
	}

	/**
	 * Set up the next album item and iterate index.
	 *
	 * @since BuddyBoss 1.7.0
	 *
	 * @return object The next album item to iterate over.
	 */
	public function next_album() {
		$this->current_album++;
		$this->album = $this->albums[ $this->current_album ];

		return $this->album;
	}

	/**
	 * Rewind the posts and reset post index.
	 *
	 * @since BuddyBoss 1.7.0
	 */
	public function rewind_albums() {
		$this->current_album = -1;
		if ( $this->album_count > 0 ) {
			$this->album = $this->albums[0];
		}
	}

	/**
	 * Whether there are album items left in the loop to iterate over.
	 *
	 * This method is used by {@link bp_video_albums()} as part of the while loop
	 * that controls iteration inside the album loop, eg:
	 *     while ( bp_video_albums() ) { ...
	 *
	 * @since BuddyBoss 1.7.0
	 *
	 * @see bp_video_albums()
	 *
	 * @return bool True if there are more album items to show,
	 *              otherwise false.
	 */
	public function user_albums() {
		if ( ( $this->current_album + 1 ) < $this->album_count ) {
			return true;
		} elseif ( (int) ( $this->current_album + 1 ) === (int) $this->album_count ) {

			/**
			 * Fires right before the rewinding of album posts.
			 *
			 * @since BuddyBoss 1.7.0
			 */
			do_action( 'album_loop_end' );

			// Do some cleaning up after the loop.
			$this->rewind_albums();
		}

		$this->in_the_loop = false;

		return false;
	}

	/**
	 * Set up the current album item inside the loop.
	 *
	 * Used by {@link bp_the_album()} to set up the current album item
	 * data while looping, so that template tags used during that iteration
	 * make reference to the current album item.
	 *
	 * @since BuddyBoss 1.7.0
	 *
	 * @see bp_the_album()
	 */
	public function the_album() {

		$this->in_the_loop = true;
		$this->album       = $this->next_album();

		if ( is_array( $this->album ) ) {
			$this->album = (object) $this->album;
		}

		// Loop has just started.
		if ( 0 === (int) $this->current_album ) {

			/**
			 * Fires if the current album item is the first in the activity loop.
			 *
			 * @since BuddyBoss 1.7.0
			 */
			do_action( 'album_loop_start' );
		}
	}
}
