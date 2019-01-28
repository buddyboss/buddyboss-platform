<?php

namespace Buddyboss\LearndashIntegration\Learndash;

use Buddyboss\LearndashIntegration\Learndash\Sync;
use Buddyboss\LearndashIntegration\Learndash\Hooks;
use Buddyboss\LearndashIntegration\Learndash\Admin;
use Buddyboss\LearndashIntegration\Learndash\Group;

class Core
{
	public function __construct()
	{
		$this->sync    = new Sync;
		$this->hooks   = new Hooks;
		$this->admin   = new Admin;
		$this->group   = new Group;

		add_action('bp_ld_sync/init', [$this, 'init']);
	}

	public function init()
	{

	}
}
