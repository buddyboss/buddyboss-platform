<?php

namespace Buddyboss\LearndashIntegration\Core;

class Dependencies
{
	protected $dependencies = [];
	protected $loadedDependencies = [];

	public function __construct()
	{
		$this->dependencies = [
			'bp_init'        => __('BuddyPress', 'buddyboss'),
			'learndash_init' => __('Learndash LMS', 'buddyboss')
		];

		$this->registerHooks();
	}

	public function registerHooks()
	{
		foreach ($this->dependencies as $hook => $__) {
			add_action($hook, [$this, 'dependencyLoaded']);
		}

		add_action('init', [$this, 'appendDependencyChecker']);
	}

	public function dependencyLoaded()
	{
		$this->loadedDependencies[] = current_filter();
	}

	public function appendDependencyChecker()
	{
		global $wp_filter;

		$callbackKeys = array_keys($wp_filter['init']->callbacks);
		$lastCallbackPriority = $callbackKeys[count($callbackKeys) - 1];

		add_action('init', [$this, 'dependencyChecker'], $lastCallbackPriority);
	}

	public function dependencyChecker()
	{
		$success = count($this->dependencies) == count($this->loadedDependencies);
		do_action($success? 'bp_ld_sync/depencencies_loaded' : 'bp_ld_sync/depencencies_failed', $this);
	}

	public function getMissingDepencencies()
	{
		return array_diff_key($this->dependencies, array_flip($this->loadedDependencies));
	}

	public function getLoadedDepencencies()
	{
		return array_intersect_key($this->dependencies, array_flip($this->loadedDependencies));
	}
}
