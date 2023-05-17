<?php
/**
 * BuddyPress Video Template loop class.
 *
 * @package BuddyBoss\Video
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * The main video template loop class.
 *
 * Responsible for loading a group of video into a loop for display.
 *
 * @since BuddyBoss 1.7.0
 */
#[\AllowDynamicProperties]
class BP_Video_Template {

	/**
	 * The loop iterator.
	 *
	 * @since BuddyBoss 1.7.0
	 * @var int
	 */
	public $current_video = -1;

	/**
	 * The video count.
	 *
	 * @since BuddyBoss 1.7.0
	 * @var int
	 */
	public $video_count;

	/**
	 * The total video count.
	 *
	 * @since BuddyBoss 1.7.0
	 * @var int
	 */
	public $total_video_count;

	/**
	 * Array of video located by the query.
	 *
	 * @since BuddyBoss 1.7.0
	 * @var array
	 */
	public $videos;

	/**
	 * The video object currently being iterated on.
	 *
	 * @since BuddyBoss 1.7.0
	 * @var object
	 */
	public $video;

	/**
	 * A flag for whether the loop is currently being iterated.
	 *
	 * @since BuddyBoss 1.7.0
	 * @var bool
	 */
	public $in_the_loop;

	/**
	 * URL parameter key for video pagination. Default: 'acpage'.
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
	 * format as {@link BP_Video::get()}.
	 *
	 * @since BuddyBoss 1.7.0
	 *
	 * @see BP_Video::get() for a description of the argument
	 *      structure, as well as default values.
	 *
	 * @param array $args {
	 *     Array of arguments. Supports all arguments from
	 *     BP_Video::get(), as well as 'page_arg' and
	 *     'include'. Default values for 'per_page'
	 *     differ from the originating function, and are described below.
	 *     @type string      $page_arg         The string used as a query parameter in
	 *                                         pagination links. Default: 'acpage'.
	 *     @type array|bool  $include          Pass an array of video IDs to
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
			'page'             => 1,
			'per_page'         => 20,
			'page_arg'         => 'acpage',
			'max'              => false,
			'fields'           => 'all',
			'count_total'      => false,
			'sort'             => false,
			'order_by'         => false,
			'include'          => false,
			'exclude'          => false,
			'search_terms'     => false,
			'scope'            => false,
			'user_id'          => false,
			'album_id'         => false,
			'group_id'         => false,
			'privacy'          => false,
			'moderation_query' => true,
		);

		$r = bp_parse_args( $args, $defaults );
		extract( $r ); // phpcs:ignore WordPress.PHP.DontExtract.extract_extract

		$this->pag_arg  = sanitize_key( $r['page_arg'] );
		$this->pag_page = bp_sanitize_pagination_arg( $this->pag_arg, $r['page'] );
		$this->pag_num  = bp_sanitize_pagination_arg( 'num', $r['per_page'] );

		// Get an array of the logged in user's favorite video.
		$this->my_favs = bp_get_user_meta( bp_loggedin_user_id(), 'bp_favorite_video', true );

		// Fetch specific video items based on ID's.
		if ( ! empty( $include ) ) {
			$this->videos = bp_video_get_specific(
				array(
					'video_ids'        => explode( ',', $include ),
					'max'              => $max,
					'count_total'      => $count_total,
					'page'             => $this->pag_page,
					'per_page'         => $this->pag_num,
					'sort'             => $sort,
					'order_by'         => $order_by,
					'user_id'          => $user_id,
					'album_id'         => $album_id,
					'privacy'          => $privacy,
					'moderation_query' => $moderation_query,
				)
			);

			// Fetch all activity items.
		} else {
			$this->videos = bp_video_get(
				array(
					'max'          => $max,
					'count_total'  => $count_total,
					'fields'       => $fields,
					'per_page'     => $this->pag_num,
					'page'         => $this->pag_page,
					'sort'         => $sort,
					'order_by'     => $order_by,
					'search_terms' => $search_terms,
					'scope'        => $scope,
					'user_id'      => $user_id,
					'album_id'     => $album_id,
					'group_id'     => $group_id,
					'exclude'      => $exclude,
					'privacy'      => $privacy,
				)
			);
		}

		// The total_video_count property will be set only if a
		// 'count_total' query has taken place.
		if ( ! is_null( $this->videos['total'] ) ) {
			if ( ! $max || $max >= (int) $this->videos['total'] ) {
				$this->total_video_count = (int) $this->videos['total'];
			} else {
				$this->total_video_count = (int) $max;
			}
		}

		$this->has_more_items = $this->videos['has_more_items'];

		$this->videos = $this->videos['videos'];

		if ( $max ) {
			if ( $max >= count( $this->videos ) ) {
				$this->video_count = count( $this->videos );
			} else {
				$this->video_count = (int) $max;
			}
		} else {
			$this->video_count = count( $this->videos );
		}

		if ( (int) $this->total_video_count && (int) $this->pag_num ) {
			$this->pag_links = paginate_links(
				array(
					'base'      => add_query_arg( $this->pag_arg, '%#%' ),
					'format'    => '',
					'total'     => ceil( (int) $this->total_video_count / (int) $this->pag_num ),
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
	 * Whether there are video items available in the loop.
	 *
	 * @since BuddyBoss 1.7.0
	 *
	 * @see bp_has_video()
	 *
	 * @return bool True if there are items in the loop, otherwise false.
	 */
	public function has_video() {
		if ( $this->video_count ) {
			return true;
		}

		return false;
	}

	/**
	 * Set up the next video item and iterate index.
	 *
	 * @since BuddyBoss 1.7.0
	 *
	 * @return object The next video item to iterate over.
	 */
	public function next_video() {
		$this->current_video++;
		$this->video = $this->videos[ $this->current_video ];

		return $this->video;
	}

	/**
	 * Rewind the posts and reset post index.
	 *
	 * @since BuddyBoss 1.7.0
	 */
	public function rewind_videos() {
		$this->current_video = -1;
		if ( $this->video_count > 0 ) {
			$this->video = $this->videos[0];
		}
	}

	/**
	 * Whether there are video items left in the loop to iterate over.
	 *
	 * This method is used by {@link bp_video()} as part of the while loop
	 * that controls iteration inside the video loop, eg:
	 *     while ( bp_video() ) { ...
	 *
	 * @since BuddyBoss 1.7.0
	 *
	 * @see bp_video()
	 *
	 * @return bool True if there are more video items to show,
	 *              otherwise false.
	 */
	public function user_videos() {
		if ( ( $this->current_video + 1 ) < $this->video_count ) {
			return true;
		} elseif ( (int) ( $this->current_video + 1 ) === (int) $this->video_count ) {

			/**
			 * Fires right before the rewinding of video posts.
			 *
			 * @since BuddyBoss 1.7.0
			 */
			do_action( 'video_loop_end' );

			// Do some cleaning up after the loop.
			$this->rewind_videos();
		}

		$this->in_the_loop = false;

		return false;
	}

	/**
	 * Set up the current video item inside the loop.
	 *
	 * Used by {@link bp_the_video()} to set up the current video item
	 * data while looping, so that template tags used during that iteration
	 * make reference to the current video item.
	 *
	 * @since BuddyBoss 1.7.0
	 *
	 * @see bp_the_video()
	 */
	public function the_video() {

		$this->in_the_loop = true;
		$this->video       = $this->next_video();

		if ( is_array( $this->video ) ) {
			$this->video = (object) $this->video;
		}

		// Loop has just started.
		if ( 0 === (int) $this->current_video ) {

			/**
			 * Fires if the current video item is the first in the activity loop.
			 *
			 * @since BuddyBoss 1.7.0
			 */
			do_action( 'video_loop_start' );
		}
	}
}
