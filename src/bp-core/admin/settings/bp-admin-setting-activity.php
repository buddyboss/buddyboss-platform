<?php

class BP_Admin_Setting_Activity extends BP_Admin_Setting_tab {

	public function initialize() {
		$this->tab_label = __( 'Activity', 'buddyboss' );
		$this->tab_name  = 'bp-activity';
		$this->tab_order = 15;
	}

	public function is_active() {
		return bp_is_active( 'activity' );
	}

	public function settings_save() {
		parent::settings_save();

		$bp = buddypress();
		$active_components = $bp->active_components;
		$enable_blog_feeds = isset( $_POST['bp-enable-blog-feeds'] );

		if ( $enable_blog_feeds ) {
			$active_components['blogs'] = 1;
		} else {
			unset( $active_components['blogs'] );
		}

		// Save settings and upgrade schema.
		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
		require_once( $bp->plugin_dir . '/bp-core/admin/bp-core-admin-schema.php' );

		$bp->active_components = $active_components;
		bp_core_install( $bp->active_components );
		bp_core_add_page_mappings( $bp->active_components );
		bp_update_option( 'bp-active-components', $bp->active_components );
	}

	public function register_fields() {
		$this->add_section( 'bp_activity', __( 'Activity Settings', 'buddyboss' ) );

		// Blog Feeds Option (will sync with "blogs" component)
		$this->add_checkbox_field( 'bp-enable-blog-feeds', __( 'Blog Posts', 'buddyboss' ), [
			'input_text' => __( 'Automatically publish new blog posts into the activity feed', 'buddyboss' )
		] );

		// Activity commenting on post and comments.
		$this->add_field( 'bp-disable-blogforum-comments', __( 'Post Comments', 'buddyboss' ), 'bp_admin_setting_callback_blogforum_comments', 'bp_admin_sanitize_callback_blogforum_comments' );

		// Allow subscriptions setting.
		$this->add_field( '_bp_enable_heartbeat_refresh', __( 'Activity auto-refresh', 'buddyboss' ), 'bp_admin_setting_callback_heartbeat', 'intval' );

		// Allow subscriptions setting.
		if ( is_plugin_active( 'akismet/akismet.php' ) && defined( 'AKISMET_VERSION' ) ) {
			var_dump('asdf');
			// $this->add_field( '_bp_enable_akismet', __( 'Akismet', 'buddyboss' ), 'bp_admin_setting_callback_activity_akismet', 'intval' );
		}
	}
}

return new BP_Admin_Setting_Activity;
