<?php

class BP_Admin_Integration_Learndash extends BP_Admin_Integration_tab {

	public function initialize() {
		$this->tab_label       = __( 'Learndash', 'buddyboss' );
		$this->tab_name        = 'bp-learndash';
		$this->required_plugin = 'sfwd-lms/sfwd_lms.php';
		$this->intro_template  = buddypress()->plugin_dir . '/bp-integrations/learndash/admin/templates/tab-intro.php';
	}

}

return new BP_Admin_Integration_Learndash;
