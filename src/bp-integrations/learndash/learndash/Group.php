<?php
/**
 * @todo add description
 * 
 * @package BuddyBoss\LearnDash
 * @since BuddyBoss 1.0.0
 */ 

namespace Buddyboss\LearndashIntegration\Learndash;

use WP_Query;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * @todo add title/description
 * 
 * @since BuddyBoss 1.0.0
 */
class Group
{
	/**
	 * @todo add title/description
	 *
	 * @since BuddyBoss 1.0.0
	 */
	public function getUnassociatedGroups($include = null)
	{
		$meta_query = [
			'relation' => 'OR',
			[
				'key'   => '_sync_group_id',
				'value' => [0, ''],
			],
			[
				'key'     => '_sync_group_id',
				'compare' => 'NOT EXISTS',
			],
		];

		if ($include) {
			$meta_query[] = [
				'key'   => '_sync_group_id',
				'value' => is_array($include) ? $include : [$include]
			];
		}

		return (new WP_Query([
			'post_type'      => 'groups',
			'posts_per_page' => -1,
			'orderby'        => 'name',
			'order'          => 'asc',
			'meta_query'     => [$meta_query],
		]))->posts;
	}

}
