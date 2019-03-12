<?php
/**
 * @todo add description
 * 
 * @package BuddyBoss\LearnDash
 * @since BuddyBoss 1.0.0
 */ 

namespace Buddyboss\LearndashIntegration\Core;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * @todo add title/description
 * 
 * @since BuddyBoss 1.0.0
 */
class Dependencies
{
	protected $dependencies = [];
	protected $loadedDependencies = [];

	/**
	 * @todo add title/description
	 *
	 * @since BuddyBoss 1.0.0
	 */
	public function __construct()
	{
		$this->dependencies = [
			'bp_init'        => __('BuddyBoss Platform', 'buddyboss'),
			'learndash_init' => __('Learndash LMS', 'buddyboss')
		];

		$this->registerHooks();
	}

	/**
	 * @todo add title/description
	 *
	 * @since BuddyBoss 1.0.0
	 */
	public function registerHooks()
	{
		foreach ($this->dependencies as $hook => $__) {
			add_action($hook, [$this, 'dependencyLoaded']);
		}

		add_action('init', [$this, 'appendDependencyChecker']);
	}

	/**
	 * @todo add title/description
	 *
	 * @since BuddyBoss 1.0.0
	 */
	public function dependencyLoaded()
	{
		$this->loadedDependencies[] = current_filter();
	}

	/**
	 * @todo add title/description
	 *
	 * @since BuddyBoss 1.0.0
	 */
	public function appendDependencyChecker()
	{
		global $wp_filter;

		$callbackKeys = array_keys($wp_filter['init']->callbacks);
		$lastCallbackPriority = $callbackKeys[count($callbackKeys) - 1];

		add_action('init', [$this, 'dependencyChecker'], $lastCallbackPriority);
	}

	/**
	 * @todo add title/description
	 *
	 * @since BuddyBoss 1.0.0
	 */
	public function dependencyChecker()
	{
		$success = count($this->dependencies) == count($this->loadedDependencies);
		do_action($success? 'bp_ld_sync/depencencies_loaded' : 'bp_ld_sync/depencencies_failed', $this);
	}

	/**
	 * @todo add title/description
	 *
	 * @since BuddyBoss 1.0.0
	 */
	public function getMissingDepencencies()
	{
		return array_diff_key($this->dependencies, array_flip($this->loadedDependencies));
	}

	/**
	 * @todo add title/description
	 *
	 * @since BuddyBoss 1.0.0
	 */
	public function getLoadedDepencencies()
	{
		return array_intersect_key($this->dependencies, array_flip($this->loadedDependencies));
	}
}
