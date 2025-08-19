<?php
/**
 * Readylaunch class.
 *
 * @since   BuddyBoss 2.9.00
 * @package BuddyBoss\Core
 */

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'BB_Readylaunch' ) ) {
	/**
	 * BuddyBoss Readylaunch object.
	 *
	 * @since BuddyBoss 2.9.00
	 */
	#[\AllowDynamicProperties]
	class BB_Readylaunch {

		/**
		 * The single instance of the class.
		 *
		 * @since  BuddyBoss 2.9.00
		 *
		 * @access private
		 * @var self
		 */
		private static $instance = null;

		/**
		 * ReadyLaunch Settings.
		 *
		 * @since BuddyBoss 2.9.00
		 * @var array
		 */
		public $settings = array();

		/**
		 * Get the instance of this class.
		 *
		 * @since BuddyBoss 2.9.00
		 *
		 * @return BB_Readylaunch|null
		 */
		public static function instance() {
			if ( null === self::$instance ) {
				$class_name     = __CLASS__;
				self::$instance = new $class_name();
			}

			return self::$instance;
		}

		/**
		 * Constructor method.
		 *
		 * @since BuddyBoss 2.9.00
		 */
		public function __construct() {
			$enabled = bb_is_readylaunch_enabled();

			// Add ReadyLaunch settings to the platform settings API.
			add_filter( 'bp_rest_platform_settings', array( $this, 'bb_rest_readylaunch_platform_settings' ), 10, 1 );
			add_filter( 'bb_telemetry_platform_options', array( $this, 'bb_rl_telemetry_platform_options' ), 10, 1 );

			//Localise the script for admin.
			add_filter( 'bb_admin_localize_script', array( $this, 'bb_rl_admin_localize_script' ), 10, 2 );

			if ( ! $enabled ) {
				return;
			}

			// Register the ReadyLaunch menu.
			$this->bb_register_readylaunch_menus();

			add_action( 'bb_blocks_init', array( $this, 'bb_rl_register_blocks' ), 20 );
			add_filter( 'bp_search_js_settings', array( $this, 'bb_rl_filter_search_js_settings' ) );
			add_action( 'admin_enqueue_scripts', array( $this, 'bb_admin_enqueue_scripts' ), 1 );

			$admin_enabled = $this->bb_is_readylaunch_admin_enabled();
			if ( $admin_enabled ) {
				add_filter( 'bb_document_icon_class', array( $this, 'bb_readylaunch_document_icon_class' ) );
			}

			$this->load_template_stack();
			$this->load_login_registration_integration();
			$this->load_hooks();

			// Added support for Forums integration.
			if ( bp_is_active( 'forums' ) ) {
				add_filter( 'bbp_use_template_canvas', '__return_false' );
				add_filter( 'bbp_get_page_by_path', array( $this, 'bb_rl_forums_get_page_by_path' ), 99, 1 );
			}

			$enabled_for_page = $this->bb_is_readylaunch_enabled_for_page();
			if ( $enabled_for_page ) {
				// Specific to the ReadyLaunch pages.
			}

			add_action( 'bp_admin_enqueue_scripts', array( $this, 'bb_rl_admin_enqueue_scripts' ), 1 );

			// LearnDash integration.
			add_filter( 'bp_is_sidebar_enabled_for_courses', array( $this, 'bb_is_sidebar_enabled_for_courses' ) );

			// Common LMS stylesheets.
			add_action( 'wp_enqueue_scripts', array( $this, 'bb_readylaunch_lms_enqueue_styles' ), 10 );

			if ( $enabled_for_page && class_exists( 'memberpress\courses\helpers\Courses' ) ) {
				// MemberPress Courses integration.
				add_action( 'wp_enqueue_scripts', array( $this, 'bb_readylaunch_meprlms_enqueue_styles' ), 10 );

				require_once buddypress()->compatibility_dir . '/class-bb-readylaunch-memberpress-courses-helper.php';
				BB_Readylaunch_Memberpress_Courses_Helper::instance();
			}
		}

		/**
		 * Register the ReadyLaunch telemetry data.
		 *
		 * @since BuddyBoss 2.9.00
		 * @param array $option_array The array of telemetry options.
		 *
		 * @return array The modified array of telemetry options.
		 */
		public function bb_rl_telemetry_platform_options( $option_array ) {
			$op_options = array( 'bb_rl_enabled' );
			if ( bb_is_readylaunch_enabled() ) {
				$op_options[] = 'bb_rl_theme_mode';
				$op_options[] = 'bb_rl_enabled_pages';
				$op_options[] = 'bb_rl_activity_sidebars';
				$op_options[] = 'bb_rl_member_profile_sidebars';
				$op_options[] = 'bb_rl_groups_sidebars';
				$op_options[] = 'bb_rl_side_menu';
				$op_options[] = 'bb_rl_custom_links';
			}

			$option_array = array_merge( $option_array, $op_options );

			return $option_array;
		}

		/**
		 * Load template stack for ReadyLaunch.
		 *
		 * @since BuddyBoss 2.9.00
		 */
		protected function load_template_stack() {
			add_filter(
				'template_include',
				array(
					$this,
					'override_page_templates',
				),
				PHP_INT_MAX,
				1
			); // High priority, so we have the last say here.

			// Remove BuddyPress template locations.
			remove_filter( 'bp_get_template_stack', 'bp_add_template_stack_locations' );
			remove_filter( 'bbp_get_template_stack', 'bbp_add_template_stack_locations' );

			// Add Readylaunch template locations.
			add_filter( 'bp_get_template_stack', array( $this, 'add_template_stack' ), PHP_INT_MAX );
			add_filter( 'bbp_get_template_stack', array( $this, 'add_forum_template_stack' ), PHP_INT_MAX );
		}

		/**
		 * Load component integration for ReadyLaunch.
		 *
		 * @since BuddyBoss 2.9.00
		 */
		protected function load_component_integration() {
			if ( bp_is_active( 'activity' ) ) {
				BB_Activity_Readylaunch::instance();
			}

			if (
				bp_is_active( 'groups' ) &&
				(
					bp_is_groups_directory() ||
					bp_is_group_single() ||
					bp_is_group_create()
				)
			) {
				BB_Group_Readylaunch::instance();
			}

			if ( bp_is_messages_component() ) {
				BB_Messages_Readylaunch::instance();
			}
		}

		/**
		 * Load login registration integration for ReadyLaunch.
		 *
		 * @since BuddyBoss 2.9.00
		 */
		protected function load_login_registration_integration() {
			// Remove BuddyBoss Theme login hooks.
			add_action(
				'login_init',
				function () {
					remove_action( 'login_message', 'change_register_message' );
					remove_action( 'login_message', 'signin_login_message' );
					remove_action( 'login_head', 'buddyboss_login_scripts', 150 );
					remove_action( 'login_head', 'login_custom_head', 150 );
					remove_action( 'login_form', 'login_custom_form' );
					remove_action( 'init', 'buddyboss_theme_login_load' );
					remove_action( 'login_enqueue_scripts', 'login_enqueue_scripts' );
					remove_action( 'login_message', 'login_message' );
					remove_filter( 'login_headertext', 'login_headertext' );
					remove_filter( 'login_headerurl', 'login_headerurl' );
				},
				20
			);

			// Dequeue BuddyBoss Theme login styles.
			add_action(
				'login_enqueue_scripts',
				function () {
					wp_dequeue_style( 'buddyboss-theme-login' );
					wp_deregister_style( 'buddyboss-theme-login' );
				},
				20
			);

			// Login page.
			add_action( 'login_enqueue_scripts', array( $this, 'bb_rl_login_enqueue_scripts' ), 999 );
			add_action( 'login_head', array( $this, 'bb_rl_login_header' ), 999 );
			add_filter( 'login_headerurl', array( $this, 'bb_rl_login_header_url' ) );
			add_action( 'login_footer', array( $this, 'bb_rl_login_footer' ), 999 );
			add_filter( 'login_message', array( $this, 'bb_rl_signin_login_message' ) );
			add_action( 'login_form', array( $this, 'bb_rl_login_custom_form' ) );

			add_action( 'login_form_retrievepassword', array( $this, 'bb_rl_overwrite_login_email_field_label_hook' ) );
			add_action( 'login_form_lostpassword', array( $this, 'bb_rl_overwrite_login_email_field_label_hook' ) );
			add_action( 'login_form_login', array( $this, 'bb_rl_overwrite_login_email_field_label_hook' ) );

			add_action( 'wp_login_errors', array( $this, 'bb_rl_wp_login_errors' ) );
		}

		/**
		 * Load hooks for ReadyLaunch.
		 *
		 * @since BuddyBoss 2.9.00
		 */
		protected function load_hooks() {

			add_action( 'bp_init', array( $this, 'bb_rl_init' ), 9 );

			// Add Dynamic colours.
			add_action( 'wp_head', array( $this, 'bb_rl_dynamic_colors' ) );

			add_action( 'wp_ajax_bb_fetch_header_messages', array( $this, 'bb_fetch_header_messages' ) );
			add_action( 'wp_ajax_bb_fetch_header_notifications', array( $this, 'bb_fetch_header_notifications' ) );
			add_action( 'wp_ajax_bb_mark_notification_read', array( $this, 'bb_mark_notification_read' ) );

			add_filter( 'bp_core_avatar_full_width', array( $this, 'bb_rl_avatar_full_width' ) );
			add_filter( 'bp_core_avatar_full_height', array( $this, 'bb_rl_avatar_full_height' ) );
			add_filter( 'bp_core_avatar_thumb_width', array( $this, 'bb_rl_avatar_thumb_width' ) );
			add_filter( 'bp_core_avatar_thumb_height', array( $this, 'bb_rl_avatar_thumb_height' ) );

			add_filter( 'bp_document_svg_icon', array( $this, 'bb_rl_document_svg_icon' ), 10, 2 );
			add_filter( 'heartbeat_received', array( $this, 'bb_heartbeat_unread_notifications' ), 12, 2 );
			add_filter( 'heartbeat_nopriv_received', array( $this, 'bb_heartbeat_unread_notifications' ), 12, 2 );

			// Directory filters.
			add_filter( 'bp_nouveau_get_filter_label', array( $this, 'bb_nouveau_get_filter_label_hook' ), 10, 2 );
			add_filter( 'bp_nouveau_get_filter_id', array( $this, 'bb_rl_prefix_key' ) );
			add_filter( 'bp_nouveau_get_nav_id', array( $this, 'bb_rl_prefix_key' ) );

			add_filter( 'bp_nouveau_register_scripts', array( $this, 'bb_rl_nouveau_register_scripts' ), 99, 1 );

			add_filter( 'wp_ajax_bb_rl_invite_form', array( $this, 'bb_rl_invite_form_callback' ) );

			if ( bp_is_active( 'messages' ) ) {
				add_filter( 'bp_get_send_message_button_args', array( $this, 'bb_rl_override_send_message_button_text' ) );
			}

			add_filter( 'bb_member_directories_get_profile_actions', array( $this, 'bb_rl_member_directories_get_profile_actions' ), 10, 3 );

			// override default images for the avatar image.
			add_filter( 'bb_attachments_get_default_profile_group_avatar_image', array( $this, 'bb_rl_group_default_group_avatar_image' ), 999, 2 );

			add_filter( 'bp_core_register_common_scripts', array( $this, 'bb_rl_register_common_scripts' ), 999, 1 );
			add_filter( 'bp_core_register_common_styles', array( $this, 'bb_rl_register_common_styles' ), 999, 1 );

			add_filter( 'bp_nouveau_get_document_description_html', array( $this, 'bb_rl_modify_document_description_html' ), 10 );
			add_filter( 'bp_nouveau_get_media_description_html', array( $this, 'bb_rl_modify_document_description_html' ), 10 );
			add_filter( 'bp_nouveau_get_video_description_html', array( $this, 'bb_rl_modify_document_description_html' ), 10 );
			add_filter( 'bp_core_get_js_strings', array( $this, 'bb_rl_modify_js_strings' ), 20, 1 );

			// Update notification item action links.
			add_filter( 'bp_get_the_notification_mark_unread_link', array( $this, 'bb_rl_notifications_mark_unread_link' ), 1, 1 );
			add_filter( 'bp_get_the_notification_mark_read_link', array( $this, 'bb_rl_notifications_mark_read_link' ), 1, 1 );
			add_filter( 'bp_get_the_notification_delete_link', array( $this, 'bb_rl_notifications_delete_link' ), 1, 1 );

			add_filter( 'bp_nouveau_get_nav_link_text', array( $this, 'bb_rl_get_nav_link_text' ), 10, 3 );

			if ( bp_is_active( 'search' ) ) {
				add_filter( 'bp_search_results_group_start_html', array( $this, 'bb_rl_modify_search_results_group_start_html' ), 11 );
				add_filter( 'bp_search_results_group_end_html', array( $this, 'bb_rl_modify_search_results_group_start_html' ), 11 );
			}

			add_filter( 'bp_activity_get_visibility_levels', array( $this, 'bb_rl_modify_visibility_levels' ), 10 );
			add_filter( 'bp_document_get_visibility_levels', array( $this, 'bb_rl_modify_visibility_levels' ), 10 );
			add_filter( 'bp_media_get_visibility_levels', array( $this, 'bb_rl_modify_visibility_levels' ), 10 );
			add_filter( 'bp_video_get_visibility_levels', array( $this, 'bb_rl_modify_visibility_levels' ), 10 );

			if ( bp_is_active( 'xprofile' ) ) {
				add_filter( 'bp_xprofile_get_visibility_levels', array( $this, 'bb_rl_modify_xprofile_visibility_levels' ) );
			}

			if ( bp_is_active( 'friends' ) ) {
				add_filter( 'bp_get_add_friend_button', array( $this, 'bb_rl_modify_add_friend_button' ) );
			}

			add_action( 'bp_template_title', array( $this, 'bb_rl_remove_sso_template_title' ), 0 );

			add_filter( 'bp_nouveau_get_notifications_filters', array( $this, 'bb_rl_modify_notifications_filters' ) );

			add_filter( 'bp_moderation_user_report_button', array( $this, 'bb_rl_modify_member_report_button' ), 10 );

			if ( bb_enable_content_counts() ) {
				add_filter( 'bp_nouveau_nav_has_count', array( $this, 'bb_rl_modify_nav_get_count' ), 10, 2 );
				add_filter( 'bp_nouveau_get_nav_count', array( $this, 'bb_rl_modify_nav_get_count' ), 10, 2 );
			}

			add_filter( 'bp_nouveau_get_submit_button', array( $this, 'bb_rl_modify_bp_nouveau_get_submit_button' ) );
		}

		/**
		 * Required load for BuddyBoss ReadyLaunch.
		 *
		 * @since BuddyBoss 2.9.00
		 *
		 * @return void
		 */
		protected function bb_rl_required_load() {
			// Dequeue theme/plugins styles.
			add_action( 'wp_enqueue_scripts', array( $this, 'bb_dequeue_styles' ), PHP_INT_MAX );
			add_action( 'wp_enqueue_scripts', array( $this, 'bb_enqueue_scripts' ), 1 );
			add_action( 'wp_head', array( $this, 'bb_rl_start_buffering' ), 0 );
			add_action( 'wp_footer', array( $this, 'bb_rl_end_buffering' ), 999 );
			add_filter( 'body_class', array( $this, 'bb_rl_theme_body_classes' ) );
			add_filter( 'script_loader_src', array( $this, 'bb_rl_script_loader_src' ), PHP_INT_MAX, 2 );
			add_action( 'bb_rl_get_template_part_content', array( $this, 'bb_rl_get_template_part_content' ), 10, 1 );
		}

		/**
		 * Initialise the hooks on BuddyBoss init.
		 *
		 * @since BuddyBoss 2.9.00
		 */
		public function bb_rl_init() {

			$this->load_component_integration();

			// Remove page content actions.
			remove_action( 'bp_before_directory_members_page', 'bp_members_directory_page_content' );
			remove_action( 'bp_before_directory_media', 'bp_media_directory_page_content' );
			remove_action( 'bp_before_directory_document', 'bb_document_directory_page_content' );

			// Remove profile search form on members directory.
			remove_action( 'bp_before_directory_members', 'bp_profile_search_show_form' );

			// Removed for update notification item action links.
			remove_filter( 'bp_get_the_notification_mark_unread_link', 'bp_nouveau_notifications_mark_unread_link', 10, 1 );
			remove_filter( 'bp_get_the_notification_mark_read_link', 'bp_nouveau_notifications_mark_read_link', 10, 1 );
			remove_filter( 'bp_get_the_notification_delete_link', 'bp_nouveau_notifications_delete_link', 10, 1 );

			// Remove BuddyPressHelper search filter at a later priority to ensure it's already added.
			if ( bp_is_active( 'search' ) ) {
				add_action( 'bp_init', array( $this, 'bb_rl_remove_buddypress_helper_search_filter' ), 20 );
			}

			if ( bp_is_active( 'forums' ) ) {
				add_filter( 'bb_nouveau_get_activity_inner_buttons', array( $this, 'bb_rl_activity_inner_buttons' ), 20, 2 );
				add_filter( 'bbp_ajax_reply', array( $this, 'bb_rl_ajax_reply' ) );
				add_action( 'bbp_new_reply_pre_extras', array( $this, 'bb_rl_new_reply_pre_extras' ), 99 );
				add_action( 'bbp_new_reply_post_extras', array( $this, 'bb_rl_new_reply_post_extras' ), 99 );
				add_action( 'wp_ajax_quick_reply_ajax', array( $this, 'bb_rl_activity_quick_reply' ) );

				if ( class_exists( 'BBPressHelper' ) ) {
					remove_filter( 'bb_nouveau_get_activity_inner_buttons', array( 'BBPressHelper', 'theme_activity_entry_buttons' ), 20, 2 );
					remove_filter( 'bbp_ajax_reply', array( 'BBPressHelper', 'ajax_reply' ) );
					remove_action( 'bbp_new_reply_pre_extras', array( 'BBPressHelper', 'new_reply_pre_extras' ), 99 );
					remove_action( 'bbp_new_reply_post_extras', array( 'BBPressHelper', 'new_reply_post_extras' ), 99 );
					remove_action( 'wp_ajax_quick_reply_ajax', array( 'BBPressHelper', 'activity_quick_reply_ajax_cb' ) );
				}

				add_filter( 'bbp_after_get_topic_stick_link_parse_args', array( $this, 'bb_rl_modify_get_topic_stick_link_parse_args' ), 10 );
			}

			add_filter( 'paginate_links_output', array( $this, 'bb_rl_filter_paginate_links_output' ), 10, 2 );

			if ( class_exists( 'SFWD_LMS' ) ) {
				require_once buddypress()->compatibility_dir . '/class-bb-readylaunch-learndash-helper.php';
			}
		}

		/**
		 * Remove BuddyPressHelper search filter at a later priority.
		 *
		 * @since BuddyBoss 2.9.30
		 */
		public function bb_rl_remove_buddypress_helper_search_filter() {
			if ( function_exists( 'buddyboss_theme' ) && buddyboss_theme()->buddypress_helper() ) {
				$buddypress_helper = buddyboss_theme()->buddypress_helper();
				if ( $buddypress_helper && is_object( $buddypress_helper ) ) {
					remove_filter( 'bp_search_results_group_start_html', array( $buddypress_helper, 'filter_bp_search_results_group_start_html' ), 10, 2 );
				}
			}
		}

		/**
		 * Mark notification as unread link.
		 *
		 * @since BuddyBoss 2.9.00
		 *
		 * @param string $link The link.
		 *
		 * @return string
		 */
		public function bb_rl_notifications_mark_unread_link( $link = '' ) {
			return $this->bb_rl_notifications_link(
				$link,
				__( 'Mark as unread', 'buddyboss' ),
				'bb-icons-rl-eye-slash',
				__( 'Mark as unread', 'buddyboss' )
			);
		}

		/**
		 * Mark the notification as read link.
		 *
		 * @since BuddyBoss 2.9.00
		 *
		 * @param string $link The link.
		 *
		 * @return string
		 */
		public function bb_rl_notifications_mark_read_link( $link = '' ) {
			return $this->bb_rl_notifications_link(
				$link,
				__( 'Mark as read', 'buddyboss' ),
				'bb-icons-rl-check',
				__( 'Mark as read', 'buddyboss' )
			);
		}

		/**
		 * Delete notification link.
		 *
		 * @since BuddyBoss 2.9.00
		 *
		 * @param string $link The link.
		 *
		 * @return string
		 */
		public function bb_rl_notifications_delete_link( $link = '' ) {
			return $this->bb_rl_notifications_link(
				$link,
				__( 'Delete notification', 'buddyboss' ),
				'bb-icons-rl-trash',
				__( 'Delete notification', 'buddyboss' )
			);
		}

		/**
		 * Generate notification link.
		 *
		 * @since BuddyBoss 2.9.00
		 *
		 * @param string $link       The link.
		 * @param string $bp_tooltip The tooltip.
		 * @param string $icon       The icon.
		 * @param string $label      The label.
		 *
		 * @return string
		 */
		private function bb_rl_notifications_link( $link = '', $bp_tooltip = '', $icon = '', $label = '' ) {
			$link = str_replace( 'class="', 'class="bp-tooltip ', $link );
			preg_match( '/<a\s[^>]*>(.*)<\/a>/siU', $link, $match );

			if ( ! empty( $match[0] ) && ! empty( $match[1] ) && ! empty( $icon ) && ! empty( $bp_tooltip ) ) {
				$link = str_replace(
					'>' . $match[1] . '<',
					sprintf(
						' data-bp-tooltip-pos="up" data-bp-tooltip="%1$s"><span class="%2$s" aria-hidden="true"></span><span class="bb_rl_label">%3$s</span><',
						esc_attr( $bp_tooltip ),
						sanitize_html_class( $icon ),
						( ! empty( $label ) ? $label : $match[1] )
					),
					$match[0]
				);
			}

			return $link;
		}

		/**
		 * Get the order of sidebar items.
		 *
		 * @since BuddyBoss 2.9.00
		 *
		 * @return array The ordered array of sidebar items.
		 */
		public function bb_rl_get_sidebar_order() {

			$defaults = array(
				'activity_feed' => array(
					'enabled' => true,
					'order'   => 0,
					'icon'    => 'pulse',
				),
				'members'       => array(
					'enabled' => true,
					'order'   => 1,
					'icon'    => 'users',
				),
				'groups'        => array(
					'enabled' => true,
					'order'   => 2,
					'icon'    => 'users-three',
				),
				'courses'       => array(
					'enabled' => true,
					'order'   => 3,
					'icon'    => 'graduation-cap',
				),
				'forums'        => array(
					'enabled' => true,
					'order'   => 4,
					'icon'    => 'chat-text',
				),
				'messages'      => array(
					'enabled' => false,
					'order'   => 5,
					'icon'    => 'chat-teardrop-text',
				),
				'notifications' => array(
					'enabled' => false,
					'order'   => 6,
					'icon'    => 'bell',
				),
			);

			if ( ! bp_is_active( 'activity' ) ) {
				unset( $defaults['activity_feed'] );
			}
			if ( ! bp_is_active( 'groups' ) ) {
				unset( $defaults['groups'] );
			}
			if ( ! bp_is_active( 'forums' ) ) {
				unset( $defaults['forums'] );
			}
			if ( ! bp_is_active( 'messages' ) ) {
				unset( $defaults['messages'] );
			}
			if ( ! bp_is_active( 'notifications' ) ) {
				unset( $defaults['notifications'] );
			}
			if ( ! $this->bb_is_sidebar_enabled_for_courses() ) {
				unset( $defaults['courses'] );
			}

			$raw_settings = wp_parse_args(
				bp_get_option( 'bb_rl_side_menu', array() ),
				$defaults
			);

			$settings = array_map(
				function ( $item ) {
					return array(
						'enabled' => ! empty( $item['enabled'] ),
						'order'   => isset( $item['order'] ) ? (int) $item['order'] : 0,
						'icon'    => isset( $item['icon'] ) ? $item['icon'] : '',
					);
				},
				$raw_settings
			);

			if ( empty( $settings ) ) {
				return array();
			}

			// Sort items by order.
			uasort(
				$settings,
				function ( $a, $b ) {
					return ( $a['order'] ?? 0 ) - ( $b['order'] ?? 0 );
				}
			);

			// Filter out disabled items.
			$ordered_items = array();
			foreach ( $settings as $key => $item ) {
				if ( ! empty( $item['enabled'] ) ) {
					$is_active = false;
					if ( 'activity_feed' === $key && bp_is_active( 'activity' ) ) {
						$is_active     = true;
						$item['url']   = bp_get_activity_directory_permalink();
						$item['label'] = __( 'News Feed', 'buddyboss' );
					} elseif ( 'members' === $key ) {
						$is_active     = true;
						$item['url']   = bp_get_members_directory_permalink();
						$item['label'] = __( 'Members', 'buddyboss' );
					} elseif ( 'groups' === $key && bp_is_active( 'groups' ) ) {
						$is_active     = true;
						$item['url']   = bp_get_groups_directory_permalink();
						$item['label'] = __( 'Groups', 'buddyboss' );
					} elseif ( 'forums' === $key && bp_is_active( 'forums' ) ) {
						$is_active     = true;
						$item['url']   = bbp_get_forums_url();
						$item['label'] = __( 'Forums', 'buddyboss' );
					} elseif ( 'courses' === $key ) {
						$item['label'] = __( 'Courses', 'buddyboss' );
						$item['url']   = '';
						if ( class_exists( 'SFWD_LMS' ) ) {
							$options = bp_get_option( 'bp_ld_sync_settings', array() );
							if (
								! empty( $options['buddypress']['enabled'] ) ||
								! empty( $options['learndash']['enabled'] )
							) {
								$is_active   = true;
								$item['url'] = get_post_type_archive_link( learndash_get_post_type_slug( 'course' ) );
							}
						} elseif (
							function_exists( 'tutor_utils' ) &&
							function_exists( 'bb_tutorlms_enable' ) &&
							bb_tutorlms_enable()
						) {
							$is_active   = true;
							$item['url'] = get_post_type_archive_link( bb_tutorlms_profile_courses_slug() );
						} elseif (
							class_exists( 'memberpress\courses\helpers\Courses' ) &&
							class_exists( 'memberpress\courses\models\Course' )
						) {
							$is_active   = true;
							$item['url'] = get_post_type_archive_link( memberpress\courses\models\Course::$cpt );
						}
					} elseif ( 'messages' === $key && bp_is_active( 'messages' ) ) {
						$is_active     = true;
						$item['url']   = trailingslashit( bp_loggedin_user_domain() . bp_get_messages_slug() );
						$item['label'] = __( 'Messages', 'buddyboss' );
					} elseif ( 'notifications' === $key && bp_is_active( 'notifications' ) ) {
						$is_active     = true;
						$item['url']   = bp_get_notifications_permalink();
						$item['label'] = __( 'Notifications', 'buddyboss' );
					}
					if ( $is_active ) {
						if ( ! empty( $item['icon'] ) ) {
							$item['icon'] = 'bb-icons-rl-' . $item['icon'];
						} else {
							$item['icon'] = '';
						}

						$ordered_items[ $key ] = $item;
					}
				}
			}

			return $ordered_items;
		}

		/**
		 * Get the template part content for readylaunch.
		 *
		 * @since BuddyBoss 2.9.00
		 */
		public function bb_rl_get_template_part_content() {
			// Initialize LearnDash variables.
			$is_ld_course_archive = false;
			$is_ld_lesson_archive = false;
			$is_ld_topic_archive  = false;
			$is_ld_quiz_archive   = false;
			$is_ld_assignment     = false;
			$is_ld_exam           = false;
			$is_ld_group_archive  = false;
			$is_ld_group_single   = false;

			// Initialize MemberPress variables.
			$is_mp_course_archive    = false;
			$is_mp_course_single     = false;
			$is_mp_lesson_single     = false;
			$is_mp_assignment_single = false;
			$is_mp_quiz_single       = false;

			// Check LearnDash post types.
			if ( function_exists( 'learndash_get_post_type_slug' ) ) {
				$is_ld_course_archive = is_post_type_archive( learndash_get_post_type_slug( 'course' ) );
				$is_ld_topic_archive  = is_post_type_archive( learndash_get_post_type_slug( 'topic' ) );
				$is_ld_lesson_archive = is_post_type_archive( learndash_get_post_type_slug( 'lesson' ) );
				$is_ld_quiz_archive   = is_post_type_archive( learndash_get_post_type_slug( 'quiz' ) );
				$is_ld_assignment     = is_singular( learndash_get_post_type_slug( 'assignment' ) );
				$is_ld_exam           = is_singular( learndash_get_post_type_slug( 'exam' ) );
				$is_ld_group_archive  = is_post_type_archive( learndash_get_post_type_slug( 'group' ) );
				$is_ld_group_single   = is_singular( learndash_get_post_type_slug( 'group' ) );
			}

			// Check for MemberPress courses archive.
			if ( class_exists( 'memberpress\courses\helpers\Courses' ) && memberpress\courses\helpers\Courses::is_course_archive() ) {
				$is_mp_course_archive = true;
			}

			// Check for MemberPress single pages.
			global $post;
			if ( is_single() && ! empty( $post ) && is_a( $post, 'WP_Post' ) ) {
				$post_type = $post->post_type;

				if ( class_exists( 'memberpress\courses\models\Course' ) && memberpress\courses\models\Course::$cpt === $post_type ) {
					$is_mp_course_single = true;
				} elseif ( class_exists( 'memberpress\courses\models\Lesson' ) && memberpress\courses\models\Lesson::$cpt === $post_type ) {
					$is_mp_lesson_single = true;
				} elseif ( class_exists( 'memberpress\assignments\models\Assignment' ) && memberpress\assignments\models\Assignment::$cpt === $post_type ) {
					$is_mp_assignment_single = true;
				} elseif ( class_exists( 'memberpress\quizzes\models\Quiz' ) && memberpress\quizzes\models\Quiz::$cpt === $post_type ) {
					$is_mp_quiz_single = true;
				}
			}

			// Load appropriate template based on page type.
			if ( $is_ld_course_archive ) {
				bp_get_template_part( 'learndash/ld30/course-loop' );
			} elseif ( $is_mp_course_archive ) {
				bp_get_template_part( 'memberpress/courses/archive-mpcs-courses' );
			} elseif ( $is_mp_course_single ) {
				bp_get_template_part( 'memberpress/courses/single-mpcs-course' );
			} elseif ( $is_mp_lesson_single ) {
				bp_get_template_part( 'memberpress/courses/single-mpcs-lesson' );
			} elseif ( $is_mp_assignment_single ) {
				bp_get_template_part( 'memberpress/assignments/single-mpcs-assignment' );
			} elseif ( $is_mp_quiz_single ) {
				bp_get_template_part( 'memberpress/quizzes/single-mpcs-quiz' );
			} elseif (
				$is_ld_topic_archive ||
				$is_ld_lesson_archive ||
				$is_ld_quiz_archive ||
				$is_ld_group_archive ||
				$is_ld_group_single ||
				$this->bb_rl_is_learndash_registration_page() ||
				$this->bb_rl_is_learndash_reset_password_page()
			) {
				$page_class = 'archive';
				if ( $is_ld_group_single ) {
					$page_class = 'single';
				} elseif ( $this->bb_rl_is_learndash_registration_page() ) {
					$page_class = 'learndash-registration';
				} elseif ( $this->bb_rl_is_learndash_reset_password_page() ) {
					$page_class = 'learndash-reset-password';
				}
				bp_get_template_part(
					'learndash/ld30/default',
					null,
					array(
						'page_class' => $page_class,
						'post_type'  => get_post_type(),
					)
				);
			} elseif ( $is_ld_assignment ) {
				bp_get_template_part( 'learndash/ld30/assignment' );
			} elseif ( $is_ld_exam ) {
				bp_get_template_part( 'learndash/ld30/challenge-exam' );
			} else {
				the_content();
			}
		}

		/**
		 * Override full width for avatar in ReadyLaunch.
		 *
		 * @since BuddyBoss 2.9.00
		 *
		 * @return int
		 */
		public function bb_rl_avatar_full_width() {
			return 384;
		}

		/**
		 * Override full height for avatar in ReadyLaunch.
		 *
		 * @since BuddyBoss 2.9.00
		 *
		 * @return int
		 */
		public function bb_rl_avatar_full_height() {
			return 384;
		}

		/**
		 * Override thumb width for avatar in ReadyLaunch.
		 *
		 * @since BuddyBoss 2.9.00
		 *
		 * @return int
		 */
		public function bb_rl_avatar_thumb_width() {
			return 200;
		}

		/**
		 * Override thumb height for avatar in ReadyLaunch.
		 *
		 * @since BuddyBoss 2.9.00
		 *
		 * @return int
		 */
		public function bb_rl_avatar_thumb_height() {
			return 200;
		}

		/**
		 * Check if ReadyLaunch is enabled for the current directory.
		 *
		 * @since BuddyBoss 2.9.00
		 *
		 * @return bool True if ReadyLaunch is enabled, false otherwise.
		 */
		public function bb_is_readylaunch_enabled_for_page() {
			return (
				bp_is_members_directory() ||
				bp_is_video_directory() ||
				bp_is_media_directory() ||
				bp_is_document_directory() ||
				bp_is_activity_directory() ||
				bp_is_groups_directory() ||
				bp_is_group_single() ||
				bp_is_group_activity() ||
				bp_is_group_create() ||
				bp_is_user() ||
				bp_is_single_activity() ||
				bp_is_user_activity() ||
				bp_is_messages_component() ||
				bp_is_current_component( 'video' ) ||
				bp_is_current_component( 'media' ) ||
				is_admin() ||
				wp_doing_ajax() ||
				self::bb_is_network_search() ||
				(
					(
						is_login() ||
						bp_is_register_page()
					) &&
					$this->bb_rl_is_page_enabled_for_integration( 'registration' )
				) ||
				bp_is_activation_page() ||
				$this->bb_rl_is_learndash_page() || // Add check for LearnDash pages.
				$this->bb_rl_is_memberpress_courses_page() // Add check for MemberPress Courses pages.
			);
		}

		/**
		 * Check if the network search is enabled.
		 *
		 * @since BuddyBoss 2.9.00
		 *
		 * @return bool True if the network search is enabled, false otherwise.
		 */
		public static function bb_is_network_search() {
			// phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$bp_search = isset( $_REQUEST['bp_search'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['bp_search'] ) ) : '';
			if (
				bp_is_active( 'search' ) &&
				! empty( $bp_search )
			) {
				return true;
			}

			return false;
		}

		/**
		 * Check if the admin is enabled.
		 *
		 * @since BuddyBoss 2.9.00
		 *
		 * @return bool True if the admin is enabled, false otherwise.
		 */
		private function bb_is_readylaunch_admin_enabled() {
			// phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$page = isset( $_GET['page'] ) ? sanitize_text_field( wp_unslash( $_GET['page'] ) ) : '';

			// phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$tab = isset( $_GET['tab'] ) ? sanitize_text_field( wp_unslash( $_GET['tab'] ) ) : '';

			if (
				(
					is_admin() &&
					! wp_doing_ajax() &&
					! empty( $page ) &&
					'bp-settings' === $page &&
					! empty( $tab ) &&
					'bp-document' === $tab
				)
			) {
				return true;
			}

			return false;
		}

		/**
		 * Register the ReadyLaunch menus.
		 *
		 * @since BuddyBoss 2.9.00
		 */
		public function bb_register_readylaunch_menus() {

			// Register the ReadyLaunch menu locations for the block theme.
			$nav_menu_locations = get_theme_mod( 'nav_menu_locations', array() );
			if ( empty( $nav_menu_locations ) ) {
				register_nav_menus(
					array( 'bb-readylaunch' => __( 'ReadyLaunch', 'buddyboss' ) )
				);
			}

			// Define the menus to create.
			$menus = array(
				'readylaunch' => __( 'ReadyLaunch', 'buddyboss' ),
			);

			foreach ( $menus as $menu_slug => $menu_name ) {
				$check_menu = wp_get_nav_menu_object( $menu_slug );
				if ( ! $check_menu ) {
					wp_create_nav_menu( $menu_name );
				}
			}
		}

		/**
		 * Override the page templates.
		 *
		 * @since BuddyBoss 2.9.00
		 *
		 * @param string $template Template to override.
		 *
		 * @return string ReadyLaunch layout template.
		 */
		public function override_page_templates( $template ) {
			// Check if this is a 404 page.
			global $wp_query;
			if ( $wp_query->is_404() ) {
				// For 404 pages, don't use ReadyLaunch layout.
				// Let WordPress handle the 404 template naturally.
				return $template;
			}

			if ( bp_is_register_page() ) {
				$this->bb_rl_required_load();
				return bp_locate_template( 'register.php' );
			}

			if ( bp_is_activation_page() ) {
				return bp_locate_template( 'activate.php' );
			}

			if (
				$this->bb_is_readylaunch_forums() ||
				$this->bb_is_readylaunch_enabled_for_page()
			) {
				$this->bb_rl_required_load();
				return bp_locate_template( 'layout.php' );
			}

			if (
				! $this->bb_is_readylaunch_enabled_for_page() &&
				! $this->bb_is_readylaunch_forums() &&
				! (
					function_exists( 'wp_is_block_theme' ) &&
					wp_is_block_theme()
				) &&
				! has_block( 'buddyboss/readylaunch-header', get_post( get_the_ID() ) )
			) {
				add_filter( 'bp_get_template_stack', 'bp_add_template_stack_locations' );
				add_filter( 'bbp_get_template_stack', 'bbp_add_template_stack_locations' );

				// Add Readylaunch template locations.
				remove_filter( 'bp_get_template_stack', array( $this, 'add_template_stack' ), PHP_INT_MAX );
				remove_filter( 'bbp_get_template_stack', array( $this, 'add_forum_template_stack' ), PHP_INT_MAX );
			}

			return $template;
		}

		/**
		 * Remove the page content for ReadyLaunch forums and the discussion page.
		 *
		 * @since BuddyBoss 2.9.00
		 *
		 * @param object $retval The page object.
		 *
		 * @return bool True if it's a ReadyLaunch forums page, false otherwise.
		 */
		public function bb_rl_forums_get_page_by_path( $retval ) {
			if (
				(
					bbp_is_topic_archive() ||
					bbp_is_forum_archive()
				) &&
				is_object( $retval ) &&
				! empty( $retval->post_content )
			) {
				$retval->post_content = '';
			}

			return $retval;
		}

		/**
		 * Add custom template stack for ReadyLaunch.
		 *
		 * @since BuddyBoss 2.9.00
		 *
		 * @param array $stack The current template stack.
		 *
		 * @return array The modified template stack with ReadyLaunch custom location.
		 */
		public function add_template_stack( $stack ) {
			$stylesheet_dir = get_stylesheet_directory();
			$template_dir   = get_template_directory();

			$stack = array_flip( $stack );

			unset( $stack[ $stylesheet_dir ], $stack[ $template_dir ] );

			$stack = array_flip( $stack );

			$custom_location = 'readylaunch';

			foreach ( $stack as $key => $value ) {

				// Check if the path already ends with readylaunch to prevent recursive appending.
				$normalized_path = untrailingslashit( $value );
				if ( 0 === substr_compare( $normalized_path, $custom_location, -strlen( $custom_location ) ) ) {
					continue;
				}

				$stack[ $key ] = untrailingslashit( trailingslashit( $value ) . $custom_location );
			}

			return $stack;
		}

		/**
		 * Add forum template stack for ReadyLaunch.
		 *
		 * @since BuddyBoss 2.9.00
		 *
		 * @param array $stack The template stack.
		 *
		 * @return array
		 */
		public function add_forum_template_stack( $stack ) {
			$stylesheet_dir = get_stylesheet_directory();
			$template_dir   = get_template_directory();

			$stack = array_flip( $stack );

			unset( $stack[ $stylesheet_dir ], $stack[ $template_dir ] );

			$stack = array_flip( $stack );

			// Add ReadyLaunch forum template directory at the first index (highest priority).
			$readylaunch_forum_dir = buddypress()->plugin_dir . 'bp-templates/bp-nouveau/readylaunch/forums/';
			array_unshift( $stack, $readylaunch_forum_dir );

			return $stack;
		}

		/**
		 * Enqueue ReadyLaunch scripts.
		 *
		 * @since BuddyBoss 2.9.00
		 */
		public function bb_enqueue_scripts() {
			$min = bp_core_get_minified_asset_suffix();

			wp_enqueue_script( 'bb-readylaunch-front', buddypress()->plugin_url . "bp-templates/bp-nouveau/readylaunch/js/bb-readylaunch-front{$min}.js", array( 'jquery', 'bp-select2', 'bp-nouveau' ), bp_get_version(), true );

			// Enqueue select2 CSS to ensure it's available for ReadyLaunch.
			wp_enqueue_style( 'bp-select2' );

			// Enqueue Cropper.js.
			wp_enqueue_script( 'bb-readylaunch-cropper-js' );
			wp_enqueue_style( 'bb-readylaunch-cropper-css' );

			wp_enqueue_style( 'bb-readylaunch-font', buddypress()->plugin_url . 'bp-templates/bp-nouveau/readylaunch/assets/fonts/fonts.css', array(), bp_get_version() );
			wp_enqueue_style( 'bb-readylaunch-style-main', buddypress()->plugin_url . "bp-templates/bp-nouveau/readylaunch/css/main{$min}.css", array(), bp_get_version() );

			// Register only if it's an Activity component.
			if ( bp_is_active( 'activity' ) && bp_is_activity_component() ) {
				wp_enqueue_style( 'bb-readylaunch-activity', buddypress()->plugin_url . "bp-templates/bp-nouveau/readylaunch/css/activity{$min}.css", array(), bp_get_version() );

				// BB icon version.
				$bb_icon_version = function_exists( 'bb_icon_font_map_data' ) ? bb_icon_font_map_data( 'version' ) : '';
				$bb_icon_version = ! empty( $bb_icon_version ) ? $bb_icon_version : bp_get_version();
				wp_enqueue_style( 'bb-readylaunch-bb-icons', buddypress()->plugin_url . "bp-templates/bp-nouveau/icons/css/bb-icons{$min}.css", array(), $bb_icon_version );
				wp_enqueue_style( 'bb-readylaunch-bb-icons-map', buddypress()->plugin_url . "bp-templates/bp-nouveau/icons/css/icons-map{$min}.css", array(), $bb_icon_version );
			}

			// Register only if it's Message component.
			if ( bp_is_active( 'messages' ) && bp_is_messages_component() ) {
				wp_enqueue_style( 'bb-readylaunch-message', buddypress()->plugin_url . "bp-templates/bp-nouveau/readylaunch/css/message{$min}.css", array(), bp_get_version() );
			}

			// Register only if it's Groups component.
			if ( bp_is_active( 'groups' ) ) {
				if (
					bp_is_group_single() ||
					bp_is_group_create() ||
					bp_is_user_groups()
				) {
					wp_enqueue_style( 'bb-readylaunch-group-single', buddypress()->plugin_url . "bp-templates/bp-nouveau/readylaunch/css/groups-single{$min}.css", array(), bp_get_version() );
					wp_enqueue_script( 'bb-rl-groups' );
					wp_localize_script(
						'bb-rl-groups',
						'bbReadyLaunchGroupsVars',
						array(
							'group_id' => bp_get_current_group_id(),
						)
					);

					if ( bp_is_group_invites() || bp_is_group_creation_step( 'group-invites' ) ) {
						wp_enqueue_script( 'bb-rl-group-invites' );
					}
				}
			}

			wp_enqueue_style( 'bb-icons-rl-css', buddypress()->plugin_url . "bp-templates/bp-nouveau/readylaunch/icons/css/bb-icons-rl{$min}.css", array(), bp_get_version() );

			if ( bp_is_members_directory() ) {
				wp_register_script(
					'bb-rl-members',
					buddypress()->plugin_url . "bp-templates/bp-nouveau/readylaunch/js/bb-readylaunch-members{$min}.js",
					array( 'bp-nouveau' ),
					bp_get_version(),
					true
				);
				wp_enqueue_script( 'bb-rl-members' );

				wp_localize_script(
					'bb-rl-members',
					'bbReadyLaunchMembersVars',
					array(
						'invite_invalid_name_message' => esc_html__( 'Name is required.', 'buddyboss' ),
						'invite_valid_email'          => esc_html__( 'Please enter a valid email address.', 'buddyboss' ),
						'invite_sending_invite'       => esc_html__( 'Sending invitation', 'buddyboss' ),
						'invite_error_notice'         => esc_html__( 'There was an error submitting the form. Please try again.', 'buddyboss' ),
					)
				);
			}

			if ( bp_is_user_profile_edit() || bp_is_user_profile() ) {
				wp_enqueue_script( 'bb-rl-xprofile' );
			}

			// Enqueue the Forums styles for ReadyLaunch.
			$this->bb_readylaunch_forums_enqueue_styles();

			wp_localize_script(
				'bb-readylaunch-front',
				'bbReadyLaunchFront',
				array(
					'ajax_url'   => admin_url( 'admin-ajax.php' ),
					'nonce'      => wp_create_nonce( 'bb-readylaunch' ),
					'more_nav'   => esc_html__( 'More', 'buddyboss' ),
					'filter_all' => esc_html__( 'All', 'buddyboss' ),
				)
			);

			wp_enqueue_script( 'bp-select2' );
			wp_enqueue_style( 'bp-select2' );
		}

		/**
		 * Enqueue admin styles for ReadyLaunch.
		 *
		 * @since BuddyBoss 2.9.00
		 */
		public function bb_admin_enqueue_scripts() {
			$min = bp_core_get_minified_asset_suffix();
			wp_enqueue_style( 'bb-icons-rl-css', buddypress()->plugin_url . "bp-templates/bp-nouveau/readylaunch/icons/css/bb-icons-rl{$min}.css", array(), bp_get_version() );
		}

		/**
		 * Enqueue admin styles for ReadyLaunch.
		 *
		 * @since BuddyBoss 2.9.00
		 */
		public function bb_rl_admin_enqueue_scripts() {
			$min = bp_core_get_minified_asset_suffix();

			// BB icon version.
			$bb_icon_version = function_exists( 'bb_icon_font_map_data' ) ? bb_icon_font_map_data( 'version' ) : '';
			$bb_icon_version = ! empty( $bb_icon_version ) ? $bb_icon_version : bp_get_version();

			// Enqueue BB icons for admin pages.
			wp_enqueue_style( 'bb-readylaunch-bb-icons', buddypress()->plugin_url . "bp-templates/bp-nouveau/icons/css/bb-icons{$min}.css", array(), $bb_icon_version );
			wp_enqueue_style( 'bb-readylaunch-bb-icons-map', buddypress()->plugin_url . "bp-templates/bp-nouveau/icons/css/icons-map{$min}.css", array(), $bb_icon_version );
		}

		/**
		 * Dequeue all styles and scripts except the ones with the allowed suffix.
		 *
		 * @since BuddyBoss 2.9.00
		 */
		public function bb_dequeue_styles() {
			global $wp_styles, $wp_scripts;

			if (
				! $this->bb_is_readylaunch_enabled_for_page() &&
				! $this->bb_is_readylaunch_forums()
			) {
				return;
			}

			$allow_suffix = array(
				'bb-readylaunch',
				'query-monitor',
			);

			// Dequeue and deregister scripts.
			foreach ( $wp_scripts->queue as $handle ) {
				$src = $wp_scripts->registered[ $handle ]->src ?? '';

				if (
					false === strpos( $src, '/wp-includes/' ) &&
					false === strpos( $src, '/buddyboss-platform/' ) &&
					false === strpos( $src, '/buddyboss-platform-pro/' ) &&
					false === strpos( $src, '/sfwd-lms/' ) &&
					false === strpos( $src, '/learndash-course-reviews/' ) &&
					false === strpos( $src, '/instructor-role/' ) &&
					false === strpos( $src, 'wp-content/plugins/' ) &&
					! $this->bb_has_allowed_suffix( $handle, $allow_suffix )
				) {
					wp_dequeue_script( $handle );
				}
			}

			// Dequeue and deregister styles.
			foreach ( $wp_styles->queue as $handle ) {
				$src = $wp_styles->registered[ $handle ]->src ?? '';

				if (
					(
						false === strpos( $src, '/wp-includes/' ) &&
						false === strpos( $src, '/buddyboss-platform/' ) &&
						false === strpos( $src, '/buddyboss-platform-pro/' ) &&
						false === strpos( $src, '/sfwd-lms/' ) &&
						false === strpos( $src, '/instructor-role/' ) &&
						false === strpos( $src, 'wp-content/plugins/' ) &&
						! $this->bb_has_allowed_suffix( $handle, $allow_suffix )
					) ||
					'bp-nouveau-bb-icons' === $handle
				) {
					wp_dequeue_style( $handle );
				}
			}
		}

		/**
		 * Function to check if the handle has an allowed suffix.
		 *
		 * @since BuddyBoss 2.9.00
		 *
		 * @param string $handle       The script handle.
		 * @param array  $allow_suffix The allowed suffix.
		 *
		 * @return bool True if the handle has an allowed suffix, false otherwise.
		 */
		private function bb_has_allowed_suffix( $handle, $allow_suffix ) {
			foreach ( $allow_suffix as $suffix ) {
				if ( false !== strpos( $handle, $suffix ) ) {
					return true;
				}
			}

			return false;
		}

		/**
		 * Remove specific inline styles.
		 *
		 * @since BuddyBoss 2.9.00
		 *
		 * @param string $buffer The buffer content.
		 *
		 * @return string The buffer content with specific inline styles removed.
		 */
		public function bb_rl_remove_specific_inline_styles( $buffer ) {
			$remove_styles = array(
				'buddyboss_theme-style',
				'buddyboss_theme-bp-style',
				'buddyboss_theme-forums-style',
				'buddyboss_theme-learndash-style',
				'buddyboss_theme_options-dynamic-css',
				'buddyboss_theme-custom-style',
				'bb_learndash_30_custom_colors',
			);
			foreach ( $remove_styles as $style ) {
				$buffer = preg_replace( '/<style[^>]*id="' . preg_quote( $style, '/' ) . '"[^>]*>.*?<\/style>/s', '', $buffer );
			}

			return $buffer;
		}

		/**
		 * Start buffering.
		 *
		 * @since BuddyBoss 2.9.00
		 */
		public function bb_rl_start_buffering() {
			ob_start( array( $this, 'bb_rl_remove_specific_inline_styles' ) );
		}

		/**
		 * End buffering.
		 *
		 * @since BuddyBoss 2.9.00
		 */
		public function bb_rl_end_buffering() {
			ob_end_flush();
		}

		/**
		 * Fetch header messages.
		 *
		 * @since BuddyBoss 2.9.00
		 */
		public function bb_fetch_header_messages() {
			check_ajax_referer( 'bb-readylaunch', 'nonce' );

			$type = ! empty( $_POST['tab'] ) ? sanitize_text_field( wp_unslash( $_POST['tab'] ) ) : 'all';

			ob_start();
			bp_get_template_part( 'header/unread-messages', null, array( 'type' => $type ) );
			$messages = ob_get_clean();
			wp_send_json_success( $messages );
		}

		/**
		 * Fetch header notifications.
		 *
		 * @since BuddyBoss 2.9.00
		 */
		public function bb_fetch_header_notifications() {
			check_ajax_referer( 'bb-readylaunch', 'nonce' );

			$page = ! empty( $_POST['page'] ) ? intval( sanitize_text_field( wp_unslash( $_POST['page'] ) ) ) : 1;

			ob_start();
			bp_get_template_part( 'header/unread-notifications', null, array( 'page' => $page ) );
			$notifications = ob_get_clean();
			wp_send_json_success( $notifications );
		}

		/**
		 * Check if the sidebar is enabled for courses.
		 *
		 * @since BuddyBoss 2.9.00
		 *
		 * @return bool True if the sidebar is enabled for courses, false otherwise.
		 */
		public function bb_is_sidebar_enabled_for_courses() {
			$is_active = false;
			if ( class_exists( 'SFWD_LMS' ) ) {
				$options = bp_get_option( 'bp_ld_sync_settings', array() );
				if (
					! empty( $options['buddypress']['enabled'] ) ||
					! empty( $options['learndash']['enabled'] )
				) {
					$is_active = true;
				}
			} elseif (
				function_exists( 'tutor_utils' ) &&
				function_exists( 'bb_tutorlms_enable' ) &&
				bb_tutorlms_enable()
			) {
				$is_active = true;
			} elseif ( class_exists( 'memberpress\courses\helpers\Courses' ) ) {
				$is_active = true;
			}

			// Get sidebar setting.
			return apply_filters( 'bb_readylaunch_lms_sidebar_enabled', $is_active );
		}

		/**
		 * Check if any left sidebar section (groups or courses) is active.
		 *
		 * This function checks if the groups or courses sections are active in the left sidebar.
		 * It applies the 'bb_readylaunch_left_sidebar_courses' filter to get the arguments and
		 * Parses them to ensure they have the required structure.
		 *
		 * @since BuddyBoss 2.9.00
		 *
		 * @param bool $data if true, then check for return data, otherwise it will check plugin is active or not.
		 *
		 * @return array|bool The active courses array if any section is active, false otherwise.
		 */
		public function bb_is_active_any_left_sidebar_section( $data ) {
			$data = (bool) $data;
			$args = apply_filters(
				'bb_readylaunch_left_sidebar_middle_content',
				array(
					'has_sidebar_data'               => $data,
					'is_sidebar_enabled_for_groups'  => true,
					'is_sidebar_enabled_for_courses' => $this->bb_is_sidebar_enabled_for_courses(),
				)
			);

			bp_parse_args(
				$args,
				array(
					'integration' => '',
				)
			);

			if (
				! $data &&
				(
					empty( $args['groups']['integration'] ) &&
					(
						empty( $args['courses']['integration'] ) ||
						! in_array(
							$args['courses']['integration'],
							array(
								'sfwd-courses',
								'tutorlms',
								'lifterlms',
								'meprlms',
							),
							true
						)
					)
				)
			) {
				return false;
			}

			return $args;
		}

		/**
		 * Render the middle content for the left sidebar HTML.
		 *
		 * This function generates the HTML for the middle section of the left sidebar,
		 * Displaying a list of items or an error message if no items are available.
		 *
		 * @since BuddyBoss 2.9.00
		 *
		 * @param array $args Array of arguments for the left sidebar middle content.
		 */
		public function bb_render_left_sidebar_middle_html( $args = array() ) {
			bp_parse_args(
				$args,
				array(
					'heading'    => '',
					'items'      => array(),
					'error_text' => '',
				)
			);

			$title          = ! empty( $args['heading'] ) ? $args['heading'] : __( 'Courses', 'buddyboss' );
			$items          = ! empty( $args['items'] ) ? $args['items'] : array();
			$error_text     = ! empty( $args['error_text'] ) ? $args['error_text'] : __( 'There are no courses to display.', 'buddyboss' );
			$has_more_items = ! empty( $args['has_more_items'] ) ? $args['has_more_items'] : false;
			?>
			<div class="bb-rl-list">
				<h2>
					<?php
						echo esc_html( $title );
					?>
				</h2>
				<?php
				if ( ! empty( $items ) ) {
					?>
						<ul class="bb-rl-item-list" aria-live="polite" aria-relevant="all" aria-atomic="true">
						<?php
						foreach ( $items as $item ) {
							?>
									<li>
								<?php
								if ( ! empty( $item['thumbnail'] ) ) {
									?>
												<div class="item-avatar">
													<a href="<?php echo esc_url( $item['permalink'] ); ?>" aria-label="<?php echo esc_attr( $item['title'] ); ?>">
											<?php
												echo wp_kses(
													$item['thumbnail'],
													array(
														'img' => array(
															'src'    => true,
															'class'  => true,
															'id'     => true,
															'width'  => true,
															'height' => true,
															'alt'    => true,
														),
													)
												);
											?>
													</a>
												</div>
											<?php
								} else {
									?>
												<div class="item-avatar">
													<a href="<?php echo esc_url( $item['permalink'] ); ?>" title="<?php echo esc_attr( $item['title'] ); ?>" class="bb-rl-placeholder-avatar">
														<span class="bb-rl-placeholder-avatar-image"></span>
													</a>
												</div>
										<?php
								}
								?>
										<div class="item-title">
											<a href="<?php echo esc_url( $item['permalink'] ); ?>">
										<?php echo esc_html( $item['title'] ); ?>
											</a>
										</div>
									</li>
									<?php
						}
						if ( ! empty( $has_more_items ) ) {
							?>
									<a href="<?php echo ! empty( $args['show_more_link'] ) ? esc_url( $args['show_more_link'] ) : ''; ?>" class="bb-rl-show-more">
										<i class="bb-icons-rl-caret-down"></i>
								<?php echo esc_html__( 'Show More', 'buddyboss' ); ?>
									</a>
								<?php
						}
						?>
						</ul>
						<?php
				} else {
					?>
						<div class="widget-error">
						<?php
							echo esc_html( $error_text );
						?>
						</div>
						<?php
				}
				?>
			</div>
			<?php
		}

		/**
		 * Adds unread notifications to the heartbeat response.
		 *
		 * This function checks if the user is logged in and if the notification component is active.
		 * If both conditions are met, it retrieves the unread notifications template part and the total
		 * Count of unread notifications, then adds them to the response array.
		 *
		 * @since BuddyBoss 2.9.00
		 *
		 * @param array $response The existing heartbeat response array.
		 * @param array $data     The data passed to the heartbeat request.
		 *
		 * @return array The modified heartbeat response array with unread notifications' data.
		 */
		public function bb_heartbeat_unread_notifications( $response = array(), $data = array() ) {
			if (
				bp_loggedin_user_id() &&
				bp_is_active( 'notifications' ) &&
				! empty( $data['bb_fetch_header_notifications'] )
			) {
				// Handle the mark_as_read_notifications.
				if ( ! empty( $data['mark_as_read_notifications'] ) ) {
					$ids = array_map( 'intval', explode( ',', $data['mark_as_read_notifications'] ) );
					foreach ( $ids as $id ) {
						BP_Notifications_Notification::update(
							array( 'is_new' => 0 ),
							array(
								'id'      => $id,
								'user_id' => bp_loggedin_user_id(),
							)
						);
					}

					// Indicate that the notifications were processed.
					$response['mark_as_read_processed'] = true;
				}

				// Handle the delete_notifications.
				if ( ! empty( $data['mark_as_delete_notifications'] ) ) {
					$ids = array_map( 'intval', explode( ',', $data['mark_as_delete_notifications'] ) );
					foreach ( $ids as $id ) {
						BP_Notifications_Notification::delete(
							array(
								'id'      => $id,
								'user_id' => bp_loggedin_user_id(),
							)
						);
					}

					// Indicate that the notifications were processed.
					$response['mark_as_delete_processed'] = true;
				}
				ob_start();
				bp_get_template_part( 'header/unread-notifications' );
				$response['unread_notifications'] = ob_get_clean();
				$response['total_notifications']  = bp_notifications_get_unread_notification_count( bp_loggedin_user_id() );
			}

			return $response;
		}

		/**
		 * Marks BuddyBoss notifications as read.
		 *
		 * This function handles AJAX requests to mark notifications as read for the logged-in user.
		 * It checks if the notification component is active and verifies the AJAX nonce.
		 *
		 * Depending on the provided notification ID, it either marks a specific notification or all notifications as read.
		 * Finally, it returns the updated unread notifications count and content via a JSON response.
		 *
		 * @since BuddyBoss 2.9.00
		 *
		 * @return void
		 */
		public function bb_mark_notification_read() {
			if ( ! bp_is_active( 'notifications' ) ) {
				return;
			}

			check_ajax_referer( 'bb-readylaunch', 'nonce' );

			$user_id = bp_loggedin_user_id();

			$id = ! empty( $_POST['read_notification_ids'] ) ? sanitize_text_field( wp_unslash( $_POST['read_notification_ids'] ) ) : '';
			if ( 'all' !== $id ) {
				if ( false !== strpos( $id, ',' ) ) {
					$id = array_map( 'intval', explode( ',', $id ) );
				} else {
					$id = array( intval( $id ) );
				}
			}

			$deleted_notification_ids = ! empty( $_POST['deleted_notification_ids'] ) ? sanitize_text_field( wp_unslash( $_POST['deleted_notification_ids'] ) ) : '';
			$deleted_notification_ids = ! empty( $deleted_notification_ids ) ? array_map( 'intval', explode( ',', $deleted_notification_ids ) ) : array();
			if ( ! empty( $deleted_notification_ids ) ) {
				foreach ( $deleted_notification_ids as $deleted_notification_id ) {
					BP_Notifications_Notification::delete(
						array(
							'id'      => $deleted_notification_id,
							'user_id' => $user_id,
						)
					);
				}
			}

			if ( ! empty( $id ) && 'all' !== $id ) {
				foreach ( $id as $notification_id ) {
					BP_Notifications_Notification::update(
						array( 'is_new' => 0 ),
						array(
							'id'      => $notification_id,
							'user_id' => $user_id,
						)
					);
				}
			} elseif ( 'all' === $id ) {
				$notification_ids = BP_Notifications_Notification::get(
					array(
						'user_id'           => $user_id,
						'order_by'          => 'date_notified',
						'sort_order'        => 'DESC',
						'page'              => 1,
						'per_page'          => 25,
						'update_meta_cache' => false,
					)
				);
				if ( $notification_ids ) {
					foreach ( $notification_ids as $notification_id ) {
						BP_Notifications_Notification::update(
							array( 'is_new' => 0 ),
							array(
								'id'      => $notification_id->id,
								'user_id' => $user_id,
							)
						);
					}
				}
			}
			$response = array();
			ob_start();
			bp_get_template_part( 'header/unread-notifications' );
			$response['contents']            = ob_get_clean();
			$response['total_notifications'] = bp_notifications_get_unread_notification_count( $user_id );
			wp_send_json_success( $response );
		}

		/**
		 * Get the SVG icon for the document.
		 *
		 * @since BuddyBoss 2.9.00
		 *
		 * @param string $icons     The icons.
		 * @param string $extension The extension.
		 *
		 * @return string The SVG icon.
		 */
		public function bb_rl_document_svg_icon( $icons, $extension ) {
			$svg = array(
				'font' => '',
				'svg'  => '',
			);

			switch ( $extension ) {
				case '7z':
				case 'ace':
				case 'tar':
				case 'rar':
					$svg = array(
						'font' => 'bb-icons-rl-file-archive',
						'svg'  => '<svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 256 256"><path d="M213.66,82.34l-56-56A8,8,0,0,0,152,24H56A16,16,0,0,0,40,40V216a16,16,0,0,0,16,16H200a16,16,0,0,0,16-16V88A8,8,0,0,0,213.66,82.34ZM160,51.31,188.69,80H160ZM200,216H112V200h8a8,8,0,0,0,0-16h-8V168h8a8,8,0,0,0,0-16h-8V136h8a8,8,0,0,0,0-16h-8v-8a8,8,0,0,0-16,0v8H88a8,8,0,0,0,0,16h8v16H88a8,8,0,0,0,0,16h8v16H88a8,8,0,0,0,0,16h8v16H56V40h88V88a8,8,0,0,0,8,8h48V216Z"></path></svg>',
					);
					break;
				case 'abw':
				case 'rtf':
				case 'txt':
					$svg = array(
						'font' => 'bb-icons-rl-file-text',
						'svg'  => '<svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 256 256"><path d="M213.66,82.34l-56-56A8,8,0,0,0,152,24H56A16,16,0,0,0,40,40V216a16,16,0,0,0,16,16H200a16,16,0,0,0,16-16V88A8,8,0,0,0,213.66,82.34ZM160,51.31,188.69,80H160ZM200,216H56V40h88V88a8,8,0,0,0,8,8h48V216Zm-32-80a8,8,0,0,1-8,8H96a8,8,0,0,1,0-16h64A8,8,0,0,1,168,136Zm0,32a8,8,0,0,1-8,8H96a8,8,0,0,1,0-16h64A8,8,0,0,1,168,168Z"></path></svg>',
					);
					break;
				case 'css':
					$svg = array(
						'font' => 'bb-icons-rl-file-css',
						'svg'  => '<svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 256 256"><path d="M48,180c0,11,7.18,20,16,20a14.24,14.24,0,0,0,10.22-4.66A8,8,0,1,1,85.77,206.4,30,30,0,0,1,64,216c-17.65,0-32-16.15-32-36s14.35-36,32-36a30,30,0,0,1,21.77,9.6,8,8,0,1,1-11.55,11.06A14.24,14.24,0,0,0,64,160C55.18,160,48,169,48,180Zm79.6-8.69c-4-1.16-8.14-2.35-10.45-3.84-1.26-.81-1.23-1-1.12-1.9a4.54,4.54,0,0,1,2-3.67c4.6-3.12,15.34-1.73,19.83-.56a8,8,0,0,0,4.07-15.48c-2.12-.55-21-5.22-32.83,2.76a20.55,20.55,0,0,0-9,14.95c-2,15.88,13.64,20.41,23,23.11,12.07,3.49,13.13,4.92,12.78,7.59-.31,2.41-1.26,3.34-2.14,3.93-4.6,3.06-15.17,1.56-19.55.36a8,8,0,0,0-4.3,15.41,61.23,61.23,0,0,0,15.18,2c5.83,0,12.3-1,17.49-4.46a20.82,20.82,0,0,0,9.19-15.23C154,179,137.48,174.17,127.6,171.31Zm64,0c-4-1.16-8.14-2.35-10.45-3.84-1.25-.81-1.23-1-1.12-1.9a4.54,4.54,0,0,1,2-3.67c4.6-3.12,15.34-1.73,19.82-.56a8,8,0,0,0,4.07-15.48c-2.11-.55-21-5.22-32.83,2.76a20.58,20.58,0,0,0-8.95,14.95c-2,15.88,13.65,20.41,23,23.11,12.06,3.49,13.12,4.92,12.78,7.59-.31,2.41-1.26,3.34-2.15,3.93-4.6,3.06-15.16,1.56-19.54.36A8,8,0,0,0,173.93,214a61.34,61.34,0,0,0,15.19,2c5.82,0,12.3-1,17.49-4.46a20.81,20.81,0,0,0,9.18-15.23C218,179,201.48,174.17,191.59,171.31ZM40,112V40A16,16,0,0,1,56,24h96a8,8,0,0,1,5.66,2.34l56,56A8,8,0,0,1,216,88v24a8,8,0,1,1-16,0V96H152a8,8,0,0,1-8-8V40H56v72a8,8,0,0,1-16,0ZM160,80h28.69L160,51.31Z"></path></svg>',
					);
					break;
				case 'csv':
					$svg = array(
						'font' => 'bb-icons-rl-file-csv',
						'svg'  => '<svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 256 256"><path d="M87.82,196.31a20.82,20.82,0,0,1-9.19,15.23C73.44,215,67,216,61.14,216A61.23,61.23,0,0,1,46,214a8,8,0,0,1,4.3-15.41c4.38,1.2,14.95,2.7,19.55-.36.88-.59,1.83-1.52,2.14-3.93.35-2.67-.71-4.1-12.78-7.59-9.35-2.7-25-7.23-23-23.11a20.55,20.55,0,0,1,9-14.95c11.84-8,30.72-3.31,32.83-2.76a8,8,0,0,1-4.07,15.48c-4.48-1.17-15.23-2.56-19.83.56a4.54,4.54,0,0,0-2,3.67c-.11.9-.14,1.09,1.12,1.9,2.31,1.49,6.44,2.68,10.45,3.84C73.5,174.17,90.06,179,87.82,196.31ZM216,88v24a8,8,0,0,1-16,0V96H152a8,8,0,0,1-8-8V40H56v72a8,8,0,1,1-16,0V40A16,16,0,0,1,56,24h96a8,8,0,0,1,5.65,2.34l56,56A8,8,0,0,1,216,88Zm-56-8h28.69L160,51.31Zm-13.3,64.47a8,8,0,0,0-10.23,4.84L124,184.21l-12.47-34.9a8,8,0,1,0-15.06,5.38l20,56a8,8,0,0,0,15.07,0l20-56A8,8,0,0,0,146.7,144.47ZM208,176h-8a8,8,0,0,0,0,16v5.29a13.38,13.38,0,0,1-8,2.71c-8.82,0-16-9-16-20s7.18-20,16-20a13.27,13.27,0,0,1,7.53,2.38,8,8,0,0,0,8.95-13.26A29.38,29.38,0,0,0,192,144c-17.64,0-32,16.15-32,36s14.36,36,32,36a30.06,30.06,0,0,0,21.78-9.6,8,8,0,0,0,2.22-5.53V184A8,8,0,0,0,208,176Z"></path></svg>',
					);
					break;
				case 'doc':
				case 'docm':
				case 'docx':
				case 'dotm':
				case 'dotx':
					$svg = array(
						'font' => 'bb-icons-rl-file-doc',
						'svg'  => '<svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 256 256"><path d="M52,144H36a8,8,0,0,0-8,8v56a8,8,0,0,0,8,8H52a36,36,0,0,0,0-72Zm0,56H44V160h8a20,20,0,0,1,0,40Zm169.53-4.91a8,8,0,0,1,.25,11.31A30.06,30.06,0,0,1,200,216c-17.65,0-32-16.15-32-36s14.35-36,32-36a30.06,30.06,0,0,1,21.78,9.6,8,8,0,0,1-11.56,11.06A14.24,14.24,0,0,0,200,160c-8.82,0-16,9-16,20s7.18,20,16,20a14.24,14.24,0,0,0,10.22-4.66A8,8,0,0,1,221.53,195.09ZM128,144c-17.65,0-32,16.15-32,36s14.35,36,32,36,32-16.15,32-36S145.65,144,128,144Zm0,56c-8.82,0-16-9-16-20s7.18-20,16-20,16,9,16,20S136.82,200,128,200ZM48,120a8,8,0,0,0,8-8V40h88V88a8,8,0,0,0,8,8h48v16a8,8,0,0,0,16,0V88a8,8,0,0,0-2.34-5.66l-56-56A8,8,0,0,0,152,24H56A16,16,0,0,0,40,40v72A8,8,0,0,0,48,120ZM160,51.31,188.69,80H160Z"></path></svg>',
					);
					break;
				case 'svg':
					$svg = array(
						'font' => 'bb-icons-rl-file-svg',
						'svg'  => '<svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 256 256"><path d="M213.66,82.34l-56-56A8,8,0,0,0,152,24H56A16,16,0,0,0,40,40V216a16,16,0,0,0,16,16H200a16,16,0,0,0,16-16V88A8,8,0,0,0,213.66,82.34ZM160,51.31,188.69,80H160ZM200,216H56V40h88V88a8,8,0,0,0,8,8h48V216Z"></path></svg>',
					);
					break;
				case 'gif':
				case 'ico':
				case 'png':
				case 'tif':
				case 'tiff':
				case 'jpg':
				case 'jpeg':
					$svg = array(
						'font' => 'bb-icons-rl-file-image',
						'svg'  => '<svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 256 256"><path d="M110.66,147.56a8,8,0,0,0-13.32,0L76.49,178.85l-9.76-15.18a8,8,0,0,0-13.46,0l-36,56A8,8,0,0,0,24,232H152a8,8,0,0,0,6.66-12.44ZM38.65,216,60,182.79l9.63,15a8,8,0,0,0,13.39.11l21-31.47L137.05,216Zm175-133.66-56-56A8,8,0,0,0,152,24H56A16,16,0,0,0,40,40v88a8,8,0,0,0,16,0V40h88V88a8,8,0,0,0,8,8h48V216h-8a8,8,0,0,0,0,16h8a16,16,0,0,0,16-16V88A8,8,0,0,0,213.66,82.34ZM160,51.31,188.69,80H160Z"></path></svg>',
					);
					break;
				case 'gz':
				case 'gzip':
				case 'zip':
					$svg = array(
						'font' => 'bb-icons-rl-file-zip',
						'svg'  => '<svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 256 256"><path d="M184,144H168a8,8,0,0,0-8,8v56a8,8,0,0,0,16,0v-8h8a28,28,0,0,0,0-56Zm0,40h-8V160h8a12,12,0,0,1,0,24Zm-48-32v56a8,8,0,0,1-16,0V152a8,8,0,0,1,16,0ZM96,208a8,8,0,0,1-8,8H56a8,8,0,0,1-7-12l25.16-44H56a8,8,0,0,1,0-16H88a8,8,0,0,1,7,12L69.79,200H88A8,8,0,0,1,96,208ZM213.66,82.34l-56-56A8,8,0,0,0,152,24H56A16,16,0,0,0,40,40v72a8,8,0,0,0,16,0V40h88V88a8,8,0,0,0,8,8h48v16a8,8,0,0,0,16,0V88A8,8,0,0,0,213.66,82.34ZM160,80V51.31L188.69,80Z"></path></svg>',
					);
					break;
				case 'htm':
				case 'html':
					$svg = array(
						'font' => 'bb-icons-rl-file-html',
						'svg'  => '<svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 256 256"><path d="M216,120V88a8,8,0,0,0-2.34-5.66l-56-56A8,8,0,0,0,152,24H56A16,16,0,0,0,40,40v80a8,8,0,0,0,16,0V40h88V88a8,8,0,0,0,8,8h48v24a8,8,0,0,0,16,0ZM160,51.31,188.69,80H160ZM68,160v48a8,8,0,0,1-16,0V192H32v16a8,8,0,0,1-16,0V160a8,8,0,0,1,16,0v16H52V160a8,8,0,0,1,16,0Zm56,0a8,8,0,0,1-8,8h-8v40a8,8,0,0,1-16,0V168H84a8,8,0,0,1,0-16h32A8,8,0,0,1,124,160Zm72,0v48a8,8,0,0,1-16,0V184l-9.6,12.8a8,8,0,0,1-12.8,0L148,184v24a8,8,0,0,1-16,0V160a8,8,0,0,1,14.4-4.8L164,178.67l17.6-23.47A8,8,0,0,1,196,160Zm56,48a8,8,0,0,1-8,8H216a8,8,0,0,1-8-8V160a8,8,0,0,1,16,0v40h20A8,8,0,0,1,252,208Z"></path></svg>',
					);
					break;
				case 'ics':
				case 'jar':
				case 'yaml':
					$svg = array(
						'font' => 'bb-icons-rl-file-code',
						'svg'  => '<svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 256 256"><path d="M181.66,146.34a8,8,0,0,1,0,11.32l-24,24a8,8,0,0,1-11.32-11.32L164.69,152l-18.35-18.34a8,8,0,0,1,11.32-11.32Zm-72-24a8,8,0,0,0-11.32,0l-24,24a8,8,0,0,0,0,11.32l24,24a8,8,0,0,0,11.32-11.32L91.31,152l18.35-18.34A8,8,0,0,0,109.66,122.34ZM216,88V216a16,16,0,0,1-16,16H56a16,16,0,0,1-16-16V40A16,16,0,0,1,56,24h96a8,8,0,0,1,5.66,2.34l56,56A8,8,0,0,1,216,88Zm-56-8h28.69L160,51.31Zm40,136V96H152a8,8,0,0,1-8-8V40H56V216H200Z"></path></svg>',
					);
					break;
				case 'js':
					$svg = array(
						'font' => 'bb-icons-rl-file-js',
						'svg'  => '<svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 256 256"><path d="M213.66,82.34l-56-56A8,8,0,0,0,152,24H56A16,16,0,0,0,40,40v72a8,8,0,0,0,16,0V40h88V88a8,8,0,0,0,8,8h48V216H176a8,8,0,0,0,0,16h24a16,16,0,0,0,16-16V88A8,8,0,0,0,213.66,82.34ZM160,51.31,188.69,80H160Zm-12.19,145a20.82,20.82,0,0,1-9.19,15.23C133.43,215,127,216,121.13,216a61.34,61.34,0,0,1-15.19-2,8,8,0,0,1,4.31-15.41c4.38,1.2,15,2.7,19.55-.36.88-.59,1.83-1.52,2.14-3.93.34-2.67-.71-4.1-12.78-7.59-9.35-2.7-25-7.23-23-23.11a20.56,20.56,0,0,1,9-14.95c11.84-8,30.71-3.31,32.83-2.76a8,8,0,0,1-4.07,15.48c-4.49-1.17-15.23-2.56-19.83.56a4.54,4.54,0,0,0-2,3.67c-.12.9-.14,1.09,1.11,1.9,2.31,1.49,6.45,2.68,10.45,3.84C133.49,174.17,150.05,179,147.81,196.31ZM80,152v38a26,26,0,0,1-52,0,8,8,0,0,1,16,0,10,10,0,0,0,20,0V152a8,8,0,0,1,16,0Z"></path></svg>',
					);
					break;
				case 'mp3':
				case 'wav':
					$svg = array(
						'font' => 'bb-icons-rl-file-audio',
						'svg'  => '<svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 256 256"><path d="M99.06,128.61a8,8,0,0,0-8.72,1.73L68.69,152H48a8,8,0,0,0-8,8v40a8,8,0,0,0,8,8H68.69l21.65,21.66A8,8,0,0,0,104,224V136A8,8,0,0,0,99.06,128.61ZM88,204.69,77.66,194.34A8,8,0,0,0,72,192H56V168H72a8,8,0,0,0,5.66-2.34L88,155.31ZM152,180a40.55,40.55,0,0,1-20,34.91A8,8,0,0,1,124,201.09a24.49,24.49,0,0,0,0-42.18A8,8,0,0,1,132,145.09,40.55,40.55,0,0,1,152,180Zm61.66-97.66-56-56A8,8,0,0,0,152,24H56A16,16,0,0,0,40,40v80a8,8,0,0,0,16,0V40h88V88a8,8,0,0,0,8,8h48V216H168a8,8,0,0,0,0,16h32a16,16,0,0,0,16-16V88A8,8,0,0,0,213.66,82.34ZM160,51.31,188.69,80H160Z"></path></svg>',
					);
					break;
				case 'pdf':
					$svg = array(
						'font' => 'bb-icons-rl-file-pdf',
						'svg'  => '<svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 256 256"><path d="M224,152a8,8,0,0,1-8,8H192v16h16a8,8,0,0,1,0,16H192v16a8,8,0,0,1-16,0V152a8,8,0,0,1,8-8h32A8,8,0,0,1,224,152ZM92,172a28,28,0,0,1-28,28H56v8a8,8,0,0,1-16,0V152a8,8,0,0,1,8-8H64A28,28,0,0,1,92,172Zm-16,0a12,12,0,0,0-12-12H56v24h8A12,12,0,0,0,76,172Zm88,8a36,36,0,0,1-36,36H112a8,8,0,0,1-8-8V152a8,8,0,0,1,8-8h16A36,36,0,0,1,164,180Zm-16,0a20,20,0,0,0-20-20h-8v40h8A20,20,0,0,0,148,180ZM40,112V40A16,16,0,0,1,56,24h96a8,8,0,0,1,5.66,2.34l56,56A8,8,0,0,1,216,88v24a8,8,0,0,1-16,0V96H152a8,8,0,0,1-8-8V40H56v72a8,8,0,0,1-16,0ZM160,80h28.69L160,51.31Z"></path></svg>',
					);
					break;
				case 'potm':
				case 'pptm':
				case 'potx':
				case 'pptx':
				case 'pps':
				case 'ppsx':
				case 'ppt':
					$svg = array(
						'font' => 'bb-icons-rl-file-ppt',
						'svg'  => '<svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 256 256"><path d="M224,152a8,8,0,0,1-8,8H204v48a8,8,0,0,1-16,0V160H176a8,8,0,0,1,0-16h40A8,8,0,0,1,224,152ZM92,172a28,28,0,0,1-28,28H56v8a8,8,0,0,1-16,0V152a8,8,0,0,1,8-8H64A28,28,0,0,1,92,172Zm-16,0a12,12,0,0,0-12-12H56v24h8A12,12,0,0,0,76,172Zm84,0a28,28,0,0,1-28,28h-8v8a8,8,0,0,1-16,0V152a8,8,0,0,1,8-8h16A28,28,0,0,1,160,172Zm-16,0a12,12,0,0,0-12-12h-8v24h8A12,12,0,0,0,144,172ZM40,112V40A16,16,0,0,1,56,24h96a8,8,0,0,1,5.66,2.34l56,56A8,8,0,0,1,216,88v24a8,8,0,0,1-16,0V96H152a8,8,0,0,1-8-8V40H56v72a8,8,0,0,1-16,0ZM160,80h28.69L160,51.31Z"></path></svg>',
					);
					break;
				case 'xlam':
				case 'xls':
				case 'xlsb':
				case 'xlsm':
				case 'xlsx':
					$svg = array(
						'font' => 'bb-icons-rl-file-xls',
						'svg'  => '<svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 256 256"><path d="M156,208a8,8,0,0,1-8,8H120a8,8,0,0,1-8-8V152a8,8,0,0,1,16,0v48h20A8,8,0,0,1,156,208ZM92.65,145.49a8,8,0,0,0-11.16,1.86L68,166.24,54.51,147.35a8,8,0,1,0-13,9.3L58.17,180,41.49,203.35a8,8,0,0,0,13,9.3L68,193.76l13.49,18.89a8,8,0,0,0,13-9.3L77.83,180l16.68-23.35A8,8,0,0,0,92.65,145.49Zm98.94,25.82c-4-1.16-8.14-2.35-10.45-3.84-1.25-.82-1.23-1-1.12-1.9a4.54,4.54,0,0,1,2-3.67c4.6-3.12,15.34-1.72,19.82-.56a8,8,0,0,0,4.07-15.48c-2.11-.55-21-5.22-32.83,2.76a20.58,20.58,0,0,0-8.95,14.95c-2,15.88,13.65,20.41,23,23.11,12.06,3.49,13.12,4.92,12.78,7.59-.31,2.41-1.26,3.33-2.15,3.93-4.6,3.06-15.16,1.55-19.54.35A8,8,0,0,0,173.93,214a60.63,60.63,0,0,0,15.19,2c5.82,0,12.3-1,17.49-4.46a20.81,20.81,0,0,0,9.18-15.23C218,179,201.48,174.17,191.59,171.31ZM40,112V40A16,16,0,0,1,56,24h96a8,8,0,0,1,5.66,2.34l56,56A8,8,0,0,1,216,88v24a8,8,0,1,1-16,0V96H152a8,8,0,0,1-8-8V40H56v72a8,8,0,0,1-16,0ZM160,80h28.68L160,51.31Z"></path></svg>',
					);
					break;
				case 'xltm':
				case 'xltx':
				case 'xml':
					$svg = array(
						'font' => 'bb-icons-rl-file-x',
						'svg'  => '<svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 256 256"><path d="M213.66,82.34l-56-56A8,8,0,0,0,152,24H56A16,16,0,0,0,40,40V216a16,16,0,0,0,16,16H200a16,16,0,0,0,16-16V88A8,8,0,0,0,213.66,82.34ZM160,51.31,188.69,80H160ZM200,216H56V40h88V88a8,8,0,0,0,8,8h48V216Zm-42.34-82.34L139.31,152l18.35,18.34a8,8,0,0,1-11.32,11.32L128,163.31l-18.34,18.35a8,8,0,0,1-11.32-11.32L116.69,152,98.34,133.66a8,8,0,0,1,11.32-11.32L128,140.69l18.34-18.35a8,8,0,0,1,11.32,11.32Z"></path></svg>',
					);
					break;
				case 'mp4':
				case 'webm':
				case 'ogg':
				case 'mov':
					$svg = array(
						'font' => 'bb-icons-rl-file-video',
						'svg'  => '<svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 256 256"><path d="M213.66,82.34l-56-56A8,8,0,0,0,152,24H56A16,16,0,0,0,40,40v72a8,8,0,0,0,16,0V40h88V88a8,8,0,0,0,8,8h48V216h-8a8,8,0,0,0,0,16h8a16,16,0,0,0,16-16V88A8,8,0,0,0,213.66,82.34ZM160,51.31,188.69,80H160ZM155.88,145a8,8,0,0,0-8.12.22l-19.95,12.46A16,16,0,0,0,112,144H48a16,16,0,0,0-16,16v48a16,16,0,0,0,16,16h64a16,16,0,0,0,15.81-13.68l19.95,12.46A8,8,0,0,0,160,216V152A8,8,0,0,0,155.88,145ZM112,208H48V160h64v48Zm32-6.43-16-10V176.43l16-10Z"></path></svg>',
					);
					break;
				case 'folder':
					$svg = array(
						'font' => 'bb-icons-rl-folders',
						'svg'  => '<svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 256 256"><path d="M224,64H154.67L126.93,43.2a16.12,16.12,0,0,0-9.6-3.2H72A16,16,0,0,0,56,56V72H40A16,16,0,0,0,24,88V200a16,16,0,0,0,16,16H192.89A15.13,15.13,0,0,0,208,200.89V184h16.89A15.13,15.13,0,0,0,240,168.89V80A16,16,0,0,0,224,64ZM192,200H40V88H85.33l29.87,22.4A8,8,0,0,0,120,112h72Zm32-32H208V112a16,16,0,0,0-16-16H122.67L94.93,75.2a16.12,16.12,0,0,0-9.6-3.2H72V56h45.33L147.2,78.4A8,8,0,0,0,152,80h72Z"></path></svg>',
					);
					break;
				case 'download':
					$svg = array(
						'font' => 'bb-icons-rl-download-simple',
						'svg'  => '<svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 256 256"><path d="M224,144v64a8,8,0,0,1-8,8H40a8,8,0,0,1-8-8V144a8,8,0,0,1,16,0v56H208V144a8,8,0,0,1,16,0Zm-101.66,5.66a8,8,0,0,0,11.32,0l40-40a8,8,0,0,0-11.32-11.32L136,124.69V32a8,8,0,0,0-16,0v92.69L93.66,98.34a8,8,0,0,0-11.32,11.32Z"></path></svg>',
					);
					break;
				default:
					$svg = array(
						'font' => 'bb-icons-rl-file',
						'svg'  => '<svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 256 256"><path d="M213.66,82.34l-56-56A8,8,0,0,0,152,24H56A16,16,0,0,0,40,40V216a16,16,0,0,0,16,16H200a16,16,0,0,0,16-16V88A8,8,0,0,0,213.66,82.34ZM160,51.31,188.69,80H160ZM200,216H56V40h88V88a8,8,0,0,0,8,8h48V216Z"></path></svg>',
					);
			}

			return $svg['font'];
		}

		/**
		 * Get the icon class for the document.
		 *
		 * @since BuddyBoss 2.9.00
		 *
		 * @param string $icon_class The icon class.
		 *
		 * @return string The icon class.
		 */
		public function bb_readylaunch_document_icon_class( $icon_class ) {
			$mapped_icon = array(
				'bb-icon-file'             => 'bb-icons-rl-file',
				'bb-icon-file-zip'         => 'bb-icons-rl-file-archive',
				'bb-icon-file-mp3'         => 'bb-icons-rl-file-audio',
				'bb-icon-file-html'        => 'bb-icons-rl-file-html',
				'bb-icon-file-psd'         => 'bb-icons-rl-file-dashed',
				'bb-icon-file-png'         => 'bb-icons-rl-file-image',
				'bb-icon-file-pptx'        => 'bb-icons-rl-file-ppt',
				'bb-icon-file-xlsx'        => 'bb-icons-rl-file-xls',
				'bb-icon-file-txt'         => 'bb-icons-rl-file-text',
				'bb-icon-file-video'       => 'bb-icons-rl-file-video',
				'bb-icon-file-abw'         => 'bb-icons-rl-file-text',
				'bb-icon-file-ace'         => 'bb-icons-rl-file-archive',
				'bb-icon-file-archive'     => 'bb-icons-rl-file-archive',
				'bb-icon-file-ai'          => '', // ai.
				'bb-icon-file-apk'         => '', // apk.
				'bb-icon-file-css'         => 'bb-icons-rl-file-css',
				'bb-icon-file-csv'         => 'bb-icons-rl-file-csv',
				'bb-icon-file-doc'         => 'bb-icons-rl-file-doc',
				'bb-icon-file-docm'        => 'bb-icons-rl-file-doc',
				'bb-icon-file-docx'        => 'bb-icons-rl-file-doc',
				'bb-icon-file-dotm'        => 'bb-icons-rl-file-doc',
				'bb-icon-file-dotx'        => 'bb-icons-rl-file-doc',
				'bb-icon-file-svg'         => 'bb-icons-rl-file-svg',
				'bb-icon-file-gif'         => 'bb-icons-rl-file-image',
				'bb-icon-file-excel'       => '', // hlam, hlsb, hlsm.
				'bb-icon-file-code'        => 'bb-icons-rl-file-html',
				'bb-icon-file-image'       => 'bb-icons-rl-file-image',
				'bb-icon-file-mobile'      => '', // ipa.
				'bb-icon-file-audio'       => 'bb-icons-rl-file-audio',
				'bb-icon-file-spreadsheet' => '', // ods, odt.
				'bb-icon-file-pdf'         => 'bb-icons-rl-file-pdf',
				'bb-icon-file-vector'      => '', // psd.
				'bb-icon-file-pptm'        => 'bb-icons-rl-file-ppt',
				'bb-icon-file-pps'         => 'bb-icons-rl-file-ppt',
				'bb-icon-file-ppsx'        => 'bb-icons-rl-file-ppt',
				'bb-icon-file-ppt'         => 'bb-icons-rl-file-ppt',
				'bb-icon-file-rar'         => 'bb-icons-rl-file-archive',
				'bb-icon-file-rtf'         => 'bb-icons-rl-file-text',
				'bb-icon-file-rss'         => '', // rss.
				'bb-icon-file-sketch'      => '', // sketch.
				'bb-icon-file-tar'         => 'bb-icons-rl-file-archive',
				'bb-icon-file-vcf'         => '', // vcf.
				'bb-icon-file-wav'         => 'bb-icons-rl-file-audio',
				'bb-icon-file-xltm'        => 'bb-icons-rl-file-x',
				'bb-icon-file-xltx'        => 'bb-icons-rl-file-x',
				'bb-icon-file-xml'         => 'bb-icons-rl-file-x',
				'bb-icon-file-yaml'        => 'bb-icons-rl-file-code',
				'bb-icon-folder-stacked'   => 'bb-icons-rl-folders',
				'bb-icon-download'         => 'bb-icons-rl-download-simple',
			);

			return ! empty( $mapped_icon[ $icon_class ] ) ? $mapped_icon[ $icon_class ] : $icon_class;
		}

		/**
		 * Filters the label for BuddyPress Nouveau filters.
		 *
		 * @since BuddyBoss 2.9.00
		 *
		 * @param string $label     Label for BuddyPress Nouveau filter.
		 * @param array  $component The data filter's data-bp-filter attribute value.
		 */
		public function bb_nouveau_get_filter_label_hook( $label, $component ) {
			if ( 'members' === $component['object'] || 'groups' === $component['object'] ) {
				$label = __( 'Sort by', 'buddyboss' );
			}

			return $label;
		}

		/**
		 * Filters to add the ReadyLaunch prefix.
		 *
		 * @since BuddyBoss 2.9.00
		 *
		 * @param string $key Key to readylaunch prefix.
		 */
		public function bb_rl_prefix_key( $key ) {
			return 'bb-rl-' . $key;
		}

		/**
		 * Register Scripts for the Member component
		 *
		 * @since BuddyBoss 2.9.00
		 *
		 * @param array $scripts The array of scripts to register.
		 *
		 * @return array The same array with the specific messages scripts.
		 */
		public function bb_rl_nouveau_register_scripts( $scripts = array() ) {
			if ( ! isset( $scripts['bp-nouveau'] ) ) {
				return $scripts;
			}

			return array_merge(
				$scripts,
				array(
					'bb-rl-groups'              => array(
						'file'         => buddypress()->plugin_url . 'bp-templates/bp-nouveau/readylaunch/js/bb-readylaunch-groups%s.js',
						'dependencies' => array( 'bp-nouveau' ),
						'footer'       => true,
					),
					'bb-rl-group-invites'       => array(
						'file'         => buddypress()->plugin_url . 'bp-templates/bp-nouveau/readylaunch/js/bb-readylaunch-group-invites%s.js',
						'dependencies' => array( 'bp-nouveau', 'json2', 'wp-backbone' ),
						'footer'       => true,
					),
					'bp-nouveau-magnific-popup' => array(
						'file'         => buddypress()->plugin_url . 'bp-core/js/vendor/magnific-popup.js',
						'dependencies' => array( 'jquery' ),
						'footer'       => false,
					),
					'guillotine-js'             => array(
						'file'         => buddypress()->plugin_url . 'bp-templates/bp-nouveau/js/jquery.guillotine.min.js',
						'dependencies' => array( 'jquery' ),
						'version'      => bp_get_version(),
						'footer'       => true,
					),
					'bb-rl-xprofile'            => array(
						'file'         => buddypress()->plugin_url . 'bp-templates/bp-nouveau/readylaunch/js/bb-readylaunch-xprofile%s.js',
						'dependencies' => array( 'bp-nouveau', 'jquery-ui-sortable' ),
						'footer'       => true,
					),
				)
			);
		}

		/**
		 * Filters the output of pagination links to add custom classes and ensure "Previous" and "Next" links are always visible.
		 *
		 * This function modifies the HTML output generated by paginate_links() by:
		 * - Adding custom classes to all pagination links.
		 * - Ensuring "Previous" and "Next" links are always visible, even if first or last page.
		 * - Add previous and next data labels.
		 *
		 * @since BuddyBoss 2.9.00
		 *
		 * @param string $output The HTML output of the pagination links.
		 * @param array  $args   The arguments passed to paginate_links().
		 *
		 * @return string Modified pagination links output.
		 */
		public function bb_rl_filter_paginate_links_output( $output, $args ) {
			// Add custom class to span tags (disabled or active links).
			$output = str_replace( 'page-numbers', 'bb-rl-page-numbers', $output );

			$prev_label = esc_html__( 'Prev', 'buddyboss' );
			$next_label = esc_html__( 'Next', 'buddyboss' );

			// Use prev_text and next_text passed in the paginate_links arguments.
			$prev_text = $args['prev_text'] ?? __( '&larr; Prev', 'buddyboss' );
			$next_text = $args['next_text'] ?? __( 'Next &rarr;', 'buddyboss' );

			// Ensure Previous and Next links are always visible (even if disabled).
			if ( strpos( $output, 'prev bb-rl-page-numbers' ) === false ) {
				$prev_disabled = sprintf(
					'<span data-bb-rl-label="%s" class="prev bb-rl-page-numbers disabled">%s</span>',
					$prev_label,
					$prev_text
				);
				$output        = $prev_disabled . $output;
			} else {
				// Replace "Previous" link text with custom text and add data-bb-rl-label attribute.
				$output = preg_replace(
					'/<a(.*?)class="prev bb-rl-page-numbers(.*?)"(.*?)>(.*?)<\/a>/i',
					'<a$1class="prev bb-rl-page-numbers$2"$3 data-bb-rl-label="' . $prev_label . '">' . $prev_text . '</a>',
					$output
				);
			}

			if ( strpos( $output, 'next bb-rl-page-numbers' ) === false ) {
				$next_disabled = sprintf(
					'<span data-bb-rl-label="%s" class="next bb-rl-page-numbers disabled">%s</span>',
					$next_label,
					$next_text
				);
				$output       .= $next_disabled;
			} else {
				// Replace "Next" link text with custom text and add data-bb-rl-label attribute.
				$output = preg_replace(
					'/<a(.*?)class="next bb-rl-page-numbers(.*?)"(.*?)>(.*?)<\/a>/i',
					'<a$1class="next bb-rl-page-numbers$2"$3 data-bb-rl-label="' . $next_label . '">' . $next_text . '</a>',
					$output
				);
			}

			return $output;
		}

		/**
		 * Callback function for the invite form.
		 *
		 * @since BuddyBoss 2.9.00
		 *
		 * @return void|false
		 */
		public function bb_rl_invite_form_callback() {
			$response = array(
				'message' => esc_html__( 'Unable to send invite.', 'buddyboss' ),
				'type'    => 'error',
			);

			$nonce = isset( $_POST['bb_rl_invite_form_nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['bb_rl_invite_form_nonce'] ) ) : '';

			// Verify nonce.
			if (
				! empty( $nonce ) &&
				! wp_verify_nonce( $nonce, 'bb_rl_invite_form_action' )
			) {
				$response['message'] = esc_html__( 'Nonce verification failed.', 'buddyboss' );
				wp_send_json_error( $response );
			}

			$loggedin_user_id = bp_loggedin_user_id();

			// Check if the user is logged in.
			if ( ! $loggedin_user_id ) {
				$response['message'] = esc_html__( 'You should be logged in to send an invite.', 'buddyboss' );
				wp_send_json_error( $response );
			}

			if ( ! bp_is_active( 'invites' ) || ! bp_is_post_request() || empty( $_POST['bb-rl-invite-email'] ) ) {
				wp_send_json_error( $response );
			}

			$email = strtolower( sanitize_email( wp_unslash( $_POST['bb-rl-invite-email'] ) ) );
			if ( email_exists( $email ) ) {
				$response['message'] = esc_html__( 'Email address already exists.', 'buddyboss' );
				wp_send_json_error( $response );
			} elseif ( bb_is_email_address_already_invited( $email, $loggedin_user_id ) ) {
				$response['message'] = esc_html__( 'The email has already been invited', 'buddyboss' );
				wp_send_json_error( $response );
			} elseif ( ! bb_is_allowed_register_email_address( $email ) ) {
				$response['message'] = esc_html__( 'Email address restricted.', 'buddyboss' );
				wp_send_json_error( $response );
			} elseif ( ! bp_allow_user_to_send_invites() ) {
				$response['message'] = esc_html__( 'Sorry, you don\'t have permission to view invites profile type.', 'buddyboss' );
				wp_send_json_error( $response );
			}

			$name        = isset( $_POST['bb-rl-invite-name'] ) ? sanitize_text_field( wp_unslash( $_POST['bb-rl-invite-name'] ) ) : '';
			$member_type = isset( $_POST['bb-rl-invite-type'] ) ? sanitize_text_field( wp_unslash( $_POST['bb-rl-invite-type'] ) ) : '';

			$subject = bp_disable_invite_member_email_subject() && ! empty( sanitize_text_field( wp_unslash( $_POST['bp_member_invites_custom_subject'] ) ) )
				? stripslashes( wp_strip_all_tags( sanitize_text_field( wp_unslash( $_POST['bp_member_invites_custom_subject'] ) ) ) )
				: stripslashes( wp_strip_all_tags( bp_get_member_invitation_subject() ) );

			$message = bp_disable_invite_member_email_content() && ! empty( sanitize_text_field( wp_unslash( $_POST['bp_member_invites_custom_content'] ) ) )
				? stripslashes( wp_strip_all_tags( sanitize_textarea_field( wp_unslash( $_POST['bp_member_invites_custom_content'] ) ) ) )
				: stripslashes( wp_strip_all_tags( bp_get_member_invitation_message() ) );

			$message .= ' ' . bp_get_member_invites_wildcard_replace(
				stripslashes( wp_strip_all_tags( bp_get_invites_member_invite_url() ) ),
				$email
			);

			$inviter_name = bp_core_get_user_displayname( $loggedin_user_id );
			$email_encode = rawurlencode( $email );
			$inviter_url  = bp_loggedin_user_domain();

			$_POST['custom_user_email']  = $email;
			$_POST['custom_user_name']   = $name;
			$_POST['custom_user_avatar'] = apply_filters(
				'bp_sent_invite_email_avatar',
				bb_attachments_get_default_profile_group_avatar_image( array( 'object' => 'user' ) )
			);

			$accept_link = add_query_arg(
				array(
					'bp-invites' => 'accept-member-invitation',
					'email'      => $email_encode,
					// phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode -- Used for legitimate encoding of user ID in invite URL
					'inviter'    => base64_encode( (string) $loggedin_user_id ),
				),
				trailingslashit( bp_get_root_domain() ) . bp_get_signup_slug() . '/'
			);
			$accept_link = apply_filters( 'bp_member_invitation_accept_url', $accept_link );

			$args = array(
				'tokens' => array(
					'inviter.name' => $inviter_name,
					'inviter.url'  => $inviter_url,
					'invitee.url'  => $accept_link,
				),
			);

			add_filter( 'bp_email_get_salutation', '__return_false' );
			if ( ! function_exists( 'bp_invites_kses_allowed_tags' ) ) {
				require trailingslashit( buddypress()->plugin_dir . 'bp-invites/actions' ) . '/invites.php';
			}

			bp_send_email( 'invites-member-invite', $email, $args );

			$post_id = wp_insert_post(
				array(
					'post_author'  => $loggedin_user_id,
					'post_content' => $message,
					'post_title'   => $subject,
					'post_status'  => 'publish',
					'post_type'    => bp_get_invite_post_type(),
				)
			);

			if ( ! $post_id ) {
				return false;
			}

			update_post_meta( $post_id, 'bp_member_invites_accepted', '' );
			update_post_meta( $post_id, '_bp_invitee_email', $email );
			update_post_meta( $post_id, '_bp_invitee_name', $name );
			update_post_meta( $post_id, '_bp_inviter_name', $inviter_name );
			update_post_meta( $post_id, '_bp_invitee_status', 0 );
			update_post_meta( $post_id, '_bp_invitee_member_type', $member_type );

			/**
			 * Fires after a member invitation is sent.
			 *
			 * @since BuddyBoss 2.9.00
			 *
			 * @param int $post_id Invitation post ID.
			 *
			 * @param int $user_id Inviter user ID.
			 */
			do_action( 'bp_member_invite_submit', $loggedin_user_id, $post_id );

			wp_send_json_success(
				array(
					'message' => esc_html__( 'Invitation sent successfully', 'buddyboss' ),
					'type'    => 'success',
				)
			);
		}

		/**
		 * Adds custom classes to the array of body classes.
		 *
		 * @since BuddyBoss 2.9.00
		 *
		 * @param array $classes Classes for the body element.
		 *
		 * @return array
		 */
		public function bb_rl_theme_body_classes( $classes ) {
			if ( is_active_sidebar( 'bb-readylaunch-members-sidebar' ) ) {
				$classes[] = 'bb-rl-has-sidebar';
			}

			return $classes;
		}

		/**
		 * Filters the script loader source for the ReadyLaunch script.
		 *
		 * @since BuddyBoss 2.9.00
		 *
		 * @param string $src    The source URL of the script.
		 * @param string $handle The handle of the script.
		 *
		 * @return string Filtered source URL.
		 */
		public function bb_rl_script_loader_src( $src, $handle ) {
			global $bp;
			$min = bp_core_get_minified_asset_suffix();
			if ( ! empty( $src ) && 'bb-topics-manager' === $handle ) {
				$src = trailingslashit( $bp->plugin_url ) . "bp-templates/bp-nouveau/readylaunch/js/bb-topics-manager{$min}.js";
			}

			return $src;
		}


		/**
		 * Override the Send Message button text.
		 *
		 * @since BuddyBoss 2.9.00
		 *
		 * @param array $args Button arguments.
		 *
		 * @return array $args Filtered arguments.
		 */
		public function bb_rl_override_send_message_button_text( $args ) {
			$args['data-balloon'] = esc_html__( 'Message', 'buddyboss' );

			return $args;
		}

		/**
		 * Filters the member actions for member directories.
		 *
		 * @since BuddyBoss 2.9.00
		 *
		 * @param array  $buttons     Member profile actions.
		 * @param int    $user_id     Member ID.
		 * @param string $button_type Which type of buttons need "primary", "secondary" or "both".
		 *
		 * @return array $buttons Filtered buttons.
		 */
		public function bb_rl_member_directories_get_profile_actions( $buttons, $user_id, $button_type ) {
			$enabled_message_action = ! function_exists( 'bb_enabled_member_directory_profile_action' ) || bb_enabled_member_directory_profile_action( 'message' );

			// Member directories' primary actions.
			$primary_action_btn = function_exists( 'bb_get_member_directory_primary_action' )
				? bb_get_member_directory_primary_action()
				: '';

			if ( $enabled_message_action ) {
				// Skip if "send-private-message" action already exists.
				if (
					( isset( $buttons['primary'] ) && strpos( $buttons['primary'], 'send-private-message' ) !== false ) ||
					( isset( $buttons['secondary'] ) && strpos( $buttons['secondary'], 'send-private-message' ) !== false )
				) {
					return $buttons;
				}

				// Show "Message" button or not?
				add_filter( 'bp_force_friendship_to_message', '__return_false' );
				$is_message_active = apply_filters(
					'bb_member_loop_show_message_button',
					bp_is_active( 'messages' ),
					$user_id,
					bp_loggedin_user_id()
				);
				remove_filter( 'bp_force_friendship_to_message', '__return_false' );

				if ( $is_message_active ) {
					add_filter( 'bp_displayed_user_id', 'bb_member_loop_set_member_id' );
					add_filter( 'bp_is_my_profile', 'bb_member_loop_set_my_profile' );

					if ( 'message' === $primary_action_btn ) {
						$primary_button_args               = function_exists( 'bb_member_get_profile_action_arguments' )
							? bb_member_get_profile_action_arguments()
							: array();
						$primary_button_args['link_class'] = 'bb-rl-send-message-disabled';
						$buttons['primary']                = bp_get_send_message_button( $primary_button_args );
					} else {
						$secondary_button_args               = function_exists( 'bb_member_get_profile_action_arguments' )
							? bb_member_get_profile_action_arguments( 'directory', 'secondary' )
							: array();
						$secondary_button_args['link_class'] = 'bb-rl-send-message-disabled';
						$buttons['secondary']               .= bp_get_send_message_button( $secondary_button_args );
					}

					remove_filter( 'bp_displayed_user_id', 'bb_member_loop_set_member_id' );
					remove_filter( 'bp_is_my_profile', 'bb_member_loop_set_my_profile' );
				}
			}

			return $buttons;
		}

		/**
		 * Filters default group avatar image URL.
		 *
		 * @since BuddyBoss 2.9.00
		 *
		 * @param string|bool $avatar_image_url Default avatar URL, false otherwise.
		 * @param array       $params           Parameters for the avatar image.
		 *
		 * @return string|bool $avatar_image_url Default avatar URL, false otherwise.
		 */
		public function bb_rl_group_default_group_avatar_image( $avatar_image_url, $params ) {
			if ( isset( $params['object'] ) && 'group' === $params['object'] ) {
				$avatar_image_url = esc_url( buddypress()->plugin_url . 'bp-templates/bp-nouveau/readylaunch/images/group_avatar_image.jpeg' );
			}

			return $avatar_image_url;
		}

		/**
		 * Check if the current user is a group admin.
		 *
		 * @since BuddyBoss 2.9.00
		 *
		 * @return bool True if the user is a group admin, false otherwise.
		 */
		public static function bb_is_group_admin() {
			return bp_is_active( 'groups' ) &&
					bp_is_group_single() &&
					bp_get_group_current_admin_tab();
		}

		/**
		 * Register common scripts for ReadyLaunch.
		 *
		 * @since BuddyBoss 2.9.00
		 *
		 * @param array $scripts Array of registered scripts.
		 *
		 * @return array $scripts Array of registered scripts.
		 */
		public function bb_rl_register_common_scripts( $scripts ) {
			$min = bp_core_get_minified_asset_suffix();
			$url = buddypress()->plugin_url . 'bp-templates/bp-nouveau/readylaunch/js/';

			// Add Cropper.js to the common scripts.
			$scripts['bb-readylaunch-cropper-js'] = array(
				'file'         => "{$url}cropper{$min}.js",
				'dependencies' => array( 'jquery' ),
				'version'      => '1.6.2',
				'footer'       => true,
			);

			if ( isset( $scripts['bp-avatar'] ) ) {
				$scripts['bp-avatar']['file'] = "{$url}bb-readylaunch-avatar{$min}.js";
			}

			if ( isset( $scripts['bp-plupload'] ) ) {
				$scripts['bp-plupload']['file'] = "{$url}bb-readylaunch-plupload{$min}.js";
			}

			if ( isset( $scripts['bp-cover-image'] ) ) {
				$scripts['bp-cover-image']['file'] = "{$url}bb-readylaunch-cover-image{$min}.js";
			}

			return $scripts;
		}

		/**
		 * Register common styles for ReadyLaunch.
		 *
		 * @since BuddyBoss 2.9.00
		 *
		 * @param array $styles Array of registered styles.
		 *
		 * @return array $styles Array of registered styles.
		 */
		public function bb_rl_register_common_styles( $styles ) {
			$min = bp_core_get_minified_asset_suffix();
			$url = buddypress()->plugin_url . 'bp-templates/bp-nouveau/readylaunch/css/';

			$styles['bb-readylaunch-cropper-css'] = array(
				'file'         => "{$url}cropper{$min}.css",
				'dependencies' => array(),
				'version'      => '1.6.2',
			);

			return $styles;
		}

		/**
		 * Modify document description HTML for ReadyLaunch.
		 *
		 * @since BuddyBoss 2.9.00
		 *
		 * @param string $html The original HTML content.
		 */
		public function bb_rl_modify_document_description_html( $html ) {
			// Add ReadyLaunch specific classes to existing HTML structure.
			$html = str_replace(
				array(
					'class="bp-activity-head"',
					'class="activity-avatar item-avatar"',
					'class="activity-header"',
				),
				array(
					'class="bb-rl-activity-head"',
					'class="bb-rl-activity-avatar bb-rl-item-avatar"',
					'class="bb-rl-activity-header"',
				),
				$html
			);

			return $html;
		}

		/**
		 * Filters the member profile buttons.
		 *
		 * @since BuddyBoss 2.9.00
		 *
		 * @param array  $buttons The member profile buttons.
		 * @param int    $user_id The user ID.
		 * @param string $type    The type of buttons (primary, secondary, etc.).
		 */
		public static function bb_rl_member_profile_buttons( $buttons, $user_id, $type ) {
			if (
				bp_loggedin_user_id() &&
				bp_displayed_user_id() === bp_loggedin_user_id() &&
				bp_loggedin_user_id() === $user_id
			) {
				$buttons['edit_profile'] = array(
					'id'                => 'edit_profile',
					'position'          => 5,
					'component'         => 'xprofile',
					'must_be_logged_in' => true,
					'button_element'    => 'a',
					'button_attr'       => array(
						'class' => 'button edit-profile',
						'href'  => bp_loggedin_user_domain() . 'profile/edit/',
					),
					'link_text'         => esc_html__( 'Edit Profile', 'buddyboss' ),
					'link_url'          => bp_loggedin_user_domain() . 'profile/edit/',
					'link_class'        => 'bb-rl-edit-profile',
					'prefix_link_text'  => '<i class="bb-icons-rl-pencil-simple-line"></i>',
				);
			}

			foreach ( $buttons as $key => $button ) {
				if ( ! empty( $button['button_attr'] ) ) {
					if ( ! empty( $buttons[ $key ]['button_attr']['data-title'] ) ) {
						$buttons[ $key ]['button_attr']['data-balloon-pos'] = 'up';
						$buttons[ $key ]['button_attr']['data-balloon']     = $buttons[ $key ]['button_attr']['data-title'];
					}
				}
			}

			if ( ! empty( $buttons['reject_friendship'] ) && bp_is_current_action( 'requests' ) ) {
				$buttons['reject_friendship']['link_text']        = esc_html__( 'Reject', 'buddyboss' );
				$buttons['reject_friendship']['prefix_link_text'] = '<i class="bb-icons-rl-x"></i>';
			}

			if ( ! empty( $buttons['accept_friendship'] ) && bp_is_current_action( 'requests' ) ) {
				$buttons['accept_friendship']['link_text']        = esc_html__( 'Accept', 'buddyboss' );
				$buttons['accept_friendship']['prefix_link_text'] = '<i class="bb-icons-rl-check"></i>';
			}

			return $buttons;
		}

		/**
		 * Modify the JS strings.
		 *
		 * @since BuddyBoss 2.9.00
		 *
		 * @param array $strings The JS strings.
		 *
		 * @return array $strings The modified JS strings.
		 */
		public function bb_rl_modify_js_strings( $strings ) {
			$translated_string = __( '\'s post', 'buddyboss' );

			if ( bp_is_active( 'media' ) || bp_is_active( 'video' ) || bp_is_active( 'document' ) ) {
				$strings['media']['i18n_strings']['theater_title'] = $translated_string;
				$strings['media']['create_album_title']            = esc_html__( 'Create new album', 'buddyboss' );
				$strings['media']['create_folder']                 = esc_html__( 'Create new folder', 'buddyboss' );
				$strings['media']['bb_rl_invalid_media_type']      = __( 'Different types of media cannot be uploaded to a post', 'buddyboss' );
			}

			if ( bp_is_active( 'messages' ) ) {
				$strings['messages']['i18n']['to_placeholder'] = __( 'Start typing a name', 'buddyboss' );
			}

			if ( bp_is_active( 'moderation' ) ) {
				$strings['moderation']['block_member'] = __( 'Block member', 'buddyboss' );
			}

			if ( bp_is_active( 'groups' ) ) {
				$strings['groups']['i18n']['sending_request']      = esc_html__( 'Sending request', 'buddyboss' );
				$strings['groups']['i18n']['cancel_request_group'] = esc_html__( 'Canceling request', 'buddyboss' );
				$strings['groups']['member_invites_none']          = bp_nouveau_get_user_feedback( 'member-invites-none' );
			}

			if ( bp_is_active( 'friends' ) ) {
				$strings['friends']['member_requests_none'] = bp_nouveau_get_user_feedback( 'member-requests-none' );
				$strings['friends']['members_loop_none']    = bp_nouveau_get_user_feedback( 'members-loop-none' );
			}

			return $strings;
		}

		/**
		 * Enqueue the login scripts.
		 *
		 * @since BuddyBoss 2.9.00
		 */
		public function bb_rl_login_enqueue_scripts() {
			wp_enqueue_style( 'bb-rl-login-fonts', buddypress()->plugin_url . 'bp-templates/bp-nouveau/readylaunch/assets/fonts/fonts.css', array(), bp_get_version() );
			wp_enqueue_style( 'bb-rl-login-style', buddypress()->plugin_url . 'bp-templates/bp-nouveau/readylaunch/css/login.css', array(), bp_get_version() );
			wp_enqueue_style( 'bb-rl-login-style-icons', buddypress()->plugin_url . 'bp-templates/bp-nouveau/readylaunch/icons/css/bb-icons-rl.min.css', array(), bp_get_version() );
		}

		/**
		 * Modify the login header.
		 *
		 * @since BuddyBoss 2.9.00
		 */
		public function bb_rl_login_header() {
			bp_get_template_part( 'common/header-register' );
		}


		/**
		 * Custom Login Link
		 *
		 * @since BuddyBoss 2.9.00
		 */
		public function bb_rl_login_header_url() {
			if ( function_exists( 'buddypress' ) && isset( buddypress()->buddyboss ) ) {
				$enable_private_network = bp_get_option( 'bp-enable-private-network' );

				if ( '0' === $enable_private_network ) {
					return '#';
				}
			}

			return home_url();
		}

		/**
		 * Modify the login footer.
		 *
		 * @since BuddyBoss 2.9.00
		 */
		public function bb_rl_login_footer() {
			?>
			<script>
				jQuery( document ).ready( function ( $ ) {
					var $forgetMeNot = $( '.login p.forgetmenot' );
					var $lostMeNot = $( '.login p.lostmenot' );
					$( $lostMeNot ).before( $forgetMeNot );

					var $updatedClose = $( '.bb-rl-updated-close' );
					if ( $updatedClose.length > 0 ) {
						$updatedClose.on( 'click', function() {
							$( this ).closest( '.message' ).hide();
						} );
					}
				} );
			</script>
			<?php
		}

		/**
		 * Modify the login message.
		 *
		 * @since BuddyBoss 2.9.00
		 *
		 * @param string $message The login message.
		 *
		 * @return string $message The modified login message.
		 */
		public function bb_rl_signin_login_message( $message ) {
			// phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$action                   = isset( $_REQUEST['action'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['action'] ) ) : '';
			$confirm_admin_email_page = false;
			if ( 'wp-login.php' === $GLOBALS['pagenow'] && ! empty( $action ) && 'confirm_admin_email' === $action ) {
				$confirm_admin_email_page = true;
			}

			if ( false === $confirm_admin_email_page ) {
				if ( empty( $message ) ) {
					return sprintf(
						'<div class="login-heading"><h2>%s</h2></div>',
						__( 'Sign in to your account', 'buddyboss' )
					);
				} else {
					return $message;
				}
			} else {
				return $message;
			}
		}

		/**
		 * Modify the login form to add a forgot password link.
		 *
		 * @since BuddyBoss 2.9.00
		 */
		public function bb_rl_login_custom_form() {
			?>
			<p class="lostmenot"><a href="<?php echo esc_url( wp_lostpassword_url() ); ?>"><?php esc_html_e( 'Forgot Password?', 'buddyboss' ); ?></a></p>
			<?php
		}

		/**
		 * Generate color shades from base color (500 level).
		 *
		 * @since BuddyBoss 2.9.30
		 *
		 * @param string $base_color Hex color code (will be used as level 500).
		 *
		 * @return array Array of color shades from 100 to 900.
		 */
		private function bb_rl_generate_color_shades( $base_color ) {
			// Normalize base color.
			$base_color = strtoupper( ltrim( $base_color, '#' ) );

			// If the base color is the default #4946FE, return the exact palette.
			if ( '4946FE' === $base_color ) {
				return array(
					100 => '#DDE4FF',
					200 => '#C2CDFF',
					300 => '#9DABFF',
					400 => '#767EFF',
					500 => '#4946FE',
					600 => '#4937F4',
					700 => '#3E2BD7',
					800 => '#3325AE',
					900 => '#2E2689',
				);
			}

			// For other colors, use HSL-based generation.
			$hsl    = $this->bb_rl_hex_to_hsl( $base_color );
			$shades = array();

			$adjustments = array(
				100 => array( 'h' => 4, 's' => - 0.35, 'l' => 0.42 ),
				200 => array( 'h' => 2, 's' => - 0.20, 'l' => 0.32 ),
				300 => array( 'h' => 1, 's' => - 0.10, 'l' => 0.22 ),
				400 => array( 'h' => 0, 's' => - 0.02, 'l' => 0.12 ),
				500 => array( 'h' => 0, 's' => 0, 'l' => 0 ),
				600 => array( 'h' => - 1, 's' => 0.03, 'l' => - 0.08 ),
				700 => array( 'h' => - 3, 's' => 0.08, 'l' => - 0.18 ),
				800 => array( 'h' => - 6, 's' => 0.12, 'l' => - 0.32 ),
				900 => array( 'h' => - 8, 's' => 0.18, 'l' => - 0.45 ),
			);

			foreach ( $adjustments as $level => $adj ) {
				if ( $level == 500 ) {
					$shades[ $level ] = '#' . $base_color;
				} else {
					// Apply HSL adjustments
					$new_h = $hsl['h'] + $adj['h'];
					$new_s = max( 0, min( 1, $hsl['s'] + $adj['s'] ) );
					$new_l = max( 0, min( 1, $hsl['l'] + $adj['l'] ) );

					// Convert back to hex
					$shades[ $level ] = $this->bb_rl_hsl_to_hex( $new_h, $new_s, $new_l );
				}
			}

			return $shades;
		}

		/**
		 * Convert hex color to HSL.
		 *
		 * @since BuddyBoss 2.9.30
		 *
		 * @param string $hex Hex color without #.
		 *
		 * @return array HSL values.
		 */
		private function bb_rl_hex_to_hsl( $hex ) {
			$r = hexdec( substr( $hex, 0, 2 ) ) / 255;
			$g = hexdec( substr( $hex, 2, 2 ) ) / 255;
			$b = hexdec( substr( $hex, 4, 2 ) ) / 255;

			$max  = max( $r, $g, $b );
			$min  = min( $r, $g, $b );
			$diff = $max - $min;

			// Lightness
			$l = ( $max + $min ) / 2;

			if ( $diff == 0 ) {
				$h = $s = 0; // achromatic
			} else {
				// Saturation
				$s = $l > 0.5 ? $diff / ( 2 - $max - $min ) : $diff / ( $max + $min );

				// Hue
				switch ( $max ) {
					case $r:
						$h = ( $g - $b ) / $diff + ( $g < $b ? 6 : 0 );
						break;
					case $g:
						$h = ( $b - $r ) / $diff + 2;
						break;
					case $b:
						$h = ( $r - $g ) / $diff + 4;
						break;
				}
				$h /= 6;
			}

			return array( 'h' => $h * 360, 's' => $s, 'l' => $l );
		}

		/**
		 * Convert HSL to hex color.
		 *
		 * @since BuddyBoss 2.9.30
		 *
		 * @param float $h Hue (0-360).
		 * @param float $s Saturation (0-1).
		 * @param float $l Lightness (0-1).
		 *
		 * @return string Hex color with #.
		 */
		private function bb_rl_hsl_to_hex( $h, $s, $l ) {
			$h = fmod( $h, 360 );
			if ( $h < 0 ) {
				$h += 360;
			}
			$h /= 360;

			if ( $s == 0 ) {
				$r = $g = $b = $l; // achromatic
			} else {
				$hue2rgb = function ( $p, $q, $t ) {
					if ( $t < 0 ) {
						$t += 1;
					}
					if ( $t > 1 ) {
						$t -= 1;
					}
					if ( $t < 1 / 6 ) {
						return $p + ( $q - $p ) * 6 * $t;
					}
					if ( $t < 1 / 2 ) {
						return $q;
					}
					if ( $t < 2 / 3 ) {
						return $p + ( $q - $p ) * ( 2 / 3 - $t ) * 6;
					}

					return $p;
				};

				$q = $l < 0.5 ? $l * ( 1 + $s ) : $l + $s - $l * $s;
				$p = 2 * $l - $q;

				$r = $hue2rgb( $p, $q, $h + 1 / 3 );
				$g = $hue2rgb( $p, $q, $h );
				$b = $hue2rgb( $p, $q, $h - 1 / 3 );
			}

			return sprintf( '#%02x%02x%02x',
				round( $r * 255 ),
				round( $g * 255 ),
				round( $b * 255 )
			);
		}

		/**
		 * Add dynamic colours to the frontend.
		 *
		 * @since BuddyBoss 2.9.00
		 */
		public function bb_rl_dynamic_colors() {
			$color_light = bp_get_option( 'bb_rl_color_light', '#4946fe' );
			$color_dark  = bp_get_option( 'bb_rl_color_dark', '#9747FF' );
			
			// Generate color shades for light mode (500 is base).
			$light_shades = $this->bb_rl_generate_color_shades( $color_light );
			
			// Generate color shades for dark mode (500 is base).
			$dark_shades = $this->bb_rl_generate_color_shades( $color_dark );
			?>
			<style>
				:root {
					/* Light mode color shades. */
					--bb-rl-background-brand-secondary-color: <?php echo esc_attr( $light_shades[100] ); ?>;
					--bb-rl-background-brand-secondary-hover-color: <?php echo esc_attr( $light_shades[200] ); ?>;
					--bb-rl-background-brand-disabled-color: <?php echo esc_attr( $light_shades[400] ); ?>;
					--bb-rl-icon-brand-disabled-color: <?php echo esc_attr( $light_shades[400] ); ?>;
					--bb-rl-background-brand-primary-hover-color: <?php echo esc_attr( $light_shades[600] ); ?>;
					--bb-rl-text-brand-secondary-color: <?php echo esc_attr( $light_shades[800] ); ?>;
					--bb-rl-icon-brand-primary-color: <?php echo esc_attr( $light_shades[800] ); ?>;
					--bb-rl-border-brand-primary-color: <?php echo esc_attr( $light_shades[800] ); ?>;
					
					/* Keep backward compatibility. */
					--bb-rl-primary-color: <?php echo esc_attr( $color_light ); ?>;
				}

				.bb-rl-dark-mode {
					/* Dark mode color shades. */
					--bb-rl-background-brand-secondary-color: <?php echo esc_attr( $dark_shades[100] ); ?>;
					--bb-rl-text-brand-secondary-color: <?php echo esc_attr( $dark_shades[200] ); ?>;
					--bb-rl-border-brand-primary-color: <?php echo esc_attr( $dark_shades[200] ); ?>;
					--bb-rl-icon-brand-primary-color: <?php echo esc_attr( $dark_shades[200] ); ?>;
					--bb-rl-primary-300: <?php echo esc_attr( $dark_shades[300] ); ?>;
					--bb-rl-background-brand-disabled-color: <?php echo esc_attr( $dark_shades[400] ); ?>;
					--bb-rl-icon-brand-disabled-color: <?php echo esc_attr( $dark_shades[400] ); ?>;
					--bb-rl-background-brand-primary-hover-color: <?php echo esc_attr( $dark_shades[600] ); ?>;
					--bb-rl-primary-700: <?php echo esc_attr( $dark_shades[700] ); ?>;
					--bb-rl-background-brand-secondary-color: <?php echo esc_attr( $dark_shades[800] ); ?>;
					--bb-rl-background-brand-secondary-hover-color: <?php echo esc_attr( $dark_shades[900] ); ?>;
					
					/* Keep backward compatibility. */
					--bb-rl-primary-color: <?php echo esc_attr( $color_dark ); ?>;
				}
			</style>
			<?php
		}

		/**
		 * Filter the search JS settings for ReadyLaunch
		 *
		 * @since BuddyBoss 2.9.00
		 *
		 * @param array $settings Search settings array.
		 *
		 * @return array Modified settings.
		 */
		public function bb_rl_filter_search_js_settings( $settings ) {
			// Set the autocomplete selector for ReadyLaunch search form.
			$settings['rl_autocomplete_selector'] = '.bb-rl-network-search-modal .search-form';

			return $settings;
		}

		/**
		 * Register the ReadyLaunch Header block.
		 *
		 * @since BuddyBoss 2.9.00
		 */
		public function bb_rl_register_blocks() {
			// Register block assets.
			$this->register_readylaunch_header_assets();

			bb_register_block(
				array(
					'metadata'        => trailingslashit( buddypress()->plugin_dir ) . 'bp-core/blocks/readylaunch-header',
					'render_callback' => 'bb_block_render_readylaunch_header_block',
				)
			);
		}

		/**
		 * Register assets for the ReadyLaunch Header block.
		 *
		 * @since BuddyBoss 2.9.00
		 */
		private function register_readylaunch_header_assets() {
			$plugin_url = trailingslashit( buddypress()->plugin_url );
			$plugin_dir = trailingslashit( buddypress()->plugin_dir );
			$min        = bp_core_get_minified_asset_suffix();

			// Register the editor script.
			$asset_file = $plugin_dir . 'bp-core/blocks/readylaunch-header/index.asset.php';

			if ( ! file_exists( $asset_file ) ) {
				return;
			}

			$asset = include $asset_file;

			// Register the admin script.
			wp_register_script(
				'buddyboss-readylaunch-header-editor-script',
				$plugin_url . 'bp-core/blocks/readylaunch-header/index.js',
				$asset['dependencies'],
				$asset['version'],
				true
			);

			wp_set_script_translations( 'buddyboss-readylaunch-header-editor-script', 'buddyboss', buddypress()->plugin_dir . 'languages/' );

			// Register the view script.
			wp_register_script(
				'bb-readylaunch-header-view',
				$plugin_url . 'bp-core/blocks/readylaunch-header/view.js',
				array( 'jquery', 'bp-nouveau', 'bp-select2', 'wp-i18n' ),
				bp_get_version(),
				true
			);

			wp_localize_script(
				'bb-readylaunch-header-view',
				'bbReadyLaunchFront',
				array(
					'ajax_url'   => admin_url( 'admin-ajax.php' ),
					'nonce'      => wp_create_nonce( 'bb-readylaunch' ),
					'more_nav'   => esc_html__( 'More', 'buddyboss' ),
					'filter_all' => esc_html__( 'All', 'buddyboss' ),
				)
			);

			wp_set_script_translations( 'bb-readylaunch-header-view', 'buddyboss', buddypress()->plugin_dir . 'languages/' );

			wp_register_style(
				'bb-icons-rl-css',
				$plugin_url . "bp-templates/bp-nouveau/readylaunch/icons/css/bb-icons-rl{$min}.css",
				array(),
				bp_get_version()
			);
		}

		/**
		 * Add ReadyLaunch settings to the platform settings API.
		 *
		 * @since BuddyBoss 2.9.00
		 *
		 * @param array $settings Array of platform settings.
		 *
		 * @return array Modified array of platform settings.
		 */
		public function bb_rest_readylaunch_platform_settings( $settings ) {
			// Activation Settings - Boolean.
			$settings['bb_rl_enabled'] = (bool) bp_get_option( 'bb_rl_enabled', false );
			$settings['blogname']      = (string) get_bloginfo( 'name' );

			// Style Settings.
			$settings['bb_rl_light_logo'] = bp_get_option( 'bb_rl_light_logo', array() );
			$settings['bb_rl_dark_logo']  = bp_get_option( 'bb_rl_dark_logo', array() );

			if ( false === bp_get_option( 'bb_rl_color_light', false ) ) {
				bp_update_option( 'bb_rl_color_light', '#3E34FF' );
			}

			if ( false === bp_get_option( 'bb_rl_color_dark', false ) ) {
				bp_update_option( 'bb_rl_color_dark', '#A347FF' );
			}

			if ( false === bp_get_option( 'bb_rl_theme_mode', false ) ) {
				bp_update_option( 'bb_rl_theme_mode', 'light' );
			}

			$settings['bb_rl_color_light'] = (string) bp_get_option( 'bb_rl_color_light', '#3E34FF' );
			$settings['bb_rl_color_dark']  = (string) bp_get_option( 'bb_rl_color_dark', '#A347FF' );
			$settings['bb_rl_theme_mode']  = (string) bp_get_option( 'bb_rl_theme_mode', 'light' );

			$enabled_pages = array();
			if ( bp_enable_site_registration() && ! bp_allow_custom_registration() ) {
				$enabled_pages['registration'] = true;
			}
			if ( $this->bb_is_sidebar_enabled_for_courses() ) {
				$enabled_pages['courses'] = true;
			}

			if ( false === bp_get_option( 'bb_rl_enabled_pages', false ) && ! empty( $enabled_pages ) ) {
				bp_update_option( 'bb_rl_enabled_pages', $enabled_pages );
			}

			// Pages & Sidebars Settings - Boolean values in arrays.
			$settings['bb_rl_enabled_pages'] = array_map(
				function ( $value ) {
					return (bool) $value;
				},
				bp_get_option(
					'bb_rl_enabled_pages',
					$enabled_pages
				)
			);

			$activity_sidebars = array(
				'complete_profile'  => true,
				'latest_updates'    => true,
				'recent_blog_posts' => true,
				'active_members'    => true,
			);

			if ( false === bp_get_option( 'bb_rl_activity_sidebars', false ) ) {
				bp_update_option( 'bb_rl_activity_sidebars', $activity_sidebars );
			}

			$settings['bb_rl_activity_sidebars'] = array_map(
				function ( $value ) {
					return (bool) $value;
				},
				bp_get_option(
					'bb_rl_activity_sidebars',
					$activity_sidebars
				)
			);

			$member_sidebar = array( 'complete_profile' => true );

			if ( bp_is_active( 'friends' ) ) {
				$member_sidebar['connections'] = true;
			}

			if ( bp_is_active( 'activity' ) && bp_is_activity_follow_active() ) {
				$member_sidebar['my_network'] = true;
			}

			if ( false === bp_get_option( 'bb_rl_member_profile_sidebars', false ) ) {
				bp_update_option( 'bb_rl_member_profile_sidebars', $member_sidebar );
			}

			$member_sidebar = wp_parse_args(
				bp_get_option( 'bb_rl_member_profile_sidebars', $member_sidebar ),
				$member_sidebar
			);

			$settings['bb_rl_member_profile_sidebars'] = array_map(
				function ( $value ) {
					return (bool) $value;
				},
				$member_sidebar
			);

			$group_sidebars = array(
				'about_group'   => true,
				'group_members' => true,
			);

			if ( false === bp_get_option( 'bb_rl_groups_sidebars', false ) ) {
				bp_update_option( 'bb_rl_groups_sidebars', $group_sidebars );
			}

			$settings['bb_rl_groups_sidebars'] = array_map(
				function ( $value ) {
					return (bool) $value;
				},
				bp_get_option(
					'bb_rl_groups_sidebars',
					$group_sidebars
				)
			);

			// Menu Settings.
			$settings['bb_rl_header_menu'] = (string) bp_get_option( 'bb_rl_header_menu', 'readylaunch' );

			$defaults = array(
				'activity_feed' => array(
					'enabled' => true,
					'order'   => 0,
					'icon'    => 'pulse',
				),
				'members'       => array(
					'enabled' => true,
					'order'   => 1,
					'icon'    => 'users',
				),
				'groups'        => array(
					'enabled' => true,
					'order'   => 2,
					'icon'    => 'users-three',
				),
				'courses'       => array(
					'enabled' => true,
					'order'   => 3,
					'icon'    => 'graduation-cap',
				),
				'forums'        => array(
					'enabled' => true,
					'order'   => 4,
					'icon'    => 'chat-text',
				),
				'messages'      => array(
					'enabled' => false,
					'order'   => 5,
					'icon'    => 'chat-teardrop-text',
				),
				'notifications' => array(
					'enabled' => false,
					'order'   => 6,
					'icon'    => 'bell',
				),
			);

			if ( ! bp_is_active( 'activity' ) ) {
				unset( $defaults['activity_feed'] );
			}
			if ( ! bp_is_active( 'groups' ) ) {
				unset( $defaults['groups'] );
			}
			if ( ! bp_is_active( 'forums' ) ) {
				unset( $defaults['forums'] );
			}
			if ( ! bp_is_active( 'messages' ) ) {
				unset( $defaults['messages'] );
			}
			if ( ! bp_is_active( 'notifications' ) ) {
				unset( $defaults['notifications'] );
			}
			if ( ! $this->bb_is_sidebar_enabled_for_courses() ) {
				unset( $defaults['courses'] );
			}

			if ( false === bp_get_option( 'bb_rl_side_menu', false ) ) {
				bp_update_option( 'bb_rl_side_menu', $defaults );
			}

			$raw_settings = wp_parse_args(
				bp_get_option( 'bb_rl_side_menu', $defaults ),
				$defaults
			);

			$settings['bb_rl_side_menu'] = array_map(
				function ( $item ) {
					return array(
						'enabled' => ! empty( $item['enabled'] ),
						'order'   => isset( $item['order'] ) ? (int) $item['order'] : 0,
						'icon'    => isset( $item['icon'] ) ? $item['icon'] : '',
					);
				},
				$raw_settings
			);

			// Custom Links - Array of objects with specific types.
			$custom_links = bp_get_option( 'bb_rl_custom_links', array() );

			$settings['bb_rl_custom_links'] = array_map(
				function ( $link ) {
					return array(
						'id'    => (int) $link['id'],
						'title' => (string) $link['title'],
						'url'   => (string) $link['url'],
					);
				},
				$custom_links
			);

			return $settings;
		}

		/**
		 * Enqueue LMS styles for ReadyLaunch.
		 *
		 * @since BuddyBoss 2.9.00
		 */
		public function bb_readylaunch_lms_enqueue_styles() {
			if ( ! bb_is_readylaunch_enabled() || ( ! class_exists( 'SFWD_LMS' ) && ! class_exists( 'memberpress\courses\helpers\Courses' ) ) ) {
				return;
			}

			// Enqueue LearnDash ReadyLaunch styles.
			wp_enqueue_style(
				'bb-readylaunch-lms',
				buddypress()->plugin_url . 'bp-templates/bp-nouveau/readylaunch/css/lms.css',
				array(),
				bp_get_version()
			);
		}

		/**
		 * Enqueue MemberPress Courses styles for ReadyLaunch.
		 *
		 * @since BuddyBoss 2.9.00
		 */
		public function bb_readylaunch_meprlms_enqueue_styles() {
			if ( ! bb_is_readylaunch_enabled() || ! class_exists( 'memberpress\courses\helpers\Courses' ) ) {
				return;
			}

			// Enqueue MemberPress Courses ReadyLaunch styles.
			wp_enqueue_style(
				'bb-readylaunch-meprlms',
				buddypress()->plugin_url . 'bp-templates/bp-nouveau/readylaunch/css/meprlms.css',
				array(),
				bp_get_version()
			);

			// Enqueue our MemberPress Courses helper JavaScript.
			wp_enqueue_script(
				'bb-readylaunch-meprlms-js',
				buddypress()->plugin_url . 'bp-templates/bp-nouveau/readylaunch/js/bb-readylaunch-meprlms.js',
				array( 'jquery' ),
				bp_get_version(),
				true
			);

			wp_localize_script(
				'bb-readylaunch-meprlms-js',
				'bbReadylaunchMeprlms',
				array(
					'courses_url' => home_url( '/courses/' ),
				)
			);
		}

		/**
		 * Check if the current page is a LearnDash page.
		 *
		 * @since BuddyBoss 2.9.00
		 *
		 * @return bool True if the current page is a LearnDash page, false otherwise.
		 */
		public function bb_rl_is_learndash_page() {
			if ( ! class_exists( 'SFWD_LMS' ) ) {
				return false;
			}

			$courses_integration = bp_get_option( 'bb_rl_enabled_pages' )['courses'] ?? false;
			if ( ! $courses_integration ) {
				return false;
			}

			global $post, $wp_query;

			// Multiple ways to get the post type.
			$post_type = '';

			// Get post type.
			if ( function_exists( 'get_post_type' ) ) {
				$post_type = get_post_type();
			}

			// Check global $post.
			if ( empty( $post_type ) && isset( $post->post_type ) ) {
				$post_type = $post->post_type;
			}

			// Check queried object.
			if ( empty( $post_type ) && is_object( $wp_query ) ) {
				$queried_object = get_queried_object();
				if ( $queried_object && isset( $queried_object->post_type ) ) {
					$post_type = $queried_object->post_type;
				}
			}

			// Check query vars.
			if ( empty( $post_type ) && is_object( $wp_query ) && isset( $wp_query->query_vars['post_type'] ) ) {
				$post_type = $wp_query->query_vars['post_type'];
			}

			// LearnDash post types.
			$ld_post_types = array(
				learndash_get_post_type_slug( 'course' ),
				learndash_get_post_type_slug( 'lesson' ),
				learndash_get_post_type_slug( 'topic' ),
				learndash_get_post_type_slug( 'quiz' ),
				learndash_get_post_type_slug( 'assignment' ),
				learndash_get_post_type_slug( 'essays' ),
				learndash_get_post_type_slug( 'group' ),
				learndash_get_post_type_slug( 'exam' ),
			);

			// Check for course archive using multiple methods.
			if ( is_post_type_archive( $ld_post_types ) || is_singular( $ld_post_types ) ) {
				return true;
			}

			// Check if post type matches LearnDash types.
			if ( ! empty( $post_type ) && in_array( $post_type, $ld_post_types, true ) ) {
				return true;
			}

			// Check REQUEST_URI for LearnDash patterns.
			if (
				(
					! bp_is_user() &&
					! bp_is_group() &&
					! bp_is_groups_directory() &&
					! bp_is_group_single() &&
					! bp_is_group_create()
				) &&
				isset( $_SERVER['REQUEST_URI'] )
			) {
				$request_uri = sanitize_text_field( wp_unslash( $_SERVER['REQUEST_URI'] ) );

				// Check for any LearnDash post type in the URL.
				foreach ( $ld_post_types as $ld_post_type ) {
					if ( ! empty( $ld_post_type ) && strpos( $request_uri, $ld_post_type ) !== false ) {
						return true;
					}
				}

				// Additional patterns to check for LearnDash URLs (excluding BuddyPress patterns).
				$ld_patterns = array(
					'/lesson/',
					'/lessons/',
					'/course/',
					'/courses/',
					'/topic/',
					'/topics/',
					'/quiz/',
					'/quizzes/',
					'/assignment/',
					'/assignments/',
					'/essays/',
					'sfwd-lessons',
					'sfwd-courses',
					'sfwd-topic',
					'sfwd-quiz',
					'sfwd-assignment',
					'sfwd-essays',
					'sfwd-groups', // Use specific LearnDash group slug.
				);

				foreach ( $ld_patterns as $pattern ) {
					if ( strpos( $request_uri, $pattern ) !== false ) {
						return true;
					}
				}

				// Legacy check for courses.
				if ( defined( 'LDLMS_Post_Types::COURSE' ) && strpos( $request_uri, LDLMS_Post_Types::COURSE ) !== false ) {
					return true;
				}
			}

			$ld_taxonomies = array(
				'ld_course_category',
				'ld_course_tag',
				'ld_lesson_category',
				'ld_lesson_tag',
			);

			foreach ( $ld_taxonomies as $tax ) {
				if ( is_tax( $tax ) ) {
					return true;
				}
			}

			// Group leader pages.
			if ( function_exists( 'learndash_is_group_leader_user' ) && learndash_is_group_leader_user() ) {
				return true;
			}

			// Check if current page is a LearnDash registration or reset password page.
			if (
				$this->bb_rl_is_learndash_registration_page() ||
				$this->bb_rl_is_learndash_reset_password_page()
			) {
				return true;
			}

			return false;
		}

		/**
		 * Return the theme mode option.
		 *
		 * @since BuddyBoss 2.9.00
		 */
		public function bb_rl_get_theme_mode() {
			$bb_rl_theme_mode = bp_get_option( 'bb_rl_theme_mode', 'light' );

			return $bb_rl_theme_mode;
		}

		/**
		 * Return the theme logo option.
		 *
		 * @since BuddyBoss 2.9.00
		 *
		 * @param string $key The key of the logo.
		 *
		 * @return array
		 */
		public function bb_rl_get_theme_logo( $key ) {
			$bb_rl_theme_logo = bp_get_option( 'bb_rl_' . $key . '_logo', array() );

			return $bb_rl_theme_logo;
		}

		/**
		 * Function to check pages is enabled for integration.
		 *
		 * @since BuddyBoss 2.9.00
		 *
		 * @param string $page The page to check.
		 *
		 * @return bool True if the page is enabled for integration, false otherwise.
		 */
		public function bb_rl_is_page_enabled_for_integration( $page ) {
			if ( 'registration' === $page && ( ! bp_enable_site_registration() || bp_allow_custom_registration() ) ) {
				return false;
			}

			$enabled_pages = bp_get_option( 'bb_rl_enabled_pages' );

			return ! empty( $enabled_pages[ $page ] );
		}

		/**
		 * Check if current page is a LearnDash registration page
		 *
		 * @since BuddyBoss 2.9.30
		 *
		 * @return bool True if current page is a LearnDash registration page
		 */
		public function bb_rl_is_learndash_registration_page() {
			// Check if LearnDash is active.
			if ( ! function_exists( 'learndash_registration_page_get_id' ) ) {
				return false;
			}

			// Check for URL parameters that indicate registration.
			if ( isset( $_GET['ld_register_id'] ) || isset( $_GET['course_id'] ) || isset( $_GET['group_id'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
				return true;
			}

			// Check if current page has registration shortcode.
			global $post;
			if ( $post && has_shortcode( $post->post_content, 'ld_registration' ) ) {
				return true;
			}

			// Get the registration page ID.
			$registration_page_id = learndash_registration_page_get_id();

			// Only check page ID if a registration page is actually set.
			if ( ! empty( $registration_page_id ) ) {
				// Check if current page matches the registration page.
				$current_page_id = get_queried_object_id();
				if ( $current_page_id && (int) $current_page_id === (int) $registration_page_id ) {
					return true;
				}
			}

			return false;
		}

		/**
		 * Check if the current page is a LearnDash reset password page.
		 *
		 * @since BuddyBoss 2.9.30
		 *
		 * @return bool True if the current page is a LearnDash reset password page, false otherwise.
		 */
		public function bb_rl_is_learndash_reset_password_page() {
			// Check if LearnDash is active and integration is enabled.
			// For reset password pages, we'll bypass this check to ensure it works.
			$integration_enabled = $this->bb_rl_is_page_enabled_for_integration( 'learndash' );
			if ( ! $integration_enabled ) {
				// Don't return false here - continue with detection.
			}

			// Check for URL parameters that indicate password reset.
			if ( isset( $_GET['ld-resetpw'] ) || isset( $_GET['password_reset'] ) || isset( $_GET['key'] ) || isset( $_GET['login'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
				return true;
			}

			// Check if the current page template is being used for password reset.
			global $post;
			if ( $post && has_shortcode( $post->post_content, 'ld_reset_password' ) ) {
				return true;
			}

			// Check if this is the LearnDash reset password page.
			if ( function_exists( 'learndash_get_reset_password_page_id' ) ) {
				$reset_password_page_id = learndash_get_reset_password_page_id();
				if ( ! empty( $reset_password_page_id ) ) {
					$current_page_id = get_queried_object_id();
					if ( $current_page_id && (int) $current_page_id === (int) $reset_password_page_id ) {
						return true;
					}
				}
			}

			return false;
		}

		/**
		 * Check if the current page is a MemberPress courses page.
		 *
		 * @since BuddyBoss 2.9.00
		 *
		 * @return bool True if the current page is a MemberPress courses page, false otherwise.
		 */
		public function bb_rl_is_memberpress_courses_page() {
			if ( ! class_exists( 'memberpress\courses\helpers\Courses' ) || ! $this->bb_rl_is_page_enabled_for_integration( 'courses' ) ) {
				return false;
			}

			global $post, $wp_query;

			// Method 1: Use MemberPress's own detection methods (most reliable).
			if ( isset( $post ) && is_a( $post, 'WP_Post' ) ) {
				// Check if this is a course page using MemberPress helper.
				if (
					class_exists( 'memberpress\courses\helpers\Courses' ) &&
					method_exists( 'memberpress\courses\helpers\Courses', 'is_a_course' ) &&
					memberpress\courses\helpers\Courses::is_a_course( $post )
				) {
					return true;
				}

				// Check if this is a lesson page using MemberPress helper.
				if (
					class_exists( 'memberpress\courses\helpers\Lessons' ) &&
					method_exists( 'memberpress\courses\helpers\Lessons', 'is_a_lesson' ) &&
					memberpress\courses\helpers\Lessons::is_a_lesson( $post )
				) {
					return true;
				}
			}

			// Method 2: Check for course archive using URL patterns.
			if (
				class_exists( 'memberpress\courses\helpers\Courses' ) &&

				method_exists( 'memberpress\courses\helpers\Courses', 'get_permalink_base' )
			) {
				$courses_base = memberpress\courses\helpers\Courses::get_permalink_base();
				if ( ! empty( $courses_base ) && isset( $_SERVER['REQUEST_URI'] ) ) {
					$request_uri = sanitize_text_field( wp_unslash( $_SERVER['REQUEST_URI'] ) );
					// Check if this is the courses archive page.
					if ( strpos( $request_uri, '/' . $courses_base ) !== false ) {
						return true;
					}
				}
			}

			// Method 3: Fallback to post type detection.
			$post_type = '';

			// Check global $post first.
			if ( isset( $post ) && is_a( $post, 'WP_Post' ) && ! empty( $post->post_type ) ) {
				$post_type = $post->post_type;
			}

			// Get post type using WordPress function.
			if ( empty( $post_type ) && function_exists( 'get_post_type' ) ) {
				$current_post_type = get_post_type();
				if ( ! empty( $current_post_type ) ) {
					$post_type = $current_post_type;
				}
			}

			// Check queried object.
			if ( empty( $post_type ) && is_object( $wp_query ) ) {
				$queried_object = get_queried_object();
				if ( $queried_object && isset( $queried_object->post_type ) && ! empty( $queried_object->post_type ) ) {
					$post_type = $queried_object->post_type;
				}
			}

			// Check query vars.
			if ( empty( $post_type ) && is_object( $wp_query ) && isset( $wp_query->query_vars['post_type'] ) && ! empty( $wp_query->query_vars['post_type'] ) ) {
				$post_type = $wp_query->query_vars['post_type'];
			}

			// Method 4: Check against known MemberPress post types.
			if ( ! empty( $post_type ) && is_single() ) {

				// Check if this is a course post type.
				if ( class_exists( 'memberpress\courses\models\Course' ) && memberpress\courses\models\Course::$cpt === $post_type ) {
					return true;
				}

				// Check if this is a lesson post type.
				if ( class_exists( 'memberpress\courses\models\Lesson' ) && memberpress\courses\models\Lesson::$cpt === $post_type ) {
					return true;
				}

				// Check if this is an assignment post type.
				if ( class_exists( 'memberpress\assignments\models\Assignment' ) && memberpress\assignments\models\Assignment::$cpt === $post_type ) {
					return true;
				}

				// Check if this is a quiz post type.
				if ( class_exists( 'memberpress\quizzes\models\Quiz' ) && memberpress\quizzes\models\Quiz::$cpt === $post_type ) {
					return true;
				}
			}

			return false;
		}

		/**
		 * Get the header menu option.
		 *
		 * @since BuddyBoss 2.9.00
		 */
		public function bb_rl_get_header_menu_location() {
			$header_menu_slug = bp_get_option( 'bb_rl_header_menu', 'readylaunch' );

			if ( empty( $header_menu_slug ) ) {
				return '';
			}

			$header_menu_id = '';
			$menus          = wp_get_nav_menu_object( $header_menu_slug );
			if ( ! empty( $menus ) ) {
				$header_menu_id = $menus->term_id;
			}

			return $header_menu_id;
		}

		/**
		 * Check if the current page is a BuddyBoss ReadyLaunch Forums page.
		 *
		 * @since BuddyBoss 2.9.00
		 *
		 * @return bool True if the current page is a ReadyLaunch Forums page, false otherwise.
		 */
		public function bb_is_readylaunch_forums() {
			return bp_is_active( 'forums' )
					&& (
						bbp_is_forum_archive() ||
						bbp_is_topic_archive() ||
						bbp_is_single_forum() ||
						bbp_is_forum_edit() ||
						( bbp_is_single_topic() && ! bp_is_activity_component() ) ||
						bbp_is_topic_edit() ||
						bbp_is_topic_split() ||
						bbp_is_topic_merge() ||
						bbp_is_single_reply() ||
						bbp_is_reply_edit() ||
						bbp_is_reply_move() ||
						bbp_is_single_view() ||
						bbp_is_search() ||
						bbp_is_topic_tag_edit() ||
						bbp_is_topic_tag() ||
						is_singular( bbp_get_topic_post_type() ) ||
						is_singular( bbp_get_forum_post_type() ) ||
						is_singular( bbp_get_reply_post_type() ) ||
						is_post_type_archive( bbp_get_topic_post_type() ) ||
						is_post_type_archive( bbp_get_forum_post_type() ) ||
						is_post_type_archive( bbp_get_reply_post_type() ) ||
						(
							bbp_is_group_forums_active() &&
							(
								bp_is_group_single() ||
								bp_is_group_forum_topic() ||
								bp_is_group_forum_topic_edit()
							)
						)
					);
		}

		/**
		 * Enqueue styles and scripts for ReadyLaunch Forums.
		 *
		 * @since BuddyBoss 2.9.00
		 */
		public function bb_readylaunch_forums_enqueue_styles() {
			if (
				! $this->bb_is_readylaunch_forums() &&
				! (
					bp_is_active( 'forums' ) &&
					bbp_is_single_user()
				)
			) {
				return;
			}

			$min = bp_core_get_minified_asset_suffix();

			// enqueue select2, emojionearea, medium editor.
			wp_enqueue_script( 'bp-select2' );
			wp_enqueue_style( 'bp-select2' );

			wp_enqueue_style( 'emojionearea' );
			wp_enqueue_script( 'emojionearea' );

			wp_enqueue_script( 'bp-medium-editor' );
			wp_enqueue_style( 'bp-medium-editor' );
			wp_enqueue_style( 'bp-medium-editor-beagle' );

			wp_enqueue_script( 'giphy' );

			// Enqueue Forum ReadyLaunch styles.
			wp_enqueue_style(
				'bb-readylaunch-forums',
				buddypress()->plugin_url . "bp-templates/bp-nouveau/readylaunch/css/forums{$min}.css",
				array(),
				bp_get_version()
			);

			// Dequeue default bbpress scripts to avoid conflict with readylaunch scripts.
			// Should load after readylaunch scripts.
			wp_deregister_script( 'bb-topic-reply-draft' );
			wp_dequeue_script( 'bb-topic-reply-draft' );

			// Enqueue Topic Reply Draft JavaScript.
			wp_enqueue_script(
				'bb-readylaunch-topic-reply-draft',
				buddypress()->plugin_url . "bp-templates/bp-nouveau/readylaunch/js/bb-readylaunch-topic-reply-draft{$min}.js",
				array( 'jquery' ),
				bp_get_version(),
				true
			);

			// Enqueue our Forum helper JavaScript.
			wp_enqueue_script(
				'bb-readylaunch-forums-js',
				buddypress()->plugin_url . "bp-templates/bp-nouveau/readylaunch/js/bb-readylaunch-forums{$min}.js",
				array( 'jquery', 'bp-nouveau' ),
				bp_get_version(),
				true
			);

			// Localize data to the forums script.
			wp_localize_script(
				'bb-readylaunch-forums-js',
				'bbrlForumsEditorJsStrs',
				array(
					'description' => __( 'Write a description', 'buddyboss' ),
					'type_reply'  => __( 'Type your reply here', 'buddyboss' ),
					'type_topic'  => __( 'Type your discussion content here', 'buddyboss' ),
				)
			);

			$no_load_topic = true;
			if ( bbp_allow_topic_tags() && current_user_can( 'assign_topic_tags' ) ) {
				$no_load_topic = false;
			}

			$common_array = array(
				'loading_text' => __( 'Loading', 'buddyboss' ),
				'ajax_url'     => bp_core_ajax_url(),
				'nonce'        => wp_create_nonce( 'search_tag' ),
				'load'         => $no_load_topic,
				'tag_text'     => __( 'Add Tags:', 'buddyboss' ),
			);

			wp_localize_script( 'bb-readylaunch-forums-js', 'bbrlForumsCommonJsData', $common_array );
			if ( bbp_is_single_topic() || ( function_exists( 'bp_is_group' ) && bp_is_group() ) ) {
				ob_start();
				bbp_get_template_part( 'form', 'reply' );
				$reply_form_html = ob_get_clean();
				wp_localize_script(
					'bb-readylaunch-forums-js',
					'bbpReplyAjaxJS',
					array(
						'bbp_ajaxurl'          => bbp_get_ajax_url(),
						'generic_ajax_error'   => esc_html__( 'Something went wrong. Refresh your browser and try again.', 'buddyboss' ),
						'is_user_logged_in'    => is_user_logged_in(),
						'reply_nonce'          => wp_create_nonce( 'reply-ajax_' . get_the_ID() ),
						'topic_id'             => bbp_get_topic_id(),
						'reply_form_html'      => $reply_form_html,
						'threaded_reply'       => bbp_allow_threaded_replies(),
						'threaded_reply_depth' => bbp_thread_replies_depth(),
						'reply_to_text'        => esc_html__( 'Reply to', 'buddyboss' ),
						'type_reply_here_text' => esc_html__( 'Type your reply here', 'buddyboss' ),
					)
				);
			}
		}

		/**
		 * Add the 'Quick Reply' button to the activity stream.
		 *
		 * @since BuddyBoss 2.9.00
		 *
		 * @param array $buttons Array of buttons.
		 * @param int   $activity_id Activity ID.
		 *
		 * @return array Modified array of buttons.
		 */
		public function bb_rl_activity_inner_buttons( $buttons, $activity_id ) {
			// Get activity post data.
			$activities = bp_activity_get_specific( array( 'activity_ids' => $activity_id ) );

			if ( empty( $activities['activities'] ) ) {
				return $buttons;
			}

			$activity = array_shift( $activities['activities'] );

			if ( 'bbp_topic_create' === $activity->type ) {
				// Set topic id when the activity component is not groups.
				if ( 'bbpress' === $activity->component ) {
					$topic_id = $activity->item_id;
				}

				// Set topic id when the activity component is groups.
				if ( 'groups' === $activity->component ) {
					$topic_id = $activity->secondary_item_id;
				}

				// bbp_get_topic_author_id.
				$topic_title = get_post_field( 'post_title', $topic_id, 'raw' );
				$user_id     = bbp_get_topic_author_id( $topic_id );
				$author      = bp_core_get_user_displayname( $user_id );

				// New meta button as 'Quick Reply'.
				$buttons['quick_reply'] = array(
					'id'                => 'quick_reply',
					'position'          => 5,
					'component'         => 'activity',
					'must_be_logged_in' => true,
					'button_element'    => 'a',
					'link_text'         => sprintf(
						'<span class="bp-screen-reader-text">%1$s</span> <span class="comment-count">%2$s</span>',
						esc_html__( 'Quick Reply', 'buddyboss' ),
						esc_html__( 'Quick Reply', 'buddyboss' )
					),
					'button_attr'       => array(
						'class'            => 'bb-icon-l button bb-icon-comment bp-secondary-action',
						'data-btn-id'      => 'bbp-reply-form',
						'data-topic-title' => esc_attr( $topic_title ),
						'data-topic-id'    => $topic_id,
						'aria-expanded'    => 'false',
						'href'             => '#new-post',
						'data-author-name' => $author,
					),
				);
			}

			return $buttons;
		}

		/**
		 * Handle AJAX reply submission.
		 *
		 * @since BuddyBoss 2.9.00
		 */
		public function bb_rl_ajax_reply() {
			// phpcs:ignore
			$action = $_POST['bbp_reply_form_action'];
			if ( 'bbp-new-reply' === $action ) {
				bbp_new_reply_handler( $action );
			} elseif ( 'bbp-edit-reply' === $action ) {
				bbp_edit_reply_handler( $action );
			}
		}

		/**
		 * New pre-replies.
		 *
		 * @since BuddyBoss 2.9.00
		 */
		public function bb_rl_new_reply_pre_extras() {
			if ( ! bbp_is_ajax() ) {
				return;
			}

			// if reply posting has errors then show them in form.
			if ( bbp_has_errors() ) {
				ob_start();
				bbp_template_notices();
				$reply_error_html = ob_get_clean();
				$extra_info       = array(
					'error' => '1',
				);
				bbp_ajax_response( false, $reply_error_html, 200, $extra_info );
			}
		}

		/**
		 * New replies.
		 *
		 * @since BuddyBoss 2.9.00
		 *
		 * @param int $reply_id Reply ID.
		 *
		 * @uses bb_rl_reply_ajax_response() Generate an Ajax response.
		 */
		public function bb_rl_new_reply_post_extras( $reply_id ) {
			if ( ! bbp_is_ajax() ) {
				return;
			}
			$this->bb_rl_reply_ajax_response( $reply_id, 'new' );
		}

		/**
		 * Ajax callback for Quick Reply.
		 *
		 * @since BuddyBoss 2.9.00
		 *
		 * @uses  bbp_get_template_part() Load required template.
		 *
		 * @return void
		 */
		public function bb_rl_activity_quick_reply() {
			?>
			<div id="bbpress-forums" class="bbpress-forums-activity bb-quick-reply-form-wrap" data-component="activity" style="display: none;">
				<?php
				// phpcs:ignore
				if ( isset( $_POST['action'] ) && 'quick_reply_ajax' === $_POST['action'] ) {
					$_POST['action'] = 'reply';
				}

				add_filter( 'bb_forum_attachment_group_id', array( $this, 'bb_rl_forum_attachment_group_id' ) );
				add_filter( 'bb_forum_attachment_forum_id', array( $this, 'bb_rl_forum_attachment_forum_id' ) );

				// Timeline quick reply form template.
				bbp_get_template_part( 'form', 'reply-activity' );

				// Success message template.
				bbp_get_template_part( 'form-reply', 'success' );
				?>
			</div>
			<?php
			die();
		}

		/**
		 * Generate an Ajax response.
		 * Sends the HTML for the reply along with some extra information.
		 *
		 * @since BuddyBoss 2.9.00
		 *
		 * @param integer $reply_id Reply ID.
		 * @param string  $type     Type of reply ('new' or 'edit').
		 */
		private function bb_rl_reply_ajax_response( $reply_id, $type ) {
			$reply_html = $this->bb_rl_get_reply_html( $reply_id );
			$topic_id   = (int) ( isset( $_REQUEST['bbp_topic_id'] ) ? $_REQUEST['bbp_topic_id'] : 0 ); // phpcs:ignore

			/**
			 * Redirect to last page when anyone reply from begging of the page.
			 */
			$redirect_to = bbp_get_redirect_to();
			$reply_url   = bbp_get_reply_url( $reply_id, $redirect_to );
			$total_pages = '';
			if ( bbp_thread_replies() ) {
				if ( function_exists( 'bbp_get_total_parent_reply' ) ) {
					$parent_reply = (int) bbp_get_total_parent_reply( $topic_id );
					$parent_reply = ( bbp_show_lead_topic() ? $parent_reply - 1 : $parent_reply );
					$total_pages  = ceil( (int) $parent_reply / (int) bbp_get_replies_per_page() ); // 1;
				}
			} else {
				$total_pages = ceil( (int) bbp_get_reply_position( $reply_id, $topic_id ) / (int) bbp_get_replies_per_page() );
			}
			$current_page = get_query_var( 'paged', $reply_url );
			if ( 0 === (int) $current_page ) {
				$current_page = 1;
			}

			ob_start();
			if ( bbp_show_lead_topic() ) {
				$topic_reply_count = (int) bbp_get_topic_reply_count( $topic_id );
				echo esc_html( $topic_reply_count );
				$topic_reply_text = 1 !== $topic_reply_count ? esc_html__( 'Replies', 'buddyboss' ) : esc_html__( 'Reply', 'buddyboss' );
			} else {
				$topic_post_count = (int) bbp_get_topic_post_count( $topic_id );
				echo esc_html( $topic_post_count );
				$topic_reply_text = 1 !== $topic_post_count ? esc_html__( 'Posts', 'buddyboss' ) : esc_html__( 'Post', 'buddyboss' );
			}
			echo ' ' . wp_kses_post( $topic_reply_text );
			$topic_total_reply_count_html = ob_get_clean();

			/**
			 * Ended code for redirection to the last page.
			 */
			$extra_info = array(
				'reply_id'          => $reply_id,
				'reply_type'        => $type,
				'reply_parent'      => (int) $_REQUEST['bbp_reply_to'], // phpcs:ignore
				'tags'              => $this->bb_rl_get_topic_tags( $topic_id ),
				'redirect_url'      => $reply_url, // Get last page URl - Redirect to last page when anyone reply from begging of the page.
				'current_page'      => $current_page, // Get current page - Redirect to last page when anyone reply from begging of the page.
				'total_pages'       => $total_pages, // Get total pages - Redirect to last page when anyone reply from begging of the page.
				'total_reply_count' => $topic_total_reply_count_html, // Get total pages - Redirect to last page when anyone reply from begging of the page.
			);
			bbp_ajax_response( true, $reply_html, 200, $extra_info );
		}

		/**
		 * Uses a bbPress template file to generate reply HTML.
		 *
		 * @since BuddyBoss 2.9.00
		 *
		 * @param int $reply_id Reply ID.
		 *
		 * @return string
		 */
		private function bb_rl_get_reply_html( $reply_id ) {
			ob_start();
			$reply_query      = new \WP_Query(
				array(
					'p'         => (int) $reply_id,
					'post_type' => bbp_get_reply_post_type(),
				)
			);
			$bbp              = bbpress();
			$bbp->reply_query = $reply_query;

			if ( function_exists( 'bbp_make_clickable' ) ) {
				// Convert plaintext URI to HTML links.
				add_filter( 'bbp_get_reply_content', 'bbp_make_clickable', 4 );
			}

			if ( ! has_filter( 'bbp_get_reply_content', 'convert_smilies' ) ) {
				add_filter( 'bbp_get_reply_content', 'convert_smilies', 20 );
			}

			if ( function_exists( 'bp_media_forums_embed_attachments' ) && ! has_filter( 'bbp_get_reply_content', 'bp_media_forums_embed_attachments' ) ) {
				add_filter( 'bbp_get_reply_content', 'bp_media_forums_embed_attachments', 999, 2 );
			}
			if ( function_exists( 'bp_video_forums_embed_attachments' ) && ! has_filter( 'bbp_get_reply_content', 'bp_video_forums_embed_attachments' ) ) {
				add_filter( 'bbp_get_reply_content', 'bp_video_forums_embed_attachments', 999, 2 );
			}
			if ( function_exists( 'bp_document_forums_embed_attachments' ) && ! has_filter( 'bbp_get_reply_content', 'bp_document_forums_embed_attachments' ) ) {
				add_filter( 'bbp_get_reply_content', 'bp_document_forums_embed_attachments', 999, 2 );
			}

			if ( function_exists( 'bp_media_forums_embed_gif' ) && ! has_filter( 'bbp_get_reply_content', 'bp_media_forums_embed_gif' ) ) {
				add_filter( 'bbp_get_reply_content', 'bp_media_forums_embed_gif', 999, 2 );
			}

			bbp_reply_content_autoembed();

			// Add mentioned to be clickable.
			add_filter( 'bbp_get_reply_content', 'bbp_make_mentions_clickable' );

			// Link Preview.
			if ( function_exists( 'bb_forums_link_preview' ) && ! has_filter( 'bbp_get_reply_content', 'bb_forums_link_preview' ) ) {
				add_filter( 'bbp_get_reply_content', 'bb_forums_link_preview', 999, 2 );
			}

			while ( bbp_replies() ) :
				bbp_the_reply();
				bbp_get_template_part( 'loop', 'single-reply' );
			endwhile;
			$reply_html = ob_get_clean();

			if ( function_exists( 'bbp_make_clickable' ) ) {
				remove_filter( 'bbp_get_reply_content', 'bbp_make_clickable', 4 );
			}

			if ( function_exists( 'bp_media_forums_embed_attachments' ) ) {
				remove_filter( 'bbp_get_reply_content', 'bp_media_forums_embed_attachments', 999, 2 );
			}

			if ( function_exists( 'bp_document_forums_embed_attachments' ) ) {
				remove_filter( 'bbp_get_reply_content', 'bp_document_forums_embed_attachments', 999, 2 );
			}
			if ( function_exists( 'bp_media_forums_embed_gif' ) ) {
				remove_filter( 'bbp_get_reply_content', 'bp_media_forums_embed_gif', 999, 2 );
			}
			return $reply_html;
		}

		/**
		 * Get group ID for forum attachments.
		 *
		 * @since BuddyBoss 2.9.00
		 *
		 * @param int $group_id Group ID.
		 *
		 * @return mixed
		 */
		public function bb_rl_forum_attachment_group_id( $group_id ) {
			if (
				function_exists( 'bp_is_active' ) &&
				bp_is_active( 'groups' ) &&
				isset( $_POST['group_id'] ) && // phpcs:ignore
				! empty( $_POST['group_id'] ) // phpcs:ignore
			) {
				$group_id = $_POST['group_id']; // phpcs:ignore
			}

			return $group_id;
		}

		/**
		 * Get forum ID for forum attachments.
		 *
		 * @since BuddyBoss 2.9.00
		 *
		 * @param int $forum_id Forum ID.
		 *
		 * @return int|mixed
		 */
		public function bb_rl_forum_attachment_forum_id( $forum_id ) {
			if (
				function_exists( 'bp_is_active' ) &&
				bp_is_active( 'forums' ) &&
				isset( $_POST['topic_id'] ) && // phpcs:ignore
				! empty( $_POST['topic_id'] ) // phpcs:ignore
			) {
				$topic_id = $_POST['topic_id']; // phpcs:ignore
				$forum_id = bbp_get_topic_forum_id( $topic_id );
			}

			return $forum_id;
		}

		/**
		 * Get topic tags.
		 *
		 * @since BuddyBoss 2.9.00
		 *
		 * @param int $topic_id Topic ID.
		 *
		 * @return string HTML list of topic tags.
		 */
		public function bb_rl_get_topic_tags( $topic_id ) {

			$new_terms = array();

			// Topic exists.
			if ( ! empty( $topic_id ) ) {

				// Topic is spammed so display pre-spam terms.
				if ( bbp_is_topic_spam( $topic_id ) ) {
					$new_terms = get_post_meta( $topic_id, '_bbp_spam_topic_tags', true );

					// Topic is not spam so get real terms.
				} else {
					$terms     = array_filter( (array) get_the_terms( $topic_id, bbp_get_topic_tag_tax_id() ) );
					$new_terms = wp_list_pluck( $terms, 'name' );
				}
			}

			$html_li = '';
			$html    = '';
			if ( $new_terms ) {
				foreach ( $new_terms as $tag ) {
					$html_li .= '<li><a href="' . bbp_get_topic_tag_link( $tag ) . '">' . $tag . '</a></li>';
				}

				$html = '<ul> ' . rtrim( $html_li, ',' ) . '</ul>';
			}
			return $html;
		}

		/**
		 * Localize script for ReadyLaunch admin.
		 *
		 * @since BuddyBoss 2.9.00
		 *
		 * @param array  $localize_arg Localized arguments.
		 * @param string $screen_id Screen ID.
		 *
		 * @return array
		 */
		public function bb_rl_admin_localize_script( $localize_arg, $screen_id ) {
			if ( strpos( $screen_id, 'bb-readylaunch' ) === false ) {
				return $localize_arg;
			}

			wp_dequeue_script( 'bp-fitvids-js' );

			$component_pages = array();
			if ( bp_is_active( 'activity' ) ) {
				$component_pages['activity'] = bp_get_activity_directory_permalink();
			}

			$component_pages['xprofile'] = esc_url( bp_core_get_user_domain( bp_loggedin_user_id() ) );

			if ( bp_is_active( 'groups' ) ) {
				$groups = groups_get_groups(
					array(
						'user_id'  => bp_loggedin_user_id(),
						'type'     => 'active',
						'per_page' => 1,
					)
				);

				if ( ! empty( $groups ) && ! empty( $groups['groups'] ) ) {
					$component_pages['single_group'] = bp_get_group_permalink( $groups['groups'][0] );
				}
			}

			$localize_arg['component_pages'] = $component_pages;

			// Check if ReadyLaunch onboarding is completed.
			$localize_arg['rl_onboarding_completed'] = bp_get_option( 'bb_rl_onboarding_completed', false );

			return $localize_arg;
		}

		/**
		 * Get forum freshness link.
		 *
		 * @since BuddyBoss 2.9.00
		 *
		 * @param string $active_time Active time.
		 *
		 * @return string
		 */
		public function bb_rl_get_forum_last_active( $active_time ) {
			if ( empty( $active_time ) ) {
				return $active_time;
			}

			// Check if the time contains a comma (indicating multiple time periods).
			if ( false !== strpos( $active_time, ',' ) ) {
				// Extract only the first part before the comma.
				$parts      = explode( ',', $active_time );
				$first_part = trim( $parts[0] );

				// Add "ago" if it's not already there.
				$active_time = sprintf(
					/* translators: %s: forum freshness link */
					apply_filters( 'bbp_core_time_since_ago_text', __( '%s ago', 'buddyboss' ) ),
					$first_part
				);
			}

			return $active_time;
		}

		/**
		 * Get forum freshness link.
		 *
		 * @since BuddyBoss 2.9.00
		 *
		 * @param string $anchor Anchor.
		 * @param int    $forum_id Forum ID.
		 * @param string $time_since Time since.
		 * @param string $link_url Link URL.
		 * @param string $title Title.
		 * @param int    $active_id Active ID.
		 *
		 * @return string
		 */
		public function bb_rl_get_forum_freshness_link( $anchor, $forum_id, $time_since, $link_url, $title, $active_id ) {
			if ( empty( $anchor ) || empty( $link_url ) ) {
				return $anchor;
			}

			if ( $time_since ) {
				return sprintf(
					/* translators: %s: forum freshness link */
					__( '<span>Active</span> %s', 'buddyboss' ),
					$anchor
				);
			}

			return $anchor;
		}

		/**
		 * Modify super sticky text for readylaunch.
		 *
		 * @since BuddyBoss 2.9.00
		 *
		 * @param array $r Arguments.
		 *
		 * @return array $r Arguments.
		 */
		public function bb_rl_modify_get_topic_stick_link_parse_args( $r ) {
			if ( empty( $r['stick_text'] ) ) {
				return $r;
			}

			$r['super_text'] = __( 'Super Sticky', 'buddyboss' );

			return $r;
		}

		/**
		 * Modify nav link text for readylaunch.
		 *
		 * @since BuddyBoss 2.9.10
		 *
		 * @param string $link_text  Link text.
		 * @param object $nav_item   Nav item.
		 * @param object $bp_nouveau BP Nouveau.
		 *
		 * @return string
		 */
		public function bb_rl_get_nav_link_text( $link_text, $nav_item, $bp_nouveau ) {
			if ( 'subscriptions' === $nav_item->slug ) {
				$link_text = esc_html__( 'Group Subscriptions', 'buddyboss' );
			}

			return $link_text;
		}

		/**
		 * Modify search default text for group members.
		 *
		 * @since BuddyBoss 2.9.10
		 *
		 * @param string $default_text Default text.
		 *
		 * @return string
		 */
		public function bb_rl_modify_group_members_search_placeholder( $default_text ) {
			if ( ! bp_is_active( 'groups' ) ) {
				return $default_text;
			}

			$current_component        = function_exists( 'bp_current_component' ) ? bp_current_component() : '';
			$current_action_variables = function_exists( 'bp_action_variables' ) ? bp_action_variables() : array();
			$current_action_variables = ! empty( $current_action_variables ) ? $current_action_variables[0] : '';
			if ( 'groups' === $current_component && 'members' === $current_action_variables ) {
				$default_text = __( 'Search member', 'buddyboss' );
			}

			return $default_text;
		}

		/**
		 * Overwrite login email field label.
		 *
		 * @since BuddyBoss 2.9.10
		 *
		 * @return void
		 */
		public function bb_rl_overwrite_login_email_field_label_hook() {
			add_filter( 'gettext', array( $this, 'bb_rl_overwrite_login_email_field_label' ), 10, 3 );
		}

		/**
		 * Overwrite login email field label.
		 *
		 * @since BuddyBoss 2.9.10
		 *
		 * @param string $translated_text Translated text.
		 * @param string $text Text.
		 * @param string $domain Domain.
		 *
		 * @return string
		 */
		public function bb_rl_overwrite_login_email_field_label( $translated_text, $text, $domain ) {
			if ( 'Username or Email Address' === $text && 'default' === $domain ) {
				remove_filter( 'gettext', array( $this, 'bb_rl_overwrite_login_email_field_label' ) );
				return __( 'Email', 'buddyboss' );
			}

			return $translated_text;
		}

		/**
		 * Modify logout message.
		 *
		 * @since BuddyBoss 2.9.10
		 *
		 * @param object $errors Errors.
		 *
		 * @return object
		 */
		public function bb_rl_wp_login_errors( $errors ) {
			if ( isset( $_GET['loggedout'] ) && $_GET['loggedout'] ) {
				$errors->remove( 'loggedout' );
				$notice  = esc_html__( 'You are logged out', 'buddyboss' );
				$notice .= ' <span class="bb-rl-updated-close"><i class="bb-icons-rl-x"></i></span>';
				$errors->add( 'loggedout', $notice, 'message' );
			}

			return $errors;
		}

		/**
		 * Modify search results start HTML.
		 *
		 * @since BuddyBoss 2.9.10
		 *
		 * @param string $html HTML.
		 *
		 * @return string
		 */
		public function bb_rl_modify_search_results_group_start_html( $html ) {
			$bp_search = isset( $_REQUEST['bp_search'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['bp_search'] ) ) : '';
			$view      = isset( $_REQUEST['view'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['view'] ) ) : '';
			if ( $bp_search || $view ) {
				return $html;
			}

			return '';
		}

		/**
		 * Modify visibility levels for readylaunch.
		 *
		 * @since BuddyBoss 2.9.10
		 *
		 * @param array $visibility_levels The visibility levels.
		 *
		 * @return array Modified visibility levels.
		 */
		public function bb_rl_modify_visibility_levels( $visibility_levels ) {
			$visibility_levels['loggedin'] = __( 'All members', 'buddyboss' );
			if ( bp_is_active( 'friends' ) ) {
				$visibility_levels['friends'] = __( 'My connections', 'buddyboss' );
			}
			$visibility_levels['onlyme'] = __( 'Only me', 'buddyboss' );

			return $visibility_levels;
		}

		/**
		 * Modify the data-balloon attribute for the add friend button.
		 *
		 * @since BuddyBoss 2.9.30
		 *
		 * @param array|string $button The button array or HTML string.
		 *
		 * @return array|string The modified button array or HTML string.
		 */
		public function bb_rl_modify_add_friend_button( $button ) {
			// Check if $button is an array and has the required keys.
			if ( ! is_array( $button ) || empty( $button['link_href'] ) ) {
				return $button;
			}

			if ( false !== strpos( $button['link_href'], '/remove-friend/' ) ) {
				$remove_connection_text = __( 'Remove connection', 'buddyboss' );
				$button['data-balloon'] = $remove_connection_text;
				if ( empty( $button['is_tooltips'] ) ) {
					$button['link_class']               .= ' bb-rl-primary-hover-action';
					$button['button_attr']['data-hover'] = $remove_connection_text;
				}
			} elseif ( false !== strpos( $button['link_href'], '/requests/cancel' ) ) {
				$cancel_request_text    = __( 'Cancel request', 'buddyboss' );
				$button['data-balloon'] = $cancel_request_text;
				if ( empty( $button['is_tooltips'] ) ) {
					$button['link_class']               .= ' bb-rl-primary-hover-action';
					$button['button_attr']['data-hover'] = $cancel_request_text;
				}
			} elseif ( false !== strpos( $button['link_href'], '/requests/' ) ) {
				$accept_request_text    = __( 'Review request', 'buddyboss' );
				$button['data-balloon'] = $accept_request_text;
				if ( empty( $button['is_tooltips'] ) ) {
					$button['link_class']               .= ' bb-rl-primary-hover-action';
					$button['button_attr']['data-hover'] = $accept_request_text;
				}
			}
			return $button;
		}

		/**
		 * Remove SSO template title.
		 *
		 * @since BuddyBoss 2.9.30
		 */
		public function bb_rl_remove_sso_template_title() {
			remove_action( 'bp_template_title', 'BB_SSO::bp_template_title' );
		}

		/**
		 * Modify view all option for notifications filter.
		 *
		 * @since BuddyBoss 2.9.30
		 *
		 * @param string $output The output.
		 *
		 * @return string The modified output.
		 */
		public function bb_rl_modify_notifications_filters( $output ) {
			$output = str_replace(
				esc_html__( '- View All -', 'buddyboss' ),
				esc_html__( 'View All', 'buddyboss' ),
				$output
			);

			return $output;
		}

		/**
		 * Modify member's joined date.
		 *
		 * @since BuddyBoss 2.9.30
		 *
		 * @param string $user_registered_date The user registered date.
		 * @param string $register_date        The register date.
		 *
		 * @return string The modified user registered date.
		 */
		public static function bb_rl_modify_member_joined_date( $user_registered_date, $register_date ) {

			$register_date        = date_i18n( 'd M Y', strtotime( $register_date ) );
			$user_registered_date = sprintf(
				/* translators: 1: User joined date. */
				esc_html__( 'Joined %s', 'buddyboss' ),
				esc_html( $register_date )
			);

			return $user_registered_date;
		}

		/**
		 * Modify the member report button.
		 *
		 * @since BuddyBoss 2.9.30
		 *
		 * @param array $button The button.
		 *
		 * @return array The modified button.
		 */
		public function bb_rl_modify_member_report_button( $button ) {
			if ( empty( $button['link_text'] ) ) {
				return $button;
			}

			$button['link_text'] = str_replace( __( 'Report Member', 'buddyboss' ), __( 'Report', 'buddyboss' ), $button['link_text'] );

			return $button;
		}

		/**
		 * Modify the nav get count.
		 *
		 * @since BuddyBoss 2.9.30
		 *
		 * @param int    $count    The count.
		 * @param object $nav_item The nav item.
		 *
		 * @return int The modified count.
		 */
		public function bb_rl_modify_nav_get_count( $count, $nav_item ) {
			if ( empty( $nav_item ) ) {
				return $count;
			}

			if ( bp_is_active( 'friends' ) ) {
				if ( 'my-friends' === $nav_item->slug ) {
					$count = friends_get_total_friend_count();
				} elseif ( 'requests' === $nav_item->slug ) {
					$count = bp_friend_get_total_requests_count();
				} elseif ( 'mutual' === $nav_item->slug ) {
					$mutual_friendships_ids      = bp_get_mutual_friendships();
					$mutual_friendships_exploded = ! empty( $mutual_friendships_ids ) ? explode( ',', $mutual_friendships_ids ) : array();
					$count                       = ! empty( $mutual_friendships_exploded ) ? count( $mutual_friendships_exploded ) : 0;
				}
			}
			if ( bp_is_active( 'groups' ) ) {
				if ( 'my-groups' === $nav_item->slug ) {
					$count = bp_get_total_group_count_for_user( bp_loggedin_user_id() );
				} elseif ( 'invites' === $nav_item->slug ) {
					$count = groups_get_invite_count_for_user();
				}
			}

			return $count;
		}

		/**
		 * Modify the save changes button for group invites for ReadyLaunch.
		 *
		 * @since BuddyBoss 2.9.30
		 *
		 * @param array $actions The list of submit buttons.
		 *
		 * @return array Modified actions array.
		 */
		public function bb_rl_modify_bp_nouveau_get_submit_button( $actions ) {
			if ( isset( $actions['member-group-invites']['attributes']['value'] ) ) {
				$actions['member-group-invites']['attributes']['value'] = esc_html__( 'Save Changes', 'buddyboss' );
			}

			return $actions;
		}

		/**
		 * Modify visibility levels for xprofile.
		 *
		 * @since BuddyBoss 2.9.30
		 *
		 * @param array $visibility_levels The visibility levels.
		 *
		 * @return array Modified visibility levels.
		 */
		public function bb_rl_modify_xprofile_visibility_levels( $visibility_levels ) {
			$visibility_levels['loggedin']['label'] = __( 'All members', 'buddyboss' );
			if ( bp_is_active( 'friends' ) ) {
				$visibility_levels['friends']['label'] = __( 'My connections', 'buddyboss' );
			}
			$visibility_levels['adminsonly']['label'] = __( 'Only me', 'buddyboss' );

			return $visibility_levels;
		}
	}
}
