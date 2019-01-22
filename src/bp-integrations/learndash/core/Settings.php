<?php

namespace Buddyboss\LearndashIntegration\Core;

use Buddyboss\LearndashIntegration\Library\ValueLoader;

class Settings
{
	protected $loader;
	protected $options = [];
	protected $optionKey = 'bp_ld_sync_settings';

	public function __construct()
	{
		$this->installDefaultSettings();
		$this->loader = new ValueLoader($this->options);
	}

	public function getName($key = '')
	{
		$name = $this->optionKey;

		foreach (array_filter(explode('.', $key)) as $peice) {
			$name .= "[{$peice}]";
		}

		return $name;
	}

	public function get($key = null, $default = null)
	{
		return $this->loader->get($key, $default);
	}

	public function set($key = null, $value = null)
	{
		$this->loader->set($key, $value);
		return $this;
	}

	public function update()
	{
		bp_update_option($this->optionKey, $this->loader->get());
		return $this;
	}

	protected function installDefaultSettings()
	{
		$default = [
			'buddypress' => [
				'enabled'                 => false,
				'show_in_bp_create'       => true,
				'show_in_bp_manage'       => true,
				'tab_access'              => 'anyone',
				'default_auto_sync'       => true,
				'delete_ld_on_delete'     => false,
				'default_user_on_ban'     => true,
				'default_admin_sync_to'   => 'admin',
				'default_mod_sync_to'     => 'admin',
				'default_user_sync_to'    => 'user',
			],
			'learndash' => [
				'enabled'                  => false,
				'default_auto_sync'        => true,
				'default_bp_privacy'       => 'private',
				'default_bp_invite_status' => 'admin',
				'default_admin_sync_to'    => 'admin',
				'default_user_sync_to'     => 'user',
				'delete_bp_on_delete'      => false,
			],
			'reports' => [
				'enabled' => false,
				'access'  => ['admin', 'mod'],
				'cache_time' => 60
			],
		];

		if (! $options = get_option($this->optionKey)) {
			$options = $default;
			bp_update_option($this->optionKey, $default);
		}

		$this->options = $this->parseArgsDeep($options, $default);
	}

	protected function parseArgsDeep(&$a, $b)
	{
		$a = (array) $a;
		$b = (array) $b;
		$result = $b;

		foreach ($a as $k => &$v) {
			if (is_array($v) && isset($result[$k])) {
				$result[$k] = $this->parseArgsDeep($v, $result[$k]);
			} else {
				$result[$k] = $v;
			}
		}

		return $result;
	}
}
