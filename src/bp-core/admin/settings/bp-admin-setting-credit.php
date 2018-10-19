<?php

class BP_Admin_Setting_Credit extends BP_Admin_Setting_tab {

	public function initialize() {
		$this->tab_label = __( 'Credit', 'buddyboss' );
		$this->tab_name  = 'bp-credit';
	}

	public function is_tab_visible() {
		return true;
	}

	public function form_html()
	{
		require_once trailingslashit( buddypress()->plugin_dir  . 'bp-core/admin/templates' ) . '/credit-screen.php';
	}
}

return new BP_Admin_Setting_Credit;
