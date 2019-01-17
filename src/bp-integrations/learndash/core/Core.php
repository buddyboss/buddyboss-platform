<?php

namespace Buddyboss\LearndashIntegration\Core;

use Buddyboss\LearndashIntegration\Buddypress\Core as BuddypressCore;
use Buddyboss\LearndashIntegration\Core\Admin;
use Buddyboss\LearndashIntegration\Core\Dependencies;
use Buddyboss\LearndashIntegration\Core\Requirements;
use Buddyboss\LearndashIntegration\Core\Settings;
use Buddyboss\LearndashIntegration\Learndash\Core as LearndashCore;;

class Core
{
	public function __construct()
	{
		$this->registerAutoloader();

		$this->admin        = new Admin;
		$this->dependencies = new Dependencies;
		$this->requirements = new Requirements;
		$this->buddypress   = new BuddypressCore;
		$this->learndash    = new LearndashCore;
		$this->settings     = new Settings;

		$this->pluginName = __('BuddyPress Learndash', 'buddyboss');

		add_action('bp_ld_sync/requirements_checked', [$this, 'init']);
	}

	public function init()
	{
		do_action('bp_ld_sync/init');
	}

    public function path($path = '')
    {
        return bp_learndash_path(trim($path, '/\\'));
    }

    public function url($uri = '')
    {
        return bp_learndash_url(trim($uri, '/\\'));
    }

    public function template($path = '')
    {
        return bp_learndash_path('templates/' . trim($path, '/\\'));
    }

    public function getRequest($key = '*', $default = null, $type = null) {
		if ($type) {
			return $key == '*'? $$type : (isset($$type[$key])? $$type[$key] : $default);
		}

		$merged = array_merge($_GET, $_POST, $_REQUEST);
		return $key == '*'? $merged : (isset($merged[$key])? $merged[$key] : $default);
	}

	public function isRequestExists( $key, $default = null, $type = null ) {
		if ($type) {
			return isset($$type[$key]);
		}

		$merged = array_merge($_GET, $_POST, $_REQUEST);
		return isset($merged[$key]);
	}

	public function registerAutoloader()
	{
		spl_autoload_register(function($class) {
			$psr4 = [
				'Buddyboss\LearndashIntegration\Core'       => 'core',
				'Buddyboss\LearndashIntegration\Buddypress' => 'buddypress',
				'Buddyboss\LearndashIntegration\Buddypress\Generators' => 'buddypress/generators',
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
