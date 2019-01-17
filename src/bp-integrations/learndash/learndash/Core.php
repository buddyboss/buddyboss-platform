<?php

namespace Buddyboss\LearndashIntegration\Learndash;

class Core
{
	public function __construct()
	{
		add_action('bp_ld_sync/init', [$this, 'registerHooks']);
	}

	public function registerHooks()
	{

	}
}
