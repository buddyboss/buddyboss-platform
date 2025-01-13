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

			$enabled = $this->bb_is_readylaunch_enabled();

			// Register the ReadyLaunch menu.
			$this->bb_register_readylaunch_menus();

			add_action( 'bp_admin_init', array( $this, 'bb_core_admin_readylaunch_page_fields' ) );
			add_action( 'bp_admin_init', array( $this, 'bb_core_admin_maybe_save_readylaunch_settings' ), 100 );

			if ( $enabled ) {
				add_filter( 'template_include',
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

				add_action( 'wp_enqueue_scripts', array( $this, 'bb_enqueue_scripts' ) );

				add_action( 'wp_ajax_bb_fetch_header_messages', array( $this, 'bb_fetch_header_messages' ) );
				add_action( 'wp_ajax_bb_fetch_header_notifications', array( $this, 'bb_fetch_header_notifications' ) );
			}
		}

		/**
		 * Check if ReadyLaunch is enabled for the current directory.
		 *
		 * @since BuddyBoss [BBVERSION]
		 *
		 * @return bool True if ReadyLaunch is enabled, false otherwise.
		 */
		private function bb_is_readylaunch_enabled() {
			$enabled_pages = bb_get_enabled_readylaunch();

			if (
				(
					bp_is_members_directory() &&
					! empty( $enabled_pages['members'] )
				) ||
				(
					bp_is_video_directory() &&
					! empty( $enabled_pages['video'] ) &&
					bp_is_current_component( 'video' )
				) ||
				(
					bp_is_media_directory() &&
					! empty( $enabled_pages['media'] ) &&
					bp_is_current_component( 'media' )
				) ||
				(
					bp_is_document_directory() &&
					! empty( $enabled_pages['document'] )
				) ||
				(
					bp_is_groups_directory() &&
					! empty( $enabled_pages['groups'] )
				) ||
				(
					bp_is_activity_directory() &&
					! empty( $enabled_pages['activity'] )
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
		 * Adds settings fields for the ReadyLaunch admin page.
		 *
		 * @since BuddyBoss [BBVERSION]
		 */
		public function bb_core_admin_readylaunch_page_fields() {
			global $wp_settings_sections;

			// Add the ReadyLaunch settings section.
			add_settings_section(
				'bb_readylaunch',
				__( 'ReadyLaunch', 'buddyboss' ),
				array( $this, 'bb_admin_readylaunch_pages_description' ),
				'bb-readylaunch'
			);

			// Get the directory pages.
			$directory_pages = bp_core_admin_get_directory_pages();

			// Add an icon to the settings section if the function exists.
			if ( function_exists( 'bb_admin_icons' ) ) {
				$wp_settings_sections['bb-readylaunch']['bb_readylaunch']['icon'] = bb_admin_icons( 'bb_readylaunch' );
			}

			// Get the enabled ReadyLaunch pages and BuddyPress directory page IDs.
			$enabled_pages = bb_get_enabled_readylaunch();
			$bp_pages      = bp_core_get_directory_page_ids( 'all' );
			$description   = '';

			// Loop through each directory page and add a settings field if applicable.
			foreach ( $directory_pages as $name => $label ) {
				if (
					! empty( $bp_pages[ $name ] ) ||
					(
						'new_forums_page' === $name &&
						! empty( bp_get_forum_page_id() )
					)
				) {
					add_settings_field( $name, $label, array(
						$this,
						'bb_enable_setting_callback_page_directory',
					), 'bb-readylaunch', 'bb_readylaunch', compact( 'enabled_pages', 'name', 'label', 'description' ) );
					register_setting( 'bb-readylaunch', $name, array(
						'default' => array(),
					) );
				}
			}
		}

		/**
		 * ReadyLaunch pages description callback.
		 *
		 * @since BuddyBoss [BBVERSION]
		 */
		public function bb_admin_readylaunch_pages_description() {
		}

		/**
		 * Pages drop downs callback.
		 *
		 * @since BuddyBoss [BBVERSION]
		 *
		 * @param array $args
		 */
		public function bb_enable_setting_callback_page_directory( $args ) {
			extract( $args );

			// Switch to the root blog if not already on it.
			if ( ! bp_is_root_blog() ) {
				switch_to_blog( bp_get_root_blog_id() );
			}

			$checked = ! empty( $enabled_pages ) && isset( $enabled_pages[ $name ] );

			// For the button.
			if ( 'button' === $name ) {
				printf(
					'<p><a href="%s" class="button">%s</a></p>',
					esc_url( $label['link'] ),
					esc_html( $label['label'] )
				);
			} else {
				printf(
					'<input type="checkbox" value="1" name="bb-readylaunch[%s]" %s />',
					esc_attr( $name ),
					checked( $checked, true, false )
				);
			}

			// Restore the current blog if switched.
			if ( ! bp_is_root_blog() ) {
				restore_current_blog();
			}
		}

		/**
		 * Save ReadyLaunch settings if applicable.
		 *
		 * @since BuddyBoss [BBVERSION]
		 *
		 * @return bool False if settings are not saved, true otherwise.
		 */
		public function bb_core_admin_maybe_save_readylaunch_settings() {
			// Check if the page and submit parameters are set.
			if ( ! isset( $_GET['page'] ) || ! isset( $_POST['submit'] ) ) {
				return false;
			}

			// Check if the current page is the ReadyLaunch settings page.
			if ( 'bb-readylaunch' !== $_GET['page'] ) {
				return false;
			}

			// Verify the nonce for security.
			if ( ! check_admin_referer( 'bb-readylaunch-options' ) ) {
				return false;
			}

			// Save the ReadyLaunch settings if provided.
			if ( isset( $_POST['bb-readylaunch'] ) ) {
				bp_update_option( 'bb_readylaunch', $_POST['bb-readylaunch'] );
			}

			// Redirect to the ReadyLaunch settings page with a success message.
			bp_core_redirect(
				bp_get_admin_url(
					add_query_arg(
						array(
							'page'  => 'bb-readylaunch',
							'added' => 'true',
						),
						'admin.php'
					)
				)
			);
		}

		/**
		 * Override the page templates.
		 *
		 * @since BuddyBoss [BBVERSION]
		 *
		 * @return string ReadyLaunch layout template.
		 */
		public function override_page_templates() {

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

			wp_enqueue_script( 'bb-readylaunch-front', buddypress()->plugin_url . "bp-templates/bp-nouveau/readylaunch/assets/js/bb-readylaunch-front{$min}.js", array( 'jquery' ), bp_get_version(), true );
			wp_localize_script(
				'bb-readylaunch-front',
				'bbReadyLaunchFront',
				array(
					'ajax_url' => admin_url( 'admin-ajax.php' ),
					'nonce'    => wp_create_nonce( 'bb-readylaunch' ),
				)
			);
		}

		/**
		 * Fetch header messages.
		 *
		 * @since BuddyBoss [BBVERSION]
		 */
		public function bb_fetch_header_messages() {

			check_ajax_referer( 'bb-readylaunch', 'nonce' );

			ob_start();
			get_template_part( 'template-parts/unread-messages' );
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

			ob_start();
			get_template_part( 'template-parts/unread-notifications' );
			$notifications = ob_get_clean();
			wp_send_json_success( $notifications );
		}
	}

}
