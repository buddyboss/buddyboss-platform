<?php

class BP_Admin_Setting_Search extends BP_Admin_Setting_tab {

	public function initialize() {

		$this->tab_label = __( 'Search', 'buddyboss' );
		$this->tab_name  = 'bp-search';
		$this->tab_order = 40;
	}

	public function is_active() {
		return bp_is_active( 'search' );
	}

	public function register_fields() {
		$this->add_section( 'bp_search', __( 'Network Search Settings', 'buddyboss' ) );
	}
}

return new BP_Admin_Setting_Search;
