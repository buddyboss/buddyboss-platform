<?php

class BP_Admin_Setting_Activity extends BP_Admin_Setting_tab {

	public function initialize() {
		$this->tab_label = __( 'Activity', 'buddyboss' );
		$this->tab_name  = 'bp-activity';

		$this->register_fields();
	}

	public function is_active() {
		return bp_is_active( 'activity' );
	}

	public function register_fields() {
		$this->add_section( 'bp_activity', __( 'Activity Settings', 'buddyboss' ) );

		// Activity commenting on post and comments.
		$this->add_field( 'bp-disable-blogforum-comments', __( 'Post Comments', 'buddyboss' ), 'bp_admin_setting_callback_blogforum_comments', 'bp_admin_setting_callback_activity_akismet' );

		// Allow subscriptions setting.
		$this->add_field( '_bp_enable_heartbeat_refresh', __( 'Activity auto-refresh', 'buddyboss' ), 'bp_admin_setting_callback_heartbeat', 'intval' );

		// Allow subscriptions setting.
		if ( is_plugin_active( 'akismet/akismet.php' ) && defined( 'AKISMET_VERSION' ) ) {
			$this->add_field( '_bp_enable_akismet', __( 'Akismet', 'buddyboss' ), 'bp_admin_setting_callback_activity_akismet', 'intval' );
		}
	}
}

return new BP_Admin_Setting_Activity;
