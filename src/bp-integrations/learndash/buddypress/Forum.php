<?php

namespace Buddyboss\LearndashIntegration\Buddypress;

use Buddyboss\LearndashIntegration\Buddypress\Generators\ForumsReportsGenerator;

class Forum
{
	public function __construct()
	{
		add_action('bp_ld_sync/init', [$this, 'init']);
	}

	public function init()
	{
		if (! function_exists('bbpress')) {
			return;
		}

		add_filter('bp_ld_sync/reports_generators', [$this, 'addForumReportGenerator']);
	}

	public function addForumReportGenerator($generators)
	{
		if (! $this->groupHasForum()) {
			return $generators;
		}

		$generators['forum'] = [
			'name'  => __('Forums', 'buddyboss'),
			'class' => ForumsReportsGenerator::class
		];

		return $generators;
	}

	public function groupHasForum()
	{
		$bpGroupId = bp_ld_sync()->getRequest('group') ?: groups_get_current_group()->id;
		return !! bbp_get_group_forum_ids($bpGroupId);
	}
}
