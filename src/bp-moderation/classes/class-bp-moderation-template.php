<?php
/**
 * BuddyPress Moderation Template loop class.
 *
 * @since   BuddyBoss 1.5.6
 * @package BuddyBoss\Moderation
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * The main moderation template loop class.
 * Responsible for loading moderation into a loop for display.
 *
 * @since BuddyBoss 1.5.6
 */
class BP_Moderation_Template {

	/**
	 * The loop iterator.
	 *
	 * @since BuddyBoss 1.5.6
	 * @var int
	 */
	public $current_moderation = - 1;

	/**
	 * The moderation count.
	 *
	 * @since BuddyBoss 1.5.6
	 * @var int
	 */
	public $moderation_count;

	/**
	 * The total moderation count.
	 *
	 * @since BuddyBoss 1.5.6
	 * @var int
	 */
	public $total_moderation_count;

	/**
	 * Array of moderation located by the query.
	 *
	 * @since BuddyBoss 1.5.6
	 * @var array
	 */
	public $moderations;

	/**
	 * The moderation object currently being iterated on.
	 *
	 * @since BuddyBoss 1.5.6
	 * @var object
	 */
	public $moderation;

	/**
	 * A flag for whether the loop is currently being iterated.
	 *
	 * @since BuddyBoss 1.5.6
	 * @var bool
	 */
	public $in_the_loop;

	/**
	 * URL parameter key for moderation pagination. Default: 'acpage'.
	 *
	 * @since BuddyBoss 1.5.6
	 * @var string
	 */
	public $pag_arg;

	/**
	 * The page number being requested.
	 *
	 * @since BuddyBoss 1.5.6
	 * @var int
	 */
	public $pag_page;

	/**
	 * The number of items being requested per page.
	 *
	 * @since BuddyBoss 1.5.6
	 * @var int
	 */
	public $pag_num;

	/**
	 * An HTML string containing pagination links.
	 *
	 * @since BuddyBoss 1.5.6
	 * @var string
	 */
	public $pag_links;

	/**
	 * The displayed user's full name.
	 *
	 * @since BuddyBoss 1.5.6
	 * @var string
	 */
	public $full_name;

	/**
	 *
	 * Constructor method.
	 * The arguments passed to this class constructor are of the same
	 * format as {@link BP_Moderation::get()}.
	 *
	 * @since BuddyBoss 1.5.6
	 *
	 * @param array $args     {
	 *                        Array of arguments. Supports all arguments from
	 *                        BP_Moderation::get(), as well as 'page_arg' and
	 *                        'include'. Default values for 'per_page'
	 *                        differ from the originating function, and are described below.
	 *
	 *                                     pagination links. Default: 'acpage'.
	 *
	 * @type array|bool $in       Pass an array of moderation IDs to
	 *                                         retrieve only those items, or false to noop the 'include'
	 *                                         parameter. 'include' differs from 'in' in that 'in' forms
	 *                                         an IN clause that works in conjunction with other filters
	 *                                         passed to the function, while 'include' is interpreted as
	 *                                         an exact list of items to retrieve, which skips all other
	 *                                         filter-related parameters. Default: false.
	 * @type int|bool   $per_page Default: 20.
	 * }
	 * @see   BP_Moderation::get() for a description of the argument
	 *        structure, as well as default values.
	 */
	public function __construct( $args ) {

		$defaults = array(
			'page'              => 1,               // The current page.
			'per_page'          => 20,              // Moderation items per page.
			'user_id'           => false,           // filter by user id.
			'max'               => false,           // Max number of items to return.
			'fields'            => 'all',           // Fields to include.
			'sort'              => 'DESC',          // ASC or DESC.
			'order_by'          => 'last_updated', // Column to order by.
			'exclude'           => false,           // Array of ids to exclude.
			'in'                => false,           // Array of ids to limit query by (IN).
			'exclude_types'     => false,           // Array of type to exclude.
			'in_types'          => false,           // Array of type to limit query by (IN).
			// phpcs:ignore
			'meta_query'        => false,           // Filter by moderationmeta.
			'date_query'        => false,           // Filter by date.
			'filter_query'      => false,           // Advanced filtering - see BP_Moderation_Query.
			// phpcs:ignore
			'filter'            => false,           // See self::get_filter_sql().
			'display_reporters' => false,           // Whether or not to fetch user data.
			'update_meta_cache' => true,            // Whether or not to update meta cache.
			'count_total'       => true,           // Whether or not to use count_total.
		);

		$r              = bp_parse_args( $args, $defaults );
		$this->pag_arg  = isset( $r['page_arg'] ) ? sanitize_key( $r['page_arg'] ) : false;
		$this->pag_page = bp_sanitize_pagination_arg( $this->pag_arg, $r['page'] );
		$this->pag_num  = bp_sanitize_pagination_arg( 'num', $r['per_page'] );

		$this->moderations = bp_moderation_get(
			array(
				'max'               => $r['max'],
				'user_id'           => $r['user_id'],
				'fields'            => $r['fields'],
				'page'              => $r['page'],
				'per_page'          => $r['per_page'],
				'sort'              => $r['sort'],
				'order_by'          => $r['order_by'],
				'meta_query'        => $r['meta_query'],
				'date_query'        => $r['date_query'],
				'filter_query'      => $r['filter_query'],
				'exclude'           => $r['exclude'],
				'in'                => $r['in'],
				'exclude_types'     => $r['exclude_types'],
				'in_types'          => $r['in_types'],
				'update_meta_cache' => $r['update_meta_cache'],
				'display_reporters' => $r['display_reporters'],
				'count_total'       => $r['count_total'],
			)
		);

		// The total_moderation_count property will be set only if a
		// 'count_total' query has taken place.
		if ( ! is_null( $this->moderations['total'] ) ) {
			if ( ! $r['max'] || $r['max'] >= (int) $this->moderations['total'] ) {
				$this->total_moderation_count = (int) $this->moderations['total'];
			} else {
				$this->total_moderation_count = (int) $r['max'];
			}
		}

		$this->has_more_items = $this->moderations['has_more_items'];

		$this->moderations = $this->moderations['moderations'];

		if ( $r['max'] ) {
			if ( $r['max'] >= count( $this->moderations ) ) {
				$this->moderation_count = count( $this->moderations );
			} else {
				$this->moderation_count = (int) $r['max'];
			}
		} else {
			$this->moderation_count = count( $this->moderations );
		}

		if ( (int) $this->total_moderation_count && (int) $this->pag_num ) {
			$this->pag_links = paginate_links(
				array(
					'base'      => add_query_arg( $this->pag_arg, '%#%' ),
					'format'    => '',
					'total'     => ceil( (int) $this->total_moderation_count / (int) $this->pag_num ),
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
	 * Whether there are moderation items available in the loop.
	 *
	 * @since BuddyBoss 1.5.6
	 * @return bool True if there are items in the loop, otherwise false.
	 * @see   bp_has_moderation()
	 */
	public function has_moderation() {
		if ( $this->moderation_count ) {
			return true;
		}

		return false;
	}

	/**
	 * Whether there are moderation items left in the loop to iterate over.
	 * This method is used by {@link bp_moderation()} as part of the while loop
	 * that controls iteration inside the moderation loop, eg:
	 *     while ( bp_moderation() ) { ...
	 *
	 * @since BuddyBoss 1.5.6
	 * @return bool True if there are more moderation items to show,
	 *              otherwise false.
	 * @see   bp_moderation()
	 */
	public function user_moderations() {
		if ( ( $this->current_moderation + 1 ) < $this->moderation_count ) {
			return true;
		} elseif ( ( $this->current_moderation + 1 ) === $this->moderation_count ) {

			/**
			 * Fires right before the rewinding of moderation posts.
			 *
			 * @since BuddyBoss 1.5.6
			 */
			do_action( 'moderation_loop_end' );

			// Do some cleaning up after the loop.
			$this->rewind_moderations();
		}

		$this->in_the_loop = false;

		return false;
	}

	/**
	 * Rewind the posts and reset post index.
	 *
	 * @since BuddyBoss 1.5.6
	 */
	public function rewind_moderations() {
		$this->current_moderation = - 1;
		if ( $this->moderation_count > 0 ) {
			$this->moderation = $this->moderations[0];
		}
	}

	/**
	 * Set up the current moderation item inside the loop.
	 * Used by {@link bp_the_moderation()} to set up the current moderation item
	 * data while looping, so that template tags used during that iteration
	 * make reference to the current moderation item.
	 *
	 * @since BuddyBoss 1.5.6
	 * @see   bp_the_moderation()
	 */
	public function the_moderation() {

		$this->in_the_loop = true;
		$this->moderation  = $this->next_moderation();

		if ( is_array( $this->moderation ) ) {
			$this->moderation = (object) $this->moderation;
		}

		// Loop has just started.
		if ( 0 === $this->current_moderation ) {

			/**
			 * Fires if the current moderation item is the first in the loop.
			 *
			 * @since BuddyBoss 1.5.6
			 */
			do_action( 'moderation_loop_start' );
		}
	}

	/**
	 * Set up the next moderation item and iterate index.
	 *
	 * @since BuddyBoss 1.5.6
	 *
	 * @return object The next moderation item to iterate over.
	 */
	public function next_moderation() {
		$this->current_moderation ++;
		$this->moderation = $this->moderations[ $this->current_moderation ];

		return $this->moderation;
	}
}
