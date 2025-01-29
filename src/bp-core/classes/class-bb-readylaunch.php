<?php
/**
 * Readylaunch class.
 *
 * @package BuddyBoss\Core
 * @since BuddyBoss [BBVERSION]
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
		 * @since BuddyBoss [BBVERSION]
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

			$enabled = $this->is_readylaunch_enabled();

			add_action( 'bp_admin_init', array( $this, 'bb_core_admin_readylaunch_page_fields' ) );
			add_action( 'bp_admin_init', array( $this, 'bb_core_admin_maybe_save_readylaunch_settings' ), 100 );

			if ( $enabled ) {
				add_filter( 'template_include', array( $this, 'override_page_templates' ), 999999 ); // High priority so we have the last say here

				// Filter BuddyPress template locations.
				remove_filter( 'bp_get_template_stack', 'bp_add_template_stack_locations' );

				add_filter( 'bp_get_template_stack', array( $this, 'add_template_stack' ), PHP_INT_MAX );
			}
		}

		public function bb_core_admin_readylaunch_page_fields() {
			global $wp_settings_sections;

			add_settings_section(
				'bb_readylaunch',
				__( 'ReadyLaunch', 'buddyboss' ),
				array( $this, 'bb_admin_readylaunch_pages_description' ),
				'bb-readylaunch'
			);

			$directory_pages = bp_core_admin_get_directory_pages();
			if ( function_exists( 'bb_admin_icons' ) ) {
				$wp_settings_sections['bb-readylaunch']['bb_readylaunch']['icon'] = bb_admin_icons( 'bb_readylaunch' );
			}

			$enabled_pages = bb_get_enabled_readylaunch();
			$bp_pages      = bp_core_get_directory_page_ids( 'all' );
			$description   = '';

			foreach ( $directory_pages as $name => $label ) {
				if (
					! empty( $bp_pages[ $name ] ) ||
					(
						$name === 'new_forums_page' &&
						! empty( bp_get_forum_page_id() )
					)
				) {
					add_settings_field( $name, $label, array( $this, 'bb_enable_setting_callback_page_directory' ), 'bb-readylaunch', 'bb_readylaunch', compact( 'enabled_pages', 'name', 'label', 'description' ) );
					register_setting( 'bb-readylaunch', $name, array(
						'default' => array()
					) );
				}
			}
		}

		public function bb_admin_readylaunch_pages_description() {
		}

		/**
		 * Pages drop downs callback
		 *
		 * @since BuddyBoss [BBVERSION]
		 *
		 * @param $args
		 */
		public function bb_enable_setting_callback_page_directory( $args ) {
			extract( $args );

			if ( ! bp_is_root_blog() ) {
				switch_to_blog( bp_get_root_blog_id() );
			}

			$checked  = ! empty( $enabled_pages ) && isset( $enabled_pages[ $name ] );

			// For the button.
			if ( 'button' === $name ) {
				printf( '<p><a href="%s" class="button">%s</a> </p>', $args['label']['link'], $args['label']['label'] );
				// For the forums will set the page selected from the custom option `_bbp_root_slug_custom_slug`.
			} else {
				echo '<input type="checkbox" value="1" name="' . 'bb-readylaunch[' . esc_attr( $name ) . ']' . '" ' . checked( $checked, true, false ) . '/>';
			}

			if ( ! bp_is_root_blog() ) {
				restore_current_blog();
			}
		}

		public function bb_core_admin_maybe_save_readylaunch_settings() {
			if ( ! isset( $_GET['page'] ) || ! isset( $_POST['submit'] ) ) {
				return false;
			}

			if ( 'bb-readylaunch' !== $_GET['page'] ) {
				return false;
			}

			if ( ! check_admin_referer( 'bb-readylaunch-options' ) ) {
				return false;
			}

			if ( isset( $_POST['bb-readylaunch'] ) ) {
				bp_update_option( 'bb_readylaunch', $_POST['bb-readylaunch'] );
			}

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

		public function override_page_templates( $template ) {
			$template = bp_locate_template( 'layout.php' );
			if ( $template ) {
				return $template;
			}

			return $template;
		}

		public function add_template_stack( $stack ) {
			$stylesheet_dir = get_stylesheet_directory();
			$template_dir   = get_template_directory();

			$stack = array_flip($stack);

			unset( $stack[$stylesheet_dir], $stack[$template_dir] );

			$stack = array_flip($stack);

			$custom_location = 'readylaunch';

			foreach ( $stack as $key => $value ) {
				$stack[$key] = untrailingslashit( trailingslashit( $value ) . $custom_location );
			}

			return $stack;
		}

		private function is_readylaunch_enabled() {
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
	}

}
