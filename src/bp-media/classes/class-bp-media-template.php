<?php
/**
 * BuddyPress Media Template loop class.
 *
 * @package BuddyBoss\Media
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * The main media template loop class.
 *
 * Responsible for loading a group of media into a loop for display.
 *
 * @since BuddyPress 1.0.0
 */
#[\AllowDynamicProperties]
class BP_Media_Template {

	/**
	 * The loop iterator.
	 *
	 * @since BuddyBoss 1.0.0
	 * @var int
	 */
	public $current_media = -1;

	/**
	 * The media count.
	 *
	 * @since BuddyBoss 1.0.0
	 * @var int
	 */
	public $media_count;

	/**
	 * The total media count.
	 *
	 * @since BuddyBoss 1.0.0
	 * @var int
	 */
	public $total_media_count;

	/**
	 * Array of media located by the query.
	 *
	 * @since BuddyBoss 1.0.0
	 * @var array
	 */
	public $medias;

	/**
	 * The media object currently being iterated on.
	 *
	 * @since BuddyBoss 1.0.0
	 * @var object
	 */
	public $media;

	/**
	 * A flag for whether the loop is currently being iterated.
	 *
	 * @since BuddyBoss 1.0.0
	 * @var bool
	 */
	public $in_the_loop;

	/**
	 * URL parameter key for media pagination. Default: 'acpage'.
	 *
	 * @since BuddyBoss 1.0.0
	 * @var string
	 */
	public $pag_arg;

	/**
	 * The page number being requested.
	 *
	 * @since BuddyBoss 1.0.0
	 * @var int
	 */
	public $pag_page;

	/**
	 * The number of items being requested per page.
	 *
	 * @since BuddyBoss 1.0.0
	 * @var int
	 */
	public $pag_num;

	/**
	 * An HTML string containing pagination links.
	 *
	 * @since BuddyBoss 1.0.0
	 * @var string
	 */
	public $pag_links;

	/**
	 * The displayed user's full name.
	 *
	 * @since BuddyBoss 1.0.0
	 * @var string
	 */
	public $full_name;

	/**
	 * Constructor method.
	 *
	 * The arguments passed to this class constructor are of the same
	 * format as {@link BP_Media::get()}.
	 *
	 * @since BuddyBoss 1.0.0
	 *
	 * @see BP_Media::get() for a description of the argument
	 *      structure, as well as default values.
	 *
	 * @param array $args {
	 *     Array of arguments. Supports all arguments from
	 *     BP_Media::get(), as well as 'page_arg' and
	 *     'include'. Default values for 'per_page'
	 *     differ from the originating function, and are described below.
	 *     @type string      $page_arg         The string used as a query parameter in
	 *                                         pagination links. Default: 'acpage'.
	 *     @type array|bool  $include          Pass an array of media IDs to
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
			'video'            => false,
			'moderation_query' => true,
		);
		$r        = bp_parse_args( $args, $defaults );
		extract( $r );

		$this->pag_arg  = sanitize_key( $r['page_arg'] );
		$this->pag_page = bp_sanitize_pagination_arg( $this->pag_arg, $r['page'] );
		$this->pag_num  = bp_sanitize_pagination_arg( 'num', $r['per_page'] );

		// Get an array of the logged in user's favorite media.
		$this->my_favs = bp_get_user_meta( bp_loggedin_user_id(), 'bp_favorite_media', true );

		// Fetch specific media items based on ID's.
		if ( ! empty( $include ) ) {
			$this->medias = bp_media_get_specific(
				array(
					'media_ids'        => explode( ',', $include ),
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
			$this->medias = bp_media_get(
				array(
					'max'          => $max,
					'count_total'  => $count_total,
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
					'video'        => $video,
				)
			);
		}

		// The total_media_count property will be set only if a
		// 'count_total' query has taken place.
		if ( ! is_null( $this->medias['total'] ) ) {
			if ( ! $max || $max >= (int) $this->medias['total'] ) {
				$this->total_media_count = (int) $this->medias['total'];
			} else {
				$this->total_media_count = (int) $max;
			}
		}

		// Video count compatibility if album have only single video.
		if ( isset( $this->medias['total_video'] ) && ! is_null( $this->medias['total_video'] ) && $video ) {
			$this->total_media_count = $this->total_media_count + (int) $this->medias['total_video'];
		}

		$this->has_more_items = $this->medias['has_more_items'];

		$this->medias = $this->medias['medias'];

		if ( $max ) {
			if ( $max >= count( $this->medias ) ) {
				$this->media_count = count( $this->medias );
			} else {
				$this->media_count = (int) $max;
			}
		} else {
			$this->media_count = count( $this->medias );
		}

		if ( (int) $this->total_media_count && (int) $this->pag_num ) {
			$this->pag_links = paginate_links(
				array(
					'base'      => add_query_arg( $this->pag_arg, '%#%' ),
					'format'    => '',
					'total'     => ceil( (int) $this->total_media_count / (int) $this->pag_num ),
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
	 * Whether there are media items available in the loop.
	 *
	 * @since BuddyBoss 1.0.0
	 *
	 * @see bp_has_media()
	 *
	 * @return bool True if there are items in the loop, otherwise false.
	 */
	function has_media() {
		if ( $this->media_count ) {
			return true;
		}

		return false;
	}

	/**
	 * Set up the next media item and iterate index.
	 *
	 * @since BuddyBoss 1.0.0
	 *
	 * @return object The next media item to iterate over.
	 */
	public function next_media() {
		$this->current_media++;
		$this->media = $this->medias[ $this->current_media ];

		return $this->media;
	}

	/**
	 * Rewind the posts and reset post index.
	 *
	 * @since BuddyBoss 1.0.0
	 */
	public function rewind_medias() {
		$this->current_media = -1;
		if ( $this->media_count > 0 ) {
			$this->media = $this->medias[0];
		}
	}

	/**
	 * Whether there are media items left in the loop to iterate over.
	 *
	 * This method is used by {@link bp_media()} as part of the while loop
	 * that controls iteration inside the media loop, eg:
	 *     while ( bp_media() ) { ...
	 *
	 * @since BuddyBoss 1.0.0
	 *
	 * @see bp_media()
	 *
	 * @return bool True if there are more media items to show,
	 *              otherwise false.
	 */
	public function user_medias() {
		if ( ( $this->current_media + 1 ) < $this->media_count ) {
			return true;
		} elseif ( ( $this->current_media + 1 ) == $this->media_count ) {

			/**
			 * Fires right before the rewinding of media posts.
			 *
			 * @since BuddyBoss 1.1.0
			 */
			do_action( 'media_loop_end' );

			// Do some cleaning up after the loop.
			$this->rewind_medias();
		}

		$this->in_the_loop = false;

		return false;
	}

	/**
	 * Set up the current media item inside the loop.
	 *
	 * Used by {@link bp_the_media()} to set up the current media item
	 * data while looping, so that template tags used during that iteration
	 * make reference to the current media item.
	 *
	 * @since BuddyBoss 1.0.0
	 *
	 * @see bp_the_media()
	 */
	public function the_media() {

		$this->in_the_loop = true;
		$this->media       = $this->next_media();

		if ( is_array( $this->media ) ) {
			$this->media = (object) $this->media;
		}

		// Loop has just started.
		if ( $this->current_media == 0 ) {

			/**
			 * Fires if the current media item is the first in the activity loop.
			 *
			 * @since BuddyBoss 1.1.0
			 */
			do_action( 'media_loop_start' );
		}
	}
}
