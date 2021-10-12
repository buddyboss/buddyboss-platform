<?php
/**
 * BuddyBoss LearnDash integration group class.
 *
 * @package BuddyBoss\LearnDash
 * @since BuddyBoss 1.0.0
 */

namespace Buddyboss\LearndashIntegration\Buddypress;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Class for all gruops related functions
 *
 * @since BuddyBoss 1.0.0
 */
class Group {

	/**
	 * Get groups that's not associated to ld
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

		return groups_get_groups(
		/*
		 * Added show_hidden For show all the hidden group also in Associated Social Group
		 */
			array(
				'orderby'    => 'name',
				'order'      => 'asc',
				'show_hidden'=> true,
				'meta_query' => array( $meta_query ),
				'per_page'   => - 1,
			)
		)['groups'];
	}

}
