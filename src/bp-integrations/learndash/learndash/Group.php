<?php

namespace Buddyboss\LearndashIntegration\Learndash;

use WP_Query;

class Group
{
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
