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
			add_action( 'bp_admin_init', array( $this, 'bb_core_admin_readylaunch_page_fields' ) );
			add_action( 'bp_admin_init', array( $this, 'bb_core_admin_maybe_save_readylaunch_settings' ), 100 );
			add_filter( 'template_include', array( $this, 'override_page_templates' ), 999999 ); // High priority so we have the last say here
		}

		public function bb_core_admin_readylaunch_page_fields() {
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

			$bp_pages = bp_core_get_directory_page_ids( 'all' );
			$checked  = ! empty( $enabled_pages ) && isset( $enabled_pages[ $name ] );

			if ( 'new_forums_page' === $name ) {
				$val = bp_get_forum_page_id();
			} else {
				$val = ! empty( $bp_pages ) && isset( $bp_pages[ $name ] ) ? $bp_pages[ $name ] : '';
			}

			// For the button.
			if ( 'button' === $name ) {
				printf( '<p><a href="%s" class="button">%s</a> </p>', $args['label']['link'], $args['label']['label'] );
				// For the forums will set the page selected from the custom option `_bbp_root_slug_custom_slug`.
			} else {
				echo '<input type="checkbox" value="' . esc_attr( $val ) . '" name="' . 'bb-readylaunch[' . esc_attr( $name ) . ']' . '" ' . checked( $checked, true, false ) . '/>';
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
			global $post;
			$enabled_pages = bb_get_enabled_readylaunch();
			$enabled_pages = wp_parse_id_list( $enabled_pages );

			if (
				! empty( $post->ID ) &&
				in_array( $post->ID, $enabled_pages, true )
			) {
				$compponent = array_search( $post->ID, $enabled_pages, true );
				if ( ! empty( $compponent ) && bp_is_active( $compponent ) ) {
					$template = bp_locate_template( 'readylaunch/layout.php' );
					if ( $template ) {
						add_action( 'wp_enqueue_scripts', array( $this, 'bb_readylaunch_enqueue' ) );
						return $template;
					}
				}
			}

			return $template;
		}


		public function bb_readylaunch_enqueue() {
			wp_enqueue_script( 'bp-api-request');
		}
	}

}
