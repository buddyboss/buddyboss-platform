<?php

namespace Buddyboss\LearndashIntegration\Core;

class Settings
{
	protected $settings = [];
	protected $reportSettingKey = 'learndash_settings_buddypress_groups_report';
	protected $pageSlug = 'bp-ld-sync';

	public function __construct()
	{
		$this->maybeInsertDefaultSettings();
	}

	protected function maybeInsertDefaultSettings()
	{
		if (! get_option($this->reportSettingKey)) {
			$reportDefault = [
				'enable_group_reports' => false,
				'report_access'        => ['admin', 'moderator']
			];

			$groups_report = get_option('learndash_settings_buddypress_groups_reports') ?: $reportDefault;
			delete_option('learndash_settings_buddypress_groups_reports');
			update_option($this->reportSettingKey, $groups_report);
		}
	}
}
