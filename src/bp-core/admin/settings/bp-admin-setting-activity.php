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
		$this->add_field( 'bp-disable-blogforum-comments', __( 'Post Comments', 'buddyboss' ), 'bp_admin_setting_callback_blogforum_comments', [$this, 'bp_admin_setting_callback_activity_akismet'] );

		// Allow subscriptions setting.
		$this->add_field( '_bp_enable_heartbeat_refresh', __( 'Activity auto-refresh', 'buddyboss' ), 'bp_admin_setting_callback_heartbeat', 'intval' );

		// Allow subscriptions setting.
		if ( is_plugin_active( 'akismet/akismet.php' ) && defined( 'AKISMET_VERSION' ) ) {
			$this->add_field( '_bp_enable_akismet', __( 'Akismet', 'buddyboss' ), 'bp_admin_setting_callback_activity_akismet', 'intval' );
		}
	}

	/**
	 * Allow activity comments on posts and comments.
	 *
	 * @since BuddyPress 1.6.0
	 */
	public function bp_admin_setting_callback_blogforum_comments() {
		$this->checkbox('bp-disable-blogforum-comments', __( 'Allow activity feed commenting on posts and comments', 'buddyboss' ), 'bp_disable_blogforum_comments');
	}

	/**
	 * Allow Heartbeat to refresh activity feed.
	 *
	 * @since BuddyPress 2.0.0
	 */
	public function bp_admin_setting_callback_heartbeat() {
	?>
		<input id="_bp_enable_heartbeat_refresh" name="_bp_enable_heartbeat_refresh" type="checkbox" value="1" <?php checked( bp_is_activity_heartbeat_active( true ) ); ?> />
		<label for="_bp_enable_heartbeat_refresh"><?php _e( 'Automatically check for new items while viewing the activity feed', 'buddyboss' ); ?></label>
	<?php
	}

	/**
	 * Allow Akismet setting field.
	 *
	 * @since BuddyPress 1.6.0
	 *
	 */
	public function bp_admin_setting_callback_activity_akismet() {
	?>
		<input id="_bp_enable_akismet" name="_bp_enable_akismet" type="checkbox" value="1" <?php checked( bp_is_akismet_active( true ) ); ?> />
		<label for="_bp_enable_akismet"><?php _e( 'Allow Akismet to scan for activity feed spam', 'buddyboss' ); ?></label>
	<?php
	}

	/**
	 * Sanitization for bp-disable-blogforum-comments setting.
	 *
	 * In the UI, a checkbox asks whether you'd like to *enable* post/comment activity comments. For
	 * legacy reasons, the option that we store is 1 if these comments are *disabled*. So we use this
	 * function to flip the boolean before saving the intval.
	 *
	 * @since BuddyPress 1.6.0
	 *
	 * @param bool $value Whether or not to sanitize.
	 * @return bool
	 */
	public function bp_admin_sanitize_callback_blogforum_comments( $value = false ) {
		return $value ? 0 : 1;
	}
}

return new BP_Admin_Setting_Activity;
