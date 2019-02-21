<?php

namespace Buddyboss\LearndashIntegration\Core;

class Requirements
{
	protected $requirements = [];
	protected $checkedRequirements = [];

	public function __construct()
	{
		$this->requirements = [
			'bp_group_component' => [
				'callback' => ['bp_is_active', ['groups']],
				'error' => sprintf(
		            __('BuddyBoss Platform component %s needs to be enabled.', 'buddyboss'),
		            '<b>' . __('Social Groups', 'buddyboss') . '</b>'
		        )
		    ]
		];

		add_action('bp_ld_sync/depencencies_loaded', [$this, 'checkForRequirements']);
	}

	public function checkForRequirements()
	{
		foreach ($this->requirements as $name => $data) {
			if (! call_user_func_array('call_user_func_array', $data['callback'])) {
				continue;
			}

			$this->checkedRequirements[] = $name;
		}

		$success = count($this->requirements) == count($this->checkedRequirements);
		do_action($success? 'bp_ld_sync/requirements_checked' : 'bp_ld_sync/requirements_failed', $this);
	}

	public function getMissingRequirements()
	{
		return array_diff_key($this->requirements, array_flip($this->checkedRequirements));
	}

	public function getLoadedRequirements()
	{
		return array_intersect_key($this->requirements, array_flip($this->checkedRequirements));
	}
}
