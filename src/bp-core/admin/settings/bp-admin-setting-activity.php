<?php

class BP_Admin_Setting_Activity extends BP_Admin_Setting_tab {

	public function initialize() {
		$this->tab_name      = 'Activity';
		$this->tab_slug      = 'bp-activity';
		$this->section_name  = 'bp_activity';
		$this->section_label = __( 'Activity Settings', 'buddyboss' );
	}

	protected function is_active() {
		return bp_is_active( 'activity' );
	}

	public function register_fields() {
		// Activity commenting on post and comments.
		$this->add_field( 'bp-disable-blogforum-comments', __( 'Post Comments', 'buddyboss' ), 'bp_admin_setting_callback_blogforum_comments', 'bp_admin_setting_callback_blogforum_comments' );

		// Allow subscriptions setting.
		$this->add_field( '_bp_enable_heartbeat_refresh', __( 'Activity auto-refresh', 'buddyboss' ), 'bp_admin_setting_callback_heartbeat', 'intval' );

		// Allow subscriptions setting.
		if ( is_plugin_active( 'akismet/akismet.php' ) && defined( 'AKISMET_VERSION' ) ) {
			$this->add_field( '_bp_enable_akismet', __( 'Akismet', 'buddyboss' ), 'bp_admin_setting_callback_activity_akismet', 'intval' );
		}
	}

	public function bp_admin_setting_callback_blogforum_comments() {
		$this->checkbox('bp-disable-blogforum-comments', __( 'Allow activity feed commenting on posts and comments', 'buddyboss' ), 'bp_disable_blogforum_comments');
	}

	public function bp_admin_setting_callback_heartbeat() {
	?>
		<input id="_bp_enable_heartbeat_refresh" name="_bp_enable_heartbeat_refresh" type="checkbox" value="1" <?php checked( bp_is_activity_heartbeat_active( true ) ); ?> />
		<label for="_bp_enable_heartbeat_refresh"><?php _e( 'Automatically check for new items while viewing the activity feed', 'buddyboss' ); ?></label>
	<?php
	}

	public function bp_admin_setting_callback_activity_akismet() {
	?>
		<input id="_bp_enable_akismet" name="_bp_enable_akismet" type="checkbox" value="1" <?php checked( bp_is_akismet_active( true ) ); ?> />
		<label for="_bp_enable_akismet"><?php _e( 'Allow Akismet to scan for activity feed spam', 'buddyboss' ); ?></label>
	<?php
	}
}

return new BP_Admin_Setting_Activity;
