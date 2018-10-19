<?php

class BP_Admin_Integration_Eventscalendar extends BP_Admin_Integration_tab {

	public function initialize() {
		$this->tab_label       = __( 'The Events Calendar', 'buddyboss' );
		$this->tab_name        = 'bp-eventscalendar';
		$this->required_plugin = 'the-events-calendar/the-events-calendar.php';
		$this->intro_template  = buddypress()->plugin_dir . '/bp-integrations/eventscalendar/admin/templates/tab-intro.php';
	}

}

return new BP_Admin_Integration_Eventscalendar;
