<?php
/**
 * Readylaunch class.
 *
 * @since   BuddyBoss [BBVERSION]
 * @package BuddyBoss\Core
 */

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'BB_Readylaunch' ) ) {

	/**
	 * BuddyBoss Readylaunch object.
	 *
	 * @since BuddyBoss [BBVERSION]
	 */
	#[\AllowDynamicProperties]
	class BB_Readylaunch {

		/**
		 * The single instance of the class.
		 *
		 * @since  BuddyBoss [BBVERSION]
		 *
		 * @access private
		 * @var self
		 */
		private static $instance = null;

		/**
		 * ReadyLaunch Settings.
		 *
		 * @since BuddyBoss [BBVERSION]
		 */
		public $settings = array();

		/**
		 * Get the instance of this class.
		 *
		 * @since BuddyBoss [BBVERSION]
		 *
		 * @return Controller|BB_Readylaunch|null
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
		 * @since BuddyBoss [BBVERSION]
		 */
		public function __construct() {

			$enabled = bb_is_readylaunch_enabled();

			// Register the ReadyLaunch menu.
			$this->bb_register_readylaunch_menus();

			if ( $enabled ) {
				add_action( 'bb_blocks_init', array( $this, 'bb_rl_register_blocks' ), 20 );
				add_filter( 'bp_search_js_settings', array( $this, 'bb_rl_filter_search_js_settings' ) );
				add_action( 'admin_enqueue_scripts', array( $this, 'bb_admin_enqueue_scripts' ), 1 );
			}

			$enabled_for_page = $this->bb_is_readylaunch_enabled_for_page();
			if ( $enabled_for_page ) {
				add_filter( 'bp_core_avatar_full_width', array( $this, 'bb_rl_avatar_full_width' ) );
				add_filter( 'bp_core_avatar_full_height', array( $this, 'bb_rl_avatar_full_height' ) );
				add_filter( 'bp_core_avatar_thumb_width', array( $this, 'bb_rl_avatar_thumb_width' ) );
				add_filter( 'bp_core_avatar_thumb_height', array( $this, 'bb_rl_avatar_thumb_height' ) );

				if (
					bp_is_active( 'activity' ) &&
					(
						bp_is_activity_directory() ||
						bp_is_single_activity() ||
						bp_is_user_activity() ||
						bp_is_group_activity()
					)
				) {
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

				add_filter(
					'template_include',
					array(
						$this,
						'override_page_templates',
					),
					999999
				); // High priority so we have the last say here.

				// Remove BuddyPress template locations.
				remove_filter( 'bp_get_template_stack', 'bp_add_template_stack_locations' );

				// Add Readylaunch template locations.
				add_filter( 'bp_get_template_stack', array( $this, 'add_template_stack' ), PHP_INT_MAX );

				add_filter( 'bp_document_svg_icon', array( $this, 'bb_rl_document_svg_icon' ), 10, 2 );

				add_action( 'wp_enqueue_scripts', array( $this, 'bb_enqueue_scripts' ), 1 );

				// Dequeue theme/plugins styles.
				add_action( 'wp_enqueue_scripts', array( $this, 'bb_dequeue_styles' ), PHP_INT_MAX );
				// Dequeue bbpress activity js.
				add_filter( 'bbp_is_single_topic', array( $this, 'bb_dequeue_bbpress_activity_js' ), PHP_INT_MAX );
				add_action( 'wp_head', array( $this, 'bb_rl_start_buffering' ), 0 );
				add_action( 'wp_footer', array( $this, 'bb_rl_end_buffering' ), 999 );

				add_action( 'wp_ajax_bb_fetch_header_messages', array( $this, 'bb_fetch_header_messages' ) );
				add_action( 'wp_ajax_bb_fetch_header_notifications', array( $this, 'bb_fetch_header_notifications' ) );

				add_filter( 'heartbeat_received', array( $this, 'bb_heartbeat_unread_notifications' ), 12, 2 );
				add_filter( 'heartbeat_nopriv_received', array( $this, 'bb_heartbeat_unread_notifications' ), 12, 2 );

				add_action( 'wp_ajax_bb_mark_notification_read', array( $this, 'bb_mark_notification_read' ) );

				// Directory filters.
				add_filter( 'bp_nouveau_get_filter_label', array( $this, 'bb_nouveau_get_filter_label_hook' ), 10, 2 );
				add_filter( 'bp_nouveau_get_filter_id', array( $this, 'bb_rl_prefix_key' ) );
				add_filter( 'bp_nouveau_get_nav_id', array( $this, 'bb_rl_prefix_key' ) );

				add_filter( 'bp_nouveau_register_scripts', array( $this, 'bb_rl_nouveau_register_scripts' ), 99, 1 );
				add_filter( 'paginate_links_output', array( $this, 'bb_rl_filter_paginate_links_output' ), 10, 2 );

				add_filter( 'wp_ajax_bb_rl_invite_form', array( $this, 'bb_rl_invite_form_callback' ) );

				add_filter( 'body_class', array( $this, 'bb_rl_theme_body_classes' ) );

				add_filter( 'bp_get_send_message_button_args', array( $this, 'bb_rl_override_send_message_button_text' ) );

				add_filter( 'bb_member_directories_get_profile_actions', array( $this, 'bb_rl_member_directories_get_profile_actions' ), 10, 3 );

				// override default images for the avatar image.
				add_filter( 'bb_attachments_get_default_profile_group_avatar_image', array( $this, 'bb_rl_group_default_group_avatar_image' ), 999, 2 );

				add_filter( 'bp_core_register_common_scripts', array( $this, 'bb_rl_register_common_scripts' ), 999, 1 );
				add_filter( 'bp_core_register_common_styles', array( $this, 'bb_rl_register_common_styles' ), 999, 1 );

				remove_action( 'bp_before_directory_members_page', 'bp_members_directory_page_content' );
				remove_action( 'bp_before_directory_media', 'bp_media_directory_page_content' );
				remove_action( 'bp_before_directory_document', 'bb_document_directory_page_content' );

				// Remove profile search form on members directory.
				remove_action( 'bp_before_directory_members', 'bp_profile_search_show_form' );

				add_filter( 'bp_nouveau_get_document_description_html', array( $this, 'bb_rl_modify_document_description_html' ), 10 );
				add_filter( 'bp_nouveau_get_media_description_html', array( $this, 'bb_rl_modify_document_description_html' ), 10 );
				add_filter( 'bp_nouveau_get_video_description_html', array( $this, 'bb_rl_modify_document_description_html' ), 10 );
				add_filter( 'bp_core_get_js_strings', array( $this, 'bb_rl_modify_js_strings' ), 10, 1 );

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

				// Add Dynamic colors.
				add_action( 'wp_head', array( $this, 'bb_rl_dynamic_colors' ) );
			}

			add_action( 'bp_admin_enqueue_scripts', array( $this, 'bb_rl_admin_enqueue_scripts' ), 1 );

			$admin_enabled = $this->bb_is_readylaunch_admin_enabled();
			if ( $admin_enabled ) {
				add_filter( 'bb_document_icon_class', array( $this, 'bb_readylaunch_document_icon_class' ) );
			}

			// Add ReadyLaunch settings to platform settings API.
			add_filter( 'bp_rest_platform_settings', array( $this, 'bb_rest_readylaunch_platform_settings' ), 10, 1 );

			// Update notification item action links.
			add_filter( 'bp_get_the_notification_action_links', array( $this, 'bb_rl_modify_notification_action_links' ), 10 );

			// LearnDash integration.
			add_filter( 'bp_is_sidebar_enabled_for_courses', array( $this, 'bb_is_sidebar_enabled_for_courses' ) );

			// LearnDash integration.
			add_action( 'wp_enqueue_scripts', array( $this, 'bb_readylaunch_learndash_enqueue_styles' ), 10 );

			// Set up LearnDash integration.
			if ( class_exists( 'SFWD_LMS' ) ) {
				require_once buddypress()->compatibility_dir . '/class-bb-readylaunch-learndash-helper.php';
			}
		}

		/**
		 * Get the order of sidebar items.
		 *
		 * @since BuddyBoss [BBVERSION]
		 *
		 * @return array The ordered array of sidebar items.
		 */
		public function bb_rl_get_sidebar_order() {
			$settings = bp_get_option( 'bb_rl_side_menu', array() );

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
							function_exists( 'bb_meprlms_enable' ) &&
							bb_meprlms_enable()
						) {
							$is_active   = true;
							$item['url'] = get_post_type_archive_link( bb_meprlms_profile_courses_slug() );
						}
					} elseif ( 'messages' === $key && bp_is_active( 'messages' ) ) {
						$item['url']   = trailingslashit( bp_loggedin_user_domain() . bp_get_messages_slug() );
						$item['label'] = __( 'Messages', 'buddyboss' );
					} elseif ( 'notifications' === $key && bp_is_active( 'notifications' ) ) {
						$item['url']   = bp_get_notifications_permalink();
						$item['label'] = __( 'Notifications', 'buddyboss' );
					}
					if ( $is_active ) {
						$ordered_items[ $key ] = $item;
					}
				}
			}

			return $ordered_items;
		}

		/**
		 * Override full width for avatar in ReadyLaunch.
		 *
		 * @since BuddyBoss [BBVERSION]
		 *
		 * @return int
		 */
		public function bb_rl_avatar_full_width() {
			return 384;
		}

		/**
		 * Override full height for avatar in ReadyLaunch.
		 *
		 * @since BuddyBoss [BBVERSION]
		 *
		 * @return int
		 */
		public function bb_rl_avatar_full_height() {
			return 384;
		}

		/**
		 * Override thumb width for avatar in ReadyLaunch.
		 *
		 * @since BuddyBoss [BBVERSION]
		 *
		 * @return int
		 */
		public function bb_rl_avatar_thumb_width() {
			return 200;
		}

		/**
		 * Override thumb height for avatar in ReadyLaunch.
		 *
		 * @since BuddyBoss [BBVERSION]
		 *
		 * @return int
		 */
		public function bb_rl_avatar_thumb_height() {
			return 200;
		}

		/**
		 * Check if ReadyLaunch is enabled for the current directory.
		 *
		 * @since BuddyBoss [BBVERSION]
		 *
		 * @return bool True if ReadyLaunch is enabled, false otherwise.
		 */
		private function bb_is_readylaunch_enabled_for_page() {
			if (
				bb_is_readylaunch_enabled() &&
				(
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
					$this->bb_rl_is_learndash_page() // Add check for LearnDash pages.
				)
			) {
				return true;
			}

			return false;
		}

		/**
		 * Check if the network search is enabled.
		 *
		 * @since BuddyBoss [BBVERSION]
		 *
		 * @return bool True if the network search is enabled, false otherwise.
		 */
		public static function bb_is_network_search() {
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
		 * @since BuddyBoss [BBVERSION]
		 *
		 * @return bool True if the admin is enabled, false otherwise.
		 */
		private function bb_is_readylaunch_admin_enabled() {
			$page = isset( $_GET['page'] ) ? sanitize_text_field( wp_unslash( $_GET['page'] ) ) : '';
			$tab  = isset( $_GET['tab'] ) ? sanitize_text_field( wp_unslash( $_GET['tab'] ) ) : '';

			if (
				(
					bb_is_readylaunch_enabled() &&
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
		 * @since BuddyBoss [BBVERSION]
		 */
		public function bb_register_readylaunch_menus() {
			// Define the menus and their respective theme locations.
			$menus = array(
				'bb-readylaunch'             => __( 'ReadyLaunch', 'buddyboss' ),
				'bb-top-readylaunchpanel'    => __( 'Top ReadyLaunchPanel', 'buddyboss' ),
				'bb-bottom-readylaunchpanel' => __( 'Bottom ReadyLaunchPanel', 'buddyboss' ),
			);

			foreach ( $menus as $theme_location => $menu_name ) {
				// Check if the menu already exists.
				$menu_exists = wp_get_nav_menu_object( $menu_name );

				// If the menu doesn't exist, create it.
				$menu_id = ! $menu_exists ? wp_create_nav_menu( $menu_name ) : $menu_exists->term_id;

				// Register the theme location if it has not been registered already.
				if ( ! has_nav_menu( $theme_location ) ) {
					register_nav_menu( $theme_location, $menu_name );
				}

				// If the menu exists and the theme location is ready, assign the menu to the location.
				$nav_menu_locations = get_theme_mod( 'nav_menu_locations', array() );
				if ( ! empty( $menu_id ) && ! isset( $nav_menu_locations[ $theme_location ] ) ) {
					set_theme_mod(
						'nav_menu_locations',
						array_merge(
							$nav_menu_locations,
							array( $theme_location => $menu_id )
						)
					);
				}
			}
		}

		/**
		 * Override the page templates.
		 *
		 * @since BuddyBoss [BBVERSION]
		 *
		 * @return string ReadyLaunch layout template.
		 */
		public function override_page_templates() {
			if ( bp_is_register_page() ) {
				return bp_locate_template( 'register.php' );
			}

			return bp_locate_template( 'layout.php' );
		}

		/**
		 * Add custom template stack for ReadyLaunch.
		 *
		 * @since BuddyBoss [BBVERSION]
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
				$stack[ $key ] = untrailingslashit( trailingslashit( $value ) . $custom_location );
			}

			return $stack;
		}

		/**
		 * Enqueue ReadyLaunch scripts.
		 *
		 * @since BuddyBoss [BBVERSION]
		 */
		public function bb_enqueue_scripts() {
			$min = bp_core_get_minified_asset_suffix();

			wp_enqueue_script( 'bb-readylaunch-front', buddypress()->plugin_url . "bp-templates/bp-nouveau/readylaunch/js/bb-readylaunch-front{$min}.js", array( 'jquery', 'bp-nouveau' ), bp_get_version(), true );

			// Enqueue Cropper.js.
			wp_enqueue_script( 'bb-readylaunch-cropper-js' );
			wp_enqueue_style( 'bb-readylaunch-cropper-css' );

			wp_enqueue_style( 'bb-readylaunch-font', buddypress()->plugin_url . 'bp-templates/bp-nouveau/readylaunch/assets/fonts/fonts.css', array(), bp_get_version() );
			wp_enqueue_style( 'bb-readylaunch-style-main', buddypress()->plugin_url . "bp-templates/bp-nouveau/readylaunch/css/main{$min}.css", array(), bp_get_version() );

			// Register only if it's Activity component.
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
						'invite_sending_invite'       => esc_html__( 'Sending invitation...', 'buddyboss' ),
						'invite_error_notice'         => esc_html__( 'There was an error submitting the form. Please try again.', 'buddyboss' ),
					)
				);
			}

			if ( bp_is_user_profile_edit() || bp_is_user_profile() ) {
				wp_enqueue_script( 'bb-rl-xprofile' );
			}

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
		}

		/**
		 * Enqueue admin styles for ReadyLaunch.
		 *
		 * @since BuddyBoss [BBVERSION]
		 */
		public function bb_admin_enqueue_scripts() {
			$min = bp_core_get_minified_asset_suffix();
			wp_enqueue_style( 'bb-icons-rl-css', buddypress()->plugin_url . "bp-templates/bp-nouveau/readylaunch/icons/css/bb-icons-rl{$min}.css", array(), bp_get_version() );
		}

		/**
		 * Enqueue admin styles for ReadyLaunch.
		 *
		 * @since BuddyBoss [BBVERSION]
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
		 * @since BuddyBoss [BBVERSION]
		 */
		public function bb_dequeue_styles() {
			global $wp_styles, $wp_scripts;

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
						! $this->bb_has_allowed_suffix( $handle, $allow_suffix )
					) ||
					'bp-nouveau-bb-icons' === $handle
				) {
					wp_dequeue_style( $handle );
				}
			}
		}

		/**
		 * Function to check if the handle has allowed suffix.
		 *
		 * @since BuddyBoss [BBVERSION]
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
		 * Dequeue bbPress activity js.
		 *
		 * @since BuddyBoss [BBVERSION]
		 *
		 * @return bool False to prevent bbPress activity js from being enqueued.
		 */
		public function bb_dequeue_bbpress_activity_js() {
			return false;
		}

		/**
		 * Remove specific inline styles.
		 *
		 * @since BuddyBoss [BBVERSION]
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
		 * @since BuddyBoss [BBVERSION]
		 */
		public function bb_rl_start_buffering() {
			ob_start( array( $this, 'bb_rl_remove_specific_inline_styles' ) );
		}

		/**
		 * End buffering.
		 *
		 * @since BuddyBoss [BBVERSION]
		 */
		public function bb_rl_end_buffering() {
			ob_end_flush();
		}

		/**
		 * Fetch header messages.
		 *
		 * @since BuddyBoss [BBVERSION]
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
		 * @since BuddyBoss [BBVERSION]
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
		 * @since BuddyBoss [BBVERSION]
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
			} elseif (
				class_exists( 'memberpress\courses\helpers\Courses' ) &&
				function_exists( 'bb_meprlms_enable' ) &&
				bb_meprlms_enable()
			) {
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
		 * parses them to ensure they have the required structure.
		 *
		 * @since BuddyBoss [BBVERSION]
		 *
		 * @param bool $data if true, then check for return data otherwise it will check plugin active or not.
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
		 * Render the middle content for left sidebar HTML.
		 *
		 * This function generates the HTML for the middle section of the left sidebar,
		 * displaying a list of items or an error message if no items are available.
		 *
		 * @since BuddyBoss [BBVERSION]
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
				<h2><?php echo esc_html( $title ); ?></h2>
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
										<a href="<?php echo esc_url( $item['permalink'] ); ?>">
											<?php
											echo wp_kses(
												$item['thumbnail'],
												array(
													'img' => array(
														'src' => true,
														'class' => true,
														'id' => true,
														'width' => true,
														'height' => true,
														'alt' => true,
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
						<?php echo esc_html( $error_text ); ?>
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
		 * count of unread notifications, then adds them to the response array.
		 *
		 * @since BuddyBoss [BBVERSION]
		 *
		 * @param array $response The existing heartbeat response array.
		 * @param array $data The data passed to the heartbeat request.
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
		 * @since BuddyBoss [BBVERSION]
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
					$id = intval( $id );
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
		 * @since BuddyBoss [BBVERSION]
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
		 * @since BuddyBoss [BBVERSION]
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
		 * @since BuddyBoss [BBVERSION]
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
		 * Filters to add readylaunch prefix.
		 *
		 * @since BuddyBoss [BBVERSION]
		 *
		 * @param string $key Key to readylaunch prefix.
		 */
		public function bb_rl_prefix_key( $key ) {
			return 'bb-rl-' . $key;
		}

		/**
		 * Register Scripts for the Member component
		 *
		 * @since BuddyBoss [BBVERSION]
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
		 * @since BuddyBoss [BBVERSION]
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
			$prev_text = isset( $args['prev_text'] ) ? $args['prev_text'] : __( '&larr; Prev', 'buddyboss' );
			$next_text = isset( $args['next_text'] ) ? $args['next_text'] : __( 'Next &rarr;', 'buddyboss' );

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
		 * Callback function for invite form.
		 *
		 * @since BuddyBoss [BBVERSION]
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
				$response['message'] = esc_html__( 'Email address already invited.', 'buddyboss' );
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
				? stripslashes( strip_tags( sanitize_text_field( wp_unslash( $_POST['bp_member_invites_custom_subject'] ) ) ) )
				: stripslashes( strip_tags( bp_get_member_invitation_subject() ) );

			$message = bp_disable_invite_member_email_content() && ! empty( sanitize_text_field( wp_unslash( $_POST['bp_member_invites_custom_content'] ) ) )
				? stripslashes( strip_tags( sanitize_textarea_field( wp_unslash( $_POST['bp_member_invites_custom_content'] ) ) ) )
				: stripslashes( strip_tags( bp_get_member_invitation_message() ) );

			$message .= ' ' . bp_get_member_invites_wildcard_replace(
				stripslashes( strip_tags( bp_get_invites_member_invite_url() ) ),
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
			$mail = bp_send_email( 'invites-member-invite', $email, $args );

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
			 * @param int $user_id Inviter user ID.
			 * @param int $post_id Invitation post ID.
			 *
			 * @since BuddyBoss [BBVERSION]
			 */
			do_action( 'bp_member_invite_submit', $loggedin_user_id, $post_id );

			wp_send_json_success(
				array(
					'message' => esc_html__( 'Email invite sent successfully.', 'buddyboss' ),
					'type'    => 'success',
				)
			);
		}

		/**
		 * Adds custom classes to the array of body classes.
		 *
		 * @since BuddyBoss [BBVERSION]
		 *
		 * @param array $classes Classes for the body element.
		 *
		 * @return array
		 */
		public function bb_rl_theme_body_classes( $classes ) {
			global $post, $wp_query;

			if ( is_active_sidebar( 'bb-readylaunch-members-sidebar' ) ) {
				$classes[] = 'bb-rl-has-sidebar';
			}

			return $classes;
		}

		/**
		 * Override Send Message button text.
		 *
		 * @since BuddyBoss [BBVERSION]
		 *
		 * @param array $args Button arguments.
		 *
		 * @return array $args Filtered arguments.
		 */
		public function bb_rl_override_send_message_button_text( $args ) {
			$args['link_text'] = esc_html__( 'Message', 'buddyboss' );
			return $args;
		}

		/**
		 * Filters the member actions for member directories.
		 *
		 * @since BuddyBoss [BBVERSION]
		 *
		 * @param array  $buttons     Member profile actions.
		 * @param int    $user_id     Member ID.
		 * @param string $button_type Which type of buttons need "primary", "secondary" or "both".
		 *
		 * @return array $buttons Filtered buttons.
		 */
		public function bb_rl_member_directories_get_profile_actions( $buttons, $user_id, $button_type ) {

			$enabled_message_action = function_exists( 'bb_enabled_member_directory_profile_action' )
				? bb_enabled_member_directory_profile_action( 'message' )
				: true;

			// Member directories primary actions.
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
					(bool) $enabled_message_action && bp_is_active( 'messages' ),
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
		 * @since BuddyBoss [BBVERSION]
		 *
		 * @param string|bool $avatar_image_url Default avatar URL, false otherwise.
		 * @param array       $params          Parameters for the avatar image.
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
		 * @since BuddyBoss [BBVERSION]
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
		 * @since BuddyBoss [BBVERSION]
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
		 * @since BuddyBoss [BBVERSION]
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
		 * @since BuddyBoss [BBVERSION]
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
		 * @since BuddyBoss [BBVERSION]
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

			return $buttons;
		}

		/**
		 * Modify the js strings.
		 *
		 * @since BuddyBoss [BBVERSION]
		 *
		 * @param array $strings The js strings.
		 *
		 * @return array $strings The modified js strings.
		 */
		public function bb_rl_modify_js_strings( $strings ) {
			$translated_string = __( '\'s Post', 'buddyboss' );

			if ( bp_is_active( 'media' ) || bp_is_active( 'video' ) || bp_is_active( 'document' ) ) {
				$strings['media']['i18n_strings']['theater_title'] = $translated_string;
			}

			return $strings;
		}

		/**
		 * Enqueue the login scripts.
		 *
		 * @since BuddyBoss [BBVERSION]
		 */
		public function bb_rl_login_enqueue_scripts() {
			wp_enqueue_style( 'bb-rl-login-fonts', buddypress()->plugin_url . 'bp-templates/bp-nouveau/readylaunch/assets/fonts/fonts.css', array(), bp_get_version() );
			wp_enqueue_style( 'bb-rl-login-style', buddypress()->plugin_url . 'bp-templates/bp-nouveau/readylaunch/css/login.css', array(), bp_get_version() );
			wp_enqueue_style( 'bb-rl-login-style-icons', buddypress()->plugin_url . 'bp-templates/bp-nouveau/readylaunch/icons/css/bb-icons-rl.min.css', array(), bp_get_version() );
		}

		/**
		 * Modify the login header.
		 *
		 * @since BuddyBoss [BBVERSION]
		 */
		public function bb_rl_login_header() {
			bp_get_template_part( 'common/header-register' );
		}


		/**
		 * Custom Login Link
		 *
		 * @since BuddyBoss [BBVERSION]
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
		 * @since BuddyBoss [BBVERSION]
		 */
		public function bb_rl_login_footer() {
			?>
			<script>
				jQuery( document ).ready( function( $ ) {
					var $forgetMeNot = $( '.login p.forgetmenot' );
					var $lostMeNot = $( '.login p.lostmenot' );
					$( $lostMeNot ).before( $forgetMeNot );
				} );
			</script>
			<?php
		}

		/**
		 * Modify the login message.
		 *
		 * @since BuddyBoss [BBVERSION]
		 *
		 * @param string $message The login message.
		 *
		 * @return string $message The modified login message.
		 */
		public function bb_rl_signin_login_message( $message ) {
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
		 * @since BuddyBoss [BBVERSION]
		 */
		public function bb_rl_login_custom_form() {
			?>
			<p class="lostmenot"><a href="<?php echo wp_lostpassword_url(); ?>"><?php esc_html_e( 'Forgot Password?', 'buddyboss' ); ?></a></p>
			<?php
		}

		/**
		 * Add dynamic colors to the frontend.
		 *
		 * @since BuddyBoss [BBVERSION]
		 */
		public function bb_rl_dynamic_colors() {
			$color_light = bp_get_option( 'bb_rl_color_light', '#4946fe' );
			$color_dark  = bp_get_option( 'bb_rl_color_dark', '#9747FF' );
			?>
			<style>
				:root {
					--bb-rl-primary-color: <?php echo $color_light; ?>;

					.bb-rl-dark-mode {
						--bb-rl-primary-color: <?php echo $color_dark; ?>;
					}
				}
			</style>
			<?php
		}

		/**
		 * Filter the search JS settings for ReadyLaunch
		 *
		 * @since BuddyBoss [BBVERSION]
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
		 * @since BuddyBoss [BBVERSION]
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
		 * @since BuddyBoss [BBVERSION]
		 */
		private function register_readylaunch_header_assets() {
			$plugin_url = trailingslashit( buddypress()->plugin_url );
			$min        = bp_core_get_minified_asset_suffix();

			// Register the view script.
			wp_register_script(
				'bb-readylaunch-header-view',
				$plugin_url . 'bp-core/blocks/readylaunch-header/view.js',
				array( 'jquery', 'bp-nouveau', 'bp-select2' ),
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
		 * @since BuddyBoss [BBVERSION]
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
			$settings['bb_rl_skin_appearance'] = (bool) bp_get_option( 'bb_rl_skin_appearance', false );
			$settings['bb_rl_light_logo']      = bp_get_option( 'bb_rl_light_logo', null );
			$settings['bb_rl_dark_logo']       = bp_get_option( 'bb_rl_dark_logo', null );
			$settings['bb_rl_color_light']     = (string) bp_get_option( 'bb_rl_color_light', '#3E34FF' );
			$settings['bb_rl_color_dark']      = (string) bp_get_option( 'bb_rl_color_dark', '#9747FF' );
			$settings['bb_rl_theme_mode']      = (string) bp_get_option( 'bb_rl_theme_mode', 'light' );

			// Pages & Sidebars Settings - Boolean values in arrays.
			$settings['bb_rl_enabled_pages'] = array_map(
				function ( $value ) {
					return (bool) $value;
				},
				bp_get_option(
					'bb_rl_enabled_pages',
					array(
						'registration' => true,
						'courses'      => true,
						'events'       => true,
						'gamification' => true,
					)
				)
			);

			$settings['bb_rl_activity_sidebars'] = array_map(
				function ( $value ) {
					return (bool) $value;
				},
				bp_get_option(
					'bb_rl_activity_sidebars',
					array(
						'complete_profile'  => true,
						'latest_updates'    => true,
						'recent_blog_posts' => true,
						'active_members'    => true,
					)
				)
			);

			$settings['bb_rl_member_profile_sidebars'] = array_map(
				function ( $value ) {
					return (bool) $value;
				},
				bp_get_option(
					'bb_rl_member_profile_sidebars',
					array(
						'complete_profile' => true,
						'connections'      => false,
						'my_network'       => false,
						'social'           => false,
					)
				)
			);

			$settings['bb_rl_groups_sidebars'] = array_map(
				function ( $value ) {
					return (bool) $value;
				},
				bp_get_option(
					'bb_rl_groups_sidebars',
					array(
						'about_group'   => true,
						'group_members' => false,
					)
				)
			);

			// Menu Settings.
			$settings['bb_rl_header_menu'] = (string) bp_get_option( 'bb_rl_header_menu', 'default' );

			$defaults = array(
				'activity_feed' => array(
					'enabled' => true,
					'order'   => 0,
				),
				'members'       => array(
					'enabled' => true,
					'order'   => 1,
				),
				'groups'        => array(
					'enabled' => true,
					'order'   => 2,
				),
				'courses'       => array(
					'enabled' => true,
					'order'   => 3,
				),
				'messages'      => array(
					'enabled' => false,
					'order'   => 4,
				),
				'notifications' => array(
					'enabled' => false,
					'order'   => 5,
				),
			);

			$raw_settings = bp_get_option( 'bb_rl_side_menu', $defaults );

			$settings['bb_rl_side_menu'] = array_map(
				function ( $item ) {
					return array(
						'enabled' => ! empty( $item['enabled'] ),
						'order'   => isset( $item['order'] ) ? (int) $item['order'] : 0,
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
		 * Enqueue LearnDash styles for ReadyLaunch.
		 *
		 * @since BuddyBoss [BBVERSION]
		 */
		public function bb_readylaunch_learndash_enqueue_styles() {
			if ( ! bb_is_readylaunch_enabled() || ! class_exists( 'SFWD_LMS' ) ) {
				return;
			}

			// Enqueue LearnDash ReadyLaunch styles.
			wp_enqueue_style(
				'bb-readylaunch-learndash',
				buddypress()->plugin_url . 'bp-templates/bp-nouveau/readylaunch/css/courses.css',
				array(),
				bp_get_version()
			);

			// Enqueue our LearnDash helper JavaScript.
			wp_enqueue_script(
				'bb-readylaunch-learndash-js',
				buddypress()->plugin_url . 'bp-templates/bp-nouveau/readylaunch/js/bb-readylaunch-learndash.js',
				array( 'jquery' ),
				bp_get_version(),
				true
			);

			wp_localize_script(
				'bb-readylaunch-learndash-js',
				'bbReadylaunchLearnDash',
				array(
					'courses_url' => home_url( '/courses/' ),
				)
			);
		}

		/**
		 * Check if current page is a LearnDash page.
		 *
		 * @since BuddyBoss [BBVERSION]
		 *
		 * @return bool True if current page is a LearnDash page, false otherwise.
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
					'sfwd-groups', // Use specific LearnDash group slug
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

			return false;
		}

		/**
		 * Modify notification item action links
		 *
		 * @since BuddyBoss [BBVERSION]
		 *
		 * @param string $retval HTML links for actions to take on single notifications.
		 *
		 * @return string
		 */
		public function bb_rl_modify_notification_action_links( $retval ) {
			// Replace bp-screen-reader-text with bb_rl_label.
			$retval = str_replace( 'bp-screen-reader-text', 'bb_rl_label', $retval );

			// Update link text for mark as read.
			$retval = str_replace( '>Read<', '>' . __( 'Mark as read', 'buddyboss' ) . '<', $retval );

			// Update link text for delete.
			$retval = str_replace( '>Delete<', '>' . __( 'Delete notifications', 'buddyboss' ) . '<', $retval );

			return $retval;
		}

		/**
		 * Return the skin appearance option.
		 *
		 * @since BuddyBoss [BBVERSION]
		 */
		public function bb_rl_is_skin_appearance_enabled() {

			$bb_rl_skin_appearance = bp_get_option( 'bb_rl_skin_appearance', false );

			return $bb_rl_skin_appearance;
		}

		/**
		 * Return the theme mode option.
		 *
		 * @since BuddyBoss [BBVERSION]
		 */
		public function bb_rl_get_theme_mode() {
			$bb_rl_theme_mode = bp_get_option( 'bb_rl_theme_mode', 'light' );

			if ( ! $this->bb_rl_is_skin_appearance_enabled() ) {
				return 'light';
			}

			return $bb_rl_theme_mode;
		}

		/**
		 * Return the theme logo option.
		 *
		 * @since BuddyBoss [BBVERSION]
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
		 * Function to check pages is enable for integration.
		 *
		 * @since BuddyBoss [BBVERSION]
		 *
		 * @param string $page The page to check.
		 *
		 * @return bool True if the page is enabled for integration, false otherwise.
		 */
		public function bb_rl_is_page_enabled_for_integration( $page ) {
			$enabled_pages = bp_get_option( 'bb_rl_enabled_pages' );

			return ! empty( $enabled_pages[ $page ] );
		}

		/**
		 * Integration pages callback.
		 *
		 * @since BuddyBoss [BBVERSION]
		 */
		public function bb_rl_courses_integration_page() {
			global $post, $wp_query;

			// Get LearnDash post type slugs.
			$course_slug     = learndash_get_post_type_slug( 'course' );
			$lesson_slug     = learndash_get_post_type_slug( 'lesson' );
			$topic_slug      = learndash_get_post_type_slug( 'topic' );
			$quiz_slug       = learndash_get_post_type_slug( 'quiz' );
			$assignment_slug = learndash_get_post_type_slug( 'assignment' );
			$essays_slug     = learndash_get_post_type_slug( 'essays' );
			$group_slug      = learndash_get_post_type_slug( 'group' );

			// Check for archive pages first (these take priority).
			if ( is_post_type_archive( $course_slug ) ) {
				bp_get_template_part( 'learndash/ld30/archive-sfwd-courses' );
				return;
			}

			// Check for singular pages using WordPress functions.
			if ( is_singular( $course_slug ) ) {
				bp_get_template_part( 'learndash/ld30/course' );
				return;
			}

			if ( is_singular( $lesson_slug ) ) {
				// Try to include the template directly first, fallback to bp_get_template_part.
				$template_path = bp_locate_template( 'learndash/ld30/lesson.php' );
				if ( ! empty( $template_path ) && file_exists( $template_path ) ) {
					include $template_path;
				} else {
					bp_get_template_part( 'learndash/ld30/lesson' );
				}
				return;
			}

			if ( is_singular( $topic_slug ) ) {
				bp_get_template_part( 'learndash/ld30/topic' );
				return;
			}

			if ( is_singular( $quiz_slug ) ) {
				bp_get_template_part( 'learndash/ld30/quiz' );
				return;
			}

			if ( is_singular( $assignment_slug ) ) {
				bp_get_template_part( 'learndash/ld30/assignment' );
				return;
			}

			if ( is_singular( $essays_slug ) ) {
				bp_get_template_part( 'learndash/ld30/essays' );
				return;
			}

			if ( is_singular( $group_slug ) ) {
				bp_get_template_part( 'learndash/ld30/group' );
				return;
			}

			// Fallback: Try multiple ways to get the post type for edge cases.
			$post_type = '';

			if ( function_exists( 'get_post_type' ) ) {
				$post_type = get_post_type();
			}

			if ( empty( $post_type ) && isset( $post->post_type ) ) {
				$post_type = $post->post_type;
			}

			if ( empty( $post_type ) && is_object( $wp_query ) ) {
				$queried_object = get_queried_object();
				if ( $queried_object && isset( $queried_object->post_type ) ) {
					$post_type = $queried_object->post_type;
				}
			}

			if ( empty( $post_type ) && is_object( $wp_query ) && isset( $wp_query->query_vars['post_type'] ) ) {
				$post_type = $wp_query->query_vars['post_type'];
			}

			// If we still don't have post data, try to determine from URL (for edge cases).
			if ( empty( $post_type ) && isset( $_SERVER['REQUEST_URI'] ) ) {
				$request_uri = sanitize_text_field( wp_unslash( $_SERVER['REQUEST_URI'] ) );

				// Only check for specific single post patterns, not general directory patterns.
				if ( preg_match( '#/' . preg_quote( $lesson_slug, '#' ) . '/([^/]+)/?$#', $request_uri ) ) {
					$post_type = $lesson_slug;
				} elseif ( preg_match( '#/' . preg_quote( $course_slug, '#' ) . '/([^/]+)/?$#', $request_uri ) ) {
					$post_type = $course_slug;
				} elseif ( preg_match( '#/' . preg_quote( $topic_slug, '#' ) . '/([^/]+)/?$#', $request_uri ) ) {
					$post_type = $topic_slug;
				} elseif ( preg_match( '#/' . preg_quote( $quiz_slug, '#' ) . '/([^/]+)/?$#', $request_uri ) ) {
					$post_type = $quiz_slug;
				} elseif ( preg_match( '#/' . preg_quote( $assignment_slug, '#' ) . '/([^/]+)/?$#', $request_uri ) ) {
					$post_type = $assignment_slug;
				} elseif ( preg_match( '#/' . preg_quote( $essays_slug, '#' ) . '/([^/]+)/?$#', $request_uri ) ) {
					$post_type = $essays_slug;
				} elseif ( preg_match( '#/' . preg_quote( $group_slug, '#' ) . '/([^/]+)/?$#', $request_uri ) ) {
					$post_type = $group_slug;
				}
			}

			// Handle fallback cases based on detected post type.
			if ( $post_type === $course_slug ) {
				bp_get_template_part( 'learndash/ld30/course' );
			} elseif ( $post_type === $lesson_slug ) {
				$template_path = bp_locate_template( 'learndash/ld30/lesson.php' );
				if ( ! empty( $template_path ) && file_exists( $template_path ) ) {
					include $template_path;
				} else {
					bp_get_template_part( 'learndash/ld30/lesson' );
				}
			} elseif ( $post_type === $topic_slug ) {
				bp_get_template_part( 'learndash/ld30/topic' );
			} elseif ( $post_type === $quiz_slug ) {
				bp_get_template_part( 'learndash/ld30/quiz' );
			} elseif ( $post_type === $assignment_slug ) {
				bp_get_template_part( 'learndash/ld30/assignment' );
			} elseif ( $post_type === $essays_slug ) {
				bp_get_template_part( 'learndash/ld30/essays' );
			} elseif ( $post_type === $group_slug ) {
				bp_get_template_part( 'learndash/ld30/group' );
			} else {
				// Default to courses archive.
				bp_get_template_part( 'learndash/ld30/archive-sfwd-courses' );
			}
		}

		/**
		 * Get the header menu option.
		 *
		 * @since BuddyBoss [BBVERSION]
		 */
		public function bb_rl_get_header_menu_location() {
			$header_menu_slug = bp_get_option( 'bb_rl_header_menu', 'bb-readylaunch' );

			if ( empty( $header_menu_slug ) ) {
				return 'bb-readylaunch';
			}

			$header_menu_id = null;
			$menus          = wp_get_nav_menus();
			foreach ( $menus as $menu ) {
				if ( $menu->slug === $header_menu_slug ) {
					$header_menu_id = $menu->term_id;
					break;
				}
			}
			$locations = get_nav_menu_locations();
			if ( $header_menu_id ) {
				foreach ( $locations as $location => $assigned_menu_id ) {
					if ( (int) $assigned_menu_id === (int) $header_menu_id ) {
						return $location;
					}
				}
			}

			return 'bb-readylaunch';
		}
	}
}
