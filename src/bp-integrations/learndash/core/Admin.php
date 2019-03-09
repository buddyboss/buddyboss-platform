<?php
/**
 * @todo add description
 * 
 * @package BuddyBoss\LearnDash
 * @since BuddyBoss 1.0.0
 */ 

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

namespace Buddyboss\LearndashIntegration\Core;

/**
 * 
 * 
 * @since BuddyBoss 1.0.0
 */
class Admin
{
	/**
	 * 
	 *
	 * @since BuddyBoss 1.0.0
	 */
	public function __construct()
	{
		add_action('bp_ld_sync/depencencies_failed', [$this, 'registerDependencyNotices']);
		add_action('bp_ld_sync/requirements_failed', [$this, 'registerRequirementNotices']);
	}

	/**
	 * 
	 *
	 * @since BuddyBoss 1.0.0
	 */
	public function registerDependencyNotices()
	{
		add_action('admin_notices',  [$this, 'printDependencyAdminNotice']);
	}

	/**
	 * 
	 *
	 * @since BuddyBoss 1.0.0
	 */
	public function registerRequirementNotices()
	{
		add_action('admin_notices',  [$this, 'printRequirementAdminNotice']);
	}

	/**
	 * 
	 *
	 * @since BuddyBoss 1.0.0
	 */
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

	/**
	 * 
	 *
	 * @since BuddyBoss 1.0.0
	 */
	public function printRequirementAdminNotice()
	{
		foreach (bp_ld_sync()->requirements->getMissingRequirements() as $requirement) {
			printf('<div class="notice notice-error">%s</div>', wpautop($requirement['error']));
		}
	}
}
