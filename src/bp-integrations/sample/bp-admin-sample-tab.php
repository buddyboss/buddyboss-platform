<?php

class BP_Admin_Integration_Sample extends BP_Admin_Integration_tab {

	public function initialize() {
		$this->tab_order             = 999;
		$this->intro_template        = $this->root_path . '/templates/admin/integration-tab-intro.php';
	}

	public function settings_save() {
		// var_dump( $_POST );
	}

	public function register_fields() {
		$this->add_section(
			'sample-section',
			__( 'Section Heading', 'buddyboss' )
		);
	}
}
