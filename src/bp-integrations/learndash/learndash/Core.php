<?php

namespace Buddyboss\LearndashIntegration\Learndash;

use Buddyboss\LearndashIntegration\Learndash\Sync;
use Buddyboss\LearndashIntegration\Learndash\Hooks;

class Core
{
	public function __construct()
	{
		$this->sync    = new Sync;
		$this->hooks   = new Hooks;

		add_action('bp_ld_sync/init', [$this, 'init']);
	}

	public function init()
	{

	}
}
