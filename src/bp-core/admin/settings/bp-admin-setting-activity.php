<?php
/**
 * Add admin Activity settings page in Dashboard->BuddyBoss->Settings
 *
 * @package BuddyBoss\Core
 *
 * @since BuddyBoss 1.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Main activity settings class.
 *
 * @since BuddyBoss 1.0.0
 */
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

		$enable_custom_post_type_feed = isset( $_POST['bp-enable-custom-post-type-feed'] ) && '0' !== $_POST['bp-enable-custom-post-type-feed'];

		if ( $enable_custom_post_type_feed ) {

			// Flag for activate the blogs component
			$is_blog_component_active = false;

			// Get all active custom post type.
			$post_types = get_post_types( [ 'public' => true ] );

			foreach ( $post_types as $cpt ) {
				// Exclude all the custom post type which is already in BuddyPress Activity support.
				if ( in_array( $cpt,
					[ 'forum', 'topic', 'reply', 'page', 'attachment', 'bp-group-type', 'bp-member-type' ] ) ) {
					continue;
				}

				$enable_blog_feeds = isset( $_POST["bp-feed-custom-post-type-$cpt"] );

				if ( $enable_blog_feeds ) {
					$is_blog_component_active = true;
				}

			}

			if ( $is_blog_component_active ) {
				$active_components['blogs'] = '1';
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

		} else {

			unset( $active_components['blogs'] );

			// Save settings and upgrade schema.
			require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
			require_once( $bp->plugin_dir . '/bp-core/admin/bp-core-admin-schema.php' );

			$bp->active_components = $active_components;
			bp_core_install( $bp->active_components );
			bp_core_add_page_mappings( $bp->active_components );
			bp_update_option( 'bp-active-components', $bp->active_components );

		}
	}

	public function register_fields() {
		$this->add_section( 'bp_activity', __( 'Activity Settings', 'buddyboss' ) );

		// Blog Feeds Option (will sync with "blogs" component)
//		$this->add_checkbox_field( 'bp-enable-blog-feeds', __( 'Blog Posts', 'buddyboss' ), [
//			'input_text' => __( 'Automatically publish new blog posts into the activity feed', 'buddyboss' )
//		] );

		// Activity commenting on post and comments.
		$this->add_field( 'bp-disable-blogforum-comments', __( 'Post Comments', 'buddyboss' ), 'bp_admin_setting_callback_blogforum_comments', 'bp_admin_sanitize_callback_blogforum_comments' );

		// Allow subscriptions setting.
		$this->add_field( '_bp_enable_heartbeat_refresh', __( 'Activity auto-refresh', 'buddyboss' ), 'bp_admin_setting_callback_heartbeat', 'intval' );

		// Allow follow.
		$this->add_field( '_bp_enable_activity_follow', __( 'Follow', 'buddyboss' ), 'bp_admin_setting_callback_enable_activity_follow', 'intval' );

		// Allow like.
		$this->add_field( '_bp_enable_activity_like', __( 'Likes', 'buddyboss' ), 'bp_admin_setting_callback_enable_activity_like', 'intval' );

		// Allow subscriptions setting.
		if ( is_plugin_active( 'akismet/akismet.php' ) && defined( 'AKISMET_VERSION' ) ) {
			// $this->add_field( '_bp_enable_akismet', __( 'Akismet', 'buddyboss' ), 'bp_admin_setting_callback_activity_akismet', 'intval' );
		}

		$this->add_section( 'bp_custom_post_type', __( 'Custom Post Types', 'buddyboss' ) );

		// Blog Feeds Option (will sync with "blogs" component)
		$this->add_checkbox_field( 'bp-enable-custom-post-type-feed', __( 'Custom Post Types', 'buddyboss' ), [
			'input_text' => __( 'Automatically publish new custom post types into the activity stream', 'buddyboss' )
		] );

		// Get all active custom post type.
		$post_types = get_post_types( [ 'public' => true ] );

		foreach ( $post_types as $post_type ) {
			// Exclude all the custom post type which is already in BuddyPress Activity support.
			if ( in_array( $post_type, [ 'forum', 'topic', 'reply', 'page', 'attachment', 'bp-group-type', 'bp-member-type' ] ) ) {
				continue;
			}

			$fields = [];

			$fields['args'] = [
					'post_type' => $post_type,
					'class'     => 'bp-custom-post-type-child-field'
			];

			// create field for each of custom post type.
			$this->add_field( "bp-feed-custom-post-type-$post_type", '&#65279;', 'bp_feed_settings_callback_post_type', 'intval', $fields['args'] );
		}

	}


}

return new BP_Admin_Setting_Activity;
