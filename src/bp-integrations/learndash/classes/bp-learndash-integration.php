<?php

class BP_Learndash_Integration extends BP_Integration {

	public function __construct() {
		$this->start(
			'bp-learndash',
			__( 'Learndash', 'buddyboss' ),
			buddypress()->integration_dir . '/learndash',
			[
				'required_plugin' => 'sfwd-lms/sfwd_lms.php'
			]
		);
	}

	public function setup_admin_integartion_tab() {
		require_once trailingslashit( $this->path ) . 'admin/bp-admin-learndash-tab.php';

		new BP_Learndash_Admin_Integration_Tab($this->id, $this->name, [
			'root_path' => $this->path,
			'required_plugin' => $this->required_plugin,
		]);
	}

	public function includes( $includes = array() ) {
		parent::includes([
			'groups-sync/loader.php'
		]);
	}
}
