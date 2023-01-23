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
		$this->tab_order = 40;
	}

	public function is_active() {
		return bp_is_active( 'activity' );
	}

	public function settings_save() {
		parent::settings_save();

		$bp                = buddypress();
		$active_components = $bp->active_components;

		// Flag for activate the blogs component only if any CPT OR blog posts have enabled the activity feed.
		$is_blog_component_active = false;

		// Get all active custom post type.
		$post_types = get_post_types( array( 'public' => true ) );

		foreach ( $post_types as $cpt ) {
			// Exclude all the custom post type which is already in BuddyPress Activity support.
			if ( in_array(
				$cpt,
				array( 'forum', 'topic', 'reply', 'page', 'attachment', 'bp-group-type', 'bp-member-type' )
			) ) {
				continue;
			}

			$enable_blog_feeds = isset( $_POST[ "bp-feed-custom-post-type-$cpt" ] );

			if ( $enable_blog_feeds ) {
				$is_blog_component_active = true;
			}
		}

		// Add blogs component to $active_components list.
		if ( $is_blog_component_active ) {
			$active_components['blogs'] = '1';
		} else {
			unset( $active_components['blogs'] );
		}

		// Save settings and upgrade schema.
		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		require_once $bp->plugin_dir . '/bp-core/admin/bp-core-admin-schema.php';

		$bp->active_components = $active_components;
		bp_core_install( $bp->active_components );

		// Mapping the component pages in page settings except registration pages.
		bp_core_add_page_mappings( $bp->active_components, 'keep', false );
		bp_update_option( 'bp-active-components', $bp->active_components );

	}

	public function register_fields() {
		$this->add_section( 'bp_activity', __( 'Activity Settings', 'buddyboss' ), '', 'bp_activity_settings_tutorial' );

		// Allow Activity edit setting.
		$this->add_field( '_bp_enable_activity_edit', __( 'Edit Activity', 'buddyboss' ), 'bp_admin_setting_callback_enable_activity_edit', 'intval' );
		$this->add_field( '_bp_activity_edit_time', __( 'Edit Activity Time Limit', 'buddyboss' ), '__return_true', 'intval', array(
			'class' => 'hidden',
		) );

		// Allow subscriptions setting.
		$this->add_field( '_bp_enable_heartbeat_refresh', __( 'Activity auto-refresh', 'buddyboss' ), 'bp_admin_setting_callback_heartbeat', 'intval' );

		// Allow auto-load.
		$this->add_field( '_bp_enable_activity_autoload', __( 'Activity auto-load', 'buddyboss' ), 'bp_admin_setting_callback_enable_activity_autoload', 'intval' );

		// Allow scopes/tabs.
		$this->add_field( '_bp_enable_activity_tabs', __( 'Activity tabs', 'buddyboss' ), 'bp_admin_setting_callback_enable_activity_tabs', 'intval' );

		// Allow follow.
		$this->add_field( '_bp_enable_activity_follow', __( 'Follow', 'buddyboss' ), 'bp_admin_setting_callback_enable_activity_follow', 'intval' );

		// Allow like.
		$this->add_field( '_bp_enable_activity_like', __( 'Likes', 'buddyboss' ), 'bp_admin_setting_callback_enable_activity_like', 'intval' );

		// Allow link preview.
		$this->add_field( '_bp_enable_activity_link_preview', __( 'Link Previews', 'buddyboss' ), 'bp_admin_setting_callback_enable_activity_link_preview', 'intval' );

		//Relevant Activity Feeds
		$this->add_field( '_bp_enable_relevant_feed', __( 'Relevant Activity', 'buddyboss' ), 'bp_admin_setting_callback_enable_relevant_feed', 'intval' );

		// Allow subscriptions setting.
		if ( is_plugin_active( 'akismet/akismet.php' ) && defined( 'AKISMET_VERSION' ) ) {
			// $this->add_field( '_bp_enable_akismet', __( 'Akismet', 'buddyboss' ), 'bp_admin_setting_callback_activity_akismet', 'intval' );
		}

		$this->add_section( 'bp_custom_post_type', __( 'Posts in Activity Feeds', 'buddyboss' ) );

		// create field for default Platform activity feed.
		$get_default_platform_activity_types = bp_platform_default_activity_types();
		$is_first                            = true;
		foreach ( $get_default_platform_activity_types as $type ) {
			$name          = $type['activity_name'];
			$class         = ( true === $is_first ) ? 'child-no-padding-first' : 'child-no-padding';
			$type['class'] = $class;
			$this->add_field( "bp-feed-platform-$name", ( true === $is_first ) ? __( 'BuddyBoss Platform', 'buddyboss' ) : '', 'bp_feed_settings_callback_platform', 'intval', $type );
			$is_first = false;
		}

		// flag for adding conditional CSS class.
		$count       = 0;
		$description = 0;

		foreach ( bb_feed_post_types() as $key => $post_type ) {

			$fields = array();

			$fields['args'] = array(
				'post_type'   => $post_type,
				'description' => false,
			);

			$post_type_option_name = bb_post_type_feed_option_name( $post_type );
			$comment_option_name   = bb_post_type_feed_comment_option_name( $post_type );

			if ( 'post' === $post_type ) {
				$fields['args']['class'] = 'child-no-padding-first';
				// create field for each of custom post type.
				$this->add_field( $post_type_option_name, __( 'WordPress', 'buddyboss' ), 'bp_feed_settings_callback_post_type', 'intval', $fields['args'] );

				$fields['args']['class'] = 'child-no-padding bp-display-none';
				// Activity commenting on post and comments.
				$this->add_field( $comment_option_name, '&#65279;', 'bb_feed_settings_callback_post_type_comments', 'intval', $fields['args'] );

			} else {
				if ( 0 === $description ) {
					$fields['args']['description'] = true;
					$description                   = 1;
				}
				if ( 0 === $count ) {
					$fields['args']['class'] = 'child-no-padding-first';
					// create field for each of custom post type.
					$this->add_field( $post_type_option_name, __( 'Custom Post Types', 'buddyboss' ), 'bp_feed_settings_callback_post_type', 'intval', $fields['args'] );

					$fields['args']['class'] = 'child-no-padding bp-display-none child-custom-post-type';
					$this->add_field( $comment_option_name, '', 'bb_feed_settings_callback_post_type_comments', 'intval', $fields['args'] );
				} else {

					$fields['args']['class'] = 'child-no-padding';
					// create field for each of custom post type.
					$this->add_field( $post_type_option_name, '&#65279;', 'bp_feed_settings_callback_post_type', 'intval', $fields['args'] );

					$fields['args']['class'] = 'child-no-padding bp-display-none child-custom-post-type';
					$this->add_field( $comment_option_name, '', 'bb_feed_settings_callback_post_type_comments', 'intval', $fields['args'] );
				}
				$count ++;
			}
		}

		/**
		 * Fires to register Activity tab settings fields and section.
		 *
		 * @since BuddyBoss 1.2.6
		 *
		 * @param Object $this BP_Admin_Setting_Activity.
		 */
		do_action( 'bp_admin_setting_activity_register_fields', $this );
	}
}

return new BP_Admin_Setting_Activity();
