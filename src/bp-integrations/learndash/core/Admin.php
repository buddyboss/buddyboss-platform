<?php

namespace Buddyboss\LearndashIntegration\Core;

class Admin
{
	public function __construct()
	{
		add_action('bp_ld_sync/depencencies_failed', [$this, 'registerDependencyNotices']);
		add_action('bp_ld_sync/requirements_failed', [$this, 'registerRequirementNotices']);
	}

	public function registerDependencyNotices()
	{
		add_action('admin_notices',  [$this, 'printDependencyAdminNotice']);
	}

	public function registerRequirementNotices()
	{
		add_action('admin_notices',  [$this, 'printRequirementAdminNotice']);
	}

	public function printDependencyAdminNotice()
	{
		$missingDepencencies = bp_ld_sync()->dependencies->getMissingDepencencies();

		$message = sprintf(
			_n(
				'%s is not initilized, the following required plugins are missing: %s',
				'%s is not initilized, the following required plugin is missing: %s',
				count($missingDepencencies),
				'buddyboss'
			),
			'<b>' . bp_ld_sync()->pluginName . '</b>',
			'<b>' . implode(',', $missingDepencencies) . '</b>'
		);

		printf('<div class="notice notice-error">%s</div>', wpautop($message));
	}

	public function printRequirementAdminNotice()
	{
		foreach (bp_ld_sync()->requirements->getMissingRequirements() as $requirement) {
			printf('<div class="notice notice-error">%s</div>', wpautop($requirement['error']));
		}
	}
}
