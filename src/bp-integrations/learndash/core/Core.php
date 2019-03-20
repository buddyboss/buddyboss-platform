<?php
/**
 * BuddyBoss LearnDash integration Core class.
 *
 * @package BuddyBoss\LearnDash
 * @since BuddyBoss 1.0.0
 */

namespace Buddyboss\LearndashIntegration\Core;

use Buddyboss\LearndashIntegration\Buddypress\Core as BuddypressCore;
use Buddyboss\LearndashIntegration\Core\Admin;
use Buddyboss\LearndashIntegration\Core\Dependencies;
use Buddyboss\LearndashIntegration\Core\Requirements;
use Buddyboss\LearndashIntegration\Core\Settings;
use Buddyboss\LearndashIntegration\Learndash\Core as LearndashCore;;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * COre file of the plugin
 *
 * @since BuddyBoss 1.0.0
 */
class Core
{
	/**
	 * Constructor
	 *
	 * @since BuddyBoss 1.0.0
	 */
	public function __construct()
	{
		$this->registerAutoloader();

		$this->admin        = new Admin;
		$this->dependencies = new Dependencies;
		$this->requirements = new Requirements;
		$this->buddypress   = new BuddypressCore;
		$this->learndash    = new LearndashCore;
		$this->settings     = new Settings;

		$this->pluginName = __('BuddyBoss LearnDash', 'buddyboss');

		add_action('bp_ld_sync/requirements_checked', [$this, 'init']);
	}

	/**
	 * Sub action once dependencies and requirements are checked
	 *
	 * @since BuddyBoss 1.0.0
	 */
	public function init()
	{
		do_action('bp_ld_sync/init');
	}

    /**
	 * Get absolute path from the integration folder
	 *
	 * @since BuddyBoss 1.0.0
	 */
	public function path($path = '')
    {
        return bp_learndash_path(trim($path, '/\\'));
    }

    /**
	 * Get url path from the integration folder
	 *
	 * @since BuddyBoss 1.0.0
	 */
	public function url($uri = '')
    {
        return bp_learndash_url(trim($uri, '/\\'));
    }

    /**
	 * Load template from the integration folder
	 *
	 * @since BuddyBoss 1.0.0
	 */
	public function template($path = '')
    {
        return bp_learndash_path('templates/' . trim($path, '/\\'));
    }

    /**
	 * Get the request from $_POST, $_GET, or $_REQUEST with default fallback
	 *
	 * @since BuddyBoss 1.0.0
	 */
	public function getRequest($key = '*', $default = null, $type = null) {
		if ($type) {
			return $key == '*'? $$type : (isset($$type[$key])? $$type[$key] : $default);
		}

		$merged = array_merge($_GET, $_POST, $_REQUEST);
		return $key == '*'? $merged : (isset($merged[$key])? $merged[$key] : $default);
	}

	/**
	 * Check if the given request isset
	 *
	 * @since BuddyBoss 1.0.0
	 */
	public function isRequestExists( $key, $default = null, $type = null ) {
		if ($type) {
			return isset($$type[$key]);
		}

		$merged = array_merge($_GET, $_POST, $_REQUEST);
		return isset($merged[$key]);
	}

	/**
	 * Register psr4 autoloader manually
	 *
	 * @since BuddyBoss 1.0.0
	 */
	public function registerAutoloader()
	{
		spl_autoload_register(function($class) {
			$psr4 = [
				'Buddyboss\LearndashIntegration\Core'       => 'core',
				'Buddyboss\LearndashIntegration\Library'    => 'library',
				'Buddyboss\LearndashIntegration\Buddypress' => 'buddypress',
				'Buddyboss\LearndashIntegration\Buddypress\Generators' => 'buddypress/generators',
				'Buddyboss\LearndashIntegration\Buddypress\Components' => 'buddypress/components',
				'Buddyboss\LearndashIntegration\Learndash'  => 'learndash',
			];

			$segments  = explode('\\', $class);
			$className = array_pop($segments);
			$namespace = implode('\\', $segments);

		    if (array_key_exists($namespace, $psr4)) {
		    	require_once $this->path("/{$psr4[$namespace]}/{$className}.php");
		    }
		});
	}
}


global $bp_ld_sync;
$bp_ld_sync = new Core;
