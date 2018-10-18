<?php

class BP_Admin_Setting_Credit extends BP_Admin_Setting_tab {

	public function initialize() {
		$this->tab_name         = 'Credit';
		$this->tab_slug         = 'bp-credit';
		$this->section_callback = [$this, 'html_content'];
	}

	public function show_tab() {
		return true;
	}

	public function html_content()
	{
		require_once trailingslashit( buddypress()->plugin_dir  . 'bp-core/admin/templates' ) . '/credit-screen.php';
	}
}

return new BP_Admin_Setting_Credit;
