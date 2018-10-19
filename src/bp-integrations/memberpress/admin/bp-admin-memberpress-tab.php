<?php

class BP_Admin_Integration_Memberpress extends BP_Admin_Integration_tab {

	public function initialize() {
		$this->tab_label       = __( 'Memberpress', 'buddyboss' );
		$this->tab_name        = 'bp-memberpress';
		$this->required_plugin = 'memberpress/memberpress.php';
		$this->intro_template  = buddypress()->plugin_dir . '/bp-integrations/memberpress/admin/templates/tab-intro.php';
	}

}

return new BP_Admin_Integration_Memberpress;
