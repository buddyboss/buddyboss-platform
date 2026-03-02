<?php
/**
 * BuddyBoss LearnDash integration Group class.
 *
 * @package BuddyBoss\LearnDash
 * @since BuddyBoss 1.0.0
 */

namespace Buddyboss\LearndashIntegration\Learndash;

use WP_Query;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Core class for the learndash settings
 *
 * @since BuddyBoss 1.0.0
 */
class Group {

	/**
	 * Get groups that's not associated to bp
	 *
	 * @since BuddyBoss 1.0.0
	 */
	public function getUnassociatedGroups( $include = null ) {
		$meta_query = array(
			'relation' => 'OR',
			array(
				'key'   => '_sync_group_id',
				'value' => array( 0, '' ),
			),
			array(
				'key'     => '_sync_group_id',
				'compare' => 'NOT EXISTS',
			),
		);

		if ( $include ) {
			$meta_query[] = array(
				'key'   => '_sync_group_id',
				'value' => is_array( $include ) ? $include : array( $include ),
			);
		}

		return ( new WP_Query(
			array(
				'post_type'      => 'groups',
				'posts_per_page' => -1,
				'orderby'        => 'name',
				'order'          => 'asc',
				'meta_query'     => array( $meta_query ),
			)
		) )->posts;
	}

}
