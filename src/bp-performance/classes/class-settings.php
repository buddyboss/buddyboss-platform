<?php
/**
 * BuddyBoss Performance Settings.
 *
 * @package BuddyBoss\Performance\Settings
 */

namespace BuddyBoss\Performance;

/**
 * Settings class.
 */
class Settings {

	/**
	 * Class instance.
	 *
	 * @var object
	 */
	private static $instance;

	/**
	 * Purge Nonce.
	 *
	 * @var string
	 */
	private static $purge_nonce;

	/**
	 * Setting key.
	 *
	 * @var string
	 */
	private static $option = '_bb_performance_settings';

	/**
	 * Class Instance.
	 *
	 * @return Settings
	 */
	public static function instance() {
		if ( ! isset( self::$instance ) ) {
			$class_name     = __CLASS__;
			self::$instance = new $class_name();
		}

		return self::$instance;
	}

	/**
	 * Get purge cache nonce.
	 *
	 * @return false|string
	 */
	public static function get_purge_nonce() {
		if ( ! isset( self::$purge_nonce ) ) {
			self::$purge_nonce = wp_create_nonce( 'bbapp_cache_purge' );
		}

		return self::$purge_nonce;
	}

	/**
	 * Get Purge setting page url so we can use that to redirection back to that page after purge.
	 *
	 * @return mixed|void
	 */
	public static function get_performance_purge_url() {
		/**
		 * This filter allow us to support purge in different page.
		 */
		return apply_filters( 'performance_purge_url', admin_url( 'admin.php?1=1' ) );
	}

	/**
	 * Get components list for cache enabled.
	 *
	 * @return array[]
	 */
	public static function get_performance_components() {
		self::get_purge_nonce();

		$purge_url = self::get_performance_purge_url();

		/**
		 * The URL endpoint used to perform purge operations.
		 *
		 * This variable typically holds the URL that will be
		 * invoked to clear or remove cached data or other stored
		 * elements. It is expected to be a fully qualified URL
		 * string, and its usage is to ensure proper access to
		 * the purge functionality of a system.
		 *
		 * @param array $components Performance components.
		 * @param string $purge_url Purge URL.
		 * @param string $purge_nonce Purcge nonce.
		 */
		return apply_filters( 'performance_components', array(), $purge_url, self::$purge_nonce );
	}

	/**
	 * Get performance setting.
	 *
	 * @param string $group Cache group.
	 *
	 * @return array|mixed
	 */
	public static function get_settings( $group = 'default' ) {
		$settings      = get_option( self::$option, array() );
		$group_setting = isset( $settings[ $group ] ) ? $settings[ $group ] : array();

		if ( 'default' !== $group && empty( $group_setting ) ) {
			$group_setting = isset( $settings['default'] ) ? $settings['default'] : array();
		}

		return $group_setting;
	}

	/**
	 * Allow to store performance setting.
	 *
	 * @param array  $group_setting Settings.
	 * @param string $group         Setting Group name.
	 *
	 * @return bool
	 */
	public static function save_settings( $group_setting, $group = 'default' ) {
		$settings = get_option( self::$option, array() );

		if ( empty( $settings ) ) {
			$settings = array();
		}

		$settings[ $group ] = $group_setting;

		return update_option( self::$option, $settings );
	}

	/**
	 * Get Purge actions by group.
	 *
	 * @param string $group Setting Group name.
	 *
	 * @return array
	 */
	public function get_group_purge_actions( $group ) {
		/**
		 * Filter to set cache action by giving component.
		 * It'll help us to extent it in custom code or support BuddyBoss App related group purging
		 * $actions : Cache groups actions
		 * $group: component like platofrm, Learndash, BuddyBoss App etc
		 */
		return apply_filters( 'performance_group_purge_actions', array(), $group );
	}

	/**
	 * Handle Purge cache event.
	 */
	public static function handle_purge_cache() {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( ! empty( $_GET['cache_purge'] ) && 1 === (int) $_GET['cache_purge'] && empty( $_POST ) ) {
			add_action(
				'admin_notices',
				function () {
					echo '<div class="notice notice-success"><p>' . esc_html__( 'Cache Purge Successfully', 'buddyboss' ) . '</p></div>';
				}
			);
		}

		$purge_nonce = ( ! empty( $_GET['nonce'] ) ) ? wp_unslash( $_GET['nonce'] ) : ''; //phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized

		if ( wp_verify_nonce( $purge_nonce, 'bbapp_cache_purge' ) ) {
			$group      = ( ! empty( $_GET['group'] ) ) ? self::input_clean( wp_unslash( $_GET['group'] ) ) : ''; //phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
			$components = ( ! empty( $_GET['component'] ) ) ? self::input_clean( wp_unslash( $_GET['component'] ) ) : ''; //phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized

			/**
			 * Handle group purge action.
			 */
			if ( 'all' === $components ) {
				$components = self::$instance->get_group_purge_actions( $group );
			} else {
				$components = explode( ',', $components );
			}

			if ( ! empty( $components ) ) {
				foreach ( $components as $component ) {
					Cache::instance()->purge_by_component( $component );
				}

				/**
				 * This action allow us to purge cache by component.
				 */
				do_action( 'performance_cache_purge', $components );

				$purge_url = self::get_performance_purge_url();

				wp_safe_redirect( $purge_url . '&cache_purge=1' );
				exit();
			}
		}
	}

	/**
	 * Clean variables using sanitize_text_field. Arrays are cleaned recursively.
	 * Non-scalar values are ignored.
	 *
	 * @param string|array $args Data to sanitize.
	 *
	 * @return string|array
	 */
	public static function input_clean( $args ) {
		if ( is_array( $args ) ) {
			return array_map( 'self::input_clean', $args );
		} else {
			return is_scalar( $args ) ? sanitize_text_field( $args ) : $args;
		}
	}
}
