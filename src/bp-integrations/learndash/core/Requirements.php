<?php
/**
 * BuddyBoss LearnDash integration Requirements class.
 *
 * @package BuddyBoss\LearnDash
 * @since BuddyBoss 1.0.0
 */

namespace Buddyboss\LearndashIntegration\Core;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Class handle requirement checking
 *
 * @since BuddyBoss 1.0.0
 */
class Requirements
{
	protected $requirements = [];
	protected $checkedRequirements = [];

	/**
	 * Constructor
	 *
	 * @since BuddyBoss 1.0.0
	 */
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

	/**
	 * Check if each requirement is satisfied
	 *
	 * @since BuddyBoss 1.0.0
	 */
	public function checkForRequirements()
	{
		foreach ($this->requirements as $name => $data) {
			$callback = call_user_func_array('call_user_func_array', $data['callback']);
			if (! $callback ) {
				continue;
			}

			$this->checkedRequirements[] = $name;
		}

		$success = count($this->requirements) == count($this->checkedRequirements);
		do_action($success? 'bp_ld_sync/requirements_checked' : 'bp_ld_sync/requirements_failed', $this);
	}

	/**
	 * Get the missing requirements
	 *
	 * @since BuddyBoss 1.0.0
	 */
	public function getMissingRequirements()
	{
		return array_diff_key($this->requirements, array_flip($this->checkedRequirements));
	}

	/**
	 * Get the passed requirements
	 *
	 * @since BuddyBoss 1.0.0
	 */
	public function getLoadedRequirements()
	{
		return array_intersect_key($this->requirements, array_flip($this->checkedRequirements));
	}
}
